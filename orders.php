<?php
/**
 * LUNORA — order store (PDO / MySQL backed).
 * Orders live across two tables: `orders` (one row per order) and
 * `order_items` (one row per line item) — see schema.sql. The nested shape
 * returned here (customer{}, items[], tracking{}, status_history[]) matches
 * exactly what place_order.php, order_success.php, and the admin panel
 * already expect, so only the storage underneath changed.
 */

require_once __DIR__ . '/db.php';

/** Ordered list of valid order statuses => human label. */
function lunora_order_statuses(): array {
    return [
        'pending'    => 'Pending',
        'processing' => 'Processing',
        'shipped'    => 'Shipped',
        'delivered'  => 'Delivered',
        'cancelled'  => 'Cancelled',
    ];
}

function lunora_next_order_id(): string {
    return 'o_' . strtoupper(bin2hex(random_bytes(4)));
}

/** Reassemble a flat `orders` row + its `order_items` rows into the nested shape the app expects. */
function lunora_order_row(array $row, array $itemRows): array {
    return [
        'id'             => $row['id'],
        'created_at'     => $row['created_at'],
        'user_id'        => $row['user_id'],
        'customer'       => [
            'name'    => $row['customer_name'],
            'email'   => $row['customer_email'],
            'phone'   => $row['customer_phone'],
            'address' => $row['customer_address'],
        ],
        'payment_method' => $row['payment_method'],
        'items'          => array_map(function ($it) {
            return [
                'product_id' => $it['product_id'],
                'name'       => $it['name'],
                'variant'    => $it['variant'],
                'price'      => (float) $it['price'],
                'qty'        => (int) $it['qty'],
                'image'      => $it['image'],
            ];
        }, $itemRows),
        'subtotal'       => (float) $row['subtotal'],
        'shipping'       => (float) $row['shipping'],
        'total'          => (float) $row['total'],
        'status'         => $row['status'],
        'tracking'       => [
            'carrier'         => $row['carrier'],
            'tracking_number' => $row['tracking_number'],
            'eta'             => $row['eta'],
            'notes'           => $row['notes'],
        ],
        'status_history' => $row['status_history'] ? json_decode($row['status_history'], true) : [],
    ];
}

/** All orders, oldest first (callers that want newest-first do array_reverse(), as before). */
function lunora_load_orders(): array {
    $db = lunora_db();
    $orders = $db->query('SELECT * FROM orders ORDER BY created_at ASC')->fetchAll();
    if (!$orders) return [];

    $itemStmt = $db->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
    $result = [];
    foreach ($orders as $o) {
        $itemStmt->execute([$o['id']]);
        $result[] = lunora_order_row($o, $itemStmt->fetchAll());
    }
    return $result;
}

function lunora_get_order(string $id): ?array {
    $db = lunora_db();
    $stmt = $db->prepare('SELECT * FROM orders WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if (!$row) return null;

    $itemStmt = $db->prepare('SELECT * FROM order_items WHERE order_id = ? ORDER BY id ASC');
    $itemStmt->execute([$id]);
    return lunora_order_row($row, $itemStmt->fetchAll());
}

/**
 * Create an order. $data: user_id (?string), customer (array: name, email,
 * phone, address), payment_method (string), items (array of
 * [product_id,name,variant,price,qty,image]). Writes the order row and all
 * its line items in one transaction, so a mid-way failure can't leave a
 * half-saved order behind.
 */
function lunora_create_order(array $data): array {
    $items = $data['items'] ?? [];
    $subtotal = 0.0;
    foreach ($items as $it) {
        $subtotal += (float) $it['price'] * (int) $it['qty'];
    }

    $orderId = lunora_next_order_id();
    $now = date('Y-m-d H:i:s');
    $db = lunora_db();

    $db->beginTransaction();
    try {
        $stmt = $db->prepare(
            "INSERT INTO orders
             (id, user_id, customer_name, customer_email, customer_phone, customer_address,
              payment_method, subtotal, shipping, total, status, carrier, tracking_number,
              eta, notes, status_history, status_seen_at, created_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, 'pending', '', '', '', '', ?, ?, ?)"
        );
        $stmt->execute([
            $orderId,
            $data['user_id'] ?? null,
            trim($data['customer']['name'] ?? ''),
            trim($data['customer']['email'] ?? ''),
            trim($data['customer']['phone'] ?? ''),
            trim($data['customer']['address'] ?? ''),
            $data['payment_method'] ?? 'card',
            round($subtotal, 2),
            round($subtotal, 2),
            json_encode([['status' => 'pending', 'at' => date('c')]]),
            $now, // status_seen_at: the customer just placed this themselves, so "pending" isn't a notification-worthy change.
            $now,
        ]);

        $itemStmt = $db->prepare(
            'INSERT INTO order_items (order_id, product_id, name, variant, price, qty, image)
             VALUES (?, ?, ?, ?, ?, ?, ?)'
        );
        foreach ($items as $it) {
            $itemStmt->execute([
                $orderId,
                $it['product_id'] ?? null,
                $it['name'] ?? '',
                $it['variant'] ?? '',
                (float) ($it['price'] ?? 0),
                (int) ($it['qty'] ?? 1),
                $it['image'] ?? '',
            ]);
        }

        $db->commit();
    } catch (Throwable $e) {
        $db->rollBack();
        throw $e;
    }

    return lunora_get_order($orderId);
}

/** Update status and/or delivery/tracking info for an order. */
function lunora_update_order(string $id, array $data): bool {
    $existing = lunora_get_order($id);
    if (!$existing) return false;

    $status = $existing['status'];
    $statusHistory = $existing['status_history'];
    $statusChanged = false;
    if (!empty($data['status']) && isset(lunora_order_statuses()[$data['status']]) && $data['status'] !== $status) {
        $status = $data['status'];
        $statusHistory[] = ['status' => $status, 'at' => date('c')];
        $statusChanged = true;
    }

    $carrier        = array_key_exists('carrier', $data) ? trim((string) $data['carrier']) : $existing['tracking']['carrier'];
    $trackingNumber = array_key_exists('tracking_number', $data) ? trim((string) $data['tracking_number']) : $existing['tracking']['tracking_number'];
    $eta            = array_key_exists('eta', $data) ? trim((string) $data['eta']) : $existing['tracking']['eta'];
    $notes          = array_key_exists('notes', $data) ? trim((string) $data['notes']) : $existing['tracking']['notes'];

    // A real status change (made by the admin) clears status_seen_at, which
    // is what lights up the notification badge on the customer's account
    // icon until they visit their order history again.
    if ($statusChanged) {
        $stmt = lunora_db()->prepare(
            'UPDATE orders SET status = ?, carrier = ?, tracking_number = ?, eta = ?, notes = ?, status_history = ?, status_seen_at = NULL WHERE id = ?'
        );
    } else {
        $stmt = lunora_db()->prepare(
            'UPDATE orders SET status = ?, carrier = ?, tracking_number = ?, eta = ?, notes = ?, status_history = ? WHERE id = ?'
        );
    }
    $stmt->execute([$status, $carrier, $trackingNumber, $eta, $notes, json_encode($statusHistory), $id]);

    return true;
}

/** How many of this customer's orders have an unseen status change — drives the account-icon badge. */
function lunora_count_unseen_status_changes(string $userId): int {
    $stmt = lunora_db()->prepare('SELECT COUNT(*) FROM orders WHERE user_id = ? AND status_seen_at IS NULL');
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

/** Mark all of this customer's orders as "seen" — call this once they've viewed their order history. */
function lunora_mark_orders_seen(string $userId): void {
    $stmt = lunora_db()->prepare('UPDATE orders SET status_seen_at = NOW() WHERE user_id = ? AND status_seen_at IS NULL');
    $stmt->execute([$userId]);
}

/** Quick aggregate stats for the admin dashboard. */
function lunora_order_stats(): array {
    $db = lunora_db();
    $stats = [
        'total_orders'  => (int) $db->query('SELECT COUNT(*) FROM orders')->fetchColumn(),
        'total_revenue' => (float) $db->query("SELECT COALESCE(SUM(total), 0) FROM orders WHERE status != 'cancelled'")->fetchColumn(),
        'pending'       => 0,
        'processing'    => 0,
        'shipped'       => 0,
        'delivered'     => 0,
        'cancelled'     => 0,
    ];
    $counts = $db->query('SELECT status, COUNT(*) AS c FROM orders GROUP BY status')->fetchAll();
    foreach ($counts as $row) {
        if (isset($stats[$row['status']])) $stats[$row['status']] = (int) $row['c'];
    }
    $stats['total_revenue'] = round($stats['total_revenue'], 2);
    return $stats;
}
