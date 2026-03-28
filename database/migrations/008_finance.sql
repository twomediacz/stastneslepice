-- Finance: Ostatní náklady (podestýlka, veterina, vybavení...)
CREATE TABLE IF NOT EXISTS expenses (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    expense_date DATE NOT NULL,
    category ENUM('bedding','vet','equipment','other') NOT NULL DEFAULT 'other',
    amount DECIMAL(10,2) NOT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE INDEX idx_expense_date ON expenses (expense_date);

-- Finance: Prodej / darování vajec
CREATE TABLE IF NOT EXISTS egg_transactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    transaction_date DATE NOT NULL,
    type ENUM('sale','gift') NOT NULL DEFAULT 'sale',
    quantity INT UNSIGNED NOT NULL,
    price_total DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    recipient VARCHAR(150) DEFAULT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE INDEX idx_egg_transaction_date ON egg_transactions (transaction_date);

-- Nastavení: cena vejce v obchodě
INSERT INTO settings (setting_key, setting_value) VALUES
    ('egg_market_price', '5.50')
ON DUPLICATE KEY UPDATE setting_value = setting_value;
