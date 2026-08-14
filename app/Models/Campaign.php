<?php
declare(strict_types=1);

namespace App\Models;

final class Campaign extends Model
{
    protected string $table = 'awareness_campaigns';

    public const TYPES = ['campaign','news','article','success_story','newsletter'];

    public function published(?string $type = null, int $limit = 0): array
    {
        $sql    = 'SELECT * FROM awareness_campaigns
                    WHERE status = "published" AND deleted_at IS NULL';
        $params = [];
        if ($type !== null && in_array($type, self::TYPES, true)) {
            $sql        .= ' AND type = :type';
            $params['type'] = $type;
        }
        $sql .= ' ORDER BY COALESCE(published_at, created_at) DESC';
        if ($limit > 0) {
            $sql .= ' LIMIT ' . max(1, min(50, $limit));
        }
        return $this->db()->all($sql, $params);
    }

    public function findBySlug(string $slug, bool $publishedOnly = true): ?array
    {
        $sql = 'SELECT c.*, CONCAT(u.first_name," ",u.last_name) AS author
                  FROM awareness_campaigns c
                  LEFT JOIN users u ON u.id = c.created_by
                 WHERE c.slug = ? AND c.deleted_at IS NULL';
        if ($publishedOnly) {
            $sql .= ' AND c.status = "published"';
        }
        return $this->db()->one($sql, [$slug]);
    }

    public function registerView(int $id): void
    {
        $this->db()->run('UPDATE awareness_campaigns SET views = views + 1 WHERE id = ?', [$id]);
    }

    public function uniqueSlug(string $title, ?int $ignoreId = null): string
    {
        $base = slugify($title);
        $slug = $base;
        $i    = 2;
        while (true) {
            $sql    = 'SELECT id FROM awareness_campaigns WHERE slug = ?';
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

    public function analytics(): array
    {
        return $this->db()->all(
            'SELECT type, COUNT(*) AS items, SUM(views) AS views,
                    SUM(status = "published") AS published, SUM(status = "draft") AS drafts
               FROM awareness_campaigns WHERE deleted_at IS NULL GROUP BY type'
        );
    }

    public function topViewed(int $limit = 5): array
    {
        return $this->db()->all(
            'SELECT title, type, views FROM awareness_campaigns
              WHERE deleted_at IS NULL AND status = "published"
              ORDER BY views DESC LIMIT ' . max(1, min(20, $limit))
        );
    }

    public function search(array $f, int $limit, int $offset): array
    {
        [$where, $params] = $this->filters($f);
        return $this->db()->all(
            "SELECT c.*, CONCAT(u.first_name,' ',u.last_name) AS author
               FROM awareness_campaigns c LEFT JOIN users u ON u.id = c.created_by
              WHERE $where ORDER BY c.created_at DESC LIMIT $limit OFFSET $offset",
            $params
        );
    }

    public function searchCount(array $f): int
    {
        [$where, $params] = $this->filters($f);
        return (int)$this->db()->scalar("SELECT COUNT(*) FROM awareness_campaigns c WHERE $where", $params);
    }

    private function filters(array $f): array
    {
        $where  = ['c.deleted_at IS NULL'];
        $params = [];
        if (!empty($f['q'])) {
            $where[]     = '(c.title LIKE :q OR c.summary LIKE :q)';
            $params['q'] = '%' . $f['q'] . '%';
        }
        if (!empty($f['type']) && in_array($f['type'], self::TYPES, true)) {
            $where[]        = 'c.type = :type';
            $params['type'] = $f['type'];
        }
        if (!empty($f['status'])) {
            $where[]          = 'c.status = :status';
            $params['status'] = $f['status'];
        }
        return [implode(' AND ', $where), $params];
    }
}
