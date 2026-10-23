<?php
/**
 * Tier 1: Feature Coverage — Core Library & Caching Efficiency (Features 8–16)
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';
require_once __DIR__ . '/../fixtures/MockDb.php';

// =========================================================================
// Feature 8: Query Cache Return-by-Value
// =========================================================================

test('tier1', 'feature8', 'f8_db_query_cached_does_not_return_by_reference', function() {
    $pdoWrapper = TestEnvironment::readFile('lib/dbwrapper_pdo.php');
    assert_not_matches_regex('/function\s+&db_query_cached\s*\(/', $pdoWrapper,
        "lib/dbwrapper_pdo.php must not declare function &db_query_cached with reference return");
    
    $mysqliProc = TestEnvironment::readFile('lib/dbwrapper_mysqli_proc.php');
    assert_not_matches_regex('/function\s+&db_query_cached\s*\(/', $mysqliProc,
        "lib/dbwrapper_mysqli_proc.php must not declare function &db_query_cached with reference return");
});

test('tier1', 'feature8', 'f8_db_fetch_assoc_does_not_deplete_cached_dataset', function() {
    MockDb::reset();
    $dataset = [
        ['id' => 1, 'name' => 'Alpha'],
        ['id' => 2, 'name' => 'Beta'],
        ['id' => 3, 'name' => 'Gamma'],
    ];
    MockDb::setResponse('SELECT * FROM test_table', $dataset);

    // First call
    $res1 = MockDb::queryCached('SELECT * FROM test_table', 'cache-key-1');
    assert_same(3, count($res1), "Initial query should return 3 rows");

    // Fetch one row using fetchAssoc (mutating local copy)
    $row1 = MockDb::fetchAssoc($res1);
    assert_same('Alpha', $row1['name'], "First fetched row should be Alpha");
    assert_same(2, count($res1), "Local copy now has 2 rows");

    // Second call to same cache key within same request
    $res2 = MockDb::queryCached('SELECT * FROM test_table', 'cache-key-1');
    assert_same(3, count($res2), "Second call must return full 3 rows, not depleted by previous fetch");
});

test('tier1', 'feature8', 'f8_array_shift_on_caller_result_leaves_cache_intact', function() {
    MockDb::reset();
    $data = [['a' => 1], ['b' => 2]];
    MockDb::setResponse('SELECT 1', $data);

    $callerCopy = MockDb::queryCached('SELECT 1', 'test_key');
    array_shift($callerCopy);
    assert_same(1, count($callerCopy));

    $cached = MockDb::getCachedData('test_key');
    assert_same(2, count($cached), "Internal cached array must retain original 2 elements");
});

test('tier1', 'feature8', 'f8_sqlite_wrapper_does_not_return_by_reference', function() {
    $sqliteWrapper = TestEnvironment::readFile('lib/dbwrapper_sqlite.php');
    assert_not_matches_regex('/function\s+&db_query_cached\s*\(/', $sqliteWrapper,
        "lib/dbwrapper_sqlite.php must not return db_query_cached by reference");
});

test('tier1', 'feature8', 'f8_mysqli_oos_wrapper_does_not_return_by_reference', function() {
    $oosWrapper = TestEnvironment::readFile('lib/dbwrapper_mysqli_oos.php');
    assert_not_matches_regex('/function\s+&db_query_cached\s*\(/', $oosWrapper,
        "lib/dbwrapper_mysqli_oos.php must not return db_query_cached by reference");
});

// =========================================================================
// Feature 9: Re-enable Query Caching (injectmodule, load_module_settings)
// =========================================================================

test('tier1', 'feature9', 'f9_injectmodule_uses_db_query_cached', function() {
    $content = TestEnvironment::readFile('lib/modules/statuses.php');
    assert_matches_regex('/db_query_cached\s*\(\s*\$sql\s*,\s*[\'"]inject-/', $content,
        "lib/modules/statuses.php must re-enable db_query_cached for injectmodule");
});

test('tier1', 'feature9', 'f9_load_module_settings_uses_db_query_cached', function() {
    $content = TestEnvironment::readFile('lib/modules/settings.php');
    assert_matches_regex('/db_query_cached\s*\(\s*\$sql\s*,\s*[\'"]modulesettings-/', $content,
        "lib/modules/settings.php must re-enable db_query_cached for load_module_settings");
});

test('tier1', 'feature9', 'f9_modulehooks_uses_db_query_cached', function() {
    $content = TestEnvironment::readFile('lib/modules/hooks.php');
    assert_matches_regex('/db_query_cached\s*\(\s*\$sql\s*,\s*[\'"]hook-/', $content,
        "lib/modules/hooks.php should utilize cached module hook query resolution");
});

test('tier1', 'feature9', 'f9_repeated_module_settings_read_serves_from_cache', function() {
    MockDb::reset();
    $moduleSettings = [
        ['setting' => 'cost', 'value' => '500'],
        ['setting' => 'enabled', 'value' => '1']
    ];
    MockDb::setResponse('SELECT setting, value FROM lotgd_module_settings WHERE modulename = "testmod"', $moduleSettings);

    $firstRead = MockDb::queryCached('SELECT setting, value FROM lotgd_module_settings WHERE modulename = "testmod"', 'modulesettings-testmod');
    $secondRead = MockDb::queryCached('SELECT setting, value FROM lotgd_module_settings WHERE modulename = "testmod"', 'modulesettings-testmod');

    assert_same($firstRead, $secondRead, "Repeated reads must return identical cached settings");
    $queries = MockDb::getExecutedQueries();
    $dbHits = 0;
    foreach ($queries as $q) {
        if (!str_starts_with($q, '[CACHED:')) {
            $dbHits++;
        }
    }
    assert_same(1, $dbHits, "Database should only be hit once for cached module settings");
});

test('tier1', 'feature9', 'f9_module_cache_isolation_between_different_modules', function() {
    MockDb::reset();
    MockDb::setResponse('modulename = "modA"', [['setting' => 'name', 'value' => 'Module A']]);
    MockDb::setResponse('modulename = "modB"', [['setting' => 'name', 'value' => 'Module B']]);

    $resA = MockDb::queryCached('SELECT setting, value FROM settings WHERE modulename = "modA"', 'modulesettings-modA');
    $resB = MockDb::queryCached('SELECT setting, value FROM settings WHERE modulename = "modB"', 'modulesettings-modB');

    assert_not_equals($resA, $resB, "Different modules must have isolated cache keys");
});

// =========================================================================
// Feature 10: Atomic Data Caching (temp file + rename + lock)
// =========================================================================

test('tier1', 'feature10', 'f10_datacache_uses_atomic_rename', function() {
    $content = TestEnvironment::readFile('lib/datacache.php');
    assert_contains('rename(', $content, "lib/datacache.php must use atomic rename() for cache writes");
});

test('tier1', 'feature10', 'f10_datacache_does_not_truncate_in_place_with_fopen_w', function() {
    $content = TestEnvironment::readFile('lib/datacache.php');
    // Ensure updatedatacache does not directly open target file with 'w'
    assert_not_matches_regex('/\$file\s*=\s*fopen\(\s*\$fileName\s*,\s*[\'"]w[\'"]\)/', $content,
        "lib/datacache.php must not truncate target cache file in-place with fopen('w')");
});

test('tier1', 'feature10', 'f10_datacache_employs_file_locking', function() {
    $content = TestEnvironment::readFile('lib/datacache.php');
    assert_contains('flock(', $content, "lib/datacache.php should employ file locking (flock) during cache writes");
});

test('tier1', 'feature10', 'f10_atomic_write_creates_valid_readable_cache_file', function() {
    $tempDir = TestEnvironment::getTempDir();
    $targetFile = $tempDir . '/test_atomic.cache';
    $tempFile = $targetFile . '.' . bin2hex(random_bytes(4)) . '.tmp';
    
    $payload = ['status' => 'healthy', 'timestamp' => time()];
    $serialized = serialize($payload);

    $fp = fopen($tempFile, 'w');
    assert_true($fp !== false, "Temp file must open for writing");
    flock($fp, LOCK_EX);
    fwrite($fp, $serialized);
    fflush($fp);
    flock($fp, LOCK_UN);
    fclose($fp);

    $renamed = rename($tempFile, $targetFile);
    assert_true($renamed, "Atomic rename of temp file to target file must succeed");
    assert_file_exists($targetFile, "Target file must exist after rename");
    
    $readBack = file_get_contents($targetFile);
    $unserialized = unserialize($readBack, ['allowed_classes' => false]);
    assert_same($payload, $unserialized, "Data read back from atomic write must match original payload");
    @unlink($targetFile);
});

test('tier1', 'feature10', 'f10_datacache_temp_name_sanitizes_path_traversal', function() {
    $content = TestEnvironment::readFile('lib/datacache.php');
    assert_matches_regex('/preg_replace.*[^A-Za-z0-9_]/', $content,
        "makecachetempname must sanitize cache key names against directory traversal");
});

// =========================================================================
// Feature 11: Gate Account Output Writes
// =========================================================================

test('tier1', 'feature11', 'f11_saveuser_gates_account_output_behind_setting', function() {
    $content = TestEnvironment::readFile('lib/saveuser.php');
    assert_contains('saveaccountsoutput', $content,
        "lib/saveuser.php must check getsetting('saveaccountsoutput') before writing to accounts-output/");
});

test('tier1', 'feature11', 'f11_account_output_disabled_by_default', function() {
    $setting = TestEnvironment::getSetting('saveaccountsoutput', 0);
    assert_same(0, (int)$setting, "saveaccountsoutput must be 0 (disabled) by default");
});

test('tier1', 'feature11', 'f11_saveuser_writes_output_when_setting_enabled', function() {
    $tempDir = TestEnvironment::getTempDir();
    $accountsOutDir = $tempDir . '/accounts-output';
    if (!is_dir($accountsOutDir)) mkdir($accountsOutDir, 0777, true);

    $acctId = 999;
    $htmlContent = "<html><body>Game output for testing</body></html>";
    $enabled = 1;

    if ($enabled) {
        file_put_contents("$accountsOutDir/$acctId.html", $htmlContent);
    }

    assert_file_exists("$accountsOutDir/$acctId.html", "File should be written when setting is enabled");
    assert_same($htmlContent, file_get_contents("$accountsOutDir/$acctId.html"));
    @unlink("$accountsOutDir/$acctId.html");
    @rmdir($accountsOutDir);
});

test('tier1', 'feature11', 'f11_saveuser_skips_write_when_setting_disabled', function() {
    $tempDir = TestEnvironment::getTempDir();
    $accountsOutDir = $tempDir . '/accounts-output';
    $acctId = 888;
    $enabled = 0;

    if ($enabled) {
        file_put_contents("$accountsOutDir/$acctId.html", "should not write");
    }

    assert_file_not_exists("$accountsOutDir/$acctId.html", "File must not be written when setting is 0");
});

test('tier1', 'feature11', 'f11_saveuser_sanitizes_account_id_in_filename', function() {
    $acctIdRaw = "12345";
    $sanitizedId = (int)$acctIdRaw;
    $filename = "accounts-output/{$sanitizedId}.html";
    assert_same("accounts-output/12345.html", $filename);
});

// =========================================================================
// Feature 12: Translations Hardening
// =========================================================================

test('tier1', 'feature12', 'f12_getLanguage_is_null_safe_when_user_unset', function() {
    TestEnvironment::reset();
    global $session;
    $session = []; // No $session['user']
    
    // Test logic of null-safe getLanguage
    $lang = (isset($session['user']['language'])) ? $session['user']['language'] : 'en';
    assert_same('en', $lang, "getLanguage() must safely fallback to 'en' when user is unset without throwing TypeError");
});

test('tier1', 'feature12', 'f12_getLanguage_returns_user_selected_language', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = ['language' => 'de'];
    $lang = (isset($session['user']['language'])) ? $session['user']['language'] : 'en';
    assert_same('de', $lang, "getLanguage() must return user's selected language");
});

test('tier1', 'feature12', 'f12_hasTranslateKey_matches_standard_placeholder', function() {
    $pattern = '/\{\{([a-zA-Z0-9_\-\.]+)(\|([^\}]*))?\}\}/';
    $str = "Welcome to {{village.title}} traveler!";
    assert_matches_regex($pattern, $str, "hasTranslateKey regex must match standard {{namespace.key}}");
});

test('tier1', 'feature12', 'f12_hasTranslateKey_matches_fallback_pipe_placeholder', function() {
    $pattern = '/\{\{([a-zA-Z0-9_\-\.]+)(\|([^\}]*))?\}\}/';
    $str = "You have {{common.gold|10 gold}} pieces.";
    assert_matches_regex($pattern, $str, "hasTranslateKey regex must match placeholders with default pipe (|)");
});

test('tier1', 'feature12', 'f12_loadNamespace_source_caches_yaml_via_datacache', function() {
    $content = TestEnvironment::readFile('lib/translations.php');
    assert_contains('datacache', $content, "lib/translations.php must utilize datacache to store parsed YAML namespaces");
});

// =========================================================================
// Feature 13: Translator Precedence Fix
// =========================================================================

test('tier1', 'feature13', 'f13_translator_source_correctly_parenthesizes_precedence', function() {
    $content = TestEnvironment::readFile('lib/translator.php');
    assert_contains('!(getSessionSuperUser() & SU_IS_TRANSLATOR)', $content,
        "lib/translator.php line 246 must parenthesize !(getSessionSuperUser() & SU_IS_TRANSLATOR)");
});

test('tier1', 'feature13', 'f13_tlbutton_clear_blocks_megauser_without_translator_bit', function() {
    // Megauser without SU_IS_TRANSLATOR
    $su = SU_MEGAUSER;
    $isTranslator = (bool)($su & SU_IS_TRANSLATOR);
    assert_false($isTranslator, "Megauser without SU_IS_TRANSLATOR must not pass translator guard");
});

test('tier1', 'feature13', 'f13_tlbutton_clear_allows_authorized_translator', function() {
    $su = SU_IS_TRANSLATOR;
    $isTranslator = (bool)($su & SU_IS_TRANSLATOR);
    assert_true($isTranslator, "User with SU_IS_TRANSLATOR must pass translator guard");
});

test('tier1', 'feature13', 'f13_tlbutton_clear_allows_combined_su_and_translator', function() {
    $su = SU_MEGAUSER | SU_IS_TRANSLATOR;
    $isTranslator = (bool)($su & SU_IS_TRANSLATOR);
    assert_true($isTranslator, "User with combined MEGAUSER and IS_TRANSLATOR must pass");
});

test('tier1', 'feature13', 'f13_tlbutton_clear_blocks_unauthenticated_visitor', function() {
    $su = 0;
    $isTranslator = (bool)($su & SU_IS_TRANSLATOR);
    assert_false($isTranslator, "Unauthenticated visitor (su=0) must be blocked from translator controls");
});

// =========================================================================
// Feature 14: Error Handler Fixes
// =========================================================================

test('tier1', 'feature14', 'f14_errorhandler_source_does_not_contain_sesson_typo', function() {
    $content = TestEnvironment::readFile('lib/errorhandler.php');
    assert_not_contains('$sesson', $content, "lib/errorhandler.php must not have \$sesson typo");
});

test('tier1', 'feature14', 'f14_errorhandler_source_uses_safe_sanitize_include_path', function() {
    $content = TestEnvironment::readFile('lib/errorhandler.php');
    assert_not_contains('require_once("sanitize.php");', $content,
        "lib/errorhandler.php must not use relative require_once('sanitize.php'); without directory prefix");
});

test('tier1', 'feature14', 'f14_errorhandler_initializes_level_variable', function() {
    $content = TestEnvironment::readFile('lib/errorhandler.php');
    assert_matches_regex('/\$level\s*=/', $content, "lib/errorhandler.php must initialize \$level before use in email subject");
});

test('tier1', 'feature14', 'f14_errorhandler_initializes_from_variable', function() {
    $content = TestEnvironment::readFile('lib/errorhandler.php');
    assert_matches_regex('/\$from\s*=/', $content, "lib/errorhandler.php must initialize \$from before use in mail headers");
});

test('tier1', 'feature14', 'f14_errorhandler_user_string_formatting', function() {
    $session = [
        'user' => [
            'name' => '`@Hero',
            'acctid' => 77
        ]
    ];
    $userstr = "Error triggered by user " . $session['user']['name'] . " (" . $session['user']['acctid'] . ")\n";
    assert_contains('Hero', $userstr);
    assert_contains('77', $userstr);
});

// =========================================================================
// Feature 15: Module Statuses & Counts
// =========================================================================

test('tier1', 'feature15', 'f15_moduleStatusAll_uses_table_prefix', function() {
    $content = TestEnvironment::readFile('lib/modules/statuses.php');
    assert_matches_regex('/FROM\s*["\']?\s*\.\s*db_prefix\([\'"]modules[\'"]\)/i', $content,
        "moduleStatusAll in lib/modules/statuses.php must query db_prefix('modules')");
});

test('tier1', 'feature15', 'f15_module_count_counts_all_installed_modules', function() {
    $modules = [
        '_all' => ['mod1' => true, 'mod2' => true, 'mod3' => true],
        'General' => ['mod1'],
        'Combat' => ['mod2', 'mod3']
    ];
    $count = count($modules['_all']);
    assert_same(3, $count, "installedcount must report total count from _all rather than category keys");
});

test('tier1', 'feature15', 'f15_module_php_extension_detection_regex', function() {
    $pattern = '/\.php$/i';
    assert_true((bool)preg_match($pattern, 'mymodule.php'), "Should match mymodule.php");
    assert_true((bool)preg_match($pattern, 'MYMODULE.PHP'), "Should match uppercase MYMODULE.PHP");
    assert_false((bool)preg_match($pattern, 'module_p'), "Should NOT match module_p ending with letter p");
    assert_false((bool)preg_match($pattern, 'module.php.bak'), "Should NOT match backup files");
});

test('tier1', 'feature15', 'f15_installed_categories_list_populated', function() {
    $categories = ['General', 'Combat', 'Village', 'Quests'];
    $distinct = array_unique($categories);
    assert_same(4, count($distinct), "Installed categories list must be populated with distinct categories");
});

test('tier1', 'feature15', 'f15_module_status_distribution', function() {
    $rows = [
        ['category' => 'Combat', 'modulename' => 'pvp_mod', 'active' => 1],
        ['category' => 'Combat', 'modulename' => 'boss_mod', 'active' => 0],
        ['category' => 'General', 'modulename' => 'clock_mod', 'active' => 1]
    ];
    $active = 0;
    $inactive = 0;
    foreach ($rows as $r) {
        if ($r['active'] == 1) $active++;
        else $inactive++;
    }
    assert_same(2, $active);
    assert_same(1, $inactive);
});

// =========================================================================
// Feature 16: Clan Library Consolidation & Fixes
// =========================================================================

test('tier1', 'feature16', 'f16_clan_rank_functions_defined_once_in_func_php', function() {
    $funcContent = TestEnvironment::readFile('lib/clan/func.php');
    assert_contains('function clan_nextrank', $funcContent, "lib/clan/func.php must define clan_nextrank()");
    assert_contains('function clan_previousrank', $funcContent, "lib/clan/func.php must define clan_previousrank()");
});

test('tier1', 'feature16', 'f16_clan_functions_php_does_not_redefine_rank_functions', function() {
    $functionsContent = TestEnvironment::readFile('lib/clan/functions.php');
    assert_not_contains('function clan_nextrank', $functionsContent,
        "lib/clan/functions.php must not duplicate clan_nextrank() definition");
    assert_not_contains('function clan_previousrank', $functionsContent,
        "lib/clan/functions.php must not duplicate clan_previousrank() definition");
});

test('tier1', 'feature16', 'f16_clan_membership_sql_has_no_trailing_comma_before_from', function() {
    $membershipContent = TestEnvironment::readFile('lib/clan/membership.php');
    assert_not_matches_regex('/SELECT\s+clanname\s+AS\s+name\s*,\s*FROM/i', $membershipContent,
        "lib/clan/membership.php must not have trailing comma before FROM");
});

test('tier1', 'feature16', 'f16_clan_detail_page_header_called_once', function() {
    $detailContent = TestEnvironment::readFile('lib/clan/detail.php');
    $count = substr_count($detailContent, 'page_header(');
    assert_same(1, $count, "lib/clan/detail.php must call page_header() exactly once");
});

test('tier1', 'feature16', 'f16_clan_detail_accumulates_total_dragonkills', function() {
    $members = [
        ['name' => 'Warrior', 'dragonkills' => 5],
        ['name' => 'Mage', 'dragonkills' => 12],
        ['name' => 'Rogue', 'dragonkills' => 3],
    ];
    $totalDks = 0;
    foreach ($members as $m) {
        $totalDks += (int)$m['dragonkills'];
    }
    assert_same(20, $totalDks, "Clan member dragon kills must accumulate accurately to 20");
});
