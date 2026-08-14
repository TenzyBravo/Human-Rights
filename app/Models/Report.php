<?php
declare(strict_types=1);

namespace App\Models;

final class Report extends Model
{
    protected string $table = 'reports';

    public const STATUSES = ['submitted','under_review','assigned','investigating',
                             'awaiting_information','escalated','resolved','closed','rejected'];

    /** Reference numbers look like HR-2026-000123 and are unique per year. */
    public function nextReference(): string
    {
        $year = date('Y');
        $seq  = (int)$this->db()->scalar(
            'SELECT COUNT(*) + 1 FROM reports WHERE YEAR(created_at) = ?', [(int)$year]
        );
        do {
            $ref = sprintf('HR-%s-%06d', $year, $seq);
            $taken = $this->db()->scalar('SELECT 1 FROM reports WHERE reference_no = ?', [$ref]);
            $seq++;
        } while ($taken);
        return $ref;
    }

    public function findByReference(string $ref): ?array
    {
        return $this->db()->one(
            'SELECT r.*, rc.name AS category_name, p.name AS province_name, d.name AS district_name,
                    c.case_number, c.status AS case_status, c.opened_at, c.resolved_at
               FROM reports r
               JOIN report_categories rc ON rc.id = r.category_id
               LEFT JOIN provinces p     ON p.id = r.province_id
               LEFT JOIN districts d     ON d.id = r.district_id
               LEFT JOIN cases c         ON c.report_id = r.id AND c.deleted_at IS NULL
              WHERE r.reference_no = ? AND r.deleted_at IS NULL',
            [$ref]
        );
    }

    public function detail(int $id): ?array
    {
        return $this->db()->one(
            'SELECT r.*, rc.name AS category_name, p.name AS province_name, d.name AS district_name,
                    c.id AS case_id, c.case_number,
                    CONCAT(u.first_name," ",u.last_name) AS reporter_account
               FROM reports r
               JOIN report_categories rc ON rc.id = r.category_id
               LEFT JOIN provinces p     ON p.id = r.province_id
               LEFT JOIN districts d     ON d.id = r.district_id
               LEFT JOIN cases c         ON c.report_id = r.id AND c.deleted_at IS NULL
               LEFT JOIN users u         ON u.id = r.reporter_user_id
              WHERE r.id = ? AND r.deleted_at IS NULL',
            [$id]
        );
    }

    /** @return array<int,array<string,mixed>> */
    public function forUser(int $userId): array
    {
        return $this->db()->all(
            'SELECT r.id, r.reference_no, r.title, r.status, r.urgency, r.created_at,
                    rc.name AS category_name, c.case_number
               FROM reports r
               JOIN report_categories rc ON rc.id = r.category_id
               LEFT JOIN cases c         ON c.report_id = r.id AND c.deleted_at IS NULL
              WHERE r.reporter_user_id = ? AND r.deleted_at IS NULL
              ORDER BY r.created_at DESC',
            [$userId]
        );
    }

    public function setStatus(int $id, string $status): void
    {
        if (!in_array($status, self::STATUSES, true)) {
            return;
        }
        $this->db()->update('reports', ['status' => $status], 'id = :id', ['id' => $id]);
    }

    /** @return array<int,array<string,mixed>> */
    public function attachments(int $reportId): array
    {
        return $this->db()->all(
            'SELECT * FROM attachments
              WHERE entity_type = "report" AND entity_id = ? AND deleted_at IS NULL
              ORDER BY created_at ASC',
            [$reportId]
        );
    }

    // ------------------------------------------------------------- statistics
    public function totals(): array
    {
        $row = $this->db()->one(
            'SELECT COUNT(*) AS total,
                    SUM(status = "submitted")     AS submitted,
                    SUM(status IN ("assigned","investigating","under_review","awaiting_information","escalated")) AS active,
                    SUM(status = "resolved")      AS resolved,
                    SUM(status = "closed")        AS closed,
                    SUM(status = "rejected")      AS rejected,
                    SUM(urgency = "critical")     AS critical,
                    SUM(created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)) AS last_30_days
               FROM reports WHERE deleted_at IS NULL'
        );
        return array_map(static fn($v) => (int)$v, $row ?? []);
    }

    public function byCategory(): array
    {
        return $this->db()->all(
            'SELECT rc.name AS label, COUNT(r.id) AS value
               FROM report_categories rc
               LEFT JOIN reports r ON r.category_id = rc.id AND r.deleted_at IS NULL
              WHERE rc.deleted_at IS NULL
              GROUP BY rc.id, rc.name
              HAVING value > 0
              ORDER BY value DESC'
        );
    }

    public function byProvince(): array
    {
        return $this->db()->all(
            'SELECT p.name AS label, COUNT(r.id) AS value
               FROM provinces p
               LEFT JOIN reports r ON r.province_id = p.id AND r.deleted_at IS NULL
              WHERE p.deleted_at IS NULL
              GROUP BY p.id, p.name
              ORDER BY value DESC'
        );
    }

    public function byStatus(): array
    {
        return $this->db()->all(
            'SELECT status AS label, COUNT(*) AS value FROM reports
              WHERE deleted_at IS NULL GROUP BY status ORDER BY value DESC'
        );
    }

    /** Last 12 months of submission volume. */
    public function monthlyTrend(int $months = 12): array
    {
        return $this->db()->all(
            'SELECT DATE_FORMAT(created_at, "%Y-%m") AS label, COUNT(*) AS value
               FROM reports
              WHERE deleted_at IS NULL AND created_at >= DATE_SUB(CURDATE(), INTERVAL ? MONTH)
              GROUP BY label ORDER BY label ASC',
            [$months]
        );
    }

    /** Province x category density, for the heat map. */
    public function heatmap(): array
    {
        return $this->db()->all(
            'SELECT p.name AS province, rc.name AS category, COUNT(r.id) AS value
               FROM reports r
               JOIN report_categories rc ON rc.id = r.category_id
               JOIN provinces p          ON p.id = r.province_id
              WHERE r.deleted_at IS NULL
              GROUP BY p.name, rc.name'
        );
    }

    public function recent(int $limit = 8): array
    {
        $limit = max(1, min(50, $limit));
        return $this->db()->all(
            "SELECT r.id, r.reference_no, r.title, r.status, r.urgency, r.created_at,
                    rc.name AS category_name
               FROM reports r JOIN report_categories rc ON rc.id = r.category_id
              WHERE r.deleted_at IS NULL
              ORDER BY r.created_at DESC LIMIT $limit"
        );
    }

    /** Public homepage counters — no personal data. */
    public function publicStats(): array
    {
        $row = $this->db()->one(
            'SELECT COUNT(*) AS reports_received,
                    SUM(status IN ("resolved","closed")) AS cases_resolved
               FROM reports WHERE deleted_at IS NULL'
        ) ?? [];
        return [
            'reports_received' => (int)($row['reports_received'] ?? 0),
            'cases_resolved'   => (int)($row['cases_resolved'] ?? 0),
            'campaigns'        => (int)$this->db()->scalar(
                'SELECT COUNT(*) FROM awareness_campaigns
                  WHERE status = "published" AND type = "campaign" AND deleted_at IS NULL'),
            'provinces'        => (int)$this->db()->scalar(
                'SELECT COUNT(*) FROM provinces WHERE deleted_at IS NULL'),
        ];
    }

    /** Filterable list used by the officer/supervisor report browser. */
    public function search(array $f, int $limit, int $offset): array
    {
        [$where, $params] = $this->filters($f);
        return $this->db()->all(
            "SELECT r.id, r.reference_no, r.title, r.status, r.urgency, r.incident_date, r.created_at,
                    rc.name AS category_name, p.name AS province_name, c.case_number
               FROM reports r
               JOIN report_categories rc ON rc.id = r.category_id
               LEFT JOIN provinces p     ON p.id = r.province_id
               LEFT JOIN cases c         ON c.report_id = r.id AND c.deleted_at IS NULL
              WHERE $where
              ORDER BY r.created_at DESC
              LIMIT $limit OFFSET $offset",
            $params
        );
    }

    public function searchCount(array $f): int
    {
        [$where, $params] = $this->filters($f);
        return (int)$this->db()->scalar(
            "SELECT COUNT(*) FROM reports r
               LEFT JOIN cases c ON c.report_id = r.id AND c.deleted_at IS NULL
              WHERE $where", $params
        );
    }

    private function filters(array $f): array
    {
        $where  = ['r.deleted_at IS NULL'];
        $params = [];
        if (!empty($f['q'])) {
            $where[]     = '(r.title LIKE :q OR r.reference_no LIKE :q OR r.location LIKE :q)';
            $params['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['status']) && in_array($f['status'], self::STATUSES, true)) {
            $where[]          = 'r.status = :status';
            $params['status'] = $f['status'];
        }
        if (!empty($f['category'])) {
            $where[]        = 'r.category_id = :cat';
            $params['cat']  = (int)$f['category'];
        }
        if (!empty($f['province'])) {
            $where[]         = 'r.province_id = :prov';
            $params['prov']  = (int)$f['province'];
        }
        if (!empty($f['urgency'])) {
            $where[]          = 'r.urgency = :urg';
            $params['urg']    = $f['urgency'];
        }
        if (!empty($f['from'])) {
            $where[]         = 'r.created_at >= :from';
            $params['from']  = $f['from'] . ' 00:00:00';
        }
        if (!empty($f['to'])) {
            $where[]       = 'r.created_at <= :to';
            $params['to']  = $f['to'] . ' 23:59:59';
        }
        return [implode(' AND ', $where), $params];
    }
}
