-- LUNORA — migration: product reviews
-- Run once against your EXISTING database, e.g. via phpMyAdmin's Import tab,
-- or:  mysql -u root -p lunora < migration_reviews.sql
--
-- Safe to run even if the table already exists (CREATE TABLE IF NOT EXISTS).

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
