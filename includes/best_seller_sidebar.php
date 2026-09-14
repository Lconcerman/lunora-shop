<?php
/**
 * LUNORA — sticky "Best Seller" sidebar card.
 * Included on account pages (my-orders.php, profile.php, my-wishlist.php,
 * my-notifications.php) inside .account-layout, next to .info-page__inner.
 * Shows the first bestseller product and links to the full best-sellers page.
 */
require_once __DIR__ . '/../products.php';

$lunora_featured_bestseller = lunora_bestsellers()[0] ?? null;
?>
<?php if ($lunora_featured_bestseller): ?>
<aside class="account-layout__aside">
  <div class="bestseller-card">
    <span class="bestseller-card__label">Best Seller</span>
    <img src="<?= htmlspecialchars($lunora_featured_bestseller['image']) ?>"
         alt="<?= htmlspecialchars($lunora_featured_bestseller['name']) ?>" class="bestseller-card__img">
    <div class="bestseller-card__body">
      <h3 class="bestseller-card__name"><?= htmlspecialchars($lunora_featured_bestseller['name']) ?></h3>
      <p class="bestseller-card__desc">Carried everywhere. The season&rsquo;s most-reached-for silhouette, loved for its everyday shape and easy-to-style tones.</p>
      <p class="bestseller-card__price">US$<?= number_format((float) $lunora_featured_bestseller['price'], 2) ?></p>
      <a href="best-sellers.php" class="bestseller-card__cta">Shop Best Sellers</a>
    </div>
  </div>
</aside>
<?php endif; ?>
