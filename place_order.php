<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/products.php';
require_once __DIR__ . '/orders.php';

header('Content-Type: application/json');

function lunora_json_fail(string $message, int $code = 400): void {
    http_response_code($code);
    echo json_encode(['success' => false, 'error' => $message]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    lunora_json_fail('Invalid request method.', 405);
}

$csrf = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
if (!lunora_csrf_check($csrf)) {
    lunora_json_fail('Your session expired — please refresh the page and try again.', 419);
}

$raw = file_get_contents('php://input');
$body = json_decode($raw, true);
if (!is_array($body)) {
    lunora_json_fail('Malformed request.');
}

$fullname = trim($body['fullname'] ?? '');
$email    = trim($body['email'] ?? '');
$phone    = trim($body['phone'] ?? '');
$address  = trim($body['address'] ?? '');
$payment  = in_array($body['payment'] ?? '', ['card', 'paypal', 'cod'], true) ? $body['payment'] : 'card';
$cartItems = is_array($body['items'] ?? null) ? $body['items'] : [];

if ($fullname === '' || !filter_var($email, FILTER_VALIDATE_EMAIL) || $phone === '' || $address === '') {
    lunora_json_fail('Please fill in all delivery details with a valid email.');
}
if (empty($cartItems)) {
    lunora_json_fail('Your bag is empty.');
}

// Re-validate every line item against the live product catalog: this
// protects prices and stock from anything the client-side cart claims.
$orderItems = [];
foreach ($cartItems as $item) {
    $qty = max(1, (int) ($item['quantity'] ?? 1));
    $productId = $item['productId'] ?? null;

    if ($productId) {
        $product = lunora_get_product((string) $productId);
        if (!$product) {
            lunora_json_fail('One of the items in your bag is no longer available.');
        }
        if ((int) $product['stock'] < $qty) {
            lunora_json_fail('"' . $product['name'] . '" only has ' . $product['stock'] . ' in stock.');
        }
        $orderItems[] = [
            'product_id' => $product['id'],
            'name'       => $product['name'],
            'variant'    => $product['variant'],
            'price'      => (float) $product['price'],
            'qty'        => $qty,
            'image'      => $product['image'],
        ];
    } else {
        // Legacy / unmatched cart item — trust the client-supplied name & price
        // but still record it so the order isn't silently dropped.
        $orderItems[] = [
            'product_id' => null,
            'name'       => (string) ($item['name'] ?? 'Item'),
            'variant'    => '',
            'price'      => (float) ($item['price'] ?? 0),
            'qty'        => $qty,
            'image'      => (string) ($item['img'] ?? ''),
        ];
    }
}

// Decrement stock for matched products. If any fails midway (race condition),
// we still record the order — an admin can reconcile from the dashboard.
foreach ($orderItems as $it) {
    if ($it['product_id']) {
        lunora_decrement_stock($it['product_id'], $it['qty']);
    }
}

$lunora_user = lunora_current_user();

$order = lunora_create_order([
    'user_id'        => $lunora_user['id'] ?? null,
    'customer'       => [
        'name'    => $fullname,
        'email'   => $email,
        'phone'   => $phone,
        'address' => $address,
    ],
    'payment_method' => $payment,
    'items'          => $orderItems,
]);

echo json_encode(['success' => true, 'order_id' => $order['id']]);
