LUNORA — Profile / Wishlist / Notifications / Best Sellers
=============================================================

This is the FULL feature: the account dropdown panel (person icon), the
Profile page (edit info + upload photo), a real per-account Wishlist,
real Notifications (auto-created when an order's status changes), a
sticky "Best Seller" card on every account page, and a dedicated Best
Sellers page. It also includes the earlier order-photo/layout fixes.

STEP 1 — Database migration (do this first)
---------------------------------------------
Run this once against your existing database:

    mysql -u root -p lunora < migration_profile_wishlist_notifications.sql

This adds `phone` and `profile_image` to `users`, and creates two new
tables: `wishlist_items` and `notifications`. If any single line errors
with "duplicate column" or "already exists", that part was already
applied — ignore just that line.

STEP 2 — Copy files into your project
---------------------------------------
Copy everything in this zip into C:\xampp\htdocs\Lunora.com, keeping the
folder structure (the `includes/` files go into your existing includes
folder). These files are safe to overwrite — they're either brand new,
or my edited version of a file you already have:

  NEW files:
    profile.php
    my-wishlist.php
    my-notifications.php
    best-sellers.php
    wishlist.php                     (data layer)
    wishlist_toggle.php               (AJAX endpoint)
    notifications.php                 (data layer)
    includes/account_panel.php
    includes/best_seller_sidebar.php
    migration_profile_wishlist_notifications.sql

  REPLACE your existing copy of:
    auth.php            (added profile update + photo functions)
    orders.php           (now creates a notification on status change)
    index.php             (account panel + wishlist server-sync globals)
    script.js              (account panel toggle + wishlist server-sync)
    style.css                (all new CSS: panel, profile, wishlist,
                               notifications, sticky best-seller card,
                               plus the order-card fixes from before)
    my-orders.php              (now sits in the two-column account layout
                               with the sticky Best Seller card beside it)
    includes/site_header.php    (account icon now opens the dropdown panel)
    schema.sql                   (kept in sync with the migration, for
                               anyone setting up the DB from scratch)

STEP 3 — One thing to check
-----------------------------
Your `uploads/avatars/` folder gets created automatically the first time
someone uploads a profile photo — no action needed, just make sure
C:\xampp\htdocs\Lunora.com is writable (it is, by default, on XAMPP).

STEP 4 — Commit and push
---------------------------
    cd C:\xampp\htdocs\Lunora.com
    git add auth.php orders.php index.php script.js style.css my-orders.php ^
        schema.sql includes/site_header.php profile.php my-wishlist.php ^
        my-notifications.php best-sellers.php wishlist.php wishlist_toggle.php ^
        notifications.php includes/account_panel.php includes/best_seller_sidebar.php ^
        migration_profile_wishlist_notifications.sql
    git commit -m "Add profile, wishlist, notifications, account panel, and Best Sellers page"
    git push origin main

(The ^ line continuations are for Windows PowerShell/cmd — if that gives
you trouble, just run `git add .` instead, then commit/push the same way.)

STEP 5 — Test it
-------------------
1. Log in, click the person icon top-right — the new dropdown panel
   should show your name, email, and links to My Orders / Profile /
   Notification / Wishlist.
2. Go to Profile — update your name/phone, and try uploading a photo.
3. Go to My Orders / Profile / Wishlist / Notifications — you should see
   the sticky "Best Seller" card on the right that stays in view as you
   scroll.
4. Click Best Sellers in that card — it should show the full grid.
5. Hard-refresh (Ctrl+F5) if styles look off — same caching thing as
   before.
