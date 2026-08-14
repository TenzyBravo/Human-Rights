<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Per-session CSRF token, verified on every state-changing request.
 */
final class Csrf
{
    public static function token(): string
    {
        if (empty($_SESSION['_csrf'])) {
            $_SESSION['_csrf'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['_csrf'];
    }

    public static function field(): string
    {
        $name = (string)App::config('security.csrf_token_name', '_token');
        return sprintf(
            '<input type="hidden" name="%s" value="%s">',
            htmlspecialchars($name, ENT_QUOTES, 'UTF-8'),
            htmlspecialchars(self::token(), ENT_QUOTES, 'UTF-8')
        );
    }

    public static function check(?string $candidate = null): bool
    {
        $name      = (string)App::config('security.csrf_token_name', '_token');
        $candidate ??= $_POST[$name] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $stored    = $_SESSION['_csrf'] ?? '';
        return $stored !== '' && is_string($candidate) && hash_equals($stored, $candidate);
    }

    /** Aborts the request with 419 when the token is missing or wrong. */
    public static function verify(): void
    {
        if (self::check()) {
            return;
        }
        Audit::log('csrf.failed', null, null, 'Rejected request with invalid CSRF token');
        http_response_code(419);
        if (Request::wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Session expired. Please reload the page.']);
        } else {
            echo '<h1>419 — Session expired</h1><p>Please go back, reload the page and try again.</p>';
        }
        exit;
    }
}
