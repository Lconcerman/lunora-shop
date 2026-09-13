<?php
/**
 * LUNORA — one-time migration: copies the existing data/*.json files
 * (users, products, orders) into the new MySQL tables from schema.sql.
 *
 * Safe to re-run: rows are matched by their existing id, so running this
 * twice won't create duplicates.
 *
 * Usage:
 *   php migrate_json_to_db.php
 * or open this file once in the browser, then delete it — it's not
 * meant to stay on a live site.
 */

require_once __DIR__ . '/db.php';

function migrate_read_json(string $path): array {
    if (!file_exists($path)) return [];
    $data = json_decode(file_get_contents($path), true);
    return is_array($data) ? $data : [];
}

function migrate_datetime(?string $iso): string {
    $ts = $iso ? strtotime($iso) : false;
    return date('Y-m-d H:i:s', $ts !== false ? $ts : time());
}

$db = lunora_db();
$dataDir = __DIR__ . '/data';
$imported = ['users' => 0, 'products' => 0, 'orders' => 0, 'order_items' => 0];

// ---------------- Users ----------------
$userStmt = $db->prepare(
    'INSERT IGNORE INTO users (id, full_name, email, password_hash, role, created_at)
     VALUES (:id, :full_name, :email, :password_hash, :role, :created_at)'
);
foreach (migrate_read_json($dataDir . '/users.json') as $u) {
    $userStmt->execute([
        'id'            => $u['id'] ?? ('u_' . bin2hex(random_bytes(10))),
        'full_name'     => $u['full_name'] ?? '',
        'email'         => strtolower(trim($u['email'] ?? '')),
        'password_hash' => $u['password_hash'] ?? '',
        'role'          => $u['role'] ?? 'customer',
        'created_at'    => migrate_datetime($u['created_at'] ?? null),
    ]);
    if ($userStmt->rowCount() > 0) $imported['users']++;
}

// ---------------- Products ----------------
$productStmt = $db->prepare(
    'INSERT IGNORE INTO products (id, name, variant, price, stock, image, fill, tones, badge, section, category, created_at, updated_at)
     VALUES (:id, :name, :variant, :price, :stock, :image, :fill, :tones, :badge, :section, :category, :created_at, :updated_at)'
);
foreach (migrate_read_json($dataDir . '/products.json') as $p) {
    $productStmt->execute([
        'id'         => $p['id'] ?? ('p_' . bin2hex(random_bytes(6))),
        'name'       => $p['name'] ?? '',
        'variant'    => $p['variant'] ?? '',
        'price'      => (float) ($p['price'] ?? 0),
        'stock'      => (int) ($p['stock'] ?? 0),
        'image'      => $p['image'] ?? '',
        'fill'       => $p['fill'] ?? '#ECE5D6',
        'tones'      => implode(',', array_filter((array) ($p['tones'] ?? []))),
        'badge'      => $p['badge'] ?? '',
        'section'    => in_array($p['section'] ?? 'grid', ['grid', 'bestseller'], true) ? $p['section'] : 'grid',
        'category'   => $p['category'] ?? '',
        'created_at' => migrate_datetime($p['created_at'] ?? null),
        'updated_at' => migrate_datetime($p['updated_at'] ?? null),
    ]);
    if ($productStmt->rowCount() > 0) $imported['products']++;
}

// ---------------- Orders + line items ----------------
$orderStmt = $db->prepare(
    'INSERT IGNORE INTO orders
     (id, user_id, customer_name, customer_email, customer_phone, customer_address,
      payment_method, subtotal, shipping, total, status, carrier, tracking_number, eta, notes, status_history, created_at)
     VALUES (:id, :user_id, :customer_name, :customer_email, :customer_phone, :customer_address,
      :payment_method, :subtotal, :shipping, :total, :status, :carrier, :tracking_number, :eta, :notes, :status_history, :created_at)'
);
$itemStmt = $db->prepare(
    'INSERT INTO order_items (order_id, product_id, name, variant, price, qty, image)
     VALUES (?, ?, ?, ?, ?, ?, ?)'
);
foreach (migrate_read_json($dataDir . '/orders.json') as $o) {
    $orderId = $o['id'] ?? ('o_' . strtoupper(bin2hex(random_bytes(4))));

    $orderStmt->execute([
        'id'               => $orderId,
        'user_id'          => $o['user_id'] ?? null,
        'customer_name'    => $o['customer']['name'] ?? '',
        'customer_email'   => $o['customer']['email'] ?? '',
        'customer_phone'   => $o['customer']['phone'] ?? '',
        'customer_address' => $o['customer']['address'] ?? '',
        'payment_method'   => $o['payment_method'] ?? 'card',
        'subtotal'         => (float) ($o['subtotal'] ?? 0),
        'shipping'         => (float) ($o['shipping'] ?? 0),
        'total'            => (float) ($o['total'] ?? 0),
        'status'           => $o['status'] ?? 'pending',
        'carrier'          => $o['tracking']['carrier'] ?? '',
        'tracking_number'  => $o['tracking']['tracking_number'] ?? '',
        'eta'              => $o['tracking']['eta'] ?? '',
        'notes'            => $o['tracking']['notes'] ?? '',
        'status_history'   => json_encode($o['status_history'] ?? []),
        'created_at'       => migrate_datetime($o['created_at'] ?? null),
    ]);

    // Only insert this order's items the first time the order itself is inserted,
    // so re-running the script never creates duplicate line items.
    if ($orderStmt->rowCount() > 0) {
        $imported['orders']++;
        foreach ($o['items'] ?? [] as $it) {
            $itemStmt->execute([
                $orderId,
                $it['product_id'] ?? null,
                $it['name'] ?? '',
                $it['variant'] ?? '',
                (float) ($it['price'] ?? 0),
                (int) ($it['qty'] ?? 1),
                $it['image'] ?? '',
            ]);
            $imported['order_items']++;
        }
    }
}

header('Content-Type: text/plain');
echo "Migration complete.\n\n";
foreach ($imported as $label => $count) {
    echo str_pad($label, 14) . ": {$count} imported\n";
}
echo "\nIf all the numbers look right, you can delete migrate_json_to_db.php\n";
echo "and the data/*.json files — the app no longer reads them.\n";
