<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Minimal mail transport. The default 'log' driver writes to
 * storage/logs/mail.log so the system is fully testable without an MTA.
 */
final class Mailer
{
    public static function send(string $to, string $subject, string $htmlBody): bool
    {
        $fromEmail = (string)App::config('mail.from_email');
        $fromName  = (string)App::config('mail.from_name');
        $driver    = (string)App::config('mail.driver', 'log');

        if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        // Strip header-injection attempts from the subject line.
        $subject = trim(str_replace(["\r", "\n"], ' ', $subject));
        $body    = self::wrap($subject, $htmlBody);

        if ($driver === 'log') {
            App::log('mail', sprintf("TO: %s | SUBJECT: %s\n%s\n%s", $to, $subject, $body, str_repeat('-', 70)));
            return true;
        }

        $headers = implode("\r\n", [
            'MIME-Version: 1.0',
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $fromName, $fromEmail),
            'Reply-To: ' . $fromEmail,
            'X-Mailer: HRRAMS',
        ]);

        $sent = @mail($to, $subject, $body, $headers);
        if (!$sent) {
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
