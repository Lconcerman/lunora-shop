<?php
/**
 * LUNORA — wishlist store (PDO / MySQL backed).
 * Backs the account-level Wishlist page (my-wishlist.php) and, for logged-in
 * users, the heart buttons on the product grid (index.php / script.js).
 * Guests keep using the existing localStorage-only wishlist — nothing about
 * that path changes.
 */

require_once __DIR__ . '/db.php';

function lunora_is_wishlisted(string $userId, string $productId): bool {
    $stmt = lunora_db()->prepare('SELECT 1 FROM wishlist_items WHERE user_id = ? AND product_id = ? LIMIT 1');
    $stmt->execute([$userId, $productId]);
    return (bool) $stmt->fetchColumn();
}

/** Plain array of product_id strings this user has wishlisted (fast lookup for rendering button state). */
function lunora_wishlist_product_ids(string $userId): array {
    $stmt = lunora_db()->prepare('SELECT product_id FROM wishlist_items WHERE user_id = ?');
    $stmt->execute([$userId]);
    return $stmt->fetchAll(PDO::FETCH_COLUMN);
}

/** Add or remove a product from the user's wishlist. Returns true if it's now wishlisted, false if it was removed. */
function lunora_toggle_wishlist(string $userId, string $productId): bool {
    if (lunora_is_wishlisted($userId, $productId)) {
        $stmt = lunora_db()->prepare('DELETE FROM wishlist_items WHERE user_id = ? AND product_id = ?');
        $stmt->execute([$userId, $productId]);
        return false;
    }
    $stmt = lunora_db()->prepare(
        'INSERT IGNORE INTO wishlist_items (user_id, product_id, created_at) VALUES (?, ?, ?)'
    );
    $stmt->execute([$userId, $productId, date('Y-m-d H:i:s')]);
    return true;
}

/** Full wishlist, joined with live product data, newest-added first. */
function lunora_get_wishlist(string $userId): array {
    $stmt = lunora_db()->prepare(
        'SELECT w.product_id, w.created_at, p.name, p.variant, p.price, p.image, p.stock
         FROM wishlist_items w
         JOIN products p ON p.id = w.product_id
         WHERE w.user_id = ?
         ORDER BY w.created_at DESC'
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

function lunora_remove_from_wishlist(string $userId, string $productId): void {
    $stmt = lunora_db()->prepare('DELETE FROM wishlist_items WHERE user_id = ? AND product_id = ?');
    $stmt->execute([$userId, $productId]);
}
