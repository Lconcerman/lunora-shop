<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/wishlist.php';
require_once __DIR__ . '/notifications.php';

lunora_require_login('login.php');

$lunora_user = lunora_current_user();
$lunora_flash = lunora_flash_get();
$lunora_notif_unread = lunora_count_unread_notifications($lunora_user['id']);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'remove') {
    if (lunora_csrf_check($_POST['csrf'] ?? null)) {
        lunora_remove_from_wishlist($lunora_user['id'], $_POST['product_id'] ?? '');
        lunora_flash_set('success', 'Removed from your wishlist.');
    }
    header('Location: my-wishlist.php');
    exit;
}

$items = lunora_get_wishlist($lunora_user['id']);
$csrfToken = lunora_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>My Wishlist — LUNORA</title>
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
<div class="breadcrumb-inner"><a href="index.php">Home</a><span>/</span><span>My Wishlist</span></div>
</div>

<main class="account-layout">
<div class="info-page__inner" style="max-width:820px;">
<h1>My Wishlist</h1>

<?php if (empty($items)): ?>
<div class="orders-empty">
<p>Your wishlist is empty.</p>
<p><a href="index.php">Start shopping →</a></p>
</div>
<?php else: ?>
<div class="wishlist-grid">
<?php foreach ($items as $item): ?>
    <div class="wishlist-card">
        <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" class="wishlist-card__img">
        <div class="wishlist-card__info">
            <h3 class="wishlist-card__name"><?= htmlspecialchars($item['name']) ?><?= $item['variant'] ? ' &ndash; ' . htmlspecialchars($item['variant']) : '' ?></h3>
            <p class="wishlist-card__price">US$<?= number_format((float) $item['price'], 2) ?></p>
            <?php if ((int) $item['stock'] <= 0): ?>
                <p class="wishlist-card__stock">Currently out of stock</p>
            <?php endif; ?>
        </div>
        <form method="post" class="wishlist-card__remove">
            <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="product_id" value="<?= htmlspecialchars($item['product_id']) ?>">
            <button type="submit" aria-label="Remove from wishlist">&times;</button>
        </form>
    </div>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>
<?php include __DIR__ . '/includes/best_seller_sidebar.php'; ?>
</main>

<?php include __DIR__ . '/includes/site_footer.php'; ?>
<script src="script.js"></script>
</body>
</html>
