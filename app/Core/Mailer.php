<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Mail transport with three drivers:
 *
 *   'log'  — writes to storage/logs/mail.log. The development default, so the
 *            whole system is testable without an MTA.
 *   'smtp' — authenticated SMTP via Smtp. What production should use.
 *   'mail' — PHP mail(). Works on some shared hosts, but deliverability is poor
 *            and many providers block it outright; prefer smtp.
 */
final class Mailer
{
    /** Set by send() so callers can surface the reason a message failed. */
    private static ?string $lastError = null;

    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $fromEmail = (string)App::config('mail.from_email');
        $fromName  = (string)App::config('mail.from_name');
        $driver    = (string)App::config('mail.driver', 'log');

        self::$lastError = null;

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            self::$lastError = 'Recipient address is not valid.';
            return false;
        }
        // Strip header-injection attempts from the subject line.
        $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
        $body    = self::wrap($subject, $htmlBody);

        return match ($driver) {
            'smtp'  => self::viaSmtp($fromEmail, $fromName, $to, $subject, $body),
            'mail'  => self::viaMailFunction($fromEmail, $fromName, $to, $subject, $body),
            default => self::viaLog($to, $subject, $body),
        };
    }

    public static function lastError(): ?string
    {
        return self::$lastError;
    }

    private static function viaLog(string $to, string $subject, string $body): bool
    {
        App::log('mail', sprintf("TO: %s | SUBJECT: %s\n%s\n%s", $to, $subject, $body, str_repeat('-', 70)));
        return true;
    }

    private static function viaSmtp(string $fromEmail, string $fromName, string $to, string $subject, string $body): bool
    {
        $cfg = (array)App::config('mail.smtp', []);
        if (empty($cfg['host'])) {
            self::$lastError = 'mail.driver is "smtp" but mail.smtp.host is not configured.';
            App::log('mail', 'CONFIG ERROR: ' . self::$lastError);
            return false;
        }

        try {
            $smtp = new Smtp($cfg);
            $smtp->send(['email' => $fromEmail, 'name' => $fromName], $to, $subject, $body);
            App::log('mail', sprintf('SENT via SMTP to %s | %s', $to, $subject));
            return true;
        } catch (\Throwable $e) {
            self::$lastError = $e->getMessage();
            // The transcript has credentials removed; it is the only practical way
            // to diagnose a provider rejection after the fact.
            $trace = isset($smtp) ? implode("\n  ", $smtp->transcript()) : '(no transcript)';
            App::log('mail', sprintf("SMTP FAILED to %s | %s\n  %s\n  %s",
                $to, $subject, $e->getMessage(), $trace));
            return false;
        }
    }

    private static function viaMailFunction(string $fromEmail, string $fromName, string $to, string $subject, string $body): bool
    {
        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $fromName, $fromEmail),
            'Reply-To: ' . $fromEmail,
            'X-Mailer: HRRAMS',
        ]);

        $sent = @mail($to, $subject, $body, $headers);
        if (!$sent) {
            self::$lastError = 'PHP mail() returned false — the host may block it.';
            App::log('mail', 'FAILED to send to ' . $to . ' — ' . $subject);
        }
        return $sent;
    }

    private static function wrap(string $title, string $body): string
    {
        $t = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
        $site = htmlspecialchars((string)App::config('app.name'), ENT_QUOTES, 'UTF-8');
        return <<<HTML
<!doctype html><html><body style="margin:0;background:#f8f9fa;font-family:Inter,Arial,sans-serif;color:#212529">
<div style="max-width:600px;margin:24px auto;background:#fff;border-radius:14px;overflow:hidden;border:1px solid #e9ecef">
  <div style="background:#0d6efd;color:#fff;padding:20px 24px;font-size:18px;font-weight:600">{$site}</div>
  <div style="padding:24px;line-height:1.65"><h2 style="margin:0 0 12px;font-size:18px">{$t}</h2>{$body}</div>
  <div style="padding:16px 24px;background:#f8f9fa;color:#6c757d;font-size:12px">
    This is an automated message. Please do not reply to this address.
  </div>
</div></body></html>
HTML;
    }
}
