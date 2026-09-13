<?php
/**
 * Expects, from the including page:
 *   $admin_user   — current admin's user record
 *   $active       — 'dashboard' | 'orders' | 'products'
 *   $page_title   — string
 *   $page_subtitle (optional) — string
 */
$lunora_admin_flash = lunora_flash_get();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($page_title ?? 'Admin') ?> — LUNORA Admin</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="admin.css">
</head>
<body>
<div class="admin-shell">
  <aside class="admin-sidebar">
    <div class="wordmark">LUNORA<span>Admin Panel</span></div>
    <nav class="admin-nav">
      <a href="index.php" class="<?= $active === 'dashboard' ? 'is-active' : '' ?>">
        <svg viewBox="0 0 24 24"><rect x="3" y="3" width="8" height="8"/><rect x="13" y="3" width="8" height="8"/><rect x="3" y="13" width="8" height="8"/><rect x="13" y="13" width="8" height="8"/></svg>
        Dashboard
      </a>
      <a href="orders.php" class="<?= $active === 'orders' ? 'is-active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M6 8h12l1 13H5L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
        Orders &amp; Delivery
      </a>
      <a href="products.php" class="<?= $active === 'products' ? 'is-active' : '' ?>">
        <svg viewBox="0 0 24 24"><path d="M21 8L12 3 3 8v8l9 5 9-5V8Z"/><path d="M3 8l9 5 9-5"/><path d="M12 13v8"/></svg>
        Products &amp; Stock
      </a>
    </nav>
    <div class="admin-sidebar-footer">
      Signed in as<br><strong style="color:#fff;"><?= htmlspecialchars($admin_user['full_name'] ?? '') ?></strong>
      <a href="logout.php">Log out</a>
      <a href="../index.php">&larr; View storefront</a>
    </div>
  </aside>

  <main class="admin-main">
    <div class="admin-topbar">
      <div>
        <h1><?= htmlspecialchars($page_title ?? '') ?></h1>
        <?php if (!empty($page_subtitle)): ?><p><?= htmlspecialchars($page_subtitle) ?></p><?php endif; ?>
      </div>
      <a class="view-site-link" href="../index.php" target="_blank">View storefront &#8599;</a>
    </div>

    <?php if ($lunora_admin_flash): ?>
      <div class="admin-flash admin-flash--<?= htmlspecialchars($lunora_admin_flash['type']) ?>"><?= htmlspecialchars($lunora_admin_flash['message']) ?></div>
    <?php endif; ?>
