<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/products.php';
require_once __DIR__ . '/reviews.php';
require_once __DIR__ . '/wishlist.php';

$lunora_user = lunora_current_user();
$lunora_flash = lunora_flash_get();
$bestsellers = lunora_bestsellers();
$ratingSummaries = lunora_get_rating_summaries();
$lunora_wishlist_ids = $lunora_user ? lunora_wishlist_product_ids($lunora_user['id']) : [];
$lunora_csrf_token = lunora_csrf_token();
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Best Sellers — LUNORA</title>
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
<div class="breadcrumb-inner"><a href="index.php">Home</a><span>/</span><span>Best Sellers</span></div>
</div>

<main class="info-page">
<div class="info-page__inner" style="max-width:none;">
<h1>Best Sellers</h1>

<?php if (empty($bestsellers)): ?>
<div class="orders-empty"><p>No best sellers yet — check back soon.</p></div>
<?php else: ?>
<div class="bestsellers-page-grid" style="padding:0; margin:0;">
<?php foreach ($bestsellers as $p): $outOfStock = (int) ($p['stock'] ?? 0) <= 0; ?>
  <article class="product-card" data-id="<?= htmlspecialchars($p['id']) ?>" data-price="<?= $p['price'] ?>">
    <div class="product-photo" style="--photo-bg: <?= htmlspecialchars($p['fill']) ?>1a;">
      <?php if ($outOfStock): ?><span class="badge badge--out">Out of Stock</span><?php endif; ?>
      <button class="wish-btn<?= in_array($p['id'], $lunora_wishlist_ids, true) ? ' is-active' : '' ?>" aria-label="Add to wishlist" data-wish>
        <svg viewBox="0 0 24 24"><path d="M12 20 C6 15 2 11.5 2 7.6 2 4.5 4.4 2 7.4 2 9.2 2 10.8 3 12 4.4 13.2 3 14.8 2 16.6 2 19.6 2 22 4.5 22 7.6 22 11.5 18 15 12 20Z"/></svg>
      </button>
      <img class="product-photo__icon" src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:100%; height:100%; object-fit:contain; display:block;">
      <?php if ($outOfStock): ?>
        <button class="quick-add" disabled>Out of Stock</button>
      <?php else: ?>
        <button class="quick-add" data-add data-id="<?= htmlspecialchars($p['id']) ?>" data-name="<?= htmlspecialchars($p['name']) ?>" data-tones="<?= htmlspecialchars(implode(',', $p['tones'])) ?>">+ Quick Add</button>
      <?php endif; ?>
    </div>
    <h3 class="product-name"><?= htmlspecialchars($p['name']) ?><?= $p['variant'] ? ' &ndash; ' . htmlspecialchars($p['variant']) : '' ?></h3>
    <?php
      $summary = $ratingSummaries[$p['id']] ?? null;
      if ($summary && $summary['count'] > 0):
    ?>
      <p style="font-size:0.8rem; color:var(--ink-soft); margin:2px 0;">&#9733; <?= number_format($summary['avg'], 1) ?> (<?= (int) $summary['count'] ?>)</p>
    <?php endif; ?>
    <p class="product-price"><?= lunora_price($p['price']) ?></p>
  </article>
<?php endforeach; ?>
</div>
<?php endif; ?>

</div>
</main>

<?php include __DIR__ . '/includes/site_footer.php'; ?>
<script>
  window.LUNORA_LOGGED_IN = <?= $lunora_user ? 'true' : 'false' ?>;
  window.LUNORA_WISHLIST_IDS = <?= json_encode($lunora_wishlist_ids) ?>;
  window.LUNORA_CSRF = <?= json_encode($lunora_csrf_token) ?>;
</script>
<script src="script.js"></script>
</body>
</html>
