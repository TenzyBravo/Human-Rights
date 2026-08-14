<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Core\App;
use App\Core\Audit;
use App\Core\Auth;
use App\Core\Controller;
use App\Core\Database;
use App\Core\Flash;
use App\Core\Request;
use App\Models\AuditLog;
use App\Models\CaseFile;
use App\Models\Campaign;
use App\Models\Category;
use App\Models\Lookup;
use App\Models\Report;
use App\Models\Setting;
use App\Models\User;

final class AdminController extends Controller
{
    public function dashboard(): void
    {
        $reports = new Report();
        $cases   = new CaseFile();

        $this->view('admin/dashboard', [
            'title'       => 'Administrator dashboard',
            'reportStats' => $reports->totals(),
            'caseStats'   => $cases->stats(),
            'userStats'   => $this->userStats(),
            'byCategory'  => $reports->byCategory(),
            'byProvince'  => $reports->byProvince(),
            'byStatus'    => $reports->byStatus(),
            'trend'       => $reports->monthlyTrend(12),
            'performance' => $cases->officerPerformance(),
            'recent'      => $reports->recent(6),
            'activity'    => (new AuditLog())->recent(10),
            'campaigns'   => (new Campaign())->analytics(),
        ], 'app');
    }

    // ------------------------------------------------------------------- users
    public function users(): void
    {
        $users   = new User();
        $filters = Request::only(['q', 'status', 'role']);
        $page    = $this->paginate($users->searchCount($filters));

        $this->view('admin/users', [
            'title'   => 'User management',
            'rows'    => $users->search($filters, $page['perPage'], $page['offset']),
            'page'    => $page,
            'filters' => $filters,
            'roles'   => Lookup::roles(),
        ], 'app');
    }

    public function userForm(?string $id = null): void
    {
        $user = null;
        if ($id !== null && ctype_digit($id)) {
            $user = (new User())->find((int)$id);
            if ($user === null) {
                Flash::error('That user no longer exists.');
                $this->redirect('admin/users');
            }
        }
        $this->view('admin/user-form', [
            'title'     => $user === null ? 'Add user' : 'Edit user',
            'user'      => $user,
            'roles'     => Lookup::roles(),
            'provinces' => Lookup::provinces(),
        ], 'app');
    }

    public function saveUser(): void
    {
        $id    = Request::int('id');
        $rules = [
            'first_name'  => 'required|min:2|max:80',
            'last_name'   => 'required|min:2|max:80',
            'email'       => 'required|email|max:190|unique:users,email' . ($id > 0 ? ',' . $id : ''),
            'phone'       => 'nullable|phone',
            'province_id' => 'nullable|int',
            'status'      => 'required|in:pending,active,suspended,locked',
        ];
        if ($id === 0) {
            $rules['password'] = 'required|password';
        } elseif (!empty($_POST['password'])) {
            $rules['password'] = 'password';
        }

        $data  = $this->validate($rules, [], $id > 0 ? 'admin/users/' . $id : 'admin/users/create');
        $roles = array_values(array_intersect(
            (array)($_POST['roles'] ?? []),
            array_column(Lookup::roles(), 'slug')
        ));
        if ($roles === []) {
            Flash::error('Select at least one role for this user.');
            $this->redirect($id > 0 ? 'admin/users/' . $id : 'admin/users/create');
        }

        $users   = new User();
        $payload = [
            'first_name'  => $data['first_name'],
            'last_name'   => $data['last_name'],
            'email'       => mb_strtolower((string)$data['email']),
            'phone'       => $data['phone'] ?: null,
            'province_id' => $data['province_id'] ? (int)$data['province_id'] : null,
            'status'      => $data['status'],
        ];

        if ($id === 0) {
            $payload['password_hash']     = password_hash((string)$_POST['password'], PASSWORD_BCRYPT);
            $payload['email_verified_at'] = $data['status'] === 'active' ? date('Y-m-d H:i:s') : null;
            $id = $users->create($payload);
            Audit::log('user.created', 'user', $id, 'Created ' . $data['email'] . ' [' . implode(',', $roles) . ']');
            Flash::success('User created.');
        } else {
            if (!empty($_POST['password'])) {
                $users->setPassword($id, (string)$_POST['password']);
                Audit::log('user.password_reset', 'user', $id, 'Password set by administrator');
            }
            $users->updateById($id, $payload);
            Audit::log('user.updated', 'user', $id, 'Updated ' . $data['email']);
            Flash::success('User updated.');
        }

        // Never let an administrator strip their own admin role and lock the system.
        if ($id === (int)Auth::id() && !in_array('admin', $roles, true)) {
            $roles[] = 'admin';
            Flash::warning('Your own administrator role was retained to avoid locking you out.');
        }
        $users->syncRoles($id, $roles);
        Audit::log('user.roles_changed', 'user', $id, 'Roles: ' . implode(', ', $roles));

        $this->redirect('admin/users');
    }

    public function setUserStatus(string $id): void
    {
        if (!ctype_digit($id)) {
            $this->redirect('admin/users');
        }
        $status = (string)Request::input('status', '');
        if (!in_array($status, ['pending', 'active', 'suspended', 'locked'], true)) {
            Flash::error('Invalid status.');
            $this->redirect('admin/users');
        }
        if ((int)$id === (int)Auth::id() && $status !== 'active') {
            Flash::error('You cannot suspend your own account.');
            $this->redirect('admin/users');
        }
        $update = ['status' => $status];
        if ($status === 'active') {
            $update['locked_until'] = null;
            $update['failed_attempts'] = 0;
        }
        (new User())->updateById((int)$id, $update);
        Audit::log('user.status_changed', 'user', (int)$id, 'Status set to ' . $status);
        Flash::success('User status updated.');
        $this->redirect('admin/users');
    }

    public function deleteUser(string $id): void
    {
        if (!ctype_digit($id)) {
            $this->redirect('admin/users');
        }
        if ((int)$id === (int)Auth::id()) {
            Flash::error('You cannot delete your own account.');
            $this->redirect('admin/users');
        }
        // Soft delete keeps every audit trail and case history intact.
        (new User())->softDelete((int)$id);
        Audit::log('user.deleted', 'user', (int)$id, 'Account soft-deleted');
        Flash::success('User deactivated. All history has been preserved.');
        $this->redirect('admin/users');
    }

    // ------------------------------------------------------------------- roles
    public function roles(): void
    {
        $this->view('admin/roles', [
            'title'       => 'Roles & permissions',
            'roles'       => Lookup::roles(),
            'permissions' => Lookup::permissions(),
            'matrix'      => Lookup::rolePermissionMatrix(),
        ], 'app');
    }

    public function saveRolePermissions(): void
    {
        $roleId = Request::int('role_id');
        $role   = Database::instance()->one('SELECT * FROM roles WHERE id = ?', [$roleId]);
        if ($role === null) {
            Flash::error('Unknown role.');
            $this->redirect('admin/roles');
        }
        if ($role['slug'] === 'admin') {
            Flash::warning('The administrator role always retains every permission.');
            $this->redirect('admin/roles');
        }

        $valid    = array_column(Lookup::permissions(), 'id');
        $selected = array_values(array_intersect(
            array_map('intval', (array)($_POST['permissions'] ?? [])),
            array_map('intval', $valid)
        ));

        $db = Database::instance();
        $db->begin();
        try {
            $db->run('DELETE FROM role_permissions WHERE role_id = ?', [$roleId]);
            foreach ($selected as $permissionId) {
                $db->run(
                    'INSERT INTO role_permissions (role_id, permission_id, created_by) VALUES (?, ?, ?)',
                    [$roleId, $permissionId, Auth::id()]
                );
            }
            $db->commit();
        } catch (\Throwable $e) {
            $db->rollback();
            Flash::error('Could not save the permission matrix.');
            $this->redirect('admin/roles');
        }

        Audit::log('role.permissions_changed', 'role', $roleId,
            $role['name'] . ' now has ' . count($selected) . ' permission(s)');
        Flash::success('Permissions updated for ' . $role['name'] . '.');
        $this->redirect('admin/roles');
    }

    // -------------------------------------------------------------- categories
    public function categories(): void
    {
        $this->view('admin/categories', [
            'title'      => 'Report categories',
            'categories' => (new Category())->withCounts(),
        ], 'app');
    }

    public function saveCategory(): void
    {
        $data = $this->validate([
            'id'          => 'nullable|int',
            'name'        => 'required|min:3|max:120',
            'description' => 'nullable|max:500',
            'icon'        => 'nullable|max:60',
        ], [], 'admin/categories');

        $categories = new Category();
        $id         = !empty($data['id']) ? (int)$data['id'] : null;
        $payload    = [
            'name'        => $data['name'],
            'slug'        => slugify((string)$data['name']),
            'description' => $data['description'] ?: null,
            'icon'        => $data['icon'] ?: 'bi-flag',
            'is_active'   => Request::bool('is_active') ? 1 : 0,
        ];

        if ($id === null) {
            $exists = Database::instance()->scalar(
                'SELECT 1 FROM report_categories WHERE slug = ? AND deleted_at IS NULL', [$payload['slug']]
            );
            if ($exists) {
                Flash::error('A category with that name already exists.');
                $this->redirect('admin/categories');
            }
            $id = $categories->create($payload);
            Audit::log('category.created', 'category', $id, $data['name']);
            Flash::success('Category created.');
        } else {
            $categories->updateById($id, $payload);
            Audit::log('category.updated', 'category', $id, $data['name']);
            Flash::success('Category updated.');
        }
        $this->redirect('admin/categories');
    }

    public function deleteCategory(string $id): void
    {
        if (!ctype_digit($id)) {
            $this->redirect('admin/categories');
        }
        $inUse = (int)Database::instance()->scalar(
            'SELECT COUNT(*) FROM reports WHERE category_id = ? AND deleted_at IS NULL', [(int)$id]
        );
        if ($inUse > 0) {
            // Keep referential integrity: deactivate rather than remove.
            (new Category())->updateById((int)$id, ['is_active' => 0]);
            Flash::warning("This category is used by {$inUse} report(s), so it was deactivated instead of deleted.");
        } else {
            (new Category())->softDelete((int)$id);
            Flash::success('Category deleted.');
        }
        Audit::log('category.deleted', 'category', (int)$id, 'In use by ' . $inUse . ' report(s)');
        $this->redirect('admin/categories');
    }

    // ---------------------------------------------------------------- settings
    public function settings(): void
    {
        $this->view('admin/settings', [
            'title'    => 'System settings',
            'grouped'  => Setting::grouped(),
        ], 'app');
    }

    public function saveSettings(): void
    {
        $known   = Setting::all();
        $changed = 0;
        foreach ($_POST as $key => $value) {
            if (!isset($known[$key]) || is_array($value)) {
                continue;
            }
            $clean = mb_substr(trim((string)$value), 0, 5000);
            if ($known[$key]['type'] === 'int' && !preg_match('/^\d+$/', $clean)) {
                Flash::warning('Skipped "' . e($key) . '": a whole number is required.');
                continue;
            }
            Setting::set($key, $clean);
            $changed++;
        }
        // Unchecked checkboxes never reach POST, so booleans are handled explicitly.
        foreach ($known as $key => $meta) {
            if ($meta['type'] === 'bool') {
                Setting::set($key, isset($_POST[$key]) ? '1' : '0');
            }
        }
        Audit::log('settings.updated', null, null, $changed . ' setting(s) changed');
        Flash::success('Settings saved.');
        $this->redirect('admin/settings');
    }

    // ------------------------------------------------------------------- audit
    public function audit(): void
    {
        $logs    = new AuditLog();
        $filters = Request::only(['q', 'action', 'from', 'to']);
        $page    = $this->paginate($logs->searchCount($filters), 30);

        $this->view('admin/audit', [
            'title'   => 'Audit logs',
            'rows'    => $logs->search($filters, $page['perPage'], $page['offset']),
            'page'    => $page,
            'filters' => $filters,
            'actions' => $logs->actions(),
        ], 'app');
    }

    // ------------------------------------------------------------------ backup
    public function backup(): void
    {
        $dir   = (string)App::config('app.backups');
        $files = [];
        foreach (glob($dir . '/*.sql*') ?: [] as $path) {
            $files[] = [
                'name'     => basename($path),
                'size'     => (int)filesize($path),
                'modified' => date('Y-m-d H:i:s', (int)filemtime($path)),
            ];
        }
        usort($files, static fn($a, $b) => strcmp($b['modified'], $a['modified']));

        $this->view('admin/backup', [
            'title'   => 'Backup & restore',
            'files'   => $files,
            'mysqldump' => $this->mysqldumpPath(),
        ], 'app');
    }

    public function runBackup(): void
    {
        $dump = $this->mysqldumpPath();
        if ($dump === null) {
            Flash::error('mysqldump was not found on this server. Configure its path or back up manually.');
            $this->redirect('admin/backup');
        }

        $dir  = (string)App::config('app.backups');
        if (!is_dir($dir) && !@mkdir($dir, 0775, true)) {
            Flash::error('The backup directory is not writable.');
            $this->redirect('admin/backup');
        }
        $file = $dir . '/hrrams-' . date('Ymd-His') . '.sql';
        $db   = App::config('db');

        // Credentials go through the environment, never the command line, so
        // they cannot be read from the process list.
        $command = sprintf(
            '%s --host=%s --port=%d --user=%s --single-transaction --routines --events %s > %s 2>&1',
            escapeshellcmd($dump),
            escapeshellarg((string)$db['host']),
            (int)$db['port'],
            escapeshellarg((string)$db['user']),
            escapeshellarg((string)$db['name']),
            escapeshellarg($file)
        );

        $previous = getenv('MYSQL_PWD');
        putenv('MYSQL_PWD=' . (string)$db['pass']);
        exec($command, $output, $status);
        putenv('MYSQL_PWD=' . ($previous === false ? '' : $previous));

        if ($status !== 0 || !is_file($file) || filesize($file) === 0) {
            @unlink($file);
            App::log('backup', 'mysqldump failed: ' . implode(' ', $output));
            Audit::log('backup.failed', null, null, 'mysqldump exit code ' . $status);
            Flash::error('Backup failed. See storage/logs/backup.log for details.');
            $this->redirect('admin/backup');
        }

        @chmod($file, 0600);
        Audit::log('backup.created', null, null, basename($file) . ' (' . bytes((int)filesize($file)) . ')');
        Flash::success('Backup created: ' . basename($file));
        $this->redirect('admin/backup');
    }

    public function downloadBackup(string $file): void
    {
        // Only ever serve a file matching our own generated name pattern.
        if (!preg_match('/^hrrams-\d{8}-\d{6}\.sql$/', $file)) {
            Flash::error('Invalid backup file name.');
            $this->redirect('admin/backup');
        }
        $path = (string)App::config('app.backups') . '/' . $file;
        if (!is_file($path)) {
            Flash::error('That backup no longer exists.');
            $this->redirect('admin/backup');
        }
        Audit::log('backup.downloaded', null, null, $file);
        header('Content-Type: application/sql');
        header('Content-Length: ' . (string)filesize($path));
        header('Content-Disposition: attachment; filename="' . $file . '"');
        readfile($path);
        exit;
    }

    // --------------------------------------------------------------- internals
    private function mysqldumpPath(): ?string
    {
        $candidates = [
            '/Applications/MAMP/Library/bin/mysqldump',
            '/Applications/MAMP/Library/bin/mysql80/bin/mysqldump',
            '/usr/local/mysql/bin/mysqldump',
            '/opt/homebrew/bin/mysqldump',
            '/usr/bin/mysqldump',
            '/usr/local/bin/mysqldump',
            'C:/xampp/mysql/bin/mysqldump.exe',
        ];
        foreach ($candidates as $path) {
            if (is_file($path)) {
                return $path;
            }
        }
        return null;
    }

    private function userStats(): array
    {
        $row = Database::instance()->one(
            'SELECT COUNT(*) AS total,
                    SUM(status = "active")    AS active,
                    SUM(status = "pending")   AS pending,
                    SUM(status = "suspended") AS suspended,
                    SUM(created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS new_30d
               FROM users WHERE deleted_at IS NULL'
        ) ?? [];
        $stats = array_map(static fn($v) => (int)$v, $row);
        $stats['officers'] = (int)Database::instance()->scalar(
            'SELECT COUNT(DISTINCT u.id) FROM users u
               JOIN user_roles ur ON ur.user_id = u.id
               JOIN roles r ON r.id = ur.role_id
              WHERE r.slug = "officer" AND u.deleted_at IS NULL'
        );
        return $stats;
    }
}
