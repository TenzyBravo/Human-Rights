<?php
declare(strict_types=1);

namespace App\Models;

final class Category extends Model
{
    protected string $table = 'report_categories';

    public function active(): array
    {
        return $this->db()->all(
            'SELECT * FROM report_categories WHERE is_active = 1 AND deleted_at IS NULL ORDER BY name ASC'
        );
    }

    public function withCounts(): array
    {
        return $this->db()->all(
            'SELECT rc.*, COUNT(r.id) AS report_count
               FROM report_categories rc
               LEFT JOIN reports r ON r.category_id = rc.id AND r.deleted_at IS NULL
              WHERE rc.deleted_at IS NULL
              GROUP BY rc.id ORDER BY rc.name ASC'
        );
    }
}
