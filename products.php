<?php
/**
 * LUNORA — product catalog store (PDO / MySQL backed).
 * Products live in the `products` table (see schema.sql). The admin panel
 * adds/edits/deletes rows here; the storefront (index.php) reads the same
 * table so changes show up immediately. All access is via prepared
 * statements.
 */

require_once __DIR__ . '/db.php';

define('LUNORA_PRODUCT_IMAGE_DIR', __DIR__ . '/images/products/uploads');
define('LUNORA_PRODUCT_IMAGE_WEB_PATH', 'images/products/uploads');

function lunora_ensure_product_image_dir(): void {
    if (!is_dir(LUNORA_PRODUCT_IMAGE_DIR)) {
        mkdir(LUNORA_PRODUCT_IMAGE_DIR, 0775, true);
    }
}

/** Turn a raw `products` row (tones stored as a comma string) into the
 *  shape the rest of the app expects (tones as an array, numeric types cast). */
function lunora_product_row(array $row): array {
    $row['price'] = (float) $row['price'];
    $row['stock'] = (int) $row['stock'];
    $row['tones'] = $row['tones'] !== '' ? explode(',', $row['tones']) : [];
    return $row;
}

/** All products, in the order they were added. */
function lunora_load_products(): array {
    $stmt = lunora_db()->query('SELECT * FROM products ORDER BY created_at ASC');
    return array_map('lunora_product_row', $stmt->fetchAll());
}

/** Products for the main grid (section = "grid"). */
function lunora_products(): array {
    $stmt = lunora_db()->prepare("SELECT * FROM products WHERE section = 'grid' ORDER BY created_at ASC");
    $stmt->execute();
    return array_map('lunora_product_row', $stmt->fetchAll());
}

/** Products for the Best Sellers rail (section = "bestseller"). */
function lunora_bestsellers(): array {
    $stmt = lunora_db()->prepare("SELECT * FROM products WHERE section = 'bestseller' ORDER BY created_at ASC");
    $stmt->execute();
    return array_map('lunora_product_row', $stmt->fetchAll());
}

function lunora_get_product(string $id): ?array {
    $stmt = lunora_db()->prepare('SELECT * FROM products WHERE id = ? LIMIT 1');
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    return $row ? lunora_product_row($row) : null;
}

/** Named leather tones used across the collection, each with a label + swatch hex. */
function lunora_tones(): array {
    return [
        'stone'    => ['label' => 'Stone Grey',  'hex' => '#9C948A'],
        'pecan'    => ['label' => 'Pecan Brown', 'hex' => '#7A5232'],
        'black'    => ['label' => 'Black',       'hex' => '#232019'],
        'smoky'    => ['label' => 'Smoky Blue',  'hex' => '#7C8B95'],
        'multi'    => ['label' => 'Multi',       'hex' => '#8A6A3F'],
        'burgundy' => ['label' => 'Burgundy',    'hex' => '#5C2A2E'],
        'cream'    => ['label' => 'Ivory',       'hex' => '#EDE6D8'],
        'plum'     => ['label' => 'Espresso',    'hex' => '#3B2A22'],
        'blush'    => ['label' => 'Blush',       'hex' => '#E3B8B4'],
    ];
}

function lunora_price(float $n): string {
    return 'US$' . number_format($n, 2);
}

function lunora_next_product_id(): string {
    return 'p_' . bin2hex(random_bytes(6));
}

/**
 * Create a product. $data may include: name, variant, price, stock, image,
 * fill, tones (array), badge, section ('grid'|'bestseller'), category.
 * Returns the stored record (with its generated id).
 */
function lunora_add_product(array $data): array {
    $product = [
        'id'         => lunora_next_product_id(),
        'name'       => trim($data['name'] ?? ''),
        'variant'    => trim($data['variant'] ?? ''),
        'price'      => (float) ($data['price'] ?? 0),
        'stock'      => max(0, (int) ($data['stock'] ?? 0)),
        'image'      => $data['image'] ?? '',
        'fill'       => $data['fill'] ?? '#ECE5D6',
        'tones'      => array_values(array_filter((array) ($data['tones'] ?? []))),
        'badge'      => trim($data['badge'] ?? ''),
        'section'    => in_array($data['section'] ?? 'grid', ['grid', 'bestseller'], true) ? $data['section'] : 'grid',
        'category'   => trim($data['category'] ?? ''),
        'created_at' => date('Y-m-d H:i:s'),
        'updated_at' => date('Y-m-d H:i:s'),
    ];

    $stmt = lunora_db()->prepare(
        'INSERT INTO products (id, name, variant, price, stock, image, fill, tones, badge, section, category, created_at, updated_at)
         VALUES (:id, :name, :variant, :price, :stock, :image, :fill, :tones, :badge, :section, :category, :created_at, :updated_at)'
    );
    $stmt->execute(array_merge($product, ['tones' => implode(',', $product['tones'])]));

    return $product;
}

/** Merge $data into the existing product with $id. Returns true on success. */
function lunora_update_product(string $id, array $data): bool {
    $existing = lunora_get_product($id);
    if (!$existing) return false;

    $merged = $existing;
    foreach (['name', 'variant', 'image', 'fill', 'badge', 'section', 'category'] as $field) {
        if (array_key_exists($field, $data)) $merged[$field] = trim((string) $data[$field]);
    }
    if (array_key_exists('price', $data)) $merged['price'] = (float) $data['price'];
    if (array_key_exists('stock', $data)) $merged['stock'] = max(0, (int) $data['stock']);
    if (array_key_exists('tones', $data)) $merged['tones'] = array_values(array_filter((array) $data['tones']));
    $merged['section'] = in_array($merged['section'], ['grid', 'bestseller'], true) ? $merged['section'] : 'grid';

    $stmt = lunora_db()->prepare(
        'UPDATE products SET name = :name, variant = :variant, price = :price, stock = :stock,
         image = :image, fill = :fill, tones = :tones, badge = :badge, section = :section,
         category = :category, updated_at = :updated_at WHERE id = :id'
    );
    $stmt->execute([
        'name'       => $merged['name'],
        'variant'    => $merged['variant'],
        'price'      => $merged['price'],
        'stock'      => $merged['stock'],
        'image'      => $merged['image'],
        'fill'       => $merged['fill'],
        'tones'      => implode(',', $merged['tones']),
        'badge'      => $merged['badge'],
        'section'    => $merged['section'],
        'category'   => $merged['category'],
        'updated_at' => date('Y-m-d H:i:s'),
        'id'         => $id,
    ]);

    return true;
}

function lunora_delete_product(string $id): bool {
    $stmt = lunora_db()->prepare('DELETE FROM products WHERE id = ?');
    $stmt->execute([$id]);
    return $stmt->rowCount() > 0;
}

/**
 * Decrease stock by $qty for a product, atomically. The WHERE clause
 * checks stock >= qty in the same statement the database runs, so two
 * simultaneous checkouts can't both succeed against the last unit.
 * Returns false if there wasn't enough stock (or the product doesn't exist).
 */
function lunora_decrement_stock(string $id, int $qty): bool {
    $stmt = lunora_db()->prepare('UPDATE products SET stock = stock - ? WHERE id = ? AND stock >= ?');
    $stmt->execute([$qty, $id, $qty]);
    return $stmt->rowCount() === 1;
}

/**
 * Validate + move an uploaded product image ($_FILES[$field]) into
 * images/products/uploads/. Returns the web-relative path, or null if no
 * file was uploaded. Throws a RuntimeException on validation failure.
 */
function lunora_handle_product_image_upload(string $field): ?string {
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The image failed to upload. Please try again.');
    }

    $maxBytes = 5 * 1024 * 1024; // 5MB
    if ($file['size'] > $maxBytes) {
        throw new RuntimeException('Image is too large (max 5MB).');
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    $allowed = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
        'image/gif'  => 'gif',
    ];
    if (!isset($allowed[$mime])) {
        throw new RuntimeException('Please upload a JPG, PNG, WEBP, or GIF image.');
    }

    lunora_ensure_product_image_dir();
    $filename = 'prod_' . bin2hex(random_bytes(8)) . '.' . $allowed[$mime];
    $destination = LUNORA_PRODUCT_IMAGE_DIR . '/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new RuntimeException('Could not save the uploaded image.');
    }

    return LUNORA_PRODUCT_IMAGE_WEB_PATH . '/' . $filename;
}
