<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Hardened session handling: strict mode, httpOnly cookies, idle timeout,
 * periodic id rotation and fingerprint binding against session hijacking.
 */
final class Session
{
    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');
        ini_set('session.cookie_httponly', '1');
        ini_set('session.sid_length', '48');
        ini_set('session.sid_bits_per_character', '5');

        session_name((string)App::config('security.session_name', 'HRRAMSSESSID'));
        session_set_cookie_params([
            'lifetime' => 0,
            'path'     => '/',
            'domain'   => '',
            'secure'   => (bool)App::config('security.cookie_secure', false) || App::isSecure(),
            'httponly' => true,
            'samesite' => (string)App::config('security.cookie_samesite', 'Lax'),
        ]);
        session_start();

        self::enforceFingerprint();
        self::enforceTimeout();
        self::rotate();
    }

    private static function fingerprint(): string
    {
        return hash('sha256', ($_SERVER['HTTP_USER_AGENT'] ?? '') . '|' . ($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? ''));
    }

    private static function enforceFingerprint(): void
    {
        $fp = self::fingerprint();
        if (!isset($_SESSION['_fp'])) {
            $_SESSION['_fp'] = $fp;
        } elseif (!hash_equals($_SESSION['_fp'], $fp)) {
            self::destroy();
            session_start();
            $_SESSION['_fp'] = $fp;
        }
    }

    private static function enforceTimeout(): void
    {
        $timeout = (int)App::config('security.session_timeout', 1800);
        $now     = time();
        if (isset($_SESSION['_last']) && ($now - (int)$_SESSION['_last']) > $timeout) {
            self::destroy();
            session_start();
            $_SESSION['_fp']      = self::fingerprint();
            $_SESSION['_expired'] = true;
        }
        $_SESSION['_last'] = $now;
    }

    private static function rotate(): void
    {
        $every = (int)App::config('security.session_regenerate', 300);
        if (!isset($_SESSION['_rotated'])) {
            $_SESSION['_rotated'] = time();
            return;
        }
        if ((time() - (int)$_SESSION['_rotated']) > $every) {
            session_regenerate_id(true);
            $_SESSION['_rotated'] = time();
        }
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return $_SESSION[$key] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $_SESSION[$key] = $value;
    }

    public static function has(string $key): bool
    {
        return isset($_SESSION[$key]);
    }

    public static function forget(string $key): void
    {
        unset($_SESSION[$key]);
    }

    public static function pull(string $key, mixed $default = null): mixed
    {
        $value = $_SESSION[$key] ?? $default;
        unset($_SESSION[$key]);
        return $value;
    }

    public static function destroy(): void
    {
        $_SESSION = [];
        if (ini_get('session.use_cookies')) {
            $p = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
        }
        session_destroy();
    }
}
