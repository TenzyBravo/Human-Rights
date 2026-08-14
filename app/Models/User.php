<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\App;

final class User extends Model
{
    protected string $table = 'users';

    public function find(int $id): ?array
    {
        $user = parent::find($id);
        if ($user !== null) {
            $user['roles'] = $this->roleSlugs($id);
        }
        return $user;
    }

    public function findByEmail(string $email): ?array
    {
        $user = $this->db()->one(
            'SELECT * FROM users WHERE email = ? AND deleted_at IS NULL', [mb_strtolower($email)]
        );
        if ($user !== null) {
            $user['roles'] = $this->roleSlugs((int)$user['id']);
        }
        return $user;
    }

    public function findByVerifyToken(string $token): ?array
    {
        return $this->db()->one(
            'SELECT * FROM users WHERE verify_token = ? AND deleted_at IS NULL', [$token]
        );
    }

    /** @return array<int,string> */
    public function roleSlugs(int $userId): array
    {
        $rows = $this->db()->all(
            'SELECT r.slug FROM roles r
               JOIN user_roles ur ON ur.role_id = r.id
              WHERE ur.user_id = ? AND r.deleted_at IS NULL', [$userId]
        );
        return array_column($rows, 'slug');
    }

    /** @return array<int,string> */
    public function permissionsFor(int $userId): array
    {
        $rows = $this->db()->all(
            'SELECT DISTINCT p.slug
               FROM permissions p
               JOIN role_permissions rp ON rp.permission_id = p.id
               JOIN user_roles ur       ON ur.role_id = rp.role_id
              WHERE ur.user_id = ? AND p.deleted_at IS NULL', [$userId]
        );
        return array_column($rows, 'slug');
    }

    public function register(array $data, string $roleSlug = 'citizen'): array
    {
        $token   = bin2hex(random_bytes(32));
        $requires = (bool)App::config('security.require_email_verification', true);

        $id = $this->db()->insert('users', [
            'first_name'    => $data['first_name'],
            'last_name'     => $data['last_name'],
            'email'         => mb_strtolower($data['email']),
            'phone'         => $data['phone'] ?? null,
            'password_hash' => password_hash($data['password'], PASSWORD_BCRYPT),
            'address'       => $data['address'] ?? null,
            'province_id'   => !empty($data['province_id']) ? (int)$data['province_id'] : null,
            'district_id'   => !empty($data['district_id']) ? (int)$data['district_id'] : null,
            'nationality'   => $data['nationality'] ?? null,
            'status'        => $requires ? 'pending' : 'active',
            'verify_token'  => $requires ? $token : null,
            'email_verified_at' => $requires ? null : date('Y-m-d H:i:s'),
        ]);

        $this->assignRole($id, $roleSlug);
        return ['id' => $id, 'token' => $token];
    }

    public function assignRole(int $userId, string $roleSlug): void
    {
        $roleId = $this->db()->scalar('SELECT id FROM roles WHERE slug = ?', [$roleSlug]);
        if ($roleId === null) {
            return;
        }
        $this->db()->run(
            'INSERT IGNORE INTO user_roles (user_id, role_id, created_by) VALUES (?, ?, ?)',
            [$userId, (int)$roleId, \App\Core\Auth::id()]
        );
    }

    public function syncRoles(int $userId, array $roleSlugs): void
    {
        $this->db()->run('DELETE FROM user_roles WHERE user_id = ?', [$userId]);
        foreach ($roleSlugs as $slug) {
            $this->assignRole($userId, (string)$slug);
        }
    }

    public function markVerified(int $userId): void
    {
        $this->db()->update('users', [
            'email_verified_at' => date('Y-m-d H:i:s'),
            'verify_token'      => null,
            'status'            => 'active',
        ], 'id = :id', ['id' => $userId]);
    }

    public function registerFailedAttempt(int $userId): void
    {
        $max     = (int)App::config('security.max_login_attempts', 5);
        $minutes = (int)App::config('security.lockout_minutes', 15);

        $attempts = (int)$this->db()->scalar('SELECT failed_attempts FROM users WHERE id = ?', [$userId]) + 1;
        $data     = ['failed_attempts' => $attempts];

        if ($attempts >= $max) {
            $data['locked_until']    = date('Y-m-d H:i:s', time() + $minutes * 60);
            $data['failed_attempts'] = 0;
            \App\Core\Audit::log('account.locked', 'user', $userId, "Locked after {$max} failed attempts");
        }
        $this->db()->update('users', $data, 'id = :id', ['id' => $userId]);
    }

    public function clearFailedAttempts(int $userId): void
    {
        $this->db()->update('users', [
            'failed_attempts' => 0,
            'locked_until'    => null,
            'last_login_at'   => date('Y-m-d H:i:s'),
        ], 'id = :id', ['id' => $userId]);
    }

    public function setPassword(int $userId, string $plain): void
    {
        $this->db()->update('users', [
            'password_hash'   => password_hash($plain, PASSWORD_BCRYPT),
            'failed_attempts' => 0,
            'locked_until'    => null,
            'remember_token'  => null,
        ], 'id = :id', ['id' => $userId]);
    }

    public function setRememberToken(int $userId, string $hash): void
    {
        $this->db()->update('users', ['remember_token' => $hash], 'id = :id', ['id' => $userId]);
    }

    public function clearRememberToken(int $userId): void
    {
        $this->db()->update('users', ['remember_token' => null], 'id = :id', ['id' => $userId]);
    }

    /** @return array<int,array<string,mixed>> officers available for assignment */
    public function officers(): array
    {
        return $this->db()->all(
            'SELECT u.id, u.first_name, u.last_name, u.email,
                    (SELECT COUNT(*) FROM cases c
                      WHERE c.officer_id = u.id AND c.deleted_at IS NULL
                        AND c.status NOT IN ("closed","resolved","rejected")) AS open_cases
               FROM users u
               JOIN user_roles ur ON ur.user_id = u.id
               JOIN roles r       ON r.id = ur.role_id
              WHERE r.slug = "officer" AND u.status = "active" AND u.deleted_at IS NULL
              ORDER BY open_cases ASC, u.first_name ASC'
        );
    }

    /** Paginated, filterable user list for administration. */
    public function search(array $filters, int $limit, int $offset): array
    {
        [$where, $params] = $this->buildFilters($filters);
        return $this->db()->all(
            "SELECT u.*, GROUP_CONCAT(r.name ORDER BY r.name SEPARATOR ', ') AS role_names
               FROM users u
               LEFT JOIN user_roles ur ON ur.user_id = u.id
               LEFT JOIN roles r       ON r.id = ur.role_id
              WHERE $where
              GROUP BY u.id
              ORDER BY u.created_at DESC
              LIMIT $limit OFFSET $offset",
            $params
        );
    }

    public function searchCount(array $filters): int
    {
        [$where, $params] = $this->buildFilters($filters);
        return (int)$this->db()->scalar("SELECT COUNT(DISTINCT u.id) FROM users u
            LEFT JOIN user_roles ur ON ur.user_id = u.id
            LEFT JOIN roles r ON r.id = ur.role_id WHERE $where", $params);
    }

    private function buildFilters(array $f): array
    {
        $where  = ['u.deleted_at IS NULL'];
        $params = [];
        if (!empty($f['q'])) {
            $where[]      = '(u.first_name LIKE :q OR u.last_name LIKE :q OR u.email LIKE :q OR u.phone LIKE :q)';
            $params['q']  = '%' . $f['q'] . '%';
        }
        if (!empty($f['status'])) {
            $where[]           = 'u.status = :status';
            $params['status']  = $f['status'];
        }
        if (!empty($f['role'])) {
            $where[]         = 'r.slug = :role';
            $params['role']  = $f['role'];
        }
        return [implode(' AND ', $where), $params];
    }
}
