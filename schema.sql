-- LUNORA — database schema (MySQL / MariaDB, InnoDB)
--
-- Run once:  mysql -u root -p lunora < schema.sql
--
-- IDs are kept as the same short prefixed strings the app already
-- generates (u_..., p_..., o_...) rather than switching to AUTO_INCREMENT,
-- so nothing that references an id elsewhere in the codebase has to change.

CREATE TABLE IF NOT EXISTS users (
    id             VARCHAR(64)  NOT NULL PRIMARY KEY,
    full_name      VARCHAR(190) NOT NULL,
    email          VARCHAR(190) NOT NULL,
    password_hash  VARCHAR(255) NOT NULL,
    role           VARCHAR(20)  NOT NULL DEFAULT 'customer',
    created_at     DATETIME     NOT NULL,
    UNIQUE KEY uniq_users_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS products (
    id             VARCHAR(64)   NOT NULL PRIMARY KEY,
    name           VARCHAR(190)  NOT NULL,
    variant        VARCHAR(120)  NOT NULL DEFAULT '',
    price          DECIMAL(10,2) NOT NULL DEFAULT 0,
    stock          INT           NOT NULL DEFAULT 0,
    image          VARCHAR(255)  NOT NULL DEFAULT '',
    fill           VARCHAR(20)   NOT NULL DEFAULT '#ECE5D6',
    tones          VARCHAR(255)  NOT NULL DEFAULT '',   -- comma-separated tone keys, e.g. "stone,pecan,black"
    badge          VARCHAR(60)   NOT NULL DEFAULT '',
    section        VARCHAR(20)   NOT NULL DEFAULT 'grid', -- 'grid' | 'bestseller'
    category       VARCHAR(120)  NOT NULL DEFAULT '',
    created_at     DATETIME      NOT NULL,
    updated_at     DATETIME      NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS orders (
    id                VARCHAR(64)   NOT NULL PRIMARY KEY,
    user_id           VARCHAR(64)   NULL,
    customer_name     VARCHAR(190)  NOT NULL,
    customer_email    VARCHAR(190)  NOT NULL,
    customer_phone    VARCHAR(60)   NOT NULL,
    customer_address  VARCHAR(255)  NOT NULL,
    payment_method    VARCHAR(20)   NOT NULL DEFAULT 'card',
    subtotal          DECIMAL(10,2) NOT NULL DEFAULT 0,
    shipping          DECIMAL(10,2) NOT NULL DEFAULT 0,
    total             DECIMAL(10,2) NOT NULL DEFAULT 0,
    status            VARCHAR(20)   NOT NULL DEFAULT 'pending',
    carrier           VARCHAR(120)  NOT NULL DEFAULT '',
    tracking_number   VARCHAR(120)  NOT NULL DEFAULT '',
    eta               VARCHAR(60)   NOT NULL DEFAULT '',
    notes             TEXT          NULL,
    status_history    TEXT          NULL,   -- small JSON-encoded audit log: [{status, at}, ...]
    status_seen_at    DATETIME      NULL,   -- when the customer last viewed this order's current status; NULL means "unseen change" (drives the account-icon notification badge)
    created_at        DATETIME      NOT NULL,
    KEY idx_orders_user (user_id),
    KEY idx_orders_status (status),
    CONSTRAINT fk_orders_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS order_items (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    order_id     VARCHAR(64)   NOT NULL,
    product_id   VARCHAR(64)   NULL,
    name         VARCHAR(190)  NOT NULL,
    variant      VARCHAR(120)  NOT NULL DEFAULT '',
    price        DECIMAL(10,2) NOT NULL DEFAULT 0,
    qty          INT           NOT NULL DEFAULT 1,
    image        VARCHAR(255)  NOT NULL DEFAULT '',
    KEY idx_order_items_order (order_id),
    CONSTRAINT fk_order_items_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    CONSTRAINT fk_order_items_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS auth_tokens (
    selector       VARCHAR(24)   NOT NULL PRIMARY KEY,
    validator_hash VARCHAR(255)  NOT NULL,
    user_id        VARCHAR(64)   NOT NULL,
    expires_at     DATETIME      NOT NULL,
    created_at     DATETIME      NOT NULL,
    KEY idx_auth_tokens_user (user_id),
    CONSTRAINT fk_auth_tokens_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    email          VARCHAR(190) NOT NULL,
    created_at     DATETIME     NOT NULL,
    UNIQUE KEY uniq_newsletter_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS contact_messages (
    id             INT AUTO_INCREMENT PRIMARY KEY,
    name           VARCHAR(190) NOT NULL,
    email          VARCHAR(190) NOT NULL,
    subject        VARCHAR(190) NOT NULL DEFAULT '',
    message        TEXT         NOT NULL,
    status         VARCHAR(20)  NOT NULL DEFAULT 'new',
    created_at     DATETIME     NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- One review per (user, product): a customer can only rate a given item
-- once, but a purchase is required (order_id must be a real order of
-- theirs that contains this product) — enforced in reviews.php, not just
-- by this table shape.
CREATE TABLE IF NOT EXISTS reviews (
    id           VARCHAR(64)  NOT NULL PRIMARY KEY,
    product_id   VARCHAR(64)  NOT NULL,
    user_id      VARCHAR(64)  NOT NULL,
    order_id     VARCHAR(64)  NOT NULL,
    rating       TINYINT      NOT NULL,
    comment      TEXT         NULL,
    created_at   DATETIME     NOT NULL,
    UNIQUE KEY uniq_reviews_user_product (user_id, product_id),
    KEY idx_reviews_product (product_id),
    CONSTRAINT fk_reviews_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE,
    CONSTRAINT fk_reviews_user    FOREIGN KEY (user_id)    REFERENCES users(id)    ON DELETE CASCADE,
    CONSTRAINT fk_reviews_order   FOREIGN KEY (order_id)   REFERENCES orders(id)   ON DELETE CASCADE,
    CONSTRAINT chk_reviews_rating CHECK (rating BETWEEN 1 AND 5)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Seed an admin account if you don't run the migration script below.
-- Password is "Lunora@Admin1" — change it after first login.
-- INSERT INTO users (id, full_name, email, password_hash, role, created_at) VALUES
--   ('u_admin001', 'Store Admin', 'admin@lunora.local',
--    '$2y$10$9aJtbJk.aapUnPANKqQBq.JUxBMnT7MjRliJQlPf9zNwav3Jm8JXC',
--    'admin', NOW());
