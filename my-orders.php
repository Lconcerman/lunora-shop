<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/orders.php';

$lunora_user = lunora_current_user();
$lunora_flash = lunora_flash_get();

$myOrders = [];
if ($lunora_user) {
    $all = lunora_load_orders();
    foreach (array_reverse($all) as $order) {
        if (($order['user_id'] ?? null) === $lunora_user['id']) {
            $myOrders[] = $order;
        }
    }
}

$statusLabels = lunora_order_statuses();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Check Order Status — LUNORA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<?php include __DIR__ . '/includes/site_header.php'; ?>

<div class="breadcrumb">
  <div class="breadcrumb-inner"><a href="index.php">Home</a><span>/</span><span>Check Order Status</span></div>
</div>

<main class="info-page">
  <div class="info-page__inner" style="max-width:820px;">
    <h1>Check Order Status</h1>

    <?php if (!$lunora_user): ?>
      <div class="orders-empty">
        <p>Sign in to view your order history and tracking details.</p>
        <p><a href="login.php">Log In</a> or <a href="register.php">create an account →</a></p>
      </div>
    <?php elseif (empty($myOrders)): ?>
      <div class="orders-empty">
        <p>You haven't placed any orders yet.</p>
        <p><a href="index.php">Start shopping →</a></p>
      </div>
    <?php else: ?>
      <div class="orders-list">
        <?php foreach ($myOrders as $order): ?>
          <?php
            $status = $order['status'];
            $label = $statusLabels[$status] ?? ucfirst($status);
            $itemCount = array_sum(array_column($order['items'], 'qty'));
          ?>
          <div class="order-card">
            <div class="order-card__head">
              <div>
                <div class="order-card__id">Order #<?= htmlspecialchars($order['id']) ?></div>
                <div class="order-card__date"><?= htmlspecialchars(date('F j, Y', strtotime($order['created_at']))) ?></div>
              </div>
              <span class="order-status order-status--<?= htmlspecialchars($status) ?>"><?= htmlspecialchars($label) ?></span>
            </div>
            <p class="order-card__items"><?= (int) $itemCount ?> item<?= $itemCount == 1 ? '' : 's' ?> — <?= htmlspecialchars(implode(', ', array_column($order['items'], 'name'))) ?></p>
            <div class="order-card__total">Total: US$<?= number_format($order['total'], 2) ?></div>
            <?php if (!empty($order['tracking']['tracking_number'])): ?>
              <div class="order-card__tracking">
                <?= htmlspecialchars($order['tracking']['carrier'] ?: 'Carrier') ?> — Tracking #<?= htmlspecialchars($order['tracking']['tracking_number']) ?>
                <?php if (!empty($order['tracking']['eta'])): ?> · ETA <?= htmlspecialchars($order['tracking']['eta']) ?><?php endif; ?>
              </div>
            <?php endif; ?>
          </div>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>
  </div>
</main>

<?php include __DIR__ . '/includes/site_footer.php'; ?>

<script src="script.js"></script>
</body>
</html>
