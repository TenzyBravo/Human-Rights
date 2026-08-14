<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Application kernel: configuration, autoloading, session, error handling.
 */
final class App
{
    private static array $config = [];
    private static bool $booted = false;

    public static function boot(): void
    {
        if (self::$booted) {
            return;
        }
        self::$config = require dirname(__DIR__, 2) . '/config/config.php';
        self::$booted = true;

        date_default_timezone_set(self::config('app.timezone', 'UTC'));
        self::registerAutoloader();
        self::registerErrorHandling();
        self::sendSecurityHeaders();
        Session::start();
    }

    private static function registerAutoloader(): void
    {
        spl_autoload_register(static function (string $class): void {
            if (!str_starts_with($class, 'App\\')) {
                return;
            }
            $relative = str_replace('\\', '/', substr($class, 4));
            $file     = dirname(__DIR__) . '/' . $relative . '.php';
            if (is_file($file)) {
                require $file;
            }
        });
    }

    private static function registerErrorHandling(): void
    {
        $debug = (bool)self::config('app.debug', false);
        error_reporting(E_ALL);
        ini_set('display_errors', $debug ? '1' : '0');
        ini_set('log_errors', '1');
        ini_set('error_log', self::config('app.logs') . '/php-error.log');

        set_exception_handler(static function (\Throwable $e) use ($debug): void {
            self::log('error', $e->getMessage() . ' @ ' . $e->getFile() . ':' . $e->getLine());
            http_response_code(500);
            if ($debug) {
                echo '<pre style="padding:2rem;font:13px/1.6 monospace">';
                echo htmlspecialchars((string)$e, ENT_QUOTES, 'UTF-8');
                echo '</pre>';
            } else {
                echo '<h1>Something went wrong</h1><p>The error has been logged.</p>';
            }
        });
    }

    private static function sendSecurityHeaders(): void
    {
        if (headers_sent()) {
            return;
        }
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');
        header('X-XSS-Protection: 0'); // superseded by CSP; legacy filter is unsafe
        header(
            "Content-Security-Policy: default-src 'self'; "
            . "img-src 'self' data: https://*.tile.openstreetmap.org; "
            . "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com; "
            . "font-src 'self' https://fonts.gstatic.com https://cdn.jsdelivr.net; "
            . "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net; "
            . "connect-src 'self'; frame-ancestors 'self'; base-uri 'self'; form-action 'self'"
        );
        if (self::config('app.force_https') && !self::isSecure()) {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains');
        }
    }

    public static function isSecure(): bool
    {
        return (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
            || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https'
            || (int)($_SERVER['SERVER_PORT'] ?? 80) === 443;
    }

    /** Dot-notation config reader: App::config('db.host'). */
    public static function config(string $key, mixed $default = null): mixed
    {
        $value = self::$config;
        foreach (explode('.', $key) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }
        return $value;
    }

    public static function baseUrl(): string
    {
        static $base = null;
        if ($base !== null) {
            return $base;
        }
        $configured = self::config('app.base_url');
        if ($configured) {
            return $base = rtrim((string)$configured, '/');
        }
        $scheme = self::isSecure() ? 'https' : 'http';
        $host   = $_SERVER['HTTP_HOST'] ?? 'localhost';
        // Directory the front controller lives in, e.g. /hrrams/public
        $dir    = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/index.php')), '/');
        return $base = $scheme . '://' . $host . $dir;
    }

    public static function url(string $path = ''): string
    {
        return self::baseUrl() . '/' . ltrim($path, '/');
    }

    public static function log(string $channel, string $message): void
    {
        $dir = self::config('app.logs') ?: sys_get_temp_dir();
        if (!is_dir($dir)) {
            @mkdir($dir, 0775, true);
        }
        @file_put_contents(
            $dir . '/' . $channel . '.log',
            sprintf("[%s] %s %s\n", date('Y-m-d H:i:s'), $_SERVER['REMOTE_ADDR'] ?? '-', $message),
            FILE_APPEND | LOCK_EX
        );
    }
}
