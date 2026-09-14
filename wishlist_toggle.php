<?php
/**
 * LUNORA — wishlist toggle endpoint (JSON).
 * Called via fetch() from script.js whenever a logged-in user clicks a
 * [data-wish] heart button. Guests never hit this — their wishlist stays
 * entirely in localStorage, unchanged.
 */
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/wishlist.php';

header('Content-Type: application/json');

$user = lunora_current_user();
if (!$user) {
    http_response_code(401);
    echo json_encode(['error' => 'not_logged_in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !lunora_csrf_check($_POST['csrf'] ?? null)) {
    http_response_code(400);
    echo json_encode(['error' => 'bad_request']);
    exit;
}

$productId = trim($_POST['product_id'] ?? '');
if ($productId === '') {
    http_response_code(400);
    echo json_encode(['error' => 'missing_product_id']);
    exit;
}

$active = lunora_toggle_wishlist($user['id'], $productId);
echo json_encode(['ok' => true, 'active' => $active]);
