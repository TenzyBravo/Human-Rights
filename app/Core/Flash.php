<?php
declare(strict_types=1);

namespace App\Core;

/**
 * One-shot flash messages, rendered as Bootstrap toasts by the layout.
 */
final class Flash
{
    public static function add(string $type, string $message): void
    {
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    public static function success(string $m): void { self::add('success', $m); }
    public static function error(string $m): void   { self::add('danger', $m); }
    public static function warning(string $m): void { self::add('warning', $m); }
    public static function info(string $m): void    { self::add('info', $m); }

    /** @return array<int,array{type:string,message:string}> */
    public static function take(): array
    {
        $messages = $_SESSION['_flash'] ?? [];
        unset($_SESSION['_flash']);
        return is_array($messages) ? $messages : [];
    }

    /** Keeps validation errors + old input across a redirect. */
    public static function withErrors(array $errors, array $old = []): void
    {
        $_SESSION['_errors'] = $errors;
        $_SESSION['_old']    = $old;
    }

    public static function errors(): array
    {
        return (array)Session::pull('_errors', []);
    }

    public static function old(): array
    {
        return (array)Session::pull('_old', []);
    }
}
