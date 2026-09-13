<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/orders.php';

$lunora_user = lunora_current_user();
$orderId = $_GET['id'] ?? '';
$order = $orderId ? lunora_get_order($orderId) : null;
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Order Confirmed — LUNORA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<header class="site-header">
  <div class="header-inner header-inner--auth">
    <span></span>
    <a href="index.php" class="wordmark">LUNORA</a>
    <span></span>
  </div>
</header>

<main style="max-width:640px; margin:0 auto; padding:64px 20px 100px;">
  <?php if ($order): ?>
    <div style="text-align:center; margin-bottom:36px;">
      <div style="width:56px;height:56px;border-radius:50%;background:var(--ink);color:var(--paper);display:flex;align-items:center;justify-content:center;margin:0 auto 20px;font-family:var(--font-display);font-size:1.4rem;">&#10003;</div>
      <h1 style="font-size:1.7rem;">Thank you, <?= htmlspecialchars(explode(' ', $order['customer']['name'])[0]) ?>.</h1>
      <p style="color:var(--ink-soft); margin-top:8px;">Your order has been placed. A confirmation has been noted for <?= htmlspecialchars($order['customer']['email']) ?>.</p>
    </div>

    <div style="border:1px solid var(--stone-line); border-radius:4px; padding:24px;">
      <div style="display:flex; justify-content:space-between; font-size:0.85rem; color:var(--ink-soft); margin-bottom:16px;">
        <span>Order <strong style="color:var(--ink);">#<?= htmlspecialchars($order['id']) ?></strong></span>
        <span><?= htmlspecialchars(ucfirst($order['status'])) ?></span>
      </div>
      <?php foreach ($order['items'] as $it): ?>
        <div style="display:flex; justify-content:space-between; padding:10px 0; border-top:1px solid var(--stone-line); font-size:0.9rem;">
          <span><?= htmlspecialchars($it['name']) ?><?= $it['variant'] ? ' &ndash; ' . htmlspecialchars($it['variant']) : '' ?> &times; <?= (int)$it['qty'] ?></span>
          <span>US$<?= number_format($it['price'] * $it['qty'], 2) ?></span>
        </div>
      <?php endforeach; ?>
      <div style="display:flex; justify-content:space-between; padding-top:16px; margin-top:6px; border-top:1px solid var(--stone-line); font-weight:600;">
        <span>Total</span>
        <span>US$<?= number_format($order['total'], 2) ?></span>
      </div>
    </div>

    <div style="text-align:center; margin-top:32px;">
      <a href="index.php" class="auth-submit" style="display:inline-block; width:auto; padding:12px 28px; text-decoration:none;">Continue Shopping</a>
    </div>
  <?php else: ?>
    <div style="text-align:center;">
      <h1>Order not found</h1>
      <p style="color:var(--ink-soft); margin-top:8px;">We couldn't find that order. If you just checked out, please check your email, or contact support.</p>
      <a href="index.php" class="auth-submit" style="display:inline-block; width:auto; margin-top:20px; padding:12px 28px; text-decoration:none;">Back to Shop</a>
    </div>
  <?php endif; ?>
</main>

<footer class="site-footer">
  <div class="footer-base" style="justify-content:center; border-top:none;">
    <span class="wordmark--footer">LUNORA</span>
    <span>&copy; <?= date('Y') ?> LUNORA. All rights reserved.</span>
  </div>
</footer>

</body>
</html>
