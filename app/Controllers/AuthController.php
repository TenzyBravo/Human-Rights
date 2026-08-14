<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Mailer;
use App\Core\Request;
use App\Core\Session;
use App\Models\Lookup;
use App\Models\User;

final class AuthController extends Controller
{
    // ------------------------------------------------------------ registration
    public function showRegister(): void
    {
        $this->view('auth/register', [
            'title'     => 'Create an account',
            'provinces' => Lookup::provinces(),
            'captcha'   => $this->issueCaptcha(),
        ], 'auth');
    }

    public function register(): void
    {
        $this->assertCaptcha('register');

        $data = $this->validate([
            'first_name'            => 'required|min:2|max:80',
            'last_name'             => 'required|min:2|max:80',
            'email'                 => 'required|email|max:190|unique:users,email',
            'phone'                 => 'nullable|phone',
            'address'               => 'nullable|max:255',
            'province_id'           => 'nullable|int',
            'district_id'           => 'nullable|int',
            'nationality'           => 'nullable|max:80',
            'password'              => 'required|password',
            'password_confirmation' => 'required|match:password',
            'terms'                 => 'accepted',
        ], ['password_confirmation' => 'Password confirmation', 'terms' => 'The terms of use'], 'register');

        $result = (new User())->register($data);
        Audit::log('user.registered', 'user', $result['id'], 'Self-registration: ' . $data['email']);

        if ($result['token'] !== '' && App::config('security.require_email_verification')) {
            $link = url('verify/' . $result['token']);
            Mailer::send($data['email'], 'Verify your HRRAMS account',
                '<p>Welcome to HRRAMS. Please confirm your email address to activate your account.</p>'
                . '<p><a href="' . e($link) . '" style="background:#0d6efd;color:#fff;padding:10px 18px;'
                . 'border-radius:8px;text-decoration:none;display:inline-block">Verify my email</a></p>'
                . '<p style="color:#6c757d;font-size:12px">If you did not create this account, ignore this message.</p>');
            Flash::success('Account created. Check your email for the verification link.');
        } else {
            Flash::success('Account created. You can now sign in.');
        }
        $this->redirect('login');
    }

    public function verify(string $token): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token)) {
            Flash::error('That verification link is not valid.');
            $this->redirect('login');
        }
        $users = new User();
        $user  = $users->findByVerifyToken($token);
        if ($user === null) {
            Flash::error('That verification link has already been used or has expired.');
            $this->redirect('login');
        }
        $users->markVerified((int)$user['id']);
        Audit::log('user.verified', 'user', (int)$user['id'], 'Email verified');
        Flash::success('Email verified. You can now sign in.');
        $this->redirect('login');
    }

    public function verifyNotice(): void
    {
        $this->view('auth/verify-notice', ['title' => 'Verify your email'], 'auth');
    }

    public function resendVerification(): void
    {
        $user = Auth::user();
        if ($user === null || $user['email_verified_at'] !== null) {
            $this->redirect('/');
        }
        $token = bin2hex(random_bytes(32));
        Database::instance()->update('users', ['verify_token' => $token], 'id = :id', ['id' => $user['id']]);
        Mailer::send($user['email'], 'Verify your HRRAMS account',
            '<p><a href="' . e(url('verify/' . $token)) . '">Verify my email</a></p>');
        Flash::info('A new verification link has been sent.');
        $this->redirect('verify-notice');
    }

    // ------------------------------------------------------------------- login
    public function showLogin(): void
    {
        $this->view('auth/login', [
            'title'   => 'Sign in',
            'captcha' => $this->issueCaptcha(),
        ], 'auth');
    }

    public function login(): void
    {
        // CAPTCHA only kicks in after repeated failures from this session.
        if ((int)Session::get('_login_failures', 0) >= 2) {
            $this->assertCaptcha('login');
        }

        $data = $this->validate([
            'email'    => 'required|email|max:190',
            'password' => 'required|max:255',
        ], [], 'login');

        $result = Auth::attempt($data['email'], (string)$_POST['password'], Request::bool('remember'));

        if (!$result['ok']) {
            Session::set('_login_failures', (int)Session::get('_login_failures', 0) + 1);
            Flash::error($result['message']);
            Flash::withErrors(['email' => [$result['message']]], ['email' => $data['email']]);
            $this->redirect('login');
        }

        Session::forget('_login_failures');
        Flash::success('Signed in as ' . Auth::name() . '.');

        $intended = Session::pull('_intended');
        $this->redirect(is_string($intended) && $intended !== '/login' ? ltrim($intended, '/') : Auth::homeRoute());
    }

    public function logout(): void
    {
        Auth::logout();
        Flash::info('You have been signed out.');
        $this->redirect('login');
    }

    // ---------------------------------------------------------- password reset
    public function showForgot(): void
    {
        $this->view('auth/forgot', ['title' => 'Reset your password'], 'auth');
    }

    public function sendReset(): void
    {
        $data = $this->validate(['email' => 'required|email|max:190'], [], 'forgot-password');
        $user = (new User())->findByEmail($data['email']);

        // Always show the same confirmation — never disclose whether the account exists.
        if ($user !== null) {
            $token = bin2hex(random_bytes(32));
            Database::instance()->insert('password_resets', [
                'user_id'    => (int)$user['id'],
                'token_hash' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + 3600),
            ]);
            Mailer::send($user['email'], 'Reset your HRRAMS password',
                '<p>A password reset was requested for your account. This link expires in one hour.</p>'
                . '<p><a href="' . e(url('reset-password/' . $token)) . '" style="background:#0d6efd;'
                . 'color:#fff;padding:10px 18px;border-radius:8px;text-decoration:none;display:inline-block">'
                . 'Choose a new password</a></p>'
                . '<p style="color:#6c757d;font-size:12px">If this was not you, no action is needed.</p>');
            Audit::log('password.reset_requested', 'user', (int)$user['id'], 'Reset link issued');
        }

        Flash::info('If an account exists for that address, a reset link has been sent.');
        $this->redirect('login');
    }

    public function showReset(string $token): void
    {
        if (!preg_match('/^[a-f0-9]{64}$/', $token) || $this->resetRecord($token) === null) {
            Flash::error('That reset link is invalid or has expired.');
            $this->redirect('forgot-password');
        }
        $this->view('auth/reset', ['title' => 'Choose a new password', 'token' => $token], 'auth');
    }

    public function resetPassword(): void
    {
        $data = $this->validate([
            'token'                 => 'required|min:64|max:64',
            'password'              => 'required|password',
            'password_confirmation' => 'required|match:password',
        ], ['password_confirmation' => 'Password confirmation']);

        $record = $this->resetRecord((string)$data['token']);
        if ($record === null) {
            Flash::error('That reset link is invalid or has expired.');
            $this->redirect('forgot-password');
        }

        (new User())->setPassword((int)$record['user_id'], (string)$_POST['password']);
        Database::instance()->update('password_resets',
            ['used_at' => date('Y-m-d H:i:s')], 'id = :id', ['id' => (int)$record['id']]);
        Audit::log('password.reset', 'user', (int)$record['user_id'], 'Password changed via reset link');

        Flash::success('Your password has been changed. Please sign in.');
        $this->redirect('login');
    }

    private function resetRecord(string $token): ?array
    {
        return Database::instance()->one(
            'SELECT * FROM password_resets
              WHERE token_hash = ? AND used_at IS NULL AND expires_at > NOW()',
            [hash('sha256', $token)]
        );
    }

    // ----------------------------------------------------------------- captcha
    /** Accessible arithmetic CAPTCHA — no third-party service, no images. */
    private function issueCaptcha(): string
    {
        $a = random_int(2, 9);
        $b = random_int(2, 9);
        Session::set('_captcha', $a + $b);
        return sprintf('What is %d + %d?', $a, $b);
    }

    private function assertCaptcha(string $backTo): void
    {
        $expected = Session::pull('_captcha');
        $given    = Request::input('captcha');
        if ($expected === null || !is_numeric($given) || (int)$given !== (int)$expected) {
            Flash::error('The security question answer was incorrect. Please try again.');
            Flash::withErrors(['captcha' => ['Incorrect answer.']], ['email' => Request::input('email')]);
            $this->redirect($backTo);
        }
    }
}
