<?php
declare(strict_types=1);

namespace App\Models;

final class ResourceItem extends Model
{
    protected string $table = 'educational_resources';

    public const TYPES = ['document','video','audio','image','link','training'];

    public function published(int $limit = 0): array
    {
        $sql = 'SELECT * FROM educational_resources
                 WHERE is_published = 1 AND deleted_at IS NULL ORDER BY created_at DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . max(1, min(50, $limit));
        }
        return $this->db()->all($sql);
    }

    public function findBySlug(string $slug): ?array
    {
        return $this->db()->one(
            'SELECT * FROM educational_resources WHERE slug = ? AND deleted_at IS NULL', [$slug]
        );
    }

    public function registerDownload(int $id): void
    {
        $this->db()->run('UPDATE educational_resources SET downloads = downloads + 1 WHERE id = ?', [$id]);
    }

    public function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = slugify($title);
        $slug = $base;
        $i    = 2;
        while (true) {
            $sql    = 'SELECT id FROM educational_resources WHERE slug = ?';
            $params = [$slug];
            if ($ignoreId !== null) {
                $sql     .= ' AND id <> ?';
                $params[] = $ignoreId;
            }
            if ($this->db()->scalar($sql, $params) === null) {
                return $slug;
            }
            $slug = $base . '-' . $i++;
        }
    }
}
