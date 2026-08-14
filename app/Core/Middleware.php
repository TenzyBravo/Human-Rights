<?php
declare(strict_types=1);

namespace App\Core;

final class Middleware
{
    public static function apply(string $rule): void
    {
        [$name, $arg] = array_pad(explode(':', $rule, 2), 2, null);

        match ($name) {
            'auth'     => self::auth(),
            'guest'    => self::guest(),
            'can'      => self::can((string)$arg),
            'role'     => self::role((string)$arg),
            'verified' => self::verified(),
            default    => null,
        };
    }

    private static function auth(): void
    {
        if (Auth::check()) {
            return;
        }
        if (Session::pull('_expired')) {
            Flash::warning('Your session expired due to inactivity. Please sign in again.');
        } else {
            Flash::info('Please sign in to continue.');
        }
        Session::set('_intended', Request::path());
        View::redirect('login');
    }

    private static function guest(): void
    {
        if (Auth::check()) {
            View::redirect(Auth::homeRoute());
        }
    }

    private static function verified(): void
    {
        self::auth();
        $user = Auth::user();
        if ($user !== null && $user['email_verified_at'] === null) {
            Flash::warning('Please verify your email address to continue.');
            View::redirect('verify-notice');
        }
    }

    private static function can(string $permission): void
    {
        self::auth();
        if (Auth::can($permission)) {
            return;
        }
        Audit::log('access.denied', null, null, 'Missing permission: ' . $permission);
        self::forbidden();
    }

    private static function role(string $slugs): void
    {
        self::auth();
        if (Auth::hasRole(...explode('|', $slugs))) {
            return;
        }
        Audit::log('access.denied', null, null, 'Role required: ' . $slugs);
        self::forbidden();
    }

    private static function forbidden(): void
    {
        http_response_code(403);
        if (Request::wantsJson()) {
            header('Content-Type: application/json');
            echo json_encode(['error' => 'You are not authorised to perform this action.']);
            exit;
        }
        View::render('errors/error', [
            'code'    => 403,
            'message' => 'You are not authorised to view this page.',
        ], 'public');
        exit;
    }
}
