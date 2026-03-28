-- Texty: vtipy, rady a další typy textů
CREATE TABLE IF NOT EXISTS text_snippets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    type ENUM('joke', 'tip') NOT NULL DEFAULT 'joke',
    content TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;
