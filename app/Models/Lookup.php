<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

/** Reference data: provinces, districts, roles. */
final class Lookup
{
    private static function db(): Database
    {
        return Database::instance();
    }

    public static function provinces(): array
    {
        return self::db()->all('SELECT id, name FROM provinces WHERE deleted_at IS NULL ORDER BY name ASC');
    }

    public static function districts(?int $provinceId = null): array
    {
        if ($provinceId === null) {
            return self::db()->all(
                'SELECT id, province_id, name FROM districts WHERE deleted_at IS NULL ORDER BY name ASC'
            );
        }
        return self::db()->all(
            'SELECT id, province_id, name FROM districts
              WHERE province_id = ? AND deleted_at IS NULL ORDER BY name ASC',
            [$provinceId]
        );
    }

    public static function roles(): array
    {
        return self::db()->all('SELECT id, slug, name FROM roles WHERE deleted_at IS NULL ORDER BY id ASC');
    }

    public static function permissions(): array
    {
        return self::db()->all(
            'SELECT id, slug, name, module FROM permissions WHERE deleted_at IS NULL ORDER BY module, name'
        );
    }

    public static function rolePermissionMatrix(): array
    {
        $rows = self::db()->all('SELECT role_id, permission_id FROM role_permissions');
        $map  = [];
        foreach ($rows as $row) {
            $map[(int)$row['role_id']][(int)$row['permission_id']] = true;
        }
        return $map;
    }
}
