<?php
/**
 * Automated CLI Test Runner for LotGD Modernization Project
 * Executes Tiers 1-4 opaque-box tests and static compliance audits.
 *
 * Usage:
 *   php tests/run_tests.php [options]
 *
 * Options:
 *   --tier=<1|2|3|4|5|audit> Run only tests from specified tier
 *   --filter=<pattern>       Run tests matching feature or test name
 *   --verbose, -v            Enable verbose assertion output
 *   --help, -h               Display this help message
 *
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 * Modified by jaoe1000
 */

declare(strict_types=1);

require_once __DIR__ . '/test_framework.php';

// Parse command line arguments
$options = getopt("vht:", ["tier:", "filter:", "verbose", "help"]);

if (isset($options['h']) || isset($options['help'])) {
    echo <<<HELP
LotGD Modernization Test Runner (PHP 8.4)
=========================================
Usage: php tests/run_tests.php [options]

Options:
  --tier=<1|2|3|4|5|audit> Run only tests from specified tier
  --filter=<pattern>      Filter tests by name or feature
  --verbose, -v           Verbose diagnostic output
  --help, -h              Show this help

HELP;
    exit(0);
}

$tierFilter = $options['tier'] ?? null;
if ($tierFilter !== null) {
    if (is_numeric($tierFilter)) {
        $tierFilter = 'tier' . $tierFilter;
    }
}

$nameFilter = $options['filter'] ?? null;
$verbose = isset($options['v']) || isset($options['verbose']);
TestFramework::setVerbose($verbose);

// Load all test suites
require_once __DIR__ . '/tier1/test_security_auth.php';
require_once __DIR__ . '/tier1/test_core_caching.php';
require_once __DIR__ . '/tier1/test_root_modernization.php';

require_once __DIR__ . '/tier2/test_boundary_security.php';
require_once __DIR__ . '/tier2/test_boundary_caching.php';
require_once __DIR__ . '/tier2/test_boundary_modernization.php';

require_once __DIR__ . '/tier3/test_combinations.php';
require_once __DIR__ . '/tier4/test_scenarios.php';
require_once __DIR__ . '/audit/test_static_audit.php';
if (file_exists(__DIR__ . '/tier5/test_adversarial_m1.php')) {
    require_once __DIR__ . '/tier5/test_adversarial_m1.php';
}
if (file_exists(__DIR__ . '/tier5/test_adversarial_m2.php')) {
    require_once __DIR__ . '/tier5/test_adversarial_m2.php';
}
if (file_exists(__DIR__ . '/tier5/test_adversarial_m3.php')) {
    require_once __DIR__ . '/tier5/test_adversarial_m3.php';
}

$testsToRun = TestFramework::getTests($tierFilter, $nameFilter);
$totalTests = count($testsToRun);

echo "\n=======================================================\n";
echo " LotGD Modernization Opaque-Box Test Suite\n";
echo " Runtime: PHP " . PHP_VERSION . " on " . PHP_OS_FAMILY . "\n";
echo " Filter:  " . ($tierFilter ? "Tier: $tierFilter " : "All Tiers ") . ($nameFilter ? "| Name: '$nameFilter'" : "") . "\n";
echo " Total Tests Registered: $totalTests\n";
echo "=======================================================\n\n";

if ($totalTests === 0) {
    echo "No tests matched the requested filter criteria.\n";
    exit(0);
}

$passed = 0;
$failed = 0;
$errors = 0;
$failures = [];

$startTime = microtime(true);
$currentTier = '';

foreach ($testsToRun as $index => $t) {
    $tier = $t['tier'];
    $feature = $t['feature'];
    $name = $t['name'];
    $callable = $t['callable'];

    if ($tier !== $currentTier) {
        $currentTier = $tier;
        echo "\n[" . strtoupper($tier) . "]\n";
    }

    try {
        $callable();
        $passed++;
        if ($verbose) {
            echo "  ✓ PASS: [$feature] $name\n";
        } else {
            echo ".";
            if (($passed + $failed + $errors) % 50 === 0) {
                echo " (" . ($passed + $failed + $errors) . "/$totalTests)\n";
            }
        }
    } catch (AssertionException $e) {
        $failed++;
        $failures[] = [
            'type' => 'FAIL',
            'tier' => $tier,
            'feature' => $feature,
            'name' => $name,
            'message' => $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
        if ($verbose) {
            echo "  ✗ FAIL: [$feature] $name\n     " . $e->getMessage() . "\n";
        } else {
            echo "F";
        }
    } catch (\Throwable $e) {
        $errors++;
        $failures[] = [
            'type' => 'ERROR',
            'tier' => $tier,
            'feature' => $feature,
            'name' => $name,
            'message' => get_class($e) . ': ' . $e->getMessage(),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
        ];
        if ($verbose) {
            echo "  ! ERROR: [$feature] $name\n     " . get_class($e) . ': ' . $e->getMessage() . "\n";
        } else {
            echo "E";
        }
    }
}

$duration = round(microtime(true) - $startTime, 3);
$totalAssertions = TestFramework::getAssertionCount();

echo "\n\n=======================================================\n";
echo " Test Run Summary\n";
echo "=======================================================\n";
echo " Time Elapsed:   {$duration}s\n";
echo " Total Tests:    $totalTests\n";
echo " Assertions:     $totalAssertions\n";
echo " Passed:         $passed\n";
echo " Failed:         $failed\n";
echo " Errors:         $errors\n";
echo "=======================================================\n";

if (!empty($failures)) {
    echo "\nFailures and Errors (" . count($failures) . "):\n";
    echo "-------------------------------------------------------\n";
    foreach ($failures as $i => $f) {
        $num = $i + 1;
        echo "{$num}) [{$f['type']}] [{$f['tier']}] [{$f['feature']}] {$f['name']}\n";
        echo "   Message: {$f['message']}\n";
        echo "   Location: {$f['file']}:{$f['line']}\n\n";
    }
    echo "Status: TEST SUITE FAILED\n";
    exit(1);
}

echo "\nStatus: ALL TESTS PASSED SUCCESSFULLY (100%)\n";
exit(0);
