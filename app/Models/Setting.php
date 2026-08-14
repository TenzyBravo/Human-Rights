<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

final class Setting
{
    private static ?array $cache = null;

    public static function all(): array
    {
        if (self::$cache !== null) {
            return self::$cache;
        }
        $rows = Database::instance()->all(
            'SELECT skey, svalue, stype, group_name FROM settings WHERE deleted_at IS NULL ORDER BY group_name, skey'
        );
        $out = [];
        foreach ($rows as $row) {
            $out[$row['skey']] = [
                'value' => self::cast((string)$row['svalue'], (string)$row['stype']),
                'type'  => $row['stype'],
                'group' => $row['group_name'],
            ];
        }
        return self::$cache = $out;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::all()[$key]['value'] ?? $default;
    }

    public static function set(string $key, mixed $value): void
    {
        $stored = is_array($value) ? json_encode($value) : (is_bool($value) ? ($value ? '1' : '0') : (string)$value);
        Database::instance()->run(
            'INSERT INTO settings (skey, svalue, updated_by) VALUES (?, ?, ?)
             ON DUPLICATE KEY UPDATE svalue = VALUES(svalue), updated_by = VALUES(updated_by)',
            [$key, $stored, Auth::id()]
        );
        self::$cache = null;
    }

    /** @return array<string,array<string,array<string,mixed>>> grouped for the settings screen */
    public static function grouped(): array
    {
        $grouped = [];
        foreach (self::all() as $key => $meta) {
            $grouped[$meta['group']][$key] = $meta;
        }
        return $grouped;
    }

    private static function cast(string $value, string $type): mixed
    {
        return match ($type) {
            'int'  => (int)$value,
            'bool' => $value === '1' || $value === 'true',
            'json' => json_decode($value, true) ?? [],
            default => $value,
        };
    }
}
