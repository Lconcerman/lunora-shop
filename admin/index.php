<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../products.php';
require_once __DIR__ . '/../orders.php';
lunora_require_admin('login.php');

$admin_user = lunora_current_user();
$active = 'dashboard';
$page_title = 'Dashboard';
$page_subtitle = 'A quick look at orders, delivery, and stock levels.';

$stats = lunora_order_stats();
$orders = array_reverse(lunora_load_orders()); // newest first
$recentOrders = array_slice($orders, 0, 6);

$products = lunora_load_products();
$lowStock = array_values(array_filter($products, fn($p) => (int)$p['stock'] > 0 && (int)$p['stock'] <= 5));
$outOfStock = array_values(array_filter($products, fn($p) => (int)$p['stock'] <= 0));

$statuses = lunora_order_statuses();

require __DIR__ . '/includes/layout_top.php';
?>

<div class="stat-grid">
  <div class="stat-card accent">
    <div class="stat-label">Total Orders</div>
    <div class="stat-value"><?= (int) $stats['total_orders'] ?></div>
  </div>
  <div class="stat-card accent">
    <div class="stat-label">Revenue</div>
    <div class="stat-value">US$<?= number_format($stats['total_revenue'], 2) ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Pending</div>
    <div class="stat-value"><?= (int) $stats['pending'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Processing</div>
    <div class="stat-value"><?= (int) $stats['processing'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Shipped</div>
    <div class="stat-value"><?= (int) $stats['shipped'] ?></div>
  </div>
  <div class="stat-card">
    <div class="stat-label">Delivered</div>
    <div class="stat-value"><?= (int) $stats['delivered'] ?></div>
  </div>
</div>

<div class="card">
  <div class="card-header">
    <h2>Recent Orders</h2>
    <a class="btn btn-secondary btn-sm" href="orders.php">View all orders</a>
  </div>
  <?php if (!$recentOrders): ?>
    <div class="empty-state">No orders yet — once a customer checks out, it'll show up here.</div>
  <?php else: ?>
  <div class="table-scroll">
    <table>
      <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Status</th><th>Placed</th></tr></thead>
      <tbody>
        <?php foreach ($recentOrders as $o): ?>
          <tr>
            <td><a href="orders.php?id=<?= urlencode($o['id']) ?>">#<?= htmlspecialchars($o['id']) ?></a></td>
            <td><?= htmlspecialchars($o['customer']['name']) ?></td>
            <td><?= array_sum(array_column($o['items'], 'qty')) ?> item(s)</td>
            <td>US$<?= number_format($o['total'], 2) ?></td>
            <td><span class="pill pill-<?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars($statuses[$o['status']] ?? $o['status']) ?></span></td>
            <td><?= date('M j, Y g:ia', strtotime($o['created_at'])) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<div class="card">
  <div class="card-header">
    <h2>Stock Alerts</h2>
    <a class="btn btn-secondary btn-sm" href="products.php">Manage products</a>
  </div>
  <?php if (!$lowStock && !$outOfStock): ?>
    <div class="empty-state">Stock levels look healthy — nothing low or out of stock.</div>
  <?php else: ?>
  <div class="table-scroll">
    <table>
      <thead><tr><th>Product</th><th>Variant</th><th>Stock</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach (array_merge($outOfStock, $lowStock) as $p): ?>
          <tr>
            <td><?= htmlspecialchars($p['name']) ?></td>
            <td><?= htmlspecialchars($p['variant']) ?></td>
            <td><?= (int) $p['stock'] ?></td>
            <td>
              <?php if ((int)$p['stock'] <= 0): ?>
                <span class="pill pill-outstock">Out of stock</span>
              <?php else: ?>
                <span class="pill pill-lowstock">Low stock</span>
              <?php endif; ?>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
