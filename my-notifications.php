<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/notifications.php';

lunora_require_login('login.php');

$lunora_user = lunora_current_user();
$lunora_flash = lunora_flash_get();

$notifications = lunora_get_notifications($lunora_user['id']);
// They're looking at this page right now, so clear the unread badge —
// mirrors how my-orders.php clears the order-status badge on view.
lunora_mark_notifications_read($lunora_user['id']);
$lunora_notif_unread = 0;

/** "3 hours ago", "2 days ago", etc. */
function lunora_time_ago(string $datetime): string {
    $diff = time() - strtotime($datetime);
    if ($diff < 60) return 'Just now';
    if ($diff < 3600) return floor($diff / 60) . 'm ago';
    if ($diff < 86400) return floor($diff / 3600) . 'h ago';
    if ($diff < 2592000) return floor($diff / 86400) . 'd ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Notifications — LUNORA</title>
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
<div class="breadcrumb-inner"><a href="index.php">Home</a><span>/</span><span>Notifications</span></div>
</div>

<main class="account-layout">
<div class="info-page__inner" style="max-width:640px;">
<h1>Notifications</h1>

<?php if (empty($notifications)): ?>
<div class="orders-empty">
<p>You don't have any notifications yet.</p>
</div>
<?php else: ?>
<div class="notif-list">
<?php foreach ($notifications as $n): ?>
    <?php $body = ($n['link'] ?? '') ? 'a' : 'div'; ?>
    <<?= $body ?> class="notif-item<?= empty($n['is_read']) ? ' notif-item--unread' : '' ?>"<?= ($n['link'] ?? '') ? ' href="' . htmlspecialchars($n['link']) . '"' : '' ?>>
        <div class="notif-item__dot"></div>
        <div class="notif-item__body">
            <div class="notif-item__title"><?= htmlspecialchars($n['title']) ?></div>
            <?php if (!empty($n['message'])): ?>
                <div class="notif-item__message"><?= htmlspecialchars($n['message']) ?></div>
            <?php endif; ?>
            <div class="notif-item__time"><?= htmlspecialchars(lunora_time_ago($n['created_at'])) ?></div>
        </div>
    </<?= $body ?>>
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
