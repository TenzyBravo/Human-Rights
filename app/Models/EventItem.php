<?php
declare(strict_types=1);

namespace App\Models;

final class EventItem extends Model
{
    protected string $table = 'events';

    public function upcoming(int $limit = 6): array
    {
        return $this->db()->all(
            'SELECT e.*, p.name AS province_name
               FROM events e LEFT JOIN provinces p ON p.id = e.province_id
              WHERE e.status = "published" AND e.deleted_at IS NULL AND e.starts_at >= NOW()
              ORDER BY e.starts_at ASC LIMIT ' . max(1, min(50, $limit))
        );
    }

    public function allWithProvince(): array
    {
        return $this->db()->all(
            'SELECT e.*, p.name AS province_name
               FROM events e LEFT JOIN provinces p ON p.id = e.province_id
              WHERE e.deleted_at IS NULL ORDER BY e.starts_at DESC'
        );
    }
}
