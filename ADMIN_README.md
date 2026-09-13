# LUNORA — Admin Panel Update

## What's new

- **Admin panel** at `/admin/` — dashboard, orders & delivery management, and product/stock management.
- **Real orders**: checkout now saves actual orders to `data/orders.json` (instead of just simulating success), and validates/decrements stock server-side.
- **Dynamic catalog**: products live in `data/products.json`. Anything you add/edit/delete in the admin panel shows up on the storefront immediately.
- **Stock awareness**: products with 0 stock show "Out of Stock" on the storefront and can't be added to the bag.

## Admin login

Go to `yoursite.com/admin/login.php`.

- **Email:** `admin@lunora.local`
- **Password:** `Lunora@Admin1`

**Please log in and change this password as soon as possible** — there's no in-app "change password" screen yet, so for now do it by editing `data/users.json` directly: replace the `password_hash` value for the admin account with the output of:

```php
<?php echo password_hash('YourNewPassword', PASSWORD_DEFAULT);
```

(run that as a one-off PHP script, copy the output string in, then delete the script).

## What's in the admin panel

- **Dashboard** (`admin/index.php`) — order counts by status, total revenue, recent orders, and low/out-of-stock alerts.
- **Orders & Delivery** (`admin/orders.php`) — see every order, filter by status, and open one to update its status (Pending → Processing → Shipped → Delivered / Cancelled), carrier, tracking number, ETA, and internal notes. Every status change is logged with a timestamp.
- **Products & Stock** (`admin/products.php` + `admin/product_form.php`) — add a new product (name, variant, price, stock, category, badge, section, leather-tone swatches, and an image upload), edit an existing one, adjust stock, or delete it. Uploaded images are stored in `images/products/uploads/`.

## Files added or changed

New shared logic:
- `products.php` — product catalog store (JSON-backed) + image upload handling
- `orders.php` — order store (JSON-backed)
- `place_order.php` — endpoint the checkout page calls to actually create an order
- `order_success.php` — confirmation page after checkout
- `data/products.json`, `data/users.json` — your data lives here now

Admin panel (new): everything under `admin/`

Changed:
- `auth.php` — added user roles (`customer` / `admin`) and admin-guard helpers
- `index.php` — now reads products from `products.php` instead of a hardcoded list
- `checkout.php`, `script.js` — checkout submits a real order instead of a fake success message
- `style.css` — small addition for the "Out of Stock" state

Removed (superseded / unused):
- `functions.php` — its product data has been replaced by `data/products.json`; keeping both would have caused duplicate-function errors if ever both got included
- top-level `users.json` — replaced by `data/users.json` (used by `auth.php`)

## Notes & things you may want next

- Uploaded images need PHP's `file_uploads` on and `upload_max_filesize` / `post_max_size` big enough (5MB cap is enforced in code); most hosts have generous defaults.
- Orders currently don't send email confirmations — the confirmation page shows the summary, but nothing is emailed. Say the word if you'd like that added (needs SMTP details).
- There's only one admin role right now (no "staff" vs "owner" distinction). Easy to add later if needed.
