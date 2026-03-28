-- Šťastné slepice – migrace 003: evidence slepic

USE d42462_slepice;

CREATE TABLE IF NOT EXISTS chickens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    breed VARCHAR(100) DEFAULT NULL,
    color VARCHAR(100) DEFAULT NULL,
    birth_date DATE DEFAULT NULL,
    acquired_date DATE DEFAULT NULL,
    end_date DATE DEFAULT NULL,
    status ENUM('active', 'sick', 'deceased', 'given_away') NOT NULL DEFAULT 'active',
    photo VARCHAR(255) DEFAULT NULL,
    note TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;
