<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/orders.php';
require_once __DIR__ . '/reviews.php';

$lunora_user = lunora_current_user();
$lunora_flash = lunora_flash_get();

if ($lunora_user && $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'submit_review') {
    if (!lunora_csrf_check($_POST['csrf'] ?? null)) {
        lunora_flash_set('error', 'Your session expired — please try again.');
    } else {
        $orderId   = $_POST['order_id'] ?? '';
        $productId = $_POST['product_id'] ?? '';
        $rating    = (int) ($_POST['rating'] ?? 0);
        $comment   = trim($_POST['comment'] ?? '');

        if ($rating < 1 || $rating > 5) {
            lunora_flash_set('error', 'Please choose a star rating from 1 to 5.');
        } elseif (strlen($comment) > 1000) {
            lunora_flash_set('error', 'Your review is too long (max 1000 characters).');
        } elseif (!lunora_order_contains_product_for_user($lunora_user['id'], $orderId, $productId)) {
            // Either not their order, doesn't contain this product, or the order was cancelled.
            lunora_flash_set('error', 'We could not verify that purchase, so this review could not be saved.');
        } elseif (lunora_user_has_reviewed($lunora_user['id'], $productId)) {
            lunora_flash_set('error', 'You have already reviewed this item.');
        } else {
            $ok = lunora_create_review($lunora_user['id'], $productId, $orderId, $rating, $comment);
            lunora_flash_set($ok ? 'success' : 'error', $ok ? 'Thanks for your review!' : 'You have already reviewed this item.');
        }
    }
    header('Location: my-orders.php');
    exit;
}

$myOrders = [];
$reviewableByProduct = [];
if ($lunora_user) {
    $all = lunora_load_orders();
    foreach (array_reverse($all) as $order) {
        if (($order['user_id'] ?? null) === $lunora_user['id']) {
            $myOrders[] = $order;
        }
    }
    // Index reviewable (order_id, product_id) pairs for quick lookup below.
    foreach (lunora_get_reviewable_items($lunora_user['id']) as $item) {
        $reviewableByProduct[$item['order_id'] . '|' . $item['product_id']] = true;
    }

    // They're looking at their orders right now, so clear the notification badge.
    lunora_mark_orders_seen($lunora_user['id']);
}

$statusLabels = lunora_order_statuses();
$csrfToken = lunora_csrf_token();
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

<?php if ($lunora_flash): ?>
  <div class="flash-banner flash-banner--<?= htmlspecialchars($lunora_flash['type']) ?>"><?= htmlspecialchars($lunora_flash['message']) ?></div>
<?php endif; ?>

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

            <?php foreach ($order['items'] as $item):
              if (empty($item['product_id']) || empty($reviewableByProduct[$order['id'] . '|' . $item['product_id']])) continue; ?>
              <div class="review-prompt" data-review-prompt>
                <button type="button" class="review-prompt__toggle" data-review-toggle>Rate &ldquo;<?= htmlspecialchars($item['name']) ?>&rdquo;</button>
                <form class="review-form" method="post" hidden data-review-form>
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="action" value="submit_review">
                  <input type="hidden" name="order_id" value="<?= htmlspecialchars($order['id']) ?>">
                  <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id']) ?>">
                  <fieldset class="review-form__stars" data-star-picker>
                    <legend>Your rating</legend>
                    <?php for ($s = 5; $s >= 1; $s--): $starId = 'star-' . $order['id'] . '-' . $item['product_id'] . '-' . $s; ?>
                      <input type="radio" name="rating" id="<?= htmlspecialchars($starId) ?>" value="<?= $s ?>" required>
                      <label for="<?= htmlspecialchars($starId) ?>" title="<?= $s ?> star<?= $s == 1 ? '' : 's' ?>">&#9733;</label>
                    <?php endfor; ?>
                  </fieldset>
                  <textarea name="comment" maxlength="1000" rows="3" placeholder="What did you think? (optional)"></textarea>
                  <button type="submit" class="auth-submit" style="width:auto; padding:10px 22px;">Submit Review</button>
                </form>
              </div>
            <?php endforeach; ?>
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
