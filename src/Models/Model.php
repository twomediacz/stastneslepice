<?php

namespace App\Models;

use App\Core\Database;
use PDO;

abstract class Model
{
    protected static string $table;

    protected static function db(): PDO
    {
        return Database::getInstance();
    }

    public static function findAll(string $orderBy = 'id DESC', int $limit = 100): array
    {
        $sql = "SELECT * FROM " . static::$table . " ORDER BY {$orderBy} LIMIT {$limit}";
        return static::db()->query($sql)->fetchAll();
    }

    public static function findById(int $id): ?array
    {
        $stmt = static::db()->prepare("SELECT * FROM " . static::$table . " WHERE id = ?");
        $stmt->execute([$id]);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    public static function insert(array $data): int
    {
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $stmt = static::db()->prepare(
            "INSERT INTO " . static::$table . " ({$columns}) VALUES ({$placeholders})"
        );
        $stmt->execute(array_values($data));
        return (int) static::db()->lastInsertId();
    }

    public static function update(int $id, array $data): bool
    {
        $set = implode(', ', array_map(fn($k) => "{$k} = ?", array_keys($data)));
        $stmt = static::db()->prepare(
            "UPDATE " . static::$table . " SET {$set} WHERE id = ?"
        );
        return $stmt->execute([...array_values($data), $id]);
    }

    public static function delete(int $id): bool
    {
        $stmt = static::db()->prepare("DELETE FROM " . static::$table . " WHERE id = ?");
        return $stmt->execute([$id]);
    }

    protected static function query(string $sql, array $params = []): array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    protected static function queryOne(string $sql, array $params = []): ?array
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        $row = $stmt->fetch();
        return $row ?: null;
    }

    protected static function queryValue(string $sql, array $params = []): mixed
    {
        $stmt = static::db()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchColumn();
    }
}
