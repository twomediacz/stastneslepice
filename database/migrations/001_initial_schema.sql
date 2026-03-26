-- Šťastné slepice – úvodní schéma databáze
-- Spustit proti MariaDB databázi 'stastneslepice'

CREATE DATABASE IF NOT EXISTS stastneslepice
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_czech_ci;

USE stastneslepice;

-- Denní zápis snášky vajec
CREATE TABLE IF NOT EXISTS egg_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    record_date DATE NOT NULL UNIQUE,
    egg_count SMALLINT UNSIGNED NOT NULL DEFAULT 0,
    note VARCHAR(255) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Záznamy teploty a vlhkosti
CREATE TABLE IF NOT EXISTS climate_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recorded_at DATETIME NOT NULL,
    location ENUM('coop', 'outdoor') NOT NULL,
    temperature DECIMAL(4,1) DEFAULT NULL,
    humidity DECIMAL(4,1) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE INDEX idx_climate_recorded ON climate_records (recorded_at, location);

-- Poznámky
CREATE TABLE IF NOT EXISTS notes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    note_date DATE NOT NULL,
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Fotogalerie
CREATE TABLE IF NOT EXISTS photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    filename VARCHAR(255) NOT NULL,
    caption VARCHAR(255) DEFAULT NULL,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Nastavení (počet slepic, plemeno, stáří apod.)
CREATE TABLE IF NOT EXISTS settings (
    setting_key VARCHAR(100) PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Výchozí nastavení
INSERT INTO settings (setting_key, setting_value) VALUES
    ('locale_name', 'Doloplazy');
