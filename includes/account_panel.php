<?php
/**
 * LUNORA — account dropdown panel (the person-icon menu).
 * Included from index.php's inline header and from includes/site_header.php.
 * Requires $lunora_user to already be set by the including page. Self-loads
 * notifications.php so callers don't each need to require it separately.
 */
require_once __DIR__ . '/../notifications.php';

$lunora_account_unread = $lunora_user ? lunora_count_unread_notifications($lunora_user['id']) : 0;
?>
<?php if ($lunora_user): ?>
<div class="account-wrap">
  <button class="icon-btn" aria-label="Account" id="accountToggle" aria-expanded="false">
    <?php if (!empty($lunora_user['profile_image'])): ?>
      <img src="<?= htmlspecialchars($lunora_user['profile_image']) ?>" alt="" class="account-toggle__avatar">
    <?php else: ?>
      <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
    <?php endif; ?>
    <?php if ($lunora_account_unread > 0): ?><span class="bag-count"><?= $lunora_account_unread ?></span><?php endif; ?>
  </button>

  <div class="account-panel" id="accountPanel" hidden>
    <div class="account-panel__head">
      <button type="button" class="account-panel__close" id="accountPanelClose" aria-label="Close">&times;</button>
      <div class="account-panel__name"><?= htmlspecialchars(strtoupper($lunora_user['full_name'])) ?>,</div>
      <span class="account-panel__badge">Member</span>
      <div class="account-panel__contact"><?= htmlspecialchars($lunora_user['email']) ?></div>
      <?php if (!empty($lunora_user['phone'])): ?>
        <div class="account-panel__contact"><?= htmlspecialchars($lunora_user['phone']) ?></div>
      <?php endif; ?>
    </div>
    <nav class="account-panel__nav">
      <a href="my-orders.php">
        <svg viewBox="0 0 24 24"><path d="M6 8h12l1 13H5L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
        My Orders
      </a>
      <a href="profile.php">
        <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
        Profile
      </a>
      <a href="my-notifications.php">
        <svg viewBox="0 0 24 24"><path d="M18 8a6 6 0 0 0-12 0c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.7 21a2 2 0 0 1-3.4 0"/></svg>
        Notification
        <?php if ($lunora_account_unread > 0): ?><span class="account-panel__nav-badge"><?= $lunora_account_unread ?></span><?php endif; ?>
      </a>
      <a href="my-wishlist.php">
        <svg viewBox="0 0 24 24"><path d="M12 20 C6 15 2 11.5 2 7.6 2 4.5 4.4 2 7.4 2 9.2 2 10.8 3 12 4.4 13.2 3 14.8 2 16.6 2 19.6 2 22 4.5 22 7.6 22 11.5 18 15 12 20Z"/></svg>
        Wishlist
      </a>
      <a href="logout.php" class="account-panel__logout">Log Out</a>
    </nav>
  </div>
</div>
<?php else: ?>
  <a class="icon-btn" aria-label="Account" href="login.php">
    <svg viewBox="0 0 24 24"><circle cx="12" cy="8" r="4"/><path d="M4 20c0-4.4 3.6-7 8-7s8 2.6 8 7"/></svg>
  </a>
<?php endif; ?>
