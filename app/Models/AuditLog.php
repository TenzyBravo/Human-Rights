<?php
declare(strict_types=1);

namespace App\Models;

use App\Core\Database;

final class AuditLog
{
    public function search(array $f, int $limit, int $offset): array
    {
        [$where, $params] = $this->filters($f);
        return Database::instance()->all(
            "SELECT a.*, CONCAT(u.first_name,' ',u.last_name) AS actor, u.email AS actor_email
               FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
              WHERE $where ORDER BY a.created_at DESC, a.id DESC
              LIMIT $limit OFFSET $offset",
            $params
        );
    }

    public function searchCount(array $f): int
    {
        [$where, $params] = $this->filters($f);
        return (int)Database::instance()->scalar(
            "SELECT COUNT(*) FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id WHERE $where",
            $params
        );
    }

    /** Distinct action names, for the filter dropdown. */
    public function actions(): array
    {
        $rows = Database::instance()->all('SELECT DISTINCT action FROM audit_logs ORDER BY action ASC');
        return array_column($rows, 'action');
    }

    public function recent(int $limit = 10): array
    {
        $limit = max(1, min(50, $limit));
        return Database::instance()->all(
            "SELECT a.action, a.description, a.created_at,
                    CONCAT(u.first_name,' ',u.last_name) AS actor
               FROM audit_logs a LEFT JOIN users u ON u.id = a.user_id
              ORDER BY a.id DESC LIMIT $limit"
        );
    }

    public function loginHistory(int $userId, int $limit = 20): array
    {
        $limit = max(1, min(100, $limit));
        return Database::instance()->all(
            "SELECT ip_address, user_agent, successful, created_at
               FROM login_history WHERE user_id = ?
              ORDER BY id DESC LIMIT $limit",
            [$userId]
        );
    }

    private function filters(array $f): array
    {
        $where  = ['1'];
        $params = [];
        if (!empty($f['q'])) {
            $where[]     = '(a.description LIKE :q OR u.email LIKE :q OR a.entity_type LIKE :q)';
            $params['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['action'])) {
            $where[]          = 'a.action = :action';
            $params['action'] = $f['action'];
        }
        if (!empty($f['from'])) {
            $where[]        = 'a.created_at >= :from';
            $params['from'] = $f['from'] . ' 00:00:00';
        }
        if (!empty($f['to'])) {
            $where[]      = 'a.created_at <= :to';
            $params['to'] = $f['to'] . ' 23:59:59';
        }
        return [implode(' AND ', $where), $params];
    }
}
