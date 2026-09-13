# Switching LUNORA from JSON files to MySQL/PDO

## What changed

- `db.php` **(new)** — the one place that opens the database connection, via PDO with prepared statements only.
- `schema.sql` **(new)** — run this once to create the `users`, `products`, `orders`, and `order_items` tables.
- `migrate_json_to_db.php` **(new)** — one-time script that copies your existing `data/users.json`, `data/products.json`, and `data/orders.json` into the new tables, so you don't lose the products/orders you already have.
- `auth.php`, `products.php`, `orders.php` — rewritten to read/write MySQL instead of JSON files. **Every function keeps the exact same name and return shape it had before** (`lunora_load_products()`, `lunora_get_order()`, `lunora_current_user()`, etc.), so `index.php`, `checkout.php`, `place_order.php`, `order_success.php`, and everything under `admin/` did not need to change.
- `register.php` — one small edit: it now calls a new `lunora_create_user()` helper instead of loading the whole user list, appending, and re-saving it (that pattern only made sense for a flat JSON file).

## One-time setup

1. **Create the database and import the schema:**
   ```bash
   mysql -u root -p -e "CREATE DATABASE lunora"
   mysql -u root -p lunora < schema.sql
   ```

2. **Point the app at your database.** Open `db.php` and edit the four constants at the top (or set them as environment variables with the same names on your server):
   ```php
   define('LUNORA_DB_HOST', getenv('LUNORA_DB_HOST') ?: '127.0.0.1');
   define('LUNORA_DB_NAME', getenv('LUNORA_DB_NAME') ?: 'lunora');
   define('LUNORA_DB_USER', getenv('LUNORA_DB_USER') ?: 'root');
   define('LUNORA_DB_PASS', getenv('LUNORA_DB_PASS') ?: '');
   ```

3. **Bring over your existing data** (skip this if you're fine starting with an empty catalog):
   ```bash
   php migrate_json_to_db.php
   ```
   or open `migrate_json_to_db.php` once in the browser. It prints how many users/products/orders/items it imported. It's safe to run more than once — it won't create duplicates.

4. Once you've confirmed everything shows up correctly (browse the storefront, log in, check the admin dashboard), you can delete `migrate_json_to_db.php` and the `data/*.json` files — nothing in the app reads them anymore.

## Why IDs still look like `p_a1b2c3...` instead of `1`, `2`, `3`

The tables use those same short prefixed strings as their primary keys instead of switching to auto-incrementing integers. That was a deliberate choice to keep this a pure storage-layer swap: nothing that already refers to a product/order/user by id anywhere in the codebase (URLs like `product_form.php?id=p_...`, hidden form fields, `place_order.php`, etc.) had to change.

## What's *not* covered by this change

This migration only touches persistence (where the data lives). It doesn't fix the unrelated "remember me" cookie issue in `login.php`, and it doesn't add things like connection pooling, migrations/versioning, or an ORM — for a project this size, plain PDO is the right amount of tooling.
