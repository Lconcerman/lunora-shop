<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../products.php';
lunora_require_admin('login.php');

$admin_user = lunora_current_user();
$active = 'products';

$id = $_GET['id'] ?? '';
$editing = $id ? lunora_get_product($id) : null;
if ($id && !$editing) {
    lunora_flash_set('error', 'That product could not be found.');
    header('Location: products.php');
    exit;
}

$page_title = $editing ? 'Edit Product' : 'Add Product';
$page_subtitle = $editing ? ('Editing "' . $editing['name'] . '"') : 'Add a new item to the storefront catalog.';

$errors = [];
$allTones = lunora_tones();

// Defaults (used for both a fresh form and a re-shown form after validation errors)
$values = [
    'name'     => $editing['name'] ?? '',
    'variant'  => $editing['variant'] ?? '',
    'price'    => $editing['price'] ?? '',
    'stock'    => $editing['stock'] ?? 0,
    'category' => $editing['category'] ?? '',
    'badge'    => $editing['badge'] ?? '',
    'section'  => $editing['section'] ?? 'grid',
    'fill'     => $editing['fill'] ?? '#ECE5D6',
    'tones'    => $editing['tones'] ?? [],
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!lunora_csrf_check($_POST['csrf'] ?? null)) {
        $errors[] = 'Your session expired — please try again.';
    } else {
        $values['name']     = trim($_POST['name'] ?? '');
        $values['variant']  = trim($_POST['variant'] ?? '');
        $values['price']    = $_POST['price'] ?? '';
        $values['stock']    = $_POST['stock'] ?? '0';
        $values['category'] = trim($_POST['category'] ?? '');
        $values['badge']    = trim($_POST['badge'] ?? '');
        $values['section']  = in_array($_POST['section'] ?? 'grid', ['grid', 'bestseller'], true) ? $_POST['section'] : 'grid';
        $values['fill']     = $_POST['fill'] ?? '#ECE5D6';
        $values['tones']    = array_values(array_intersect((array) ($_POST['tones'] ?? []), array_keys($allTones)));

        if ($values['name'] === '') $errors[] = 'Please enter a product name.';
        if (!is_numeric($values['price']) || (float) $values['price'] < 0) $errors[] = 'Please enter a valid price.';
        if (!is_numeric($values['stock']) || (int) $values['stock'] < 0) $errors[] = 'Please enter a valid stock quantity (0 or more).';

        $uploadedImagePath = null;
        if (!$errors) {
            try {
                $uploadedImagePath = lunora_handle_product_image_upload('image');
            } catch (RuntimeException $e) {
                $errors[] = $e->getMessage();
            }
        }

        if (!$errors) {
            $imagePath = $uploadedImagePath ?? ($editing['image'] ?? '');
            if (!$imagePath && !$editing) {
                $errors[] = 'Please upload a product image.';
            }
        }

        if (!$errors) {
            $payload = [
                'name'     => $values['name'],
                'variant'  => $values['variant'],
                'price'    => $values['price'],
                'stock'    => $values['stock'],
                'category' => $values['category'],
                'badge'    => $values['badge'],
                'section'  => $values['section'],
                'fill'     => $values['fill'],
                'tones'    => $values['tones'],
                'image'    => $imagePath,
            ];

            if ($editing) {
                lunora_update_product($editing['id'], $payload);
                lunora_flash_set('success', 'Product updated.');
            } else {
                lunora_add_product($payload);
                lunora_flash_set('success', 'Product added to the catalog.');
            }
            header('Location: products.php');
            exit;
        }
    }
}

$csrfToken = lunora_csrf_token();
require __DIR__ . '/includes/layout_top.php';
?>

<div class="card">
  <?php if ($errors): ?>
    <div class="admin-flash admin-flash--error">
      <?php foreach ($errors as $e): ?><div><?= htmlspecialchars($e) ?></div><?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="post" enctype="multipart/form-data">
    <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">

    <div class="form-grid">
      <label class="field">Product Name
        <input type="text" name="name" value="<?= htmlspecialchars($values['name']) ?>" placeholder="e.g. Aislin Hobo Bag" required>
      </label>
      <label class="field">Variant / Colour
        <input type="text" name="variant" value="<?= htmlspecialchars($values['variant']) ?>" placeholder="e.g. Stone Grey">
      </label>

      <label class="field">Price (US$)
        <input type="number" name="price" value="<?= htmlspecialchars((string) $values['price']) ?>" min="0" step="0.01" required>
      </label>
      <label class="field">Stock Quantity
        <input type="number" name="stock" value="<?= htmlspecialchars((string) $values['stock']) ?>" min="0" step="1" required>
        <span class="field-hint">Set to 0 to mark the product as out of stock on the storefront.</span>
      </label>

      <label class="field">Category <span class="field-hint">(optional)</span>
        <input type="text" name="category" value="<?= htmlspecialchars($values['category']) ?>" placeholder="e.g. Hobo Bags">
      </label>
      <label class="field">Badge <span class="field-hint">(optional)</span>
        <input type="text" name="badge" value="<?= htmlspecialchars($values['badge']) ?>" placeholder="e.g. New">
      </label>

      <label class="field">Section
        <select name="section">
          <option value="grid" <?= $values['section'] === 'grid' ? 'selected' : '' ?>>Main Grid</option>
          <option value="bestseller" <?= $values['section'] === 'bestseller' ? 'selected' : '' ?>>Best Sellers</option>
        </select>
      </label>
      <label class="field">Swatch Tint <span class="field-hint">(background tone behind the photo)</span>
        <input type="text" name="fill" value="<?= htmlspecialchars($values['fill']) ?>" placeholder="#ECE5D6">
      </label>

      <div class="field full">
        <span style="display:block; margin-bottom:8px; font-size:0.82rem; color:var(--ink-soft); font-weight:600;">Leather Tones Shown as Swatches <span class="field-hint">(optional)</span></span>
        <div class="tone-check-row">
          <?php foreach ($allTones as $key => $tone): ?>
            <label class="tone-check">
              <input type="checkbox" name="tones[]" value="<?= $key ?>" <?= in_array($key, $values['tones'], true) ? 'checked' : '' ?>>
              <?= htmlspecialchars($tone['label']) ?>
            </label>
          <?php endforeach; ?>
        </div>
      </div>

      <div class="field full">
        <label class="field">Product Image <?= $editing ? '<span class="field-hint">(leave empty to keep the current image)</span>' : '' ?>
          <input type="file" name="image" accept="image/jpeg,image/png,image/webp,image/gif">
        </label>
        <?php if ($editing && !empty($editing['image'])): ?>
          <img class="thumb-lg" style="margin-top:10px;" src="<?= htmlspecialchars('../' . $editing['image']) ?>" alt="">
        <?php endif; ?>
      </div>
    </div>

    <div class="btn-row" style="margin-top:22px;">
      <button type="submit" class="btn"><?= $editing ? 'Save Changes' : 'Add Product' ?></button>
      <a class="btn btn-secondary" href="products.php">Cancel</a>
    </div>
  </form>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
