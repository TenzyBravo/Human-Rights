<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Flash;
use App\Core\Notify;
use App\Core\Request;
use App\Models\AuditLog;
use App\Models\Lookup;
use App\Models\User;

final class ProfileController extends Controller
{
    public function edit(): void
    {
        $user = Auth::user();
        $this->view('auth/profile', [
            'title'        => 'My profile',
            'user'         => $user,
            'provinces'    => Lookup::provinces(),
            'districts'    => Lookup::districts(),
            'loginHistory' => (new AuditLog())->loginHistory((int)Auth::id(), 10),
        ], 'app');
    }

    public function update(): void
    {
        $id   = (int)Auth::id();
        $data = $this->validate([
            'first_name'  => 'required|min:2|max:80',
            'last_name'   => 'required|min:2|max:80',
            'email'       => 'required|email|max:190|unique:users,email,' . $id,
            'phone'       => 'nullable|phone',
            'address'     => 'nullable|max:255',
            'province_id' => 'nullable|int',
            'district_id' => 'nullable|int',
            'nationality' => 'nullable|max:80',
        ], [], 'my/profile');

        $before = Auth::user() ?? [];
        (new User())->updateById($id, [
            'first_name'  => $data['first_name'],
            'last_name'   => $data['last_name'],
            'email'       => mb_strtolower((string)$data['email']),
            'phone'       => $data['phone'] ?: null,
            'address'     => $data['address'] ?: null,
            'province_id' => $data['province_id'] ? (int)$data['province_id'] : null,
            'district_id' => $data['district_id'] ? (int)$data['district_id'] : null,
            'nationality' => $data['nationality'] ?: null,
        ]);

        Audit::log('profile.updated', 'user', $id, 'Profile details changed',
            ['email' => $before['email'] ?? null], ['email' => $data['email']]);
        Flash::success('Your profile has been updated.');
        $this->redirect('my/profile');
    }

    public function changePassword(): void
    {
        $data = $this->validate([
            'current_password'      => 'required',
            'password'              => 'required|password',
            'password_confirmation' => 'required|match:password',
        ], [
            'current_password'      => 'Current password',
            'password_confirmation' => 'Password confirmation',
        ], 'my/profile');

        $user = Auth::user();
        if ($user === null || !password_verify((string)$data['current_password'], $user['password_hash'])) {
            Audit::log('password.change_failed', 'user', (int)Auth::id(), 'Wrong current password');
            Flash::error('Your current password is incorrect.');
            $this->redirect('my/profile');
        }

        (new User())->setPassword((int)$user['id'], (string)$_POST['password']);
        Audit::log('password.changed', 'user', (int)$user['id'], 'Password changed from profile');
        Flash::success('Your password has been changed.');
        $this->redirect('my/profile');
    }

    public function notifications(): void
    {
        $userId = (int)Auth::id();
        $rows   = \App\Core\Database::instance()->all(
            'SELECT * FROM notifications WHERE user_id = ? AND deleted_at IS NULL
              ORDER BY created_at DESC LIMIT 100', [$userId]
        );
        $this->view('auth/notifications', [
            'title'         => 'Notifications',
            'notifications' => $rows,
        ], 'app');
    }

    public function markRead(): void
    {
        $id = Request::int('id');
        Notify::markRead((int)Auth::id(), $id > 0 ? $id : null);
        if (Request::wantsJson()) {
            $this->json(['ok' => true, 'unread' => Notify::unreadCount((int)Auth::id())]);
        }
        \App\Core\View::back();
    }
}
