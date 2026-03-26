-- Šťastné slepice – migrace 002: uživatelé a rozšíření nastavení

USE d42462_slepice;

-- Tabulka uživatelů
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(255) DEFAULT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('admin', 'viewer') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Rozšíření nastavení
INSERT IGNORE INTO settings (setting_key, setting_value) VALUES
    ('youtube_livestream_url', ''),
    ('weather_api_key', '');
