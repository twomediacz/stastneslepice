-- Krmení: Typy krmiva
CREATE TABLE IF NOT EXISTS feed_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(150) NOT NULL,
    price_per_kg DECIMAL(8,2) NOT NULL DEFAULT 0.00,
    palatability TINYINT UNSIGNED DEFAULT NULL COMMENT '1-5 hodnoceni chutnosti',
    note TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

-- Krmení: Záznamy krmení
CREATE TABLE IF NOT EXISTS feeding_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feed_type_id INT UNSIGNED NOT NULL,
    record_date DATE NOT NULL,
    amount_kg DECIMAL(6,2) NOT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_feeding_feed_type FOREIGN KEY (feed_type_id) REFERENCES feed_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE INDEX idx_feeding_date ON feeding_records (record_date);
CREATE INDEX idx_feeding_type_date ON feeding_records (feed_type_id, record_date);

-- Krmení: Nákupy krmiva
CREATE TABLE IF NOT EXISTS feed_purchases (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    feed_type_id INT UNSIGNED NOT NULL,
    purchased_at DATE NOT NULL,
    quantity_kg DECIMAL(8,2) NOT NULL,
    total_price DECIMAL(10,2) NOT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_purchase_feed_type FOREIGN KEY (feed_type_id) REFERENCES feed_types(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;
