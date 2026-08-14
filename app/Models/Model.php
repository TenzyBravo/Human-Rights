<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Auth;
use App\Core\Database;

abstract class Model
{
    protected string $table = '';
    protected bool $softDeletes = true;

    protected function db(): Database
    {
        return Database::instance();
    }

    public function find(int $id): ?array
    {
        $sql = "SELECT * FROM `{$this->table}` WHERE id = ?";
        if ($this->softDeletes) {
            $sql .= ' AND deleted_at IS NULL';
        }
        return $this->db()->one($sql, [$id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function allRows(string $orderBy = 'id ASC'): array
    {
        $sql = "SELECT * FROM `{$this->table}`";
        if ($this->softDeletes) {
            $sql .= ' WHERE deleted_at IS NULL';
        }
        return $this->db()->all($sql . ' ORDER BY ' . $this->safeOrder($orderBy));
    }

    public function create(array $data): int
    {
        if (Auth::id() !== null && $this->hasColumn('created_by')) {
            $data['created_by'] = Auth::id();
        }
        return $this->db()->insert($this->table, $data);
    }

    public function updateById(int $id, array $data): int
    {
        if (Auth::id() !== null && $this->hasColumn('updated_by')) {
            $data['updated_by'] = Auth::id();
        }
        return $this->db()->update($this->table, $data, 'id = :id', ['id' => $id]);
    }

    public function softDelete(int $id): int
    {
        return $this->db()->update(
            $this->table,
            ['deleted_at' => date('Y-m-d H:i:s')],
            'id = :id',
            ['id' => $id]
        );
    }

    public function restore(int $id): int
    {
        return $this->db()->update($this->table, ['deleted_at' => null], 'id = :id', ['id' => $id]);
    }

    public function count(string $where = '1', array $params = []): int
    {
        $sql = "SELECT COUNT(*) FROM `{$this->table}` WHERE $where";
        if ($this->softDeletes) {
            $sql .= ' AND deleted_at IS NULL';
        }
        return (int)$this->db()->scalar($sql, $params);
    }

    /** Whitelist ORDER BY input so it can never carry injected SQL. */
    protected function safeOrder(string $orderBy): string
    {
        return preg_match('/^[a-zA-Z0-9_.]+\s+(ASC|DESC)$/i', trim($orderBy)) === 1
            ? $orderBy
            : 'id DESC';
    }

    private function hasColumn(string $column): bool
    {
        static $cache = [];
        if (!isset($cache[$this->table])) {
            $rows = $this->db()->all("SHOW COLUMNS FROM `{$this->table}`");
            $cache[$this->table] = array_column($rows, 'Field');
        }
        return in_array($column, $cache[$this->table], true);
    }
}
