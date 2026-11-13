CREATE TABLE orders (
    id BIGINT UNSIGNED PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    status VARCHAR(32) NOT NULL,
    created_at DATETIME NOT NULL,
    paid_at DATETIME NULL
);

CREATE TABLE order_items (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id BIGINT UNSIGNED NOT NULL,
    product_id BIGINT UNSIGNED NOT NULL,
    product_name VARCHAR(255) NOT NULL,
    unit_price_minor BIGINT UNSIGNED NOT NULL,
    currency CHAR(3) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    CONSTRAINT fk_order_items_order
        FOREIGN KEY (order_id) REFERENCES orders(id)
        ON DELETE CASCADE
);
