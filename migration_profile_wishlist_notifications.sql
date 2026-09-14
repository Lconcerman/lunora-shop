-- LUNORA — migration: profile fields + wishlist + notifications
-- Run once against your EXISTING database:
--   mysql -u root -p lunora < migration_profile_wishlist_notifications.sql
--
-- Safe to run once. If you see "Duplicate column" or "already exists" errors,
-- that piece has already been applied — just ignore that one line.

ALTER TABLE users ADD COLUMN phone VARCHAR(60) NOT NULL DEFAULT '' AFTER email;
ALTER TABLE users ADD COLUMN profile_image VARCHAR(255) NOT NULL DEFAULT '' AFTER phone;

CREATE TABLE IF NOT EXISTS wishlist_items (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(64) NOT NULL,
  product_id VARCHAR(64) NOT NULL,
  created_at DATETIME NOT NULL,
  UNIQUE KEY uniq_wishlist_user_product (user_id, product_id),
  KEY idx_wishlist_user (user_id),
  CONSTRAINT fk_wishlist_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  CONSTRAINT fk_wishlist_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS notifications (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id VARCHAR(64) NOT NULL,
  type VARCHAR(40) NOT NULL DEFAULT 'general',
  title VARCHAR(190) NOT NULL,
  message VARCHAR(255) NOT NULL DEFAULT '',
  link VARCHAR(255) NOT NULL DEFAULT '',
  is_read TINYINT(1) NOT NULL DEFAULT 0,
  created_at DATETIME NOT NULL,
  KEY idx_notifications_user (user_id),
  CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
