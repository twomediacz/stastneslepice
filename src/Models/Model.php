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

    protected static function isSqlite(): bool
    {
        return static::db()->getAttribute(PDO::ATTR_DRIVER_NAME) === 'sqlite';
    }

    protected static function dateRangeCondition(string $column, int $amount, string $unit): array
    {
        if (!static::isSqlite()) {
            return [
                sprintf('%s >= DATE_SUB(CURDATE(), INTERVAL ? %s)', $column, strtoupper($unit)),
                [$amount],
            ];
        }

        $modifier = match (strtolower($unit)) {
            'day' => sprintf('-%d day', $amount),
            'week' => sprintf('-%d day', $amount * 7),
            'month' => sprintf('-%d month', $amount),
            default => throw new \InvalidArgumentException("Unsupported unit: {$unit}"),
        };

        return [
            sprintf("date(%s) >= date('now', ?)", $column),
            [$modifier],
        ];
    }

    protected static function monthBucket(string $column): string
    {
        return static::isSqlite()
            ? sprintf("strftime('%%Y-%%m', %s)", $column)
            : sprintf("DATE_FORMAT(%s, '%%Y-%%m')", $column);
    }

    protected static function weekBucket(string $column): string
    {
        return static::isSqlite()
            ? sprintf("strftime('%%Y-%%W', %s)", $column)
            : sprintf("YEARWEEK(%s, 1)", $column);
    }
}
