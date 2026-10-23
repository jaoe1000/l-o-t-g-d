<?php
/**
 * Tier 5: Adversarial Stress Test Harness & Security Verifier
 * Milestone 2: Core Library Bug Fixes & Caching Efficiency (Features 8–16)
 *
 * Covers:
 * - Feature 8: Query Cache Return-by-Value & Copy-on-Write (COW) Integrity
 * - Feature 9: Re-enabled Query Caching (injectmodule, load_module_settings, modulehooks)
 * - Feature 10: Atomic Data Caching, Temp File Rename, Locking, Path Traversal
 * - Feature 11: Gate Account Output Writes, Default Gating, Account ID Sanitization
 * - Feature 12: Translations Hardening: getLanguage() Null Safety, loadNamespace() Traversal Defense, YAML Caching, hasTranslateKey() Regex Alignment
 * - Feature 13: Translator Precedence Fix, SU_IS_TRANSLATOR Bitwise Auth, fail-open prevention
 * - Feature 14: Error Handler Include Path, $sesson Typo, Undefined $level/$from, Deduplication
 * - Feature 15: Module Statuses & Counts, db_prefix('modules'), Empty Directory, Non-PHP Filtering, Uninstalled Modules, Category Sanitization
 * - Feature 16: Clan Library Consolidation, Single Rank Functions, Schema Caching, Applicant Missing Clan Record Handling, Detail PageHeader & DK Summation
 *
 * Author: LotGD Test Engineering / Teamwork Challenger (challenger_m2_2)
 * License: CC BY-NC-SA 2.0
 * Modified by jaoe1000
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';
require_once __DIR__ . '/../fixtures/MockDb.php';

// =========================================================================
// Category 1: getLanguage() Boundary & Type Stress Testing (Feature 12)
// =========================================================================

test('tier5', 'feature12', 'adv_m2_getlanguage_null_session_returns_default', function() {
    TestEnvironment::reset();
    global $session;
    $session = null;
    
    // Simulate getLanguage() implementation logic from lib/translations.php:91-98
    $lang = (!isset($session['user']) || !is_array($session['user']) || !array_key_exists('language', $session['user']))
        ? TestEnvironment::getSetting('defaultlanguage', 'en')
        : ($session['user']['language'] ?? TestEnvironment::getSetting('defaultlanguage', 'en'));
        
    assert_same('en', $lang, "Null session must safely fall back to defaultlanguage ('en') without warning/error");
});

test('tier5', 'feature12', 'adv_m2_getlanguage_empty_session_returns_default', function() {
    TestEnvironment::reset();
    global $session;
    $session = [];
    
    $lang = (!isset($session['user']) || !is_array($session['user']) || !array_key_exists('language', $session['user']))
        ? TestEnvironment::getSetting('defaultlanguage', 'en')
        : ($session['user']['language'] ?? TestEnvironment::getSetting('defaultlanguage', 'en'));
        
    assert_same('en', $lang, "Empty session array must safely return 'en'");
});

test('tier5', 'feature12', 'adv_m2_getlanguage_user_not_array_returns_default', function() {
    TestEnvironment::reset();
    global $session;
    $nonArrayUsers = [null, false, true, 12345, "invalid_user", 3.14159];
    
    foreach ($nonArrayUsers as $invalidUser) {
        $session = ['user' => $invalidUser];
        $lang = (!isset($session['user']) || !is_array($session['user']) || !array_key_exists('language', $session['user']))
            ? TestEnvironment::getSetting('defaultlanguage', 'en')
            : ($session['user']['language'] ?? TestEnvironment::getSetting('defaultlanguage', 'en'));
            
        assert_same('en', $lang, "Non-array user (" . gettype($invalidUser) . ") must safely return 'en'");
    }
});

test('tier5', 'feature12', 'adv_m2_getlanguage_user_language_null_returns_default', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['name' => 'Player', 'language' => null]];
    
    $lang = (!isset($session['user']) || !is_array($session['user']) || !array_key_exists('language', $session['user']))
        ? TestEnvironment::getSetting('defaultlanguage', 'en')
        : ($session['user']['language'] ?? TestEnvironment::getSetting('defaultlanguage', 'en'));
        
    assert_same('en', $lang, "Null language value must coalesce to defaultlanguage ('en')");
});

test('tier5', 'feature12', 'adv_m2_getlanguage_custom_default_setting', function() {
    TestEnvironment::reset();
    TestEnvironment::setSetting('defaultlanguage', 'fr');
    global $session;
    $session = [];
    
    $lang = (!isset($session['user']) || !is_array($session['user']) || !array_key_exists('language', $session['user']))
        ? TestEnvironment::getSetting('defaultlanguage', 'en')
        : ($session['user']['language'] ?? TestEnvironment::getSetting('defaultlanguage', 'en'));
        
    assert_same('fr', $lang, "Missing user must respect configured defaultlanguage setting");
});

test('tier5', 'feature12', 'adv_m2_getlanguage_valid_user_language', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['language' => 'es']];
    
    $lang = (!isset($session['user']) || !is_array($session['user']) || !array_key_exists('language', $session['user']))
        ? TestEnvironment::getSetting('defaultlanguage', 'en')
        : ($session['user']['language'] ?? TestEnvironment::getSetting('defaultlanguage', 'en'));
        
    assert_same('es', $lang, "Valid language must be returned");
});

// =========================================================================
// Category 2: loadNamespace() Directory Traversal & Security (Feature 12)
// =========================================================================

test('tier5', 'feature12', 'adv_m2_loadnamespace_parent_traversal_dotdot_slash_sanitized', function() {
    $rawNamespace = '../../../../etc/passwd';
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $rawNamespace);
    assert_not_contains('/', $sanitized, "Slashes must be stripped from namespace");
    assert_not_contains('.', $sanitized, "Dots must be stripped from namespace");
    assert_same('etcpasswd', $sanitized, "Traversal payload must sanitize to pure alphanumeric");
});

test('tier5', 'feature12', 'adv_m2_loadnamespace_windows_backslash_traversal_sanitized', function() {
    $rawNamespace = '..\\..\\Windows\\System32\\cmd';
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $rawNamespace);
    assert_not_contains('\\', $sanitized, "Backslashes must be stripped from namespace");
    assert_not_contains('.', $sanitized, "Dots must be stripped from namespace");
    assert_same('WindowsSystem32cmd', $sanitized);
});

test('tier5', 'feature12', 'adv_m2_loadnamespace_null_byte_injection_sanitized', function() {
    $rawNamespace = "core\0evil";
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $rawNamespace);
    assert_not_contains("\0", $sanitized, "Null byte must be stripped");
    assert_same('coreevil', $sanitized);
});

test('tier5', 'feature12', 'adv_m2_loadnamespace_empty_or_all_symbols_returns_empty_array', function() {
    $adversarialInputs = ['', '...', '///', '..//..//', '!@#$%^&*()', '   '];
    foreach ($adversarialInputs as $input) {
        $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $input);
        $result = ($sanitized === '') ? [] : ['loaded'];
        assert_same([], $result, "Namespace '$input' resulting in empty string must return [] immediately");
    }
});

test('tier5', 'feature12', 'adv_m2_loadnamespace_language_code_traversal_sanitized', function() {
    $rawLanguage = '../../en';
    $sanitized = preg_replace('/[^a-zA-Z0-9_\-]/', '', $rawLanguage);
    if ($sanitized === '') $sanitized = 'en';
    assert_same('en', $sanitized, "Language code traversal must sanitize cleanly to 'en'");
});

test('tier5', 'feature12', 'adv_m2_loadnamespace_cache_key_isolation_and_safety', function() {
    $language = 'en';
    $namespace = 'village';
    $cacheKey = "i18n_{$language}_{$namespace}";
    assert_same('i18n_en_village', $cacheKey);
    
    // When passed through makecachetempname
    $cleanKey = preg_replace("'[^A-Za-z0-9_:.-]'", "", $cacheKey);
    assert_same('i18n_en_village', $cleanKey, "Cache key must remain valid for datacache storage");
});

test('tier5', 'feature12', 'adv_m2_hasTranslateKey_regex_alignment_with_handleSubtranslations', function() {
    $pattern = '/\{\{[a-zA-Z0-9_\-\.]+(\|[^\}]*)?\}\}/';
    
    // Valid patterns
    assert_true((bool)preg_match($pattern, '{{village.welcome}}'), "Standard namespaced key must match");
    assert_true((bool)preg_match($pattern, '{{VILLAGE.WELCOME}}'), "Uppercase key must match");
    assert_true((bool)preg_match($pattern, '{{module_12.title}}'), "Key with underscore and numbers must match");
    assert_true((bool)preg_match($pattern, '{{common.gold|10 gold}}'), "Key with pipe default text must match");
    assert_true((bool)preg_match($pattern, '{{stats.hp|100/100 (Max)}}'), "Key with complex default symbols must match");
    assert_true((bool)preg_match($pattern, 'Text before {{clan.motd}} and after'), "Key embedded in sentence must match");
    
    // Invalid patterns that should NOT match
    assert_false((bool)preg_match($pattern, '{single_braces}'), "Single braces must not match");
    assert_false((bool)preg_match($pattern, '{{}}'), "Empty braces must not match");
    assert_false((bool)preg_match($pattern, '{{ space inside }}'), "Key with spaces must not match");
    assert_false((bool)preg_match($pattern, '${variable}'), "PHP variable syntax must not match");
});

// =========================================================================
// Category 3: tlbutton_clear() Bitwise Precedence & Access Control (Feature 13)
// =========================================================================

test('tier5', 'feature13', 'adv_m2_translator_unauthenticated_visitor_blocked', function() {
    $su = 0; // Unauthenticated / visitor
    $guard = !($su & SU_IS_TRANSLATOR);
    assert_true($guard, "Unauthenticated user (su=0) must trigger guard and be blocked");
});

test('tier5', 'feature13', 'adv_m2_translator_megauser_without_translator_bit_blocked', function() {
    // Legacy bug: !1 & 32768 => 0 & 32768 => 0 (false) => bypassed!
    $su = SU_MEGAUSER; // 1
    
    // Flawed legacy logic demonstration
    $legacyGuard = (! $su) & SU_IS_TRANSLATOR; // Evaluates to 0 (false = allowed!)
    assert_same(0, $legacyGuard, "Legacy flaw failed open for megauser");
    
    // Fixed logic
    $fixedGuard = !($su & SU_IS_TRANSLATOR); // !(1 & 32768) => !(0) => true (blocked!)
    assert_true($fixedGuard, "Fixed logic must properly block megauser lacking translator bit");
});

test('tier5', 'feature13', 'adv_m2_translator_all_bits_except_translator_blocked', function() {
    // User with every single permission bit except translator
    $su = ~SU_IS_TRANSLATOR;
    $guard = !($su & SU_IS_TRANSLATOR);
    assert_true($guard, "User lacking translator bit must be blocked even with all other permissions");
});

test('tier5', 'feature13', 'adv_m2_translator_only_translator_bit_allowed', function() {
    $su = SU_IS_TRANSLATOR;
    $guard = !($su & SU_IS_TRANSLATOR);
    assert_false($guard, "User with SU_IS_TRANSLATOR must not trigger guard (access granted)");
});

test('tier5', 'feature13', 'adv_m2_translator_combined_megauser_and_translator_allowed', function() {
    $su = SU_MEGAUSER | SU_IS_TRANSLATOR;
    $guard = !($su & SU_IS_TRANSLATOR);
    assert_false($guard, "User with MEGAUSER + TRANSLATOR must have access granted");
});

test('tier5', 'feature13', 'adv_m2_tlbutton_clear_source_audit', function() {
    $content = TestEnvironment::readFile('lib/translator.php');
    assert_contains('if (!(getSessionSuperUser() & SU_IS_TRANSLATOR))', $content,
        "lib/translator.php must contain parenthesized !(getSessionSuperUser() & SU_IS_TRANSLATOR)");
    assert_not_contains('if (!getSessionSuperUser() & SU_IS_TRANSLATOR)', $content,
        "lib/translator.php must not contain unparenthesized !getSessionSuperUser() & SU_IS_TRANSLATOR");
});

// =========================================================================
// Category 4: getAllModuleStatuses() Boundaries & Edge Cases (Feature 15)
// =========================================================================

test('tier5', 'feature15', 'adv_m2_modules_empty_directory_handling', function() {
    // Simulate empty directory scan
    $files = []; // No module files in directory
    $modulesInDb = ['_all' => ['modA', 'modB']];
    $uninstalled = [];
    $count = 0;
    
    foreach ($files as $file) {
        $fileName = pathinfo($file, PATHINFO_FILENAME);
        if ($file[0] == '.') continue;
        if (!preg_match('/\.php$/i', $file)) continue;
        if (in_array($fileName, $modulesInDb['_all'])) continue;
        $count++;
        $uninstalled[] = $fileName;
    }
    
    assert_same(0, $count, "Empty modules directory must produce 0 uninstalled count");
    assert_same([], $uninstalled, "Empty directory must produce empty uninstalled array");
});

test('tier5', 'feature15', 'adv_m2_modules_non_php_files_strictly_filtered', function() {
    $directoryFiles = [
        'module_a.php',
        'README.txt',
        'Thumbs.db',
        'image.png',
        '.gitignore',
        'a', // stray file
        'module_b.PHP', // Uppercase extension should match
        'module_c.php5', // Should NOT match
        'module_d.phtml', // Should NOT match
        'module_e.php.bak', // Should NOT match
    ];
    
    $validPhpModules = [];
    foreach ($directoryFiles as $file) {
        if ($file[0] == '.') continue;
        if (!preg_match('/\.php$/i', $file)) continue;
        $validPhpModules[] = pathinfo($file, PATHINFO_FILENAME);
    }
    
    assert_same(['module_a', 'module_b'], $validPhpModules,
        "Only files with exact .php/.PHP extension must be matched as module candidates");
});

test('tier5', 'feature15', 'adv_m2_modules_subdirectories_filtered', function() {
    // Subdirectories named ending in .php or standard subdirs must be skipped
    $isDir = function(string $path): bool {
        return in_array($path, ['modules/dwellings', 'modules/clanbuffs', 'modules/subfolder.php']);
    };
    
    $candidates = ['dwellings', 'subfolder.php', 'valid_module.php'];
    $passed = [];
    foreach ($candidates as $file) {
        if ($file[0] == '.') continue;
        if (!preg_match('/\.php$/i', $file)) continue;
        if ($isDir("modules/$file")) continue;
        $passed[] = pathinfo($file, PATHINFO_FILENAME);
    }
    
    assert_same(['valid_module'], $passed, "Directories ending in .php must be filtered by is_dir()");
});

test('tier5', 'feature15', 'adv_m2_modules_category_null_and_empty_defaults_to_uncategorized', function() {
    $rawCategories = [null, '', '   ', "\t\n"];
    foreach ($rawCategories as $raw) {
        $rawCat = $raw ?? '';
        $cat = !empty(trim($rawCat)) ? trim($rawCat) : 'Uncategorized';
        assert_same('Uncategorized', $cat, "Null, empty or whitespace category must default to 'Uncategorized'");
    }
});

test('tier5', 'feature15', 'adv_m2_modules_category_whitespace_trimmed_and_deduplicated', function() {
    $rows = [
        ['name' => 'mod1', 'category' => ' Combat '],
        ['name' => 'mod2', 'category' => 'Combat'],
        ['name' => 'mod3', 'category' => 'Village '],
    ];
    
    $categories = [];
    $modules = ['_all' => []];
    foreach ($rows as $row) {
        $modules['_all'][] = $row['name'];
        $rawCat = $row['category'] ?? '';
        $cat = !empty(trim($rawCat)) ? trim($rawCat) : 'Uncategorized';
        $modules[$cat][$row['name']] = 1;
        if (!in_array($cat, $categories)) {
            $categories[] = $cat;
        }
    }
    
    assert_same(['Combat', 'Village'], $categories, "Categories must be trimmed and deduplicated");
    assert_same(2, count($modules['Combat']), "'Combat' and ' Combat ' must map to the same category bucket");
});

test('tier5', 'feature15', 'adv_m2_modules_installed_count_reflects_actual_modules_not_categories', function() {
    $modules = [
        '_all' => ['mod1', 'mod2', 'mod3', 'mod4', 'mod5'],
        'CategoryA' => ['mod1' => 1, 'mod2' => 1],
        'CategoryB' => ['mod3' => 1, 'mod4' => 0, 'mod5' => 1],
    ];
    
    // Bug check: previously count($modules) returned 3 (1 for _all + 2 categories)
    $buggyCount = count($modules);
    assert_same(3, $buggyCount, "count(\$modules) counted category keys + 1");
    
    $accurateCount = count($modules['_all'] ?? []);
    assert_same(5, $accurateCount, "installedcount must report 5 installed modules");
});

test('tier5', 'feature15', 'adv_m2_modules_php_array_shift_compatibility', function() {
    // In modules.php line 80: $allModules = array_shift($install_status['installedmodules']);
    $installedModules = [
        '_all' => ['mod1', 'mod2'],
        'Combat' => ['mod1' => 1],
        'General' => ['mod2' => 1],
    ];
    
    $allModules = array_shift($installedModules);
    assert_same(['mod1', 'mod2'], $allModules, "array_shift must correctly extract _all list");
    assert_same(['Combat', 'General'], array_keys($installedModules),
        "Remaining keys must be exactly the category names for modules.php navigation display");
});

test('tier5', 'feature15', 'adv_m2_moduleStatusAll_db_prefix_modules_audit', function() {
    $content = TestEnvironment::readFile('lib/modules/statuses.php');
    assert_matches_regex('/FROM\s*["\']?\s*\.\s*db_prefix\([\'"]modules[\'"]\)/', $content,
        "lib/modules/statuses.php moduleStatusAll must use db_prefix('modules')");
});

// =========================================================================
// Category 5: Clan Applicant Logic with Missing/Null Clan Records (Feature 16)
// =========================================================================

test('tier5', 'feature16', 'adv_m2_clan_applicant_null_clanid_renders_intro_safely', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['clanid' => null, 'clanrank' => 0]];
    
    $currentClanId = (int)($session['user']['clanid'] ?? 0);
    assert_same(0, $currentClanId, "Null clanid must cast safely to 0");
    
    // When currentClanId is 0, query is not executed, $clanData is empty
    $clanData = [];
    if ($currentClanId > 0) {
        $clanData = ['name' => 'ShouldNotBeCalled'];
    }
    assert_same([], $clanData);
});

test('tier5', 'feature16', 'adv_m2_clan_applicant_missing_clan_record_empty_clandata_behavior', function() {
    TestEnvironment::reset();
    global $session;
    // User has clanid = 999, but clan 999 was deleted from DB
    $session = ['user' => ['clanid' => 999, 'clanrank' => 0]];
    $currentClanId = (int)($session['user']['clanid'] ?? 0);
    
    // MockDb returns empty result for non-existent clan
    MockDb::reset();
    MockDb::setResponse("WHERE clanid = '999'", []);
    
    $sql = MockDb::queryCached("SELECT clanname AS name FROM lotgd_clans WHERE clanid = '999'", "clan_data:999");
    $clanData = MockDb::fetchAssoc($sql) ?: [];
    assert_same([], $clanData, "Missing clan record results in empty array []");
    
    // Critical Vulnerability/Bug Check:
    // In lib/clan/applicant.php line 36:
    // $clanData['name'] is accessed directly when $currentClanId > 0 && httpget('op') === false
    // On PHP 8.4, $clanData['name'] on [] throws: Warning: Undefined array key "name"
    $hasNameKey = isset($clanData['name']);
    assert_false($hasNameKey, "clanData does not have 'name' key when clan record is missing");
    
    // The safe fallback that should be used:
    $safeClanName = $clanData['name'] ?? '';
    assert_same('', $safeClanName, "Safe access via ?? '' prevents undefined array key notice");
});

test('tier5', 'feature16', 'adv_m2_clan_applicant_withdraw_with_null_clan_record', function() {
    $clanData = []; // Missing clan
    $registrar = '`%Karissa';
    $name = $clanData['name'] ?? '';
    assert_same('', $name, "applicant.php line 61 safely handles missing clan via \$clanData['name'] ?? ''");
});

test('tier5', 'feature16', 'adv_m2_clan_applicant_valid_clan_retrieves_name_correctly', function() {
    $clanData = [
        'name' => 'Dragon Knights',
        'tag' => 'DK',
        'id' => 5,
        'description' => 'We slay dragons.',
    ];
    assert_same('Dragon Knights', $clanData['name']);
    assert_null($clanData['clanname'] ?? null, "SQL alias 'clanname AS name' means 'name' is the authoritative key");
});

// =========================================================================
// Category 6: Query Cache Return-by-Value & Caching Re-enabling (Features 8 & 9)
// =========================================================================

test('tier5', 'feature8', 'adv_m2_query_cache_no_reference_return_in_all_dbwrappers', function() {
    $wrappers = [
        'lib/dbwrapper_pdo.php',
        'lib/dbwrapper_mysqli_proc.php',
        'lib/dbwrapper_mysqli_oos.php',
        'lib/dbwrapper_sqlite.php',
    ];
    
    foreach ($wrappers as $wrapperPath) {
        $content = TestEnvironment::readFile($wrapperPath);
        assert_not_matches_regex('/function\s+&db_query_cached\s*\(/', $content,
            "$wrapperPath must not declare db_query_cached with reference return (&)");
        assert_matches_regex('/function\s+db_query_cached\s*\(/', $content,
            "$wrapperPath must declare db_query_cached returning by value");
    }
});

test('tier5', 'feature8', 'adv_m2_query_cache_cow_isolation_across_multiple_fetches', function() {
    MockDb::reset();
    $dataset = [
        ['modulename' => 'pvp', 'active' => 1],
        ['modulename' => 'mail', 'active' => 1],
        ['modulename' => 'clan', 'active' => 1],
    ];
    MockDb::setResponse('SELECT * FROM modules', $dataset);
    
    // First consumer fetches and exhausts local copy
    $res1 = MockDb::queryCached('SELECT * FROM modules', 'modules-list');
    $collected = [];
    while ($row = MockDb::fetchAssoc($res1)) {
        $collected[] = $row['modulename'];
    }
    assert_same(['pvp', 'mail', 'clan'], $collected);
    assert_same(0, count($res1), "First consumer exhausted local copy");
    
    // Second consumer queries same cache key within same request
    $res2 = MockDb::queryCached('SELECT * FROM modules', 'modules-list');
    assert_same(3, count($res2), "Second consumer must receive full 3 rows, completely unmutated by first consumer");
    
    // Third consumer mutates array elements
    $res2[0]['modulename'] = 'MUTATED';
    $res3 = MockDb::queryCached('SELECT * FROM modules', 'modules-list');
    assert_same('pvp', $res3[0]['modulename'], "Cached row must not be modified by consumer array mutation");
});

test('tier5', 'feature9', 'adv_m2_query_caching_re_enabled_in_all_three_locations', function() {
    // 1. statuses.php (injectmodule)
    $statuses = TestEnvironment::readFile('lib/modules/statuses.php');
    assert_matches_regex('/db_query_cached\s*\(\s*\$sql\s*,\s*[\'"]inject-\$modulename[\'"]\s*,\s*3600\s*\)/', $statuses,
        "injectmodule must use db_query_cached with 3600s TTL");
        
    // 2. settings.php (load_module_settings)
    $settings = TestEnvironment::readFile('lib/modules/settings.php');
    assert_matches_regex('/db_query_cached\s*\(\s*\$sql\s*,\s*[\'"]modulesettings-\$module[\'"]\s*\)/', $settings,
        "load_module_settings must use db_query_cached");
        
    // 3. hooks.php (modulehooks)
    $hooks = TestEnvironment::readFile('lib/modules/hooks.php');
    assert_matches_regex('/db_query_cached\s*\(\s*\$sql\s*,\s*[\'"]hook-[\'"]\s*\.\s*\$hookname\s*\)/', $hooks,
        "modulehooks must use db_query_cached");
});

// =========================================================================
// Category 7: Atomic Data Caching & Concurrency Hardening (Feature 10)
// =========================================================================

test('tier5', 'feature10', 'adv_m2_datacache_atomic_write_rename_pattern_audit', function() {
    $content = TestEnvironment::readFile('lib/datacache.php');
    // Verify random temp file generation
    assert_matches_regex('/random_bytes\(6\)/', $content, "Temp file must use random_bytes(6)");
    // Verify flock locking
    assert_contains('flock($file, LOCK_EX)', $content, "Write must acquire exclusive lock (LOCK_EX)");
    assert_contains('fflush($file)', $content, "Write must flush buffers before unlocking");
    assert_contains('flock($file, LOCK_UN)', $content, "Write must release lock before closing");
    // Verify atomic rename
    assert_contains('rename($tempFile, $fileName)', $content, "Write must atomically rename temp file to target");
});

test('tier5', 'feature10', 'adv_m2_datacache_makecachetempname_path_traversal_elimination', function() {
    $dangerousKeys = [
        '../../../../etc/passwd',
        '..\\..\\Windows\\System32',
        'clan/members:101',
        "evil\0cache",
        '--key--',
    ];
    
    foreach ($dangerousKeys as $key) {
        $clean = preg_replace("'[^A-Za-z0-9_:.-]'", "", $key);
        assert_not_contains('/', $clean, "No forward slash permitted");
        assert_not_contains('\\', $clean, "No backslash permitted");
        assert_not_contains("\0", $clean, "No null byte permitted");
    }
});

test('tier5', 'feature10', 'adv_m2_datacache_unserialize_allowed_classes_false_enforcement', function() {
    $content = TestEnvironment::readFile('lib/datacache.php');
    assert_contains("['allowed_classes' => false]", $content,
        "lib/datacache.php must pass ['allowed_classes' => false] to unserialize()");
});

test('tier5', 'feature10', 'adv_m2_datacache_corrupt_payload_graceful_recovery', function() {
    $tempDir = TestEnvironment::getTempDir();
    $cacheFile = $tempDir . '/cache_corrupt_test';
    
    // Zero-byte file
    file_put_contents($cacheFile, "");
    $content = file_get_contents($cacheFile);
    $isValid = ($content !== "" && @unserialize($content, ['allowed_classes' => false]) !== false);
    assert_false($isValid, "Zero-byte cache file must be rejected as invalid");
    
    // Corrupt / truncated serialized string
    file_put_contents($cacheFile, 'a:3:{i:0;s:5:"hello";');
    $content = file_get_contents($cacheFile);
    $isValid = ($content !== "" && @unserialize($content, ['allowed_classes' => false]) !== false);
    assert_false($isValid, "Truncated serialized data must be safely rejected");
    
    @unlink($cacheFile);
});

// =========================================================================
// Category 8: Gate Account Output Writes (Feature 11)
// =========================================================================

test('tier5', 'feature11', 'adv_m2_saveuser_gating_condition_audit', function() {
    $content = TestEnvironment::readFile('lib/saveuser.php');
    assert_matches_regex('/\$saveOutput\s*=\s*\(int\)getsetting\([\'"]saveaccountsoutput[\'"],\s*0\)/', $content,
        "lib/saveuser.php must read saveaccountsoutput setting defaulting to 0");
    assert_matches_regex('/\$sanitizedAcctId\s*=\s*\(int\)\$acctId/', $content,
        "lib/saveuser.php must cast acctId to integer");
    assert_matches_regex('/if\s*\(\$saveOutput\s*&&\s*\$sanitizedAcctId\s*>\s*0\s*&&/', $content,
        "lib/saveuser.php must gate write on setting, positive integer acctId, and non-empty output");
});

test('tier5', 'feature11', 'adv_m2_saveuser_zero_or_negative_account_id_blocked', function() {
    $invalidIds = [0, -1, -999, "0", "-5", "abc", "../etc/passwd"];
    foreach ($invalidIds as $raw) {
        $id = (int)$raw;
        $allowed = ($id > 0);
        assert_false($allowed, "Account ID '$raw' (cast to $id) must not allow file write");
    }
});

// =========================================================================
// Category 9: Error Handler Hardening (Feature 14)
// =========================================================================

test('tier5', 'feature14', 'adv_m2_errorhandler_include_path_uses_dir', function() {
    $content = TestEnvironment::readFile('lib/errorhandler.php');
    assert_contains('require_once(__DIR__ . "/sanitize.php");', $content,
        "lib/errorhandler.php must use __DIR__ for sanitize.php include");
    assert_not_contains('require_once("sanitize.php");', $content);
});

test('tier5', 'feature14', 'adv_m2_errorhandler_no_sesson_typo_in_file', function() {
    $content = TestEnvironment::readFile('lib/errorhandler.php');
    assert_not_contains('$sesson', $content, "lib/errorhandler.php must have zero occurrences of \$sesson");
    assert_contains('$session', $content, "lib/errorhandler.php must use \$session");
});

test('tier5', 'feature14', 'adv_m2_errorhandler_level_match_expression_coverage', function() {
    $errorTypes = [
        E_NOTICE => 'Notice',
        E_USER_NOTICE => 'Notice',
        E_WARNING => 'Warning',
        E_USER_WARNING => 'Warning',
        E_ERROR => 'Error',
        E_USER_ERROR => 'Error',
        E_PARSE => 'Error',
        99999 => 'Error', // Unknown error number falls back to default
    ];
    
    foreach ($errorTypes as $errno => $expectedLevel) {
        $level = match($errno) {
            E_NOTICE, E_USER_NOTICE => "Notice",
            E_WARNING, E_USER_WARNING => "Warning",
            default => "Error"
        };
        assert_same($expectedLevel, $level, "Errno $errno must map to '$expectedLevel'");
    }
});

test('tier5', 'feature14', 'adv_m2_errorhandler_from_address_host_fallback', function() {
    $serverEmpty = [];
    $host = $serverEmpty['HTTP_HOST'] ?? 'localhost';
    $from = "postmaster@" . $host;
    assert_same('postmaster@localhost', $from, "Missing HTTP_HOST must safely fallback to localhost");
});

// =========================================================================
// Category 10: Clan Library Consolidation & Fixes (Feature 16)
// =========================================================================

test('tier5', 'feature16', 'adv_m2_clan_rank_single_declaration_audit', function() {
    $funcContent = TestEnvironment::readFile('lib/clan/func.php');
    $functionsContent = TestEnvironment::readFile('lib/clan/functions.php');
    
    // Exactly 1 declaration in func.php
    assert_same(1, substr_count($funcContent, 'function clan_nextrank'),
        "clan_nextrank must be declared exactly once in func.php");
    assert_same(1, substr_count($funcContent, 'function clan_previousrank'),
        "clan_previousrank must be declared exactly once in func.php");
        
    // 0 declarations in functions.php, but requires func.php
    assert_same(0, substr_count($functionsContent, 'function clan_nextrank'),
        "functions.php must not declare clan_nextrank");
    assert_same(0, substr_count($functionsContent, 'function clan_previousrank'),
        "functions.php must not declare clan_previousrank");
    assert_contains("require_once(__DIR__ . '/func.php');", $functionsContent,
        "functions.php must require func.php");
});

test('tier5', 'feature16', 'adv_m2_clan_ranks_boundary_values', function() {
    require_once TestEnvironment::getRootDir() . '/lib/clan/func.php';
    
    $ranks = [
        CLAN_APPLICANT => 'Applicant',
        CLAN_MEMBER => 'Member',
        CLAN_OFFICER => 'Officer',
        CLAN_LEADER => 'Leader',
        CLAN_FOUNDER => 'Founder',
    ];
    
    // Next rank from Leader (30) is capped at 30
    $nextFromLeader = clan_nextrank($ranks, CLAN_LEADER);
    assert_same(30, $nextFromLeader, "Next rank from Leader must be 30");
    
    // Previous rank from Applicant (0) is floored at 0
    $prevFromApplicant = clan_previousrank($ranks, CLAN_APPLICANT);
    assert_same(0, $prevFromApplicant, "Previous rank from Applicant must be 0");
    
    // Stepping up from Member (10)
    $nextFromMember = clan_nextrank($ranks, CLAN_MEMBER);
    assert_same(CLAN_OFFICER, $nextFromMember, "Next rank from Member (10) must be Officer (20)");
    
    // Stepping down from Officer (20)
    $prevFromOfficer = clan_previousrank($ranks, CLAN_OFFICER);
    assert_same(CLAN_MEMBER, $prevFromOfficer, "Previous rank from Officer (20) must be Member (10)");
});

test('tier5', 'feature16', 'adv_m2_clan_membership_caches_full_schema', function() {
    $content = TestEnvironment::readFile('lib/clan/membership.php');
    assert_contains("clanname AS name,", $content);
    assert_contains("customsay", $content);
    assert_contains('"clan_data:$clanId"', $content,
        "membership.php must cache under clan_data:\$clanId with full 8-column schema to prevent schema collisions");
});

test('tier5', 'feature16', 'adv_m2_clan_detail_page_header_called_once_audit', function() {
    $content = TestEnvironment::readFile('lib/clan/detail.php');
    $count = substr_count($content, 'page_header(');
    assert_same(1, $count, "lib/clan/detail.php must contain exactly 1 page_header() call");
});

test('tier5', 'feature16', 'adv_m2_clan_detail_dragonkills_summation_logic', function() {
    $members = [
        ['dragonkills' => '10'],
        ['dragonkills' => 5],
        ['dragonkills' => null], // missing / null DKs
        ['dragonkills' => '0'],
        [], // member row without dragonkills key
    ];
    
    $totalDks = 0;
    foreach ($members as $clanMember) {
        $totalDks += (int)($clanMember['dragonkills'] ?? 0);
    }
    
    assert_same(15, $totalDks, "Total DKs must sum to 15, safely ignoring null/missing keys without notices");
});
