<?php
declare(strict_types=1);

namespace App\Core;

use App\Models\User;

/**
 * Authentication + role/permission resolution.
 */
final class Auth
{
    private static ?array $user = null;
    private static ?array $permissions = null;

    public static function attempt(string $email, string $password, bool $remember = false): array
    {
        $users = new User();
        $user  = $users->findByEmail($email);

        // Uniform failure response — never reveals whether the account exists.
        $generic = ['ok' => false, 'message' => 'Invalid email address or password.'];

        if ($user === null) {
            self::recordLogin(null, $email, false);
            // Equalise timing against the hash comparison below.
            password_verify($password, '$2y$10$invalidinvalidinvalidinvalidinvalidinvalidinvalidinvalidinv');
            return $generic;
        }

        if ($user['locked_until'] !== null && strtotime((string)$user['locked_until']) > time()) {
            $minutes = (int)ceil((strtotime((string)$user['locked_until']) - time()) / 60);
            self::recordLogin((int)$user['id'], $email, false);
            return ['ok' => false, 'message' => "Account locked. Try again in {$minutes} minute(s)."];
        }

        if (!password_verify($password, $user['password_hash'])) {
            $users->registerFailedAttempt((int)$user['id']);
            self::recordLogin((int)$user['id'], $email, false);
            Audit::log('login.failed', 'user', (int)$user['id'], 'Wrong password');
            return $generic;
        }

        if ($user['status'] === 'suspended') {
            return ['ok' => false, 'message' => 'This account has been suspended. Contact the administrator.'];
        }
        if ($user['status'] === 'pending' && (bool)App::config('security.require_email_verification', true)
            && $user['email_verified_at'] === null) {
            return ['ok' => false, 'message' => 'Please verify your email address before signing in.'];
        }

        // Success.
        $users->clearFailedAttempts((int)$user['id']);
        session_regenerate_id(true);
        Session::set('user_id', (int)$user['id']);
        Session::set('_rotated', time());
        self::$user = null;
        self::$permissions = null;

        if ($remember) {
            self::issueRememberCookie((int)$user['id']);
        }

        self::recordLogin((int)$user['id'], $email, true);
        Audit::log('login.success', 'user', (int)$user['id'], 'Signed in');

        return ['ok' => true, 'message' => 'Welcome back.'];
    }

    public static function logout(): void
    {
        $id = self::id();
        if ($id !== null) {
            Audit::log('logout', 'user', $id, 'Signed out');
            (new User())->clearRememberToken($id);
        }
        setcookie('hrrams_remember', '', time() - 3600, '/', '', App::isSecure(), true);
        Session::destroy();
        self::$user = null;
        self::$permissions = null;
    }

    public static function id(): ?int
    {
        $id = Session::get('user_id');
        return is_int($id) ? $id : (is_numeric($id) ? (int)$id : null);
    }

    public static function check(): bool
    {
        return self::user() !== null;
    }

    public static function user(): ?array
    {
        if (self::$user !== null) {
            return self::$user;
        }
        $id = self::id();
        if ($id === null) {
            self::attemptRememberLogin();
            $id = self::id();
            if ($id === null) {
                return null;
            }
        }
        $user = (new User())->find($id);
        if ($user === null || $user['status'] === 'suspended') {
            Session::forget('user_id');
            return null;
        }
        return self::$user = $user;
    }

    public static function name(): string
    {
        $u = self::user();
        return $u === null ? 'Guest' : trim($u['first_name'] . ' ' . $u['last_name']);
    }

    /** @return array<int,string> role slugs */
    public static function roles(): array
    {
        $u = self::user();
        return $u === null ? [] : ($u['roles'] ?? []);
    }

    public static function hasRole(string ...$slugs): bool
    {
        return (bool)array_intersect($slugs, self::roles());
    }

    /** @return array<int,string> permission slugs */
    public static function permissions(): array
    {
        if (self::$permissions !== null) {
            return self::$permissions;
        }
        $id = self::id();
        return self::$permissions = $id === null ? [] : (new User())->permissionsFor($id);
    }

    public static function can(string $permission): bool
    {
        return in_array($permission, self::permissions(), true);
    }

    public static function isStaff(): bool
    {
        return self::hasRole('officer', 'supervisor', 'awareness', 'admin');
    }

    /** Home dashboard for the signed-in user's highest role. */
    public static function homeRoute(): string
    {
        return match (true) {
            self::hasRole('admin')      => 'admin/dashboard',
            self::hasRole('supervisor') => 'dashboard',
            self::hasRole('officer')    => 'dashboard',
            self::hasRole('awareness')  => 'awareness/manage',
            default                     => 'my/reports',
        };
    }

    // ------------------------------------------------------------ remember me
    private static function issueRememberCookie(int $userId): void
    {
        $token = bin2hex(random_bytes(32));
        (new User())->setRememberToken($userId, hash('sha256', $token));
        setcookie('hrrams_remember', $userId . ':' . $token, [
            'expires'  => time() + 60 * 60 * 24 * 30,
            'path'     => '/',
            'secure'   => App::isSecure(),
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
    }

    private static function attemptRememberLogin(): void
    {
        $cookie = $_COOKIE['hrrams_remember'] ?? '';
        if (!is_string($cookie) || !str_contains($cookie, ':')) {
            return;
        }
        [$id, $token] = explode(':', $cookie, 2);
        if (!ctype_digit($id) || $token === '') {
            return;
        }
        $user = (new User())->find((int)$id);
        if ($user === null || empty($user['remember_token'])
            || !hash_equals($user['remember_token'], hash('sha256', $token))) {
            return;
        }
        session_regenerate_id(true);
        Session::set('user_id', (int)$id);
    }

    private static function recordLogin(?int $userId, string $email, bool $ok): void
    {
        Database::instance()->insert('login_history', [
            'user_id'    => $userId,
            'email'      => $email,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'successful' => $ok ? 1 : 0,
        ]);
    }
}
