<?php
/**
 * LUNORA — notifications store (PDO / MySQL backed).
 * General-purpose per-user notifications. orders.php calls
 * lunora_create_notification() automatically whenever an order's status
 * changes; other features can create notifications the same way.
 */

require_once __DIR__ . '/db.php';

function lunora_create_notification(string $userId, string $type, string $title, string $message = '', string $link = ''): void {
    $stmt = lunora_db()->prepare(
        'INSERT INTO notifications (user_id, type, title, message, link, is_read, created_at)
         VALUES (?, ?, ?, ?, ?, 0, ?)'
    );
    $stmt->execute([$userId, $type, $title, $message, $link, date('Y-m-d H:i:s')]);
}

function lunora_get_notifications(string $userId, int $limit = 50): array {
    $stmt = lunora_db()->prepare('SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?');
    $stmt->bindValue(1, $userId);
    $stmt->bindValue(2, $limit, PDO::PARAM_INT);
    $stmt->execute();
    return $stmt->fetchAll();
}

function lunora_count_unread_notifications(string $userId): int {
    $stmt = lunora_db()->prepare('SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function lunora_mark_notifications_read(string $userId): void {
    $stmt = lunora_db()->prepare('UPDATE notifications SET is_read = 1 WHERE user_id = ? AND is_read = 0');
    $stmt->execute([$userId]);
}
