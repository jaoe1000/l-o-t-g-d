<?php
/**
 * Standalone Opaque-Box Test Framework for LotGD Modernization
 * Pure PHP 8.4 - Zero external dependencies.
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 */

declare(strict_types=1);

class AssertionException extends RuntimeException {}

class TestFramework {
    private static array $tests = [];
    private static int $assertionCount = 0;
    private static bool $verbose = false;

    public static function setVerbose(bool $verbose): void {
        self::$verbose = $verbose;
    }

    public static function isVerbose(): bool {
        return self::$verbose;
    }

    public static function incrementAssertions(): void {
        self::$assertionCount++;
    }

    public static function getAssertionCount(): int {
        return self::$assertionCount;
    }

    public static function register(string $tier, string $feature, string $name, callable $callable): void {
        self::$tests[] = [
            'tier' => $tier,
            'feature' => $feature,
            'name' => $name,
            'callable' => $callable,
        ];
    }

    public static function getTests(?string $tierFilter = null, ?string $nameFilter = null): array {
        $filtered = [];
        foreach (self::$tests as $t) {
            if ($tierFilter !== null && strtolower($t['tier']) !== strtolower($tierFilter)) {
                continue;
            }
            if ($nameFilter !== null && stripos($t['name'], $nameFilter) === false && stripos($t['feature'], $nameFilter) === false) {
                continue;
            }
            $filtered[] = $t;
        }
        return $filtered;
    }

    public static function clearTests(): void {
        self::$tests = [];
        self::$assertionCount = 0;
    }
}

/**
 * Register a test case
 */
function test(string $tier, string $feature, string $name, callable $callable): void {
    TestFramework::register($tier, $feature, $name, $callable);
}

// -------------------------------------------------------------
// Assertions
// -------------------------------------------------------------

function assert_true($condition, string $message = ''): void {
    TestFramework::incrementAssertions();
    if ($condition !== true) {
        $msg = $message ?: "Expected true, got " . var_export($condition, true);
        throw new AssertionException($msg);
    }
}

function assert_false($condition, string $message = ''): void {
    TestFramework::incrementAssertions();
    if ($condition !== false) {
        $msg = $message ?: "Expected false, got " . var_export($condition, true);
        throw new AssertionException($msg);
    }
}

function assert_equals($expected, $actual, string $message = ''): void {
    TestFramework::incrementAssertions();
    if ($expected != $actual) {
        $msg = $message ?: "Expected " . var_export($expected, true) . ", got " . var_export($actual, true);
        throw new AssertionException($msg);
    }
}

function assert_same($expected, $actual, string $message = ''): void {
    TestFramework::incrementAssertions();
    if ($expected !== $actual) {
        $msg = $message ?: "Expected (same) " . var_export($expected, true) . ", got " . var_export($actual, true);
        throw new AssertionException($msg);
    }
}

function assert_not_equals($expected, $actual, string $message = ''): void {
    TestFramework::incrementAssertions();
    if ($expected == $actual) {
        $msg = $message ?: "Expected not equal to " . var_export($expected, true);
        throw new AssertionException($msg);
    }
}

function assert_contains(string $needle, string $haystack, string $message = ''): void {
    TestFramework::incrementAssertions();
    if (strpos($haystack, $needle) === false) {
        $preview = strlen($haystack) > 100 ? substr($haystack, 0, 100) . '...' : $haystack;
        $msg = $message ?: "Expected '$needle' in '$preview'";
        throw new AssertionException($msg);
    }
}

function assert_not_contains(string $needle, string $haystack, string $message = ''): void {
    TestFramework::incrementAssertions();
    if (strpos($haystack, $needle) !== false) {
        $msg = $message ?: "Did not expect '$needle' in haystack";
        throw new AssertionException($msg);
    }
}

function assert_matches_regex(string $pattern, string $string, string $message = ''): void {
    TestFramework::incrementAssertions();
    if (!preg_match($pattern, $string)) {
        $msg = $message ?: "Expected string '$string' to match regex '$pattern'";
        throw new AssertionException($msg);
    }
}

function assert_not_matches_regex(string $pattern, string $string, string $message = ''): void {
    TestFramework::incrementAssertions();
    if (preg_match($pattern, $string)) {
        $msg = $message ?: "Did not expect string '$string' to match regex '$pattern'";
        throw new AssertionException($msg);
    }
}

function assert_null($value, string $message = ''): void {
    TestFramework::incrementAssertions();
    if ($value !== null) {
        $msg = $message ?: "Expected null, got " . var_export($value, true);
        throw new AssertionException($msg);
    }
}

function assert_not_null($value, string $message = ''): void {
    TestFramework::incrementAssertions();
    if ($value === null) {
        $msg = $message ?: "Expected non-null value, got null";
        throw new AssertionException($msg);
    }
}

function assert_file_exists(string $path, string $message = ''): void {
    TestFramework::incrementAssertions();
    if (!file_exists($path)) {
        $msg = $message ?: "Expected file to exist: $path";
        throw new AssertionException($msg);
    }
}

function assert_file_not_exists(string $path, string $message = ''): void {
    TestFramework::incrementAssertions();
    if (file_exists($path)) {
        $msg = $message ?: "Expected file NOT to exist: $path";
        throw new AssertionException($msg);
    }
}

function assert_greater_than($expected, $actual, string $message = ''): void {
    TestFramework::incrementAssertions();
    if ($actual <= $expected) {
        $msg = $message ?: "Expected $actual to be greater than $expected";
        throw new AssertionException($msg);
    }
}

function assert_throws(callable $callable, string $expectedClass = 'Throwable', string $message = ''): void {
    TestFramework::incrementAssertions();
    try {
        $callable();
    } catch (\Throwable $e) {
        if ($e instanceof $expectedClass) {
            return;
        }
        throw new AssertionException(
            ($message ? "$message: " : "") . "Expected exception of type $expectedClass, but caught " . get_class($e) . ": " . $e->getMessage()
        );
    }
    throw new AssertionException(($message ? "$message: " : "") . "Expected exception of type $expectedClass was not thrown.");
}
