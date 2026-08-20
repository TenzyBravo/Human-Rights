<?php
declare(strict_types=1);

namespace App\Core;

use RuntimeException;

/**
 * Minimal SMTP client — enough to deliver transactional mail through a provider
 * such as Brevo, Mailgun, Resend, SendGrid or a cPanel mailbox.
 *
 * Written against sockets rather than pulling in a mail library, so the
 * application keeps its "no Composer dependency" property. It speaks EHLO,
 * STARTTLS, AUTH LOGIN / AUTH PLAIN, MAIL FROM, RCPT TO, DATA and QUIT, which
 * is the whole of what a transactional send needs.
 *
 * Two encryption modes:
 *   'tls' → connect in the clear on 587, then upgrade with STARTTLS (usual)
 *   'ssl' → connect already-encrypted on 465 (implicit TLS)
 */
final class Smtp
{
    private const TIMEOUT = 20;
    private const CRLF    = "\r\n";

    /** @var resource|null */
    private $socket = null;
    private array $cfg;
    private array $transcript = [];
    /** Counts down the base64 credential lines that follow AUTH LOGIN. */
    private int $pendingCredentialLines = 0;

    public function __construct(array $cfg)
    {
        $this->cfg = $cfg + [
            'host' => 'localhost', 'port' => 587, 'user' => '', 'pass' => '',
            'secure' => 'tls', 'verify_peer' => true, 'helo' => null,
        ];
    }

    /**
     * @param array{email:string,name:string} $from
     * @throws RuntimeException on any protocol or connection failure
     */
    public function send(array $from, string $to, string $subject, string $htmlBody, string $textBody = ''): void
    {
        $this->connect();
        try {
            $this->handshake();
            $this->authenticate();

            $this->command('MAIL FROM:<' . $from['email'] . '>', [250]);
            $this->command('RCPT TO:<' . $to . '>', [250, 251]);
            $this->command('DATA', [354]);

            // Dot-stuffing: a line consisting of a single "." would end the message.
            $message = $this->buildMessage($from, $to, $subject, $htmlBody, $textBody);
            $message = preg_replace('/^\./m', '..', $message) ?? $message;
            $this->write($message . self::CRLF . '.' );
            $this->expect([250]);

            $this->command('QUIT', [221], false);
        } finally {
            $this->close();
        }
    }

    // ------------------------------------------------------------------ socket
    private function connect(): void
    {
        $secure = strtolower((string)$this->cfg['secure']);
        $host   = (string)$this->cfg['host'];
        $port   = (int)$this->cfg['port'];
        $target = ($secure === 'ssl' ? 'ssl://' : 'tcp://') . $host . ':' . $port;

        $context = stream_context_create(['ssl' => [
            'verify_peer'       => (bool)$this->cfg['verify_peer'],
            'verify_peer_name'  => (bool)$this->cfg['verify_peer'],
            'SNI_enabled'       => true,
            'peer_name'         => $host,
        ]]);

        $errNo = 0; $errStr = '';
        $socket = @stream_socket_client($target, $errNo, $errStr, self::TIMEOUT,
                                        STREAM_CLIENT_CONNECT, $context);
        if ($socket === false) {
            throw new RuntimeException(sprintf('SMTP connect to %s failed: %s (%d)', $target, $errStr, $errNo));
        }
        stream_set_timeout($socket, self::TIMEOUT);
        $this->socket = $socket;
        $this->expect([220]);
    }

    private function handshake(): void
    {
        $caps = $this->command('EHLO ' . $this->heloName(), [250]);

        if (strtolower((string)$this->cfg['secure']) === 'tls') {
            if (stripos($caps, 'STARTTLS') === false) {
                throw new RuntimeException('SMTP server does not advertise STARTTLS but tls was requested.');
            }
            $this->command('STARTTLS', [220]);
            /** @psalm-suppress InvalidArgument — socket is a resource here */
            $ok = @stream_socket_enable_crypto($this->socket, true,
                    STREAM_CRYPTO_METHOD_TLS_CLIENT
                    | (defined('STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT') ? STREAM_CRYPTO_METHOD_TLSv1_2_CLIENT : 0));
            if ($ok !== true) {
                throw new RuntimeException('SMTP STARTTLS negotiation failed.');
            }
            // The capability list must be re-read on the encrypted channel.
            $this->command('EHLO ' . $this->heloName(), [250]);
        }
    }

    /**
     * The HELO name should be a real hostname the sending domain owns — strict
     * servers reject a bogus one, and matching the From domain helps SPF align.
     * Order: explicit config, the From domain, the machine hostname, localhost.
     */
    private function heloName(): string
    {
        $configured = (string)($this->cfg['helo'] ?? '');
        if ($configured !== '') {
            return $configured;
        }
        $from = (string)App::config('mail.from_email');
        if (str_contains($from, '@')) {
            $domain = substr($from, strpos($from, '@') + 1);
            if (preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/i', $domain)) {
                return $domain;
            }
        }
        $host = gethostname();
        return ($host !== false && str_contains($host, '.')) ? $host : 'localhost';
    }

    private function authenticate(): void
    {
        $user = (string)$this->cfg['user'];
        $pass = (string)$this->cfg['pass'];
        if ($user === '') {
            return; // an unauthenticated relay (e.g. localhost) is legitimate
        }
        // AUTH LOGIN is the most widely supported; PLAIN is the fallback.
        try {
            $this->command('AUTH LOGIN', [334]);
            $this->command(base64_encode($user), [334]);
            $this->command(base64_encode($pass), [235]);
        } catch (RuntimeException $e) {
            $this->command('AUTH PLAIN ' . base64_encode("\0" . $user . "\0" . $pass), [235]);
        }
    }

    private function close(): void
    {
        if (is_resource($this->socket)) {
            @fclose($this->socket);
        }
        $this->socket = null;
    }

    // ----------------------------------------------------------------- protocol
    /** @param array<int,int> $expect */
    private function command(string $line, array $expect, bool $read = true): string
    {
        $this->write($line);
        return $read ? $this->expect($expect) : '';
    }

    private function write(string $data): void
    {
        if (!is_resource($this->socket)) {
            throw new RuntimeException('SMTP socket is closed.');
        }
        // Never log credentials — record the verb only.
        $this->transcript[] = '> ' . $this->redact($data);
        if (@fwrite($this->socket, $data . self::CRLF) === false) {
            throw new RuntimeException('SMTP write failed.');
        }
    }

    /** @param array<int,int> $codes */
    private function expect(array $codes): string
    {
        $reply = '';
        while (is_resource($this->socket)) {
            $line = fgets($this->socket, 8192);
            if ($line === false) {
                $meta = stream_get_meta_data($this->socket);
                throw new RuntimeException(!empty($meta['timed_out'])
                    ? 'SMTP read timed out.' : 'SMTP connection closed unexpectedly.');
            }
            $reply .= $line;
            // Multi-line replies use "250-"; the final line uses "250 ".
            if (strlen($line) >= 4 && $line[3] === ' ') {
                break;
            }
        }
        $this->transcript[] = '< ' . trim($reply);
        $code = (int)substr(ltrim($reply), 0, 3);
        if (!in_array($code, $codes, true)) {
            throw new RuntimeException(sprintf('SMTP expected %s, got: %s',
                implode('/', $codes), trim($reply)));
        }
        return $reply;
    }

    /**
     * Hide only what actually carries a secret: the AUTH argument, and the two
     * bare base64 lines that follow AUTH LOGIN. Everything else stays readable —
     * a transcript that redacts DATA and QUIT is useless for diagnosis.
     */
    private function redact(string $line): string
    {
        if (preg_match('/^AUTH\s+(\w+)/i', $line, $m)) {
            // AUTH LOGIN takes no argument; AUTH PLAIN carries the credential inline.
            $this->pendingCredentialLines = strtoupper($m[1]) === 'LOGIN' ? 2 : 0;
            return 'AUTH ' . strtoupper($m[1]) . (strtoupper($m[1]) === 'PLAIN' ? ' [redacted]' : '');
        }
        if ($this->pendingCredentialLines > 0) {
            $this->pendingCredentialLines--;
            return '[redacted]';
        }
        // Message bodies are long; keep the transcript to a readable size.
        return strlen($line) > 220 ? substr($line, 0, 220) . '… [' . strlen($line) . ' bytes]' : $line;
    }

    /** @return array<int,string> protocol transcript, credentials removed */
    public function transcript(): array
    {
        return $this->transcript;
    }

    // ------------------------------------------------------------------ message
    private function buildMessage(array $from, string $to, string $subject, string $html, string $text): string
    {
        $boundary = 'hrrams-' . bin2hex(random_bytes(12));
        $fromName = self::encodeHeader($from['name']);

        $headers = [
            'Date: ' . date('r'),
            'From: ' . $fromName . ' <' . $from['email'] . '>',
            'To: <' . $to . '>',
            'Subject: ' . self::encodeHeader($subject),
            'Message-ID: <' . bin2hex(random_bytes(16)) . '@' . $this->heloName() . '>',
            'MIME-Version: 1.0',
            'X-Mailer: HRRAMS',
            'Auto-Submitted: auto-generated',
        ];

        if ($text === '') {
            $text = trim(html_entity_decode(strip_tags(preg_replace('/<br\s*\/?>/i', "\n", $html) ?? $html),
                                            ENT_QUOTES, 'UTF-8'));
        }

        // multipart/alternative: plain text first, then HTML. Spam filters expect both.
        $headers[] = 'Content-Type: multipart/alternative; boundary="' . $boundary . '"';
        $body = implode(self::CRLF, [
            '--' . $boundary,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            quoted_printable_encode($text),
            '--' . $boundary,
            'Content-Type: text/html; charset=UTF-8',
            'Content-Transfer-Encoding: quoted-printable',
            '',
            quoted_printable_encode($html),
            '--' . $boundary . '--',
        ]);

        return implode(self::CRLF, $headers) . self::CRLF . self::CRLF . $body;
    }

    /** RFC 2047 encoding, so non-ASCII subjects survive. */
    private static function encodeHeader(string $value): string
    {
        $value = trim(str_replace(["\r", "\n"], ' ', $value));
        if (preg_match('/^[\x20-\x7E]*$/', $value)) {
            return $value;
        }
        return '=?UTF-8?B?' . base64_encode($value) . '?=';
    }
}
