-- Šťastné slepice – migrace 012: statistika návštěvnosti úvodní stránky

USE d42462_slepice;

CREATE TABLE IF NOT EXISTS visit_stats (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    page VARCHAR(120) NOT NULL DEFAULT '/',
    visitor_id CHAR(32) NOT NULL,
    visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_czech_ci;

CREATE INDEX idx_visit_stats_page_time ON visit_stats (page, visited_at);
CREATE INDEX idx_visit_stats_page_visitor ON visit_stats (page, visitor_id);
