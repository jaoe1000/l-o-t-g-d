<?php
/**
 * Static Audit & PHP Syntax Lint Runner
 * Checks syntax across all PHP files and verifies architectural constraints.
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';

/**
 * Helper to recursively gather all PHP files in directories
 */
function get_all_php_files(array $dirs): array {
    $files = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $item) {
            if ($item->isFile() && strtolower($item->getExtension()) === 'php') {
                $files[] = $item->getPathname();
            }
        }
    }
    return $files;
}

// =========================================================================
// Audit Check 1: PHP Syntax Lint Across Codebase
// =========================================================================

test('audit', 'syntax_lint', 'audit_php_syntax_lint_all_files', function() {
    $root = TestEnvironment::getRootDir();
    $targetDirs = [
        $root . '/lib',
        $root . '/modules',
        $root . '/modules_quarantine',
    ];
    $rootFiles = glob($root . '/*.php') ?: [];
    $allFiles = array_merge($rootFiles, get_all_php_files($targetDirs));

    assert_greater_than(200, count($allFiles), "Expected at least 200 PHP files in project");

    $syntaxErrors = [];
    foreach ($allFiles as $filePath) {
        $code = file_get_contents($filePath);
        if ($code === false) {
            $syntaxErrors[] = "$filePath: could not read file";
            continue;
        }

        try {
            // PHP 8 token_get_all with TOKEN_PARSE throws ParseError on invalid syntax
            token_get_all($code, TOKEN_PARSE);
        } catch (\ParseError $e) {
            $syntaxErrors[] = "$filePath: " . $e->getMessage() . " on line " . $e->getLine();
        }
    }

    $errorCount = count($syntaxErrors);
    $errorMsg = $errorCount > 0 ? "Syntax errors detected in $errorCount files:\n" . implode("\n", array_slice($syntaxErrors, 0, 10)) : "";
    assert_same(0, $errorCount, $errorMsg);
});

// =========================================================================
// Audit Check 2: Zero Occurrences of $HTTP_*_VARS
// =========================================================================

test('audit', 'superglobals', 'audit_zero_occurrences_of_HTTP_VARS', function() {
    $root = TestEnvironment::getRootDir();
    $targetDirs = [
        $root . '/lib',
        $root . '/modules',
        $root . '/modules_quarantine',
    ];
    $rootFiles = glob($root . '/*.php') ?: [];
    $allFiles = array_merge($rootFiles, get_all_php_files($targetDirs));

    $violations = [];
    foreach ($allFiles as $file) {
        $content = file_get_contents($file) ?: '';
        if (preg_match('/\$HTTP_(GET|POST|COOKIE|SERVER|ENV|SESSION)_VARS\b/', $content, $m)) {
            $violations[] = "$file contains {$m[0]}";
        }
    }

    $count = count($violations);
    $msg = $count > 0 ? "Found $count files with deprecated \$HTTP_*_VARS:\n" . implode("\n", $violations) : "";
    assert_same(0, $count, $msg);
});

// =========================================================================
// Audit Check 3: Zero Occurrences of each()
// =========================================================================

test('audit', 'deprecated_functions', 'audit_zero_occurrences_of_each', function() {
    $root = TestEnvironment::getRootDir();
    $targetDirs = [
        $root . '/lib',
        $root . '/modules',
        $root . '/modules_quarantine',
    ];
    $rootFiles = glob($root . '/*.php') ?: [];
    $allFiles = array_merge($rootFiles, get_all_php_files($targetDirs));

    $violations = [];
    foreach ($allFiles as $file) {
        $content = file_get_contents($file) ?: '';
        if (preg_match('/\beach\s*\(/i', $content)) {
            $violations[] = $file;
        }
    }

    $count = count($violations);
    $msg = $count > 0 ? "Found $count files calling removed each() function:\n" . implode("\n", array_slice($violations, 0, 10)) : "";
    assert_same(0, $count, $msg);
});

// =========================================================================
// Audit Check 4: Zero Occurrences of ereg*()
// =========================================================================

test('audit', 'deprecated_functions', 'audit_zero_occurrences_of_ereg', function() {
    $root = TestEnvironment::getRootDir();
    $targetDirs = [
        $root . '/lib',
        $root . '/modules',
        $root . '/modules_quarantine',
    ];
    $rootFiles = glob($root . '/*.php') ?: [];
    $allFiles = array_merge($rootFiles, get_all_php_files($targetDirs));

    $violations = [];
    foreach ($allFiles as $file) {
        $content = file_get_contents($file) ?: '';
        if (preg_match('/\b(ereg|eregi|ereg_replace|eregi_replace)\s*\(/i', $content, $m)) {
            $violations[] = "$file calls {$m[0]}";
        }
    }

    $count = count($violations);
    $msg = $count > 0 ? "Found $count files calling removed ereg*() function:\n" . implode("\n", $violations) : "";
    assert_same(0, $count, $msg);
});

// =========================================================================
// Audit Check 5: Zero Occurrences of mysql_*()
// =========================================================================

test('audit', 'deprecated_functions', 'audit_zero_occurrences_of_mysql_functions', function() {
    $root = TestEnvironment::getRootDir();
    $targetDirs = [
        $root . '/modules',
        $root . '/modules_quarantine',
    ];
    $allFiles = get_all_php_files($targetDirs);

    $violations = [];
    foreach ($allFiles as $file) {
        $content = file_get_contents($file) ?: '';
        if (preg_match('/\bmysql_(query|fetch_assoc|fetch_array|num_rows|connect|select_db|error)\s*\(/i', $content, $m)) {
            $violations[] = "$file calls {$m[0]}";
        }
    }

    $count = count($violations);
    $msg = $count > 0 ? "Found $count files in modules calling unabstracted mysql_*():\n" . implode("\n", $violations) : "";
    assert_same(0, $count, $msg);
});

// =========================================================================
// Audit Check 6: Zero Stray Files in Repository
// =========================================================================

test('audit', 'stray_files', 'audit_zero_stray_files_in_repository', function() {
    $root = TestEnvironment::getRootDir();
    $forbiddenFiles = [
        $root . '/pull.php',
        $root . '/test_mtime.php',
        $root . '/racehuman.php',
        $root . '/modules/clanbuffs/a',
        $root . '/modules/dwellings/images/Thumbs.db',
    ];

    $found = [];
    foreach ($forbiddenFiles as $f) {
        if (file_exists($f)) {
            $found[] = $f;
        }
    }

    $count = count($found);
    $msg = $count > 0 ? "Stray files present in repository:\n" . implode("\n", $found) : "";
    assert_same(0, $count, $msg);
});

// =========================================================================
// Audit Check 7: Creative Commons License Notice Preserved
// =========================================================================

test('audit', 'licensing', 'audit_cc_by_nc_sa_license_present', function() {
    $root = TestEnvironment::getRootDir();
    $licenseFile = $root . '/LICENSE.txt';
    assert_file_exists($licenseFile, "LICENSE.txt must be present at repository root");

    $licenseContent = file_get_contents($licenseFile) ?: '';
    assert_contains('Creative Commons', $licenseContent);
    assert_contains('Attribution-NonCommercial-ShareAlike', $licenseContent);

    $commonFile = $root . '/common.php';
    $commonContent = file_get_contents($commonFile) ?: '';
    assert_contains('Creative Commons Attribution-NonCommercial-ShareAlike', $commonContent);
});

// =========================================================================
// Audit Check 8: Modified by jaoe1000 Attribution Present
// =========================================================================

test('audit', 'attribution', 'audit_attribution_modified_by_jaoe1000_present', function() {
    $root = TestEnvironment::getRootDir();
    $commonFile = $root . '/common.php';
    $commonContent = file_get_contents($commonFile) ?: '';
    assert_contains('modified by jaoe1000', $commonContent, "common.php must have modified by jaoe1000 attribution");

    $sessionFile = $root . '/lib/session.php';
    $sessionContent = file_get_contents($sessionFile) ?: '';
    assert_contains('modified by jaoe1000', $sessionContent, "lib/session.php must have modified by jaoe1000 attribution");
});
