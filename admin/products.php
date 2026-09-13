<?php
require_once __DIR__ . '/../auth.php';
require_once __DIR__ . '/../products.php';
lunora_require_admin('login.php');

$admin_user = lunora_current_user();
$active = 'products';
$page_title = 'Products & Stock';
$page_subtitle = 'Manage the catalog shown on the storefront, and keep stock counts current.';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (!lunora_csrf_check($_POST['csrf'] ?? null)) {
        lunora_flash_set('error', 'Your session expired — please try again.');
    } else {
        $ok = lunora_delete_product($_POST['id'] ?? '');
        lunora_flash_set($ok ? 'success' : 'error', $ok ? 'Product deleted.' : 'Could not find that product.');
    }
    header('Location: products.php');
    exit;
}

$csrfToken = lunora_csrf_token();
$products = array_reverse(lunora_load_products());
$sections = ['grid' => 'Main Grid', 'bestseller' => 'Best Sellers'];

require __DIR__ . '/includes/layout_top.php';
?>

<div class="card">
  <div class="card-header">
    <h2>Catalog</h2>
    <a class="btn" href="product_form.php">+ Add Product</a>
  </div>

  <?php if (!$products): ?>
    <div class="empty-state">No products yet. Click "Add Product" to create your first listing.</div>
  <?php else: ?>
  <div class="table-scroll">
    <table>
      <thead><tr><th>Image</th><th>Product</th><th>Section</th><th>Price</th><th>Stock</th><th></th></tr></thead>
      <tbody>
        <?php foreach ($products as $p):
          $stock = (int) $p['stock'];
          $stockClass = $stock <= 0 ? 'pill-outstock' : ($stock <= 5 ? 'pill-lowstock' : 'pill-instock');
          $stockLabel = $stock <= 0 ? 'Out of stock' : ($stock <= 5 ? 'Low stock' : 'In stock');
        ?>
          <tr>
            <td>
              <?php if (!empty($p['image'])): ?>
                <img class="thumb" src="<?= htmlspecialchars('../' . $p['image']) ?>" alt="" onerror="this.style.visibility='hidden'">
              <?php else: ?>
                <div class="thumb" style="background:<?= htmlspecialchars($p['fill'] ?? '#ECE5D6') ?>;"></div>
              <?php endif; ?>
            </td>
            <td><?= htmlspecialchars($p['name']) ?><br><span style="color:var(--ink-soft); font-size:0.78rem;"><?= htmlspecialchars($p['variant']) ?></span></td>
            <td><?= htmlspecialchars($sections[$p['section'] ?? 'grid'] ?? 'Main Grid') ?></td>
            <td>US$<?= number_format($p['price'], 2) ?></td>
            <td><?= $stock ?> &middot; <span class="pill <?= $stockClass ?>"><?= $stockLabel ?></span></td>
            <td>
              <div class="btn-row">
                <a class="btn btn-secondary btn-sm" href="product_form.php?id=<?= urlencode($p['id']) ?>">Edit</a>
                <form method="post" onsubmit="return confirm('Delete this product? This cannot be undone.');">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                  <input type="hidden" name="id" value="<?= htmlspecialchars($p['id']) ?>">
                  <button type="submit" class="btn btn-danger btn-sm">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/layout_bottom.php'; ?>
