-- LUNORA — seed the admin account directly in the database.
--
-- 1. First generate a password hash (see generate-admin-hash.php).
-- 2. Replace YOUR_EMAIL_HERE and PASTE_YOUR_HASH_HERE below.
-- 3. Run this file: in phpMyAdmin, open the SQL tab and paste it in, OR
--    from a terminal:  mysql -u root -p lunora < seed_admin.sql
-- 4. Delete this file afterwards (or at least clear out the hash) so the
--    hash doesn't sit around in your project/repo.

INSERT INTO users (id, full_name, email, phone, profile_image, password_hash, role, created_at)
VALUES (
  'u_admin001',
  'Store Admin',
  'YOUR_EMAIL_HERE',
  '',
  '',
  'PASTE_YOUR_HASH_HERE',
  'admin',
  NOW()
);
