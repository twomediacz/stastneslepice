<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Core\Database;
use PDO;

final class TestDatabase
{
    public static function createFresh(?string $path = null): string
    {
        $path ??= tempnam(sys_get_temp_dir(), 'stastneslepice-test-');

        if ($path === false) {
            throw new \RuntimeException('Nepodařilo se vytvořit testovací databázi.');
        }

        if (file_exists($path)) {
            unlink($path);
        }

        self::connect($path);
        self::migrate(Database::getInstance());
        self::seedDefaults(Database::getInstance());

        return $path;
    }

    public static function connect(string $path): PDO
    {
        Database::disconnect();

        return Database::connect([
            'driver' => 'sqlite',
            'database' => $path,
        ]);
    }

    public static function destroy(?string $path): void
    {
        Database::disconnect();

        if ($path !== null && $path !== '' && file_exists($path)) {
            unlink($path);
        }
    }

    private static function migrate(PDO $pdo): void
    {
        $pdo->exec(<<<'SQL'
CREATE TABLE settings (
    setting_key TEXT PRIMARY KEY,
    setting_value TEXT NOT NULL,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE egg_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    record_date TEXT NOT NULL UNIQUE,
    egg_count INTEGER NOT NULL DEFAULT 0,
    note TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE climate_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    recorded_at TEXT NOT NULL,
    location TEXT NOT NULL,
    temperature REAL DEFAULT NULL,
    humidity REAL DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE notes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    note_date TEXT NOT NULL,
    content TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE photos (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    filename TEXT NOT NULL,
    caption TEXT DEFAULT NULL,
    uploaded_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    email TEXT DEFAULT NULL,
    password_hash TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'admin',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE chickens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    breed TEXT DEFAULT NULL,
    color TEXT DEFAULT NULL,
    birth_date TEXT DEFAULT NULL,
    acquired_date TEXT DEFAULT NULL,
    end_date TEXT DEFAULT NULL,
    status TEXT NOT NULL DEFAULT 'active',
    photo TEXT DEFAULT NULL,
    note TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE bedding_changes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    changed_at TEXT NOT NULL,
    note TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE repairs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    repaired_at TEXT NOT NULL,
    note TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE feed_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    price_per_kg REAL NOT NULL DEFAULT 0,
    palatability INTEGER DEFAULT NULL,
    note TEXT DEFAULT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE feeding_records (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    feed_type_id INTEGER NOT NULL,
    record_date TEXT NOT NULL,
    amount_kg REAL NOT NULL,
    note TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (feed_type_id) REFERENCES feed_types(id) ON DELETE CASCADE
);

CREATE TABLE feed_purchases (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    feed_type_id INTEGER NOT NULL,
    purchased_at TEXT NOT NULL,
    quantity_kg REAL NOT NULL,
    total_price REAL NOT NULL,
    note TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (feed_type_id) REFERENCES feed_types(id) ON DELETE CASCADE
);

CREATE TABLE expenses (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    expense_date TEXT NOT NULL,
    category TEXT NOT NULL DEFAULT 'other',
    amount REAL NOT NULL,
    note TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE egg_transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    transaction_date TEXT NOT NULL,
    type TEXT NOT NULL DEFAULT 'sale',
    quantity INTEGER NOT NULL,
    price_total REAL NOT NULL DEFAULT 0,
    recipient TEXT DEFAULT NULL,
    note TEXT DEFAULT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE text_snippets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    type TEXT NOT NULL DEFAULT 'joke',
    content TEXT NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE INDEX idx_climate_recorded ON climate_records (recorded_at, location);
CREATE INDEX idx_feeding_date ON feeding_records (record_date);
CREATE INDEX idx_feeding_type_date ON feeding_records (feed_type_id, record_date);
CREATE INDEX idx_expense_date ON expenses (expense_date);
CREATE INDEX idx_egg_transaction_date ON egg_transactions (transaction_date);
SQL);
    }

    private static function seedDefaults(PDO $pdo): void
    {
        $stmt = $pdo->prepare('INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)');

        foreach ([
            ['locale_name', 'Doloplazy'],
            ['youtube_livestream_url', ''],
            ['youtube_livestream_url_2', ''],
            ['weather_api_key', 'secret-weather'],
            ['climate_api_token', 'secret-climate'],
            ['egg_market_price', '5.50'],
            ['bedding_interval_days', '14'],
        ] as $row) {
            $stmt->execute($row);
        }
    }
}
