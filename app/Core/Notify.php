<?php
declare(strict_types=1);

namespace App\Core;

/**
 * Creates in-app notifications and optionally mirrors them by email.
 * SMS is stubbed behind the same interface for a future gateway.
 */
final class Notify
{
    public static function user(int $userId, string $title, string $body = '', string $link = '', bool $email = false): void
    {
        Database::instance()->insert('notifications', [
            'user_id' => $userId,
            'channel' => 'system',
            'title'   => mb_substr($title, 0, 200),
            'body'    => mb_substr($body, 0, 1000),
            'link'    => $link !== '' ? mb_substr($link, 0, 255) : null,
            'sent_at' => date('Y-m-d H:i:s'),
        ]);

        if (!$email) {
            return;
        }
        $address = Database::instance()->scalar(
            'SELECT email FROM users WHERE id = ? AND deleted_at IS NULL', [$userId]
        );
        if (is_string($address)) {
            $html = '<p>' . htmlspecialchars($body, ENT_QUOTES, 'UTF-8') . '</p>';
            if ($link !== '') {
                $url  = htmlspecialchars(App::url($link), ENT_QUOTES, 'UTF-8');
                $html .= '<p><a href="' . $url . '" style="background:#0d6efd;color:#fff;padding:10px 18px;'
                       . 'border-radius:8px;text-decoration:none;display:inline-block">Open in HRRAMS</a></p>';
            }
            Mailer::send($address, $title, $html);
        }
    }

    /** Notifies every user holding one of the given roles. */
    public static function role(string $roleSlug, string $title, string $body = '', string $link = ''): void
    {
        $ids = Database::instance()->all(
            'SELECT u.id FROM users u
               JOIN user_roles ur ON ur.user_id = u.id
               JOIN roles r       ON r.id = ur.role_id
              WHERE r.slug = ? AND u.status = "active" AND u.deleted_at IS NULL',
            [$roleSlug]
        );
        foreach ($ids as $row) {
            self::user((int)$row['id'], $title, $body, $link);
        }
    }

    /** Emails an external (possibly unregistered) reporter. */
    public static function reporter(?string $email, string $title, string $body): void
    {
        if ($email === null || $email === '') {
            return;
        }
        Mailer::send($email, $title, '<p>' . htmlspecialchars($body, ENT_QUOTES, 'UTF-8') . '</p>');
    }

    /** @return array<int,array<string,mixed>> */
    public static function unread(int $userId, int $limit = 8): array
    {
        return Database::instance()->all(
            'SELECT id, title, body, link, created_at FROM notifications
              WHERE user_id = ? AND read_at IS NULL AND deleted_at IS NULL
              ORDER BY created_at DESC LIMIT ' . max(1, min(50, $limit)),
            [$userId]
        );
    }

    public static function unreadCount(int $userId): int
    {
        return (int)Database::instance()->scalar(
            'SELECT COUNT(*) FROM notifications WHERE user_id = ? AND read_at IS NULL AND deleted_at IS NULL',
            [$userId]
        );
    }

    public static function markRead(int $userId, ?int $id = null): void
    {
        if ($id === null) {
            Database::instance()->run(
                'UPDATE notifications SET read_at = NOW() WHERE user_id = ? AND read_at IS NULL', [$userId]
            );
            return;
        }
        Database::instance()->run(
            'UPDATE notifications SET read_at = NOW() WHERE id = ? AND user_id = ?', [$id, $userId]
        );
    }
}
