<?php
/**
 * LUNORA — product review store (PDO / MySQL backed).
 * Reviews live in the `reviews` table (see schema.sql): one row per
 * (user, product) pair, always tied back to a real order — a customer
 * can only review something they actually bought, and only once per
 * product. All access is via prepared statements.
 */

require_once __DIR__ . '/db.php';

function lunora_next_review_id(): string {
    return 'r_' . bin2hex(random_bytes(8));
}

/**
 * Average rating + review count for every product that has at least one
 * review, keyed by product_id. Products with no reviews simply won't
 * appear in the returned array — callers should treat a missing key as
 * "no ratings yet".
 */
function lunora_get_rating_summaries(): array {
    $stmt = lunora_db()->query(
        'SELECT product_id, AVG(rating) AS avg_rating, COUNT(*) AS review_count
         FROM reviews GROUP BY product_id'
    );
    $summaries = [];
    foreach ($stmt->fetchAll() as $row) {
        $summaries[$row['product_id']] = [
            'avg'   => round((float) $row['avg_rating'], 1),
            'count' => (int) $row['review_count'],
        ];
    }
    return $summaries;
}

/** Rating summary for a single product. Returns avg 0 / count 0 if none yet. */
function lunora_get_rating_summary(string $productId): array {
    $stmt = lunora_db()->prepare(
        'SELECT AVG(rating) AS avg_rating, COUNT(*) AS review_count FROM reviews WHERE product_id = ?'
    );
    $stmt->execute([$productId]);
    $row = $stmt->fetch();
    return [
        'avg'   => $row && $row['review_count'] > 0 ? round((float) $row['avg_rating'], 1) : 0.0,
        'count' => $row ? (int) $row['review_count'] : 0,
    ];
}

/** All reviews for a product, newest first, with the reviewer's display name. */
function lunora_get_reviews_for_product(string $productId): array {
    $stmt = lunora_db()->prepare(
        'SELECT r.id, r.rating, r.comment, r.created_at, u.full_name
         FROM reviews r JOIN users u ON u.id = r.user_id
         WHERE r.product_id = ? ORDER BY r.created_at DESC'
    );
    $stmt->execute([$productId]);
    return $stmt->fetchAll();
}

/**
 * Every review in the store, newest first, grouped by product_id. Used to
 * hand the storefront one payload it can show inside each product's
 * quick-add modal, instead of one query per product.
 */
function lunora_get_reviews_grouped_by_product(): array {
    $stmt = lunora_db()->query(
        'SELECT r.product_id, r.rating, r.comment, r.created_at, u.full_name
         FROM reviews r JOIN users u ON u.id = r.user_id
         ORDER BY r.created_at DESC'
    );
    $grouped = [];
    foreach ($stmt->fetchAll() as $row) {
        $grouped[$row['product_id']][] = [
            'name'    => $row['full_name'],
            'rating'  => (int) $row['rating'],
            'comment' => $row['comment'],
            'date'    => date('M j, Y', strtotime($row['created_at'])),
        ];
    }
    return $grouped;
}

/** True if this user has already reviewed this product (one review per product, ever). */
function lunora_user_has_reviewed(string $userId, string $productId): bool {
    $stmt = lunora_db()->prepare('SELECT 1 FROM reviews WHERE user_id = ? AND product_id = ? LIMIT 1');
    $stmt->execute([$userId, $productId]);
    return (bool) $stmt->fetch();
}

/**
 * True if $orderId really belongs to $userId, actually contains
 * $productId, and isn't a cancelled order. This is the server-side check
 * that stops someone from reviewing a product they never bought by simply
 * editing the product_id in a submitted form.
 */
function lunora_order_contains_product_for_user(string $userId, string $orderId, string $productId): bool {
    $stmt = lunora_db()->prepare(
        "SELECT 1 FROM orders o
         JOIN order_items oi ON oi.order_id = o.id
         WHERE o.id = ? AND o.user_id = ? AND oi.product_id = ? AND o.status != 'cancelled'
         LIMIT 1"
    );
    $stmt->execute([$orderId, $userId, $productId]);
    return (bool) $stmt->fetch();
}

/**
 * Every (order, product) pair this user has purchased that they haven't
 * reviewed yet — used to show "Rate this item" prompts on their order
 * history page. Cancelled orders are excluded.
 */
function lunora_get_reviewable_items(string $userId): array {
    $stmt = lunora_db()->prepare(
        "SELECT DISTINCT oi.order_id, oi.product_id, oi.name, oi.variant, oi.image
         FROM order_items oi
         JOIN orders o ON o.id = oi.order_id
         WHERE o.user_id = ? AND o.status != 'cancelled' AND oi.product_id IS NOT NULL
         AND NOT EXISTS (
             SELECT 1 FROM reviews r WHERE r.user_id = o.user_id AND r.product_id = oi.product_id
         )
         ORDER BY oi.id DESC"
    );
    $stmt->execute([$userId]);
    return $stmt->fetchAll();
}

/**
 * Create a review. Caller must already have verified (via
 * lunora_order_contains_product_for_user and lunora_user_has_reviewed)
 * that this is legitimate — this function just performs the insert, and
 * still relies on the DB's UNIQUE constraint as a last line of defense
 * against a duplicate slipping through (e.g. a double form submit).
 * Returns true on success, false if a review already exists.
 */
function lunora_create_review(string $userId, string $productId, string $orderId, int $rating, string $comment): bool {
    $stmt = lunora_db()->prepare(
        'INSERT INTO reviews (id, product_id, user_id, order_id, rating, comment, created_at)
         VALUES (:id, :product_id, :user_id, :order_id, :rating, :comment, :created_at)'
    );
    try {
        $stmt->execute([
            'id'         => lunora_next_review_id(),
            'product_id' => $productId,
            'user_id'    => $userId,
            'order_id'   => $orderId,
            'rating'     => max(1, min(5, $rating)),
            'comment'    => $comment !== '' ? $comment : null,
            'created_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    } catch (PDOException $e) {
        // Unique key violation (23000) — they already reviewed this product.
        if ((string) $e->getCode() === '23000') {
            return false;
        }
        throw $e;
    }
}
