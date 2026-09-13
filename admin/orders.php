<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../orders.php';
lunora_require_admin('login.php');

$admin_user = lunora_current_user();
$active = 'orders';
$statuses = lunora_order_statuses();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_order') {
    $orderId = $_POST['order_id'] ?? '';
    if (!lunora_csrf_check($_POST['csrf'] ?? null)) {
        lunora_flash_set('error', 'Your session expired — please try again.');
    } else {
        $ok = lunora_update_order($orderId, [
            'status'          => $_POST['status'] ?? '',
            'carrier'         => $_POST['carrier'] ?? '',
            'tracking_number' => $_POST['tracking_number'] ?? '',
            'eta'             => $_POST['eta'] ?? '',
            'notes'           => $_POST['notes'] ?? '',
        ]);
        lunora_flash_set($ok ? 'success' : 'error', $ok ? 'Order updated.' : 'Could not find that order.');
    }
    header('Location: orders.php' . ($orderId ? '?id=' . urlencode($orderId) : ''));
    exit;
}

$csrfToken = lunora_csrf_token();
$viewingId = $_GET['id'] ?? '';
$viewOrder = $viewingId ? lunora_get_order($viewingId) : null;

$filter = $_GET['status'] ?? '';
$orders = array_reverse(lunora_load_orders());
if ($filter && isset($statuses[$filter])) {
    $orders = array_values(array_filter($orders, fn($o) => $o['status'] === $filter));
}

$page_title = $viewOrder ? ('Order #' . $viewOrder['id']) : 'Orders & Delivery';
$page_subtitle = $viewOrder ? 'Update status and delivery/tracking details.' : 'Review incoming orders and manage delivery status.';

require __DIR__ . '/includes/layout_top.php';
?>

<?php if ($viewOrder): ?>

  <p style="margin-bottom:16px;"><a class="btn btn-secondary btn-sm" href="orders.php">&larr; Back to all orders</a></p>

  <div class="card">
    <h2>Customer &amp; Items</h2>
    <p style="font-size:0.9rem; color:var(--ink-soft); margin-bottom:14px;">
      <strong style="color:var(--ink);"><?= htmlspecialchars($viewOrder['customer']['name']) ?></strong><br>
      <?= htmlspecialchars($viewOrder['customer']['email']) ?> &middot; <?= htmlspecialchars($viewOrder['customer']['phone']) ?><br>
      <?= htmlspecialchars($viewOrder['customer']['address']) ?><br>
      Payment: <?= htmlspecialchars(ucfirst($viewOrder['payment_method'])) ?> &middot; Placed <?= date('M j, Y g:ia', strtotime($viewOrder['created_at'])) ?>
    </p>
    <div class="table-scroll">
      <table>
        <thead><tr><th>Item</th><th>Variant</th><th>Qty</th><th>Price</th><th>Line Total</th></tr></thead>
        <tbody>
          <?php foreach ($viewOrder['items'] as $it): ?>
            <tr>
              <td><?= htmlspecialchars($it['name']) ?></td>
              <td><?= htmlspecialchars($it['variant']) ?></td>
              <td><?= (int) $it['qty'] ?></td>
              <td>US$<?= number_format($it['price'], 2) ?></td>
              <td>US$<?= number_format($it['price'] * $it['qty'], 2) ?></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <p style="text-align:right; margin-top:12px; font-weight:600;">Total: US$<?= number_format($viewOrder['total'], 2) ?></p>
  </div>

  <div class="card">
    <h2>Status &amp; Delivery</h2>
    <form method="post">
      <input type="hidden" name="action" value="update_order">
      <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">
      <input type="hidden" name="order_id" value="<?= htmlspecialchars($viewOrder['id']) ?>">
      <div class="form-grid">
        <label class="field">Order Status
          <select name="status">
            <?php foreach ($statuses as $key => $label): ?>
              <option value="<?= $key ?>" <?= $viewOrder['status'] === $key ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label class="field">Carrier
          <input type="text" name="carrier" value="<?= htmlspecialchars($viewOrder['tracking']['carrier'] ?? '') ?>" placeholder="e.g. LBC, J&amp;T, DHL">
        </label>
        <label class="field">Tracking Number
          <input type="text" name="tracking_number" value="<?= htmlspecialchars($viewOrder['tracking']['tracking_number'] ?? '') ?>">
        </label>
        <label class="field">Estimated Delivery
          <input type="text" name="eta" value="<?= htmlspecialchars($viewOrder['tracking']['eta'] ?? '') ?>" placeholder="e.g. Sept 15–17, 2026">
        </label>
        <label class="field full">Delivery Notes
          <textarea name="notes" rows="3" placeholder="Internal notes for this delivery"><?= htmlspecialchars($viewOrder['tracking']['notes'] ?? '') ?></textarea>
        </label>
      </div>
      <div style="margin-top:16px;"><button type="submit" class="btn">Save Changes</button></div>
    </form>
  </div>

  <?php if (!empty($viewOrder['status_history'])): ?>
  <div class="card">
    <h2>Status History</h2>
    <div class="table-scroll">
      <table>
        <thead><tr><th>Status</th><th>Date</th></tr></thead>
        <tbody>
          <?php foreach (array_reverse($viewOrder['status_history']) as $h): ?>
            <tr><td><span class="pill pill-<?= htmlspecialchars($h['status']) ?>"><?= htmlspecialchars($statuses[$h['status']] ?? $h['status']) ?></span></td><td><?= date('M j, Y g:ia', strtotime($h['at'])) ?></td></tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
  <?php endif; ?>

<?php else: ?>

  <div class="card">
    <div class="card-header">
      <h2>All Orders</h2>
      <div class="btn-row">
        <a class="btn btn-sm <?= $filter === '' ? '' : 'btn-secondary' ?>" href="orders.php">All</a>
        <?php foreach ($statuses as $key => $label): ?>
          <a class="btn btn-sm <?= $filter === $key ? '' : 'btn-secondary' ?>" href="orders.php?status=<?= $key ?>"><?= htmlspecialchars($label) ?></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (!$orders): ?>
      <div class="empty-state">No orders match this filter yet.</div>
    <?php else: ?>
    <div class="table-scroll">
      <table>
        <thead><tr><th>Order</th><th>Customer</th><th>Items</th><th>Total</th><th>Payment</th><th>Status</th><th>Placed</th><th></th></tr></thead>
        <tbody>
          <?php foreach ($orders as $o): ?>
            <tr>
              <td>#<?= htmlspecialchars($o['id']) ?></td>
              <td><?= htmlspecialchars($o['customer']['name']) ?><br><span style="color:var(--ink-soft); font-size:0.78rem;"><?= htmlspecialchars($o['customer']['email']) ?></span></td>
              <td><?= array_sum(array_column($o['items'], 'qty')) ?></td>
              <td>US$<?= number_format($o['total'], 2) ?></td>
              <td><?= htmlspecialchars(ucfirst($o['payment_method'])) ?></td>
              <td><span class="pill pill-<?= htmlspecialchars($o['status']) ?>"><?= htmlspecialchars($statuses[$o['status']] ?? $o['status']) ?></span></td>
              <td><?= date('M j, Y', strtotime($o['created_at'])) ?></td>
              <td><a class="btn btn-secondary btn-sm" href="orders.php?id=<?= urlencode($o['id']) ?>">Manage</a></td>
            </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>

<?php endif; ?>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
