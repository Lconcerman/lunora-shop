<?php
/**
 * Shared header for secondary pages (info.php, my-orders.php, contact.php).
 * The including page must require_once auth.php and set $lunora_user /
 * $lunora_flash before including this, same as index.php does.
 */
require_once __DIR__ . '/../orders.php';
$lunora_notif_count = $lunora_user ? lunora_count_unseen_status_changes($lunora_user['id']) : 0;
?>
<div class="promo-bar">Free standard delivery on all orders, no minimum spend this week</div>

<?php if (!empty($lunora_flash)): ?>
  <div class="flash-banner flash-banner--<?= htmlspecialchars($lunora_flash['type']) ?>"><?= htmlspecialchars($lunora_flash['message']) ?></div>
<?php endif; ?>

<header class="site-header">
  <div class="header-inner">
    <button class="icon-btn menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <nav class="primary-nav">
      <a href="index.php">Shop</a>
      <a href="index.php">New In</a>
      <a href="index.php#campusSection">On Campus</a>
    </nav>
    <a href="index.php" class="wordmark">LUNORA</a>
    <div class="header-actions">
      <div class="search-wrap">
        <button class="icon-btn" aria-label="Search" id="searchToggle" aria-expanded="false">
          <svg viewBox="0 0 24 24"><circle cx="11" cy="11" r="7"/><line x1="21" y1="21" x2="16.6" y2="16.6"/></svg>
        </button>
        <div class="search-bar" id="searchBar" hidden>
          <input type="search" id="searchInput" placeholder="Search bags…" aria-label="Search products">
          <button type="button" id="searchClear" aria-label="Clear search">&times;</button>
        </div>
      </div>
      <a class="icon-btn" aria-label="Wishlist" href="index.php">
        <svg viewBox="0 0 24 24"><path d="M12 20 C6 15 2 11.5 2 7.6 2 4.5 4.4 2 7.4 2 9.2 2 10.8 3 12 4.4 13.2 3 14.8 2 16.6 2 19.6 2 22 4.5 22 7.6 22 11.5 18 15 12 20Z"/></svg>
      </a>
      <a class="icon-btn" aria-label="Bag" href="checkout.php">
        <svg viewBox="0 0 24 24"><path d="M6 8h12l1 13H5L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
        <span class="bag-count" id="bagCount">0</span>
      </a>
      <a class="icon-btn" aria-label="Account" href="<?= $lunora_user ? 'my-orders.php' : 'login.php' ?>">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
        <?php if ($lunora_notif_count > 0): ?><span class="bag-count"><?= $lunora_notif_count ?></span><?php endif; ?>
      </a>
      <?php if ($lunora_user): ?>
        <span class="account-link">Hi, <?= htmlspecialchars(explode(' ', $lunora_user['full_name'])[0]) ?></span>
        <a class="account-link account-link--logout" href="logout.php">Log Out</a>
      <?php else: ?>
        <a class="account-link" href="login.php">Log In</a>
      <?php endif; ?>
      <span class="lang">English</span>
    </div>
  </div>
</header>
