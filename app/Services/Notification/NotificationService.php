<?php

declare(strict_types=1);

namespace App\Services\Notification;

use App\Core\Database\Connection;

/**
 * Panel içi bildirim (dashboard'daki 🔔 bell).
 * Ayrıca mail/SMS bildirimleri Mailer'a gönderilir.
 */
final class NotificationService
{
    public static function push(string $userType, int $userId, string $type, string $title, ?string $body = null, ?string $url = null, string $icon = '🔔'): int
    {
        return Connection::insert('notifications', [
            'user_type' => $userType,
            'user_id'   => $userId,
            'type'      => $type,
            'title'     => $title,
            'body'      => $body,
            'url'       => $url,
            'icon'      => $icon,
            'is_read'   => 0,
        ]);
    }

    public static function forUser(string $userType, int $userId, int $limit = 20): array
    {
        try {
            return Connection::select(
                "SELECT * FROM notifications
                 WHERE user_type = ? AND user_id = ?
                 ORDER BY created_at DESC LIMIT " . (int)$limit,
                [$userType, $userId]
            );
        } catch (\Throwable) { return []; }
    }

    public static function unreadCount(string $userType, int $userId): int
    {
        try {
            $row = Connection::selectOne(
                "SELECT COUNT(*) c FROM notifications WHERE user_type = ? AND user_id = ? AND is_read = 0",
                [$userType, $userId]
            );
            return (int)($row['c'] ?? 0);
        } catch (\Throwable) { return 0; }
    }

    public static function markRead(int $id, string $userType, int $userId): void
    {
        Connection::query(
            "UPDATE notifications SET is_read = 1 WHERE id = ? AND user_type = ? AND user_id = ?",
            [$id, $userType, $userId]
        );
    }

    public static function markAllRead(string $userType, int $userId): void
    {
        Connection::query(
            "UPDATE notifications SET is_read = 1 WHERE user_type = ? AND user_id = ? AND is_read = 0",
            [$userType, $userId]
        );
    }
}
