<?php
/**
 * Mock Database Driver for LotGD Opaque-Box Test Isolation
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 */

declare(strict_types=1);

class MockDb {
    private static array $responses = [];
    private static array $executedQueries = [];
    private static array $dataCache = [];
    private static string $prefix = 'lotgd_';
    private static int $lastInsertId = 0;
    private static ?string $lastError = null;

    public static function reset(): void {
        self::$responses = [];
        self::$executedQueries = [];
        self::$dataCache = [];
        self::$prefix = 'lotgd_';
        self::$lastInsertId = 0;
        self::$lastError = null;
    }

    public static function setPrefix(string $prefix): void {
        self::$prefix = $prefix;
    }

    public static function getPrefix(): string {
        return self::$prefix;
    }

    public static function prefix(string $table): string {
        return self::$prefix . $table;
    }

    public static function setResponse(string $sqlPattern, array $rows): void {
        self::$responses[$sqlPattern] = $rows;
    }

    public static function getExecutedQueries(): array {
        return self::$executedQueries;
    }

    public static function query(string $sql): array {
        self::$executedQueries[] = $sql;
        self::$lastError = null;

        foreach (self::$responses as $pattern => $rows) {
            if (stripos($sql, $pattern) !== false || (str_starts_with($pattern, '/') && preg_match($pattern, $sql))) {
                return $rows;
            }
        }
        return [];
    }

    /**
     * Return-by-value cached query execution.
     * Note: Does NOT return by reference (&array), preventing caller array_shift
     * from mutating the cache in self::$dataCache.
     */
    public static function queryCached(string $sql, string $name, int $duration = 900): array {
        self::$executedQueries[] = "[CACHED: $name] " . $sql;

        if (isset(self::$dataCache[$name])) {
            return self::$dataCache[$name];
        }

        $result = self::query($sql);
        self::$dataCache[$name] = $result;
        return $result;
    }

    public static function getCachedData(string $name): ?array {
        return self::$dataCache[$name] ?? null;
    }

    public static function clearCache(?string $name = null): void {
        if ($name === null) {
            self::$dataCache = [];
        } else {
            unset(self::$dataCache[$name]);
        }
    }

    public static function fetchAssoc(array &$result): ?array {
        if (empty($result)) {
            return null;
        }
        return array_shift($result);
    }

    public static function numRows(array $result): int {
        return count($result);
    }

    public static function setLastInsertId(int $id): void {
        self::$lastInsertId = $id;
    }

    public static function insertId(): int {
        return self::$lastInsertId;
    }

    public static function setLastError(?string $error): void {
        self::$lastError = $error;
    }

    public static function getLastError(): ?string {
        return self::$lastError;
    }
}
