<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/products.php';
require_once __DIR__ . '/reviews.php';
require_once __DIR__ . '/orders.php';
$lunora_user = lunora_current_user();
$lunora_flash = lunora_flash_get();
$lunora_notif_count = $lunora_user ? lunora_count_unseen_status_changes($lunora_user['id']) : 0;
/**
 * LUNORA — Women's Bags & Handbags
 * Product data now lives in data/products.json (see products.php) so the
 * admin panel can add products / adjust stock and have it reflected here.
 */

$subnav = ['Tote Bags','Shoulder Bags','Top Handle Bags','Crossbody Bags','Hobo Bags','Backpacks','Clutches','Mini Bags','Bucket Bags','Slouchy Bags','Wedding'];
$products = lunora_products();
$bestsellers = lunora_bestsellers();
$ratingSummaries = lunora_get_rating_summaries();

/** Render a product's average-rating line (or "No reviews yet"). */
function lunora_render_rating_snippet(string $productId, array $ratingSummaries): string {
    $summary = $ratingSummaries[$productId] ?? null;
    if (!$summary || $summary['count'] === 0) {
        return '<p class="product-rating product-rating--empty">No reviews yet</p>';
    }
    $full = (int) round($summary['avg']);
    $stars = str_repeat('&#9733;', $full) . str_repeat('&#9734;', 5 - $full);
    return '<p class="product-rating"><span class="product-rating__stars">' . $stars . '</span>'
        . htmlspecialchars((string) $summary['avg']) . ' (' . (int) $summary['count'] . ')</p>';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Women's Bags &amp; Handbags — LUNORA</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&family=Manrope:wght@400;500;600;700&display=swap" rel="stylesheet">
<link rel="stylesheet" href="style.css">
</head>
<body>

<div class="promo-bar">Free standard delivery on all orders, no minimum spend this week</div>

<?php if ($lunora_flash): ?>
  <div class="flash-banner flash-banner--<?= htmlspecialchars($lunora_flash['type']) ?>"><?= htmlspecialchars($lunora_flash['message']) ?></div>
<?php endif; ?>

<header class="site-header">
  <div class="header-inner">
    <button class="icon-btn menu-toggle" id="menuToggle" aria-label="Open menu" aria-expanded="false">
      <span></span><span></span><span></span>
    </button>
    <nav class="primary-nav">
      <a href="#productGrid" id="navShop" class="active">Shop</a>
      <a href="#productGrid" id="navNewIn">New In</a>
      <a href="#campusSection" id="navOnCampus">On Campus</a>
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
      <div class="wish-wrap">
        <button class="icon-btn" aria-label="Wishlist" id="wishToggle" aria-expanded="false">
          <svg viewBox="0 0 24 24"><path d="M12 20 C6 15 2 11.5 2 7.6 2 4.5 4.4 2 7.4 2 9.2 2 10.8 3 12 4.4 13.2 3 14.8 2 16.6 2 19.6 2 22 4.5 22 7.6 22 11.5 18 15 12 20Z"/></svg>
          <span class="bag-count" id="wishCount" hidden>0</span>
        </button>
        <div class="wish-panel" id="wishPanel" hidden>
          <div class="wish-panel__head">Wishlist</div>
          <div class="wish-panel__items" id="wishPanelItems"></div>
        </div>
      </div>
      <button class="icon-btn" aria-label="Bag" id="bagToggle">
        <svg viewBox="0 0 24 24"><path d="M6 8h12l1 13H5L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
        <span class="bag-count" id="bagCount">0</span>
      </button>
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

<div class="breadcrumb">
  <div class="breadcrumb-inner"><a href="#">Home</a><span>/</span><span>Bags</span></div>
</div>

<section class="hero" id="hero">
  <div class="hero-tile hero-tile--model" aria-hidden="true">
    <img src="images/hero_model.jpg" alt="Model carrying bag" style="width: 100%; height: 100%; object-fit: cover;">
  </div>
  <div class="hero-tile hero-tile--product" aria-hidden="true">
    <img src="images/hero_bag.jpg" alt="Featured Bag" style="width: 100%; height: 100%; object-fit: cover;">
  </div>
</section>

<main>
  <section class="intro">
    <h1>Women&rsquo;s Bags &amp; Handbags</h1>
    <p>Elevate your daily rotation with our curated edit of women&rsquo;s bags and handbags, where modern design meets
      timeless appeal. From structured commuter totes to minimalist crossbody bags, our collection balances everyday
      functionality with a fashion-forward aesthetic.</p>
  </section>

  <nav class="chip-row" aria-label="Shop by edit">
    <a class="chip" href="#productGrid" data-chip-term="9 to 5">
      <span class="chip-swatch"><img src="images/chips/9to5.jpg" alt="9 to 5 bags"></span>
      9 to 5
    </a>
    <a class="chip" href="#productGrid" data-chip-term="Uni Bags">
      <span class="chip-swatch"><img src="images/chips/uni-bags.jpg" alt="Uni Bags"></span>
      Uni Bags
    </a>
    <a class="chip" href="#productGrid" data-chip-term="Suede">
      <span class="chip-swatch"><img src="images/chips/suede.jpg" alt="Suede bags"></span>
      Suede
    </a>
    <a class="chip" href="#productGrid" data-chip-term="__trending__">
      <span class="chip-swatch"><img src="images/chips/trending-now.jpg" alt="Trending now"></span>
      Trending Now
    </a>
  </nav>

  <nav class="subnav" aria-label="Bag categories" id="subnavCategories">
    <?php foreach ($subnav as $i => $item): ?>
      <a href="#productGrid" data-category="<?= htmlspecialchars($item) ?>" class="<?= $i === 0 ? 'is-current' : '' ?>"><?= htmlspecialchars($item) ?></a>
    <?php endforeach; ?>
  </nav>

  <div class="toolbar">
    <div class="filter-wrap">
      <button class="filter-btn" id="filterToggle" aria-expanded="false">
        <svg viewBox="0 0 24 24"><line x1="4" y1="7" x2="20" y2="7"/><line x1="4" y1="12" x2="20" y2="12"/><line x1="4" y1="17" x2="20" y2="17"/><circle cx="9" cy="7" r="1.6" fill="currentColor" stroke="none"/><circle cx="15" cy="12" r="1.6" fill="currentColor" stroke="none"/><circle cx="7" cy="17" r="1.6" fill="currentColor" stroke="none"/></svg>
        Filter
      </button>
      <div class="filter-panel" id="filterPanel" hidden>
        <?php $filterCategories = array_values(array_unique(array_filter(array_map(fn($p) => $p['category'], $products)))); sort($filterCategories); ?>
        <?php if ($filterCategories): ?>
          <div class="filter-group">
            <h4>Category</h4>
            <?php foreach ($filterCategories as $cat): ?>
              <label class="filter-check"><input type="checkbox" name="filterCategory" value="<?= htmlspecialchars($cat) ?>"> <?= htmlspecialchars($cat) ?></label>
            <?php endforeach; ?>
          </div>
        <?php endif; ?>
        <div class="filter-group">
          <h4>Availability</h4>
          <label class="filter-check"><input type="checkbox" id="filterInStock"> In stock only</label>
        </div>
        <div class="filter-group">
          <h4>Price (US$)</h4>
          <div class="filter-price-row">
            <input type="number" id="filterPriceMin" min="0" placeholder="Min">
            <span>&ndash;</span>
            <input type="number" id="filterPriceMax" min="0" placeholder="Max">
          </div>
        </div>
        <button type="button" class="filter-clear" id="filterClear">Clear all</button>
      </div>
    </div>
    <div class="toolbar-right">
      <label class="sort-select">
        Sort by
        <select id="sortSelect">
          <option>Featured</option>
          <option>Price: Low to High</option>
          <option>Price: High to Low</option>
          <option>Newest</option>
        </select>
      </label>
      <span class="results-count" id="resultsCount">Showing <?= count($products) ?> of <?= count($products) ?> item(s)</span>
    </div>
  </div>

  <section class="product-grid" id="productGrid">
    <p class="no-results" id="noResultsMessage" hidden>No bags match your filters. <button type="button" id="noResultsClear">Clear filters</button></p>
    <?php foreach ($products as $p): $outOfStock = (int)($p['stock'] ?? 0) <= 0; ?>
      <article class="product-card" data-id="<?= htmlspecialchars($p['id']) ?>" data-price="<?= $p['price'] ?>" data-new="<?= !empty($p['badge']) ? '1' : '0' ?>" data-category="<?= htmlspecialchars($p['category']) ?>" data-stock="<?= $outOfStock ? '0' : '1' ?>" data-rating="<?= htmlspecialchars((string) ($ratingSummaries[$p['id']]['avg'] ?? 0)) ?>" data-rating-count="<?= (int) ($ratingSummaries[$p['id']]['count'] ?? 0) ?>">
        <div class="product-photo" style="--photo-bg: <?= htmlspecialchars($p['fill']) ?>1a;">
          <?php if (!empty($p['badge'])): ?><span class="badge"><?= htmlspecialchars($p['badge']) ?></span><?php endif; ?>
          <?php if ($outOfStock): ?><span class="badge badge--out">Out of Stock</span><?php endif; ?>
          <button class="wish-btn" aria-label="Add to wishlist" data-wish>
            <svg viewBox="0 0 24 24"><path d="M12 20 C6 15 2 11.5 2 7.6 2 4.5 4.4 2 7.4 2 9.2 2 10.8 3 12 4.4 13.2 3 14.8 2 16.6 2 19.6 2 22 4.5 22 7.6 22 11.5 18 15 12 20Z"/></svg>
          </button>
          <img class="product-photo__icon" src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:100%; height:100%; object-fit:contain; display:block;">
          <?php if ($outOfStock): ?>
            <button class="quick-add" disabled>Out of Stock</button>
          <?php else: ?>
            <button class="quick-add" data-add data-id="<?= htmlspecialchars($p['id']) ?>" data-name="<?= htmlspecialchars($p['name']) ?>" data-tones="<?= htmlspecialchars(implode(',', $p['tones'])) ?>">+ Quick Add</button>
          <?php endif; ?>
        </div>
        <div class="swatch-row">
          <?php foreach ($p['tones'] as $tKey):
            $t = lunora_tones()[$tKey] ?? null; if (!$t) continue; ?>
            <span class="swatch" style="--sw: <?= htmlspecialchars($t['hex']) ?>" title="<?= htmlspecialchars($t['label']) ?>"></span>
          <?php endforeach; ?>
        </div>
        <h3 class="product-name"><?= htmlspecialchars($p['name']) ?> &ndash; <?= htmlspecialchars($p['variant']) ?></h3>
        <?= lunora_render_rating_snippet($p['id'], $ratingSummaries) ?>
        <p class="product-price"><?= lunora_price($p['price']) ?></p>
      </article>
    <?php endforeach; ?>
  </section>

  <section class="best-sellers">
    <h2>Best Sellers</h2>
    <div class="bestseller-layout">
      <div class="bestseller-grid">
        <?php foreach ($bestsellers as $p): $outOfStock = (int)($p['stock'] ?? 0) <= 0; ?>
          <article class="product-card product-card--compact" data-id="<?= htmlspecialchars($p['id']) ?>" data-price="<?= $p['price'] ?>" data-rating="<?= htmlspecialchars((string) ($ratingSummaries[$p['id']]['avg'] ?? 0)) ?>" data-rating-count="<?= (int) ($ratingSummaries[$p['id']]['count'] ?? 0) ?>">
            <div class="product-photo" style="--photo-bg: <?= htmlspecialchars($p['fill']) ?>1a;">
              <?php if ($outOfStock): ?><span class="badge badge--out">Out of Stock</span><?php endif; ?>
              <button class="wish-btn" aria-label="Add to wishlist" data-wish>
                <svg viewBox="0 0 24 24"><path d="M12 20 C6 15 2 11.5 2 7.6 2 4.5 4.4 2 7.4 2 9.2 2 10.8 3 12 4.4 13.2 3 14.8 2 16.6 2 19.6 2 22 4.5 22 7.6 22 11.5 18 15 12 20Z"/></svg>
              </button>
              <img class="product-photo__icon" src="<?= htmlspecialchars($p['image']) ?>" alt="<?= htmlspecialchars($p['name']) ?>" style="width:100%; height:100%; object-fit:contain; display:block;">
              <?php if ($outOfStock): ?>
                <button class="quick-add" disabled>Out of Stock</button>
              <?php else: ?>
                <button class="quick-add" data-add data-id="<?= htmlspecialchars($p['id']) ?>" data-name="<?= htmlspecialchars($p['name']) ?>" data-tones="<?= htmlspecialchars(implode(',', $p['tones'])) ?>">+ Quick Add</button>
              <?php endif; ?>
            </div>
            <h3 class="product-name"><?= htmlspecialchars($p['name']) ?></h3>
            <?= lunora_render_rating_snippet($p['id'], $ratingSummaries) ?>
            <p class="product-price"><?= lunora_price($p['price']) ?></p>
          </article>
        <?php endforeach; ?>
      </div>
      <div class="bestseller-feature">
        <img class="bestseller-feature__bag" src="images/extracted_17_X10.png" alt="Bestseller Model" style="width: 100%; height: 100%; object-fit: cover;">
        <p>Carried everywhere. The season&rsquo;s most-reached-for silhouette.</p>
      </div>
    </div>
  </section>

  <section class="brand-band" style="background-image: url('images/brand/brand-band-bg.jpg');">
    <span class="brand-band__mark">L</span>
    <p class="brand-band__word">LUNORA</p>
  </section>

  <section class="editorial-strip" id="campusSection" aria-label="LUNORA on campus">
    <div class="editorial-tile"><img src="images/editorial/tile-1.jpg" alt="LUNORA styled look, powder blue knit"></div>
    <div class="editorial-tile"><img src="images/editorial/tile-2.jpg" alt="LUNORA at a campus event photocall"></div>
    <div class="editorial-tile"><img src="images/editorial/tile-3.jpg" alt="LUNORA styled look, tailored layers"></div>
    <div class="editorial-tile"><img src="images/editorial/tile-4.jpg" alt="LUNORA styled look, off-duty denim"></div>
  </section>

  <section class="perks">
    <div class="perk">
      <svg viewBox="0 0 24 24"><path d="M2 7h13v9H2z"/><path d="M15 10h4l3 3v3h-7z"/><circle cx="6.5" cy="18.5" r="1.8"/><circle cx="17.5" cy="18.5" r="1.8"/></svg>
      <span><strong>Free Standard Delivery</strong><span>On all orders with min. spend*</span></span>
    </div>
    <div class="perk">
      <svg viewBox="0 0 24 24"><path d="M3 7v6h6"/><path d="M3.5 13a8.5 8.5 0 1 0 2.2-8.2L3 7.5"/></svg>
      <span><strong>Easy Returns</strong><span>Within 30 days of order</span></span>
    </div>
    <div class="perk">
      <svg viewBox="0 0 24 24"><path d="M12 2 3 6v6c0 5 4 8.5 9 10 5-1.5 9-5 9-10V6l-9-4Z"/></svg>
      <span><strong>Qualify for Privilege Membership</strong><span>Min. spend of US$250</span></span>
    </div>
  </section>
</main>

<?php include __DIR__ . '/includes/site_footer.php'; ?>

<div class="toast" id="toast" role="status" aria-live="polite"></div>

<div class="qa-modal-overlay" id="qaModalOverlay" hidden>
  <div class="qa-modal" role="dialog" aria-modal="true" aria-labelledby="qaModalTitle">
    <button type="button" class="qa-modal-close" id="qaModalClose" aria-label="Close">&times;</button>
    <div class="qa-modal-media" id="qaModalMedia">
      <img id="qaModalImg" src="" alt="">
    </div>
    <div class="qa-modal-body">
      <h3 id="qaModalTitle"></h3>
      <p class="qa-modal-price" id="qaModalPrice"></p>
      <p class="qa-modal-rating" id="qaModalRating" hidden>
        <span class="qa-modal-rating__stars" id="qaModalRatingStars"></span>
        <span id="qaModalRatingText"></span>
      </p>

      <div class="qa-modal-colors" id="qaModalColors">
        <span class="qa-modal-colors__label">Color: <strong id="qaModalColorName"></strong></span>
        <div class="qa-modal-swatches" id="qaModalSwatches"></div>
      </div>

      <label class="qa-modal-qty">Quantity
        <select id="qaModalQty">
          <?php for ($n = 1; $n <= 8; $n++): ?><option value="<?= $n ?>"><?= $n ?></option><?php endfor; ?>
        </select>
      </label>

      <button type="button" class="auth-submit qa-modal-add" id="qaModalAdd">
        <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="1.8"><path d="M6 8h12l1 13H5L6 8Z"/><path d="M9 8V6a3 3 0 0 1 6 0v2"/></svg>
        Add to Bag
      </button>

      <div class="qa-modal-reviews" id="qaModalReviews" hidden>
        <h4>Customer Reviews</h4>
        <div id="qaModalReviewsList"></div>
      </div>
    </div>
  </div>
</div>

<script>
  window.LUNORA_TONES = <?= json_encode(lunora_tones()) ?>;
  window.LUNORA_LOGGED_IN = <?= $lunora_user ? 'true' : 'false' ?>;
  window.LUNORA_REVIEWS = <?= json_encode(lunora_get_reviews_grouped_by_product()) ?>;
</script>
<script src="script.js"></script>
</body>
</html>
