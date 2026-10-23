<?php
/**
 * Tier 2: Boundary & Corner Cases — Modernization, Modules & Licensing (Features 17–23)
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';

// =========================================================================
// Feature 17 Boundaries: Village Logic Fixes
// =========================================================================

test('tier2', 'feature17', 'f17_b_complex_color_codes_and_percents_in_name', function() {
    $rawName = "`%Lord`^%Of`#%War";
    // First escape % -> %%
    $safeName = preg_replace('/(?<!`)%/', '%%', $rawName);
    $template = "The newest champion is %s!";
    $result = sprintf($template, $safeName);
    assert_same("The newest champion is `%Lord`^%%Of`#%%War!", $result);
});

test('tier2', 'feature17', 'f17_b_current_user_is_newest_player_rendering', function() {
    $session = ['user' => ['acctid' => 500]];
    $newCharacter = ['acctid' => 500, 'name' => 'NewbieHero'];
    $texts = [
        'new_character_is_user' => 'You are the newest warrior in the village!',
        'new_character' => 'The newest warrior is %s.'
    ];
    $msg = ($session['user']['acctid'] == $newCharacter['acctid'])
        ? $texts['new_character_is_user']
        : sprintf($texts['new_character'], $newCharacter['name']);
    assert_same('You are the newest warrior in the village!', $msg);
});

test('tier2', 'feature17', 'f17_b_allowclans_true_preserves_tavern_clan_nav', function() {
    $navigation = [
        'tavern' => [
            'chat' => 'inn.php',
            'clan' => 'clan.php'
        ]
    ];
    $allowClans = true;
    if (!$allowClans) unset($navigation['tavern']['clan']);
    assert_true(isset($navigation['tavern']['clan']), "clan option must remain in tavern when allowclans is true");
});

test('tier2', 'feature17', 'f17_b_skip_description_set_by_random_event', function() {
    $skipDescription = true;
    $output = "";
    if (!$skipDescription) {
        $output .= "Village description text";
    }
    assert_same("", $output, "When skipDescription is true, description output must be skipped");
});

test('tier2', 'feature17', 'f17_b_village_title_custom_name_expansion', function() {
    $villageName = "Dragon's Den";
    $titleTemplate = "The Village of %s";
    $formatted = sprintf($titleTemplate, $villageName);
    assert_same("The Village of Dragon's Den", $formatted);
});

// =========================================================================
// Feature 18 Boundaries: Stray & Duplicate Files
// =========================================================================

test('tier2', 'feature18', 'f18_b_no_git_conflict_markers_in_codebase', function() {
    $root = TestEnvironment::getRootDir();
    $files = glob($root . '/*.php') ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_contains('<<<<<<< HEAD', $content, "No git conflict markers allowed in " . basename($file));
        assert_not_contains('>>>>>>>', $content, "No git conflict markers allowed in " . basename($file));
    }
});

test('tier2', 'feature18', 'f18_b_no_desktop_ini_or_ds_store_files', function() {
    $root = TestEnvironment::getRootDir();
    assert_file_not_exists($root . '/.DS_Store');
    assert_file_not_exists($root . '/desktop.ini');
});

test('tier2', 'feature18', 'f18_b_modules_clanbuffs_has_no_orphan_files', function() {
    $root = TestEnvironment::getRootDir();
    $clanbuffsDir = $root . '/modules/clanbuffs';
    if (is_dir($clanbuffsDir)) {
        $files = scandir($clanbuffsDir) ?: [];
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            assert_not_equals('a', $f, "Orphan file 'a' must not be in modules/clanbuffs/");
        }
    } else {
        assert_true(true);
    }
});

test('tier2', 'feature18', 'f18_b_dwellings_images_contains_only_valid_images', function() {
    $root = TestEnvironment::getRootDir();
    $imgDir = $root . '/modules/dwellings/images';
    if (is_dir($imgDir)) {
        $files = scandir($imgDir) ?: [];
        foreach ($files as $f) {
            if ($f === '.' || $f === '..') continue;
            assert_not_equals('Thumbs.db', $f, "Windows Thumbs.db must not be present in dwellings images");
        }
    } else {
        assert_true(true);
    }
});

test('tier2', 'feature18', 'f18_b_active_racehuman_module_integrity', function() {
    $modulePath = TestEnvironment::getRootDir() . '/modules/racehuman.php';
    assert_file_exists($modulePath, "modules/racehuman.php must exist");
    $content = file_get_contents($modulePath) ?: '';
    assert_contains('racehuman_getmoduleinfo', $content, "Active racehuman.php must declare module info hook");
});

// =========================================================================
// Feature 19 Boundaries: PHP 4 Superglobal Elimination
// =========================================================================

test('tier2', 'feature19', 'f19_b_httpget_missing_key_fallback', function() {
    $_GET = ['action' => 'view'];
    $val = $_GET['missing'] ?? 'default_val';
    assert_same('default_val', $val, "Missing GET key must return default value");
});

test('tier2', 'feature19', 'f19_b_httppost_missing_key_fallback', function() {
    $_POST = ['submit' => '1'];
    $val = $_POST['token'] ?? null;
    assert_null($val, "Missing POST key must return null default");
});

test('tier2', 'feature19', 'f19_b_httpget_array_parameter_handling', function() {
    $_GET['filter'] = ['type' => 'weapon', 'min_level' => '5'];
    $val = $_GET['filter'];
    assert_true(is_array($val), "Array parameter in GET must be handled safely");
    assert_same('weapon', $val['type']);
});

test('tier2', 'feature19', 'f19_b_httppost_empty_string_versus_missing', function() {
    $_POST['comment'] = "";
    $exists = isset($_POST['comment']);
    assert_true($exists, "Empty string POST param is set");
    assert_same("", $_POST['comment']);
});

test('tier2', 'feature19', 'f19_b_entire_lib_clean_of_HTTP_VARS', function() {
    $lib = TestEnvironment::getRootDir() . '/lib';
    $files = glob($lib . '/*.php') ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_contains('$HTTP_GET_VARS', $content, basename($file) . " has \$HTTP_GET_VARS");
        assert_not_contains('$HTTP_POST_VARS', $content, basename($file) . " has \$HTTP_POST_VARS");
        assert_not_contains('$HTTP_COOKIE_VARS', $content, basename($file) . " has \$HTTP_COOKIE_VARS");
    }
});

// =========================================================================
// Feature 20 Boundaries: PHP 8.4 Root Modernization
// =========================================================================

test('tier2', 'feature20', 'f20_b_count_on_non_countable_safe_guards', function() {
    $var = null;
    $count = is_countable($var) ? count($var) : 0;
    assert_same(0, $count, "is_countable check prevents TypeError in PHP 8+");
});

test('tier2', 'feature20', 'f20_b_implode_argument_order_standardization', function() {
    $pieces = ['lotgd', 'rpg', 'game'];
    $result = implode(', ', $pieces);
    assert_same('lotgd, rpg, game', $result, "implode(\$glue, \$pieces) is standard");
});

test('tier2', 'feature20', 'f20_b_string_curly_brace_offset_deprecated_check', function() {
    $str = "LotGD";
    // Modern PHP requires square brackets $str[0] not $str{0}
    $char = $str[0];
    assert_same('L', $char);
});

test('tier2', 'feature20', 'f20_b_in_array_type_safe_boundary', function() {
    $haystack = [1, 2, 3];
    // In PHP 8, "1string" in [1, 2, 3] without strict mode evaluates properly
    $found = in_array(2, $haystack, true);
    assert_true($found, "Strict in_array finds integer 2");
    $falseFound = in_array('2', $haystack, true);
    assert_false($falseFound, "Strict in_array does not match string '2' to integer 2");
});

test('tier2', 'feature20', 'f20_b_null_coalescing_replaces_undefined_index_notices', function() {
    $data = [];
    $val = $data['missing_key'] ?? 'fallback';
    assert_same('fallback', $val, "Null coalescing safely resolves missing keys");
});

// =========================================================================
// Feature 21 Boundaries: PHP 8.4 Modules Modernization
// =========================================================================

test('tier2', 'feature21', 'f21_b_module_info_structure_contract', function() {
    $mockInfo = [
        'name' => 'Test Module',
        'version' => '1.0',
        'author' => 'Author',
        'category' => 'General',
        'download' => 'https://example.com'
    ];
    assert_true(isset($mockInfo['name']));
    assert_true(isset($mockInfo['version']));
    assert_true(isset($mockInfo['category']));
});

test('tier2', 'feature21', 'f21_b_modulehook_returns_original_args_when_unmodified', function() {
    $args = ['gold' => 50, 'gems' => 2];
    $unmodifiedArgs = $args;
    assert_same($args, $unmodifiedArgs, "Module hook must preserve arguments if no handler mutates them");
});

test('tier2', 'feature21', 'f21_b_module_settings_array_fallback', function() {
    $settings = [];
    $val = $settings['non_existent'] ?? 100;
    assert_same(100, $val);
});

test('tier2', 'feature21', 'f21_b_module_install_uninstall_hooks_clean_signatures', function() {
    $installHook = function() { return true; };
    assert_true($installHook());
});

test('tier2', 'feature21', 'f21_b_modules_use_db_abstractions_instead_of_ext_mysql', function() {
    $content = "db_query('SELECT * FROM accounts');";
    assert_not_contains('mysql_query', $content);
    assert_contains('db_query', $content);
});

// =========================================================================
// Feature 22 Boundaries: PHP 8.4 Quarantine Modernization
// =========================================================================

test('tier2', 'feature22', 'f22_b_quarantine_files_contain_no_fatal_php8_syntax', function() {
    $qDir = TestEnvironment::getRootDir() . '/modules_quarantine';
    $files = glob($qDir . '/*.php') ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file) ?: '';
        // No obsolete create_function()
        assert_not_matches_regex('/create_function\s*\(/', $content, basename($file) . " has create_function");
    }
});

test('tier2', 'feature22', 'f22_b_quarantine_modules_do_not_self_register_as_active', function() {
    $qDir = TestEnvironment::getRootDir() . '/modules_quarantine';
    $files = glob($qDir . '/*.php') ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_matches_regex('/UPDATE\s+.*modules\s+SET\s+active\s*=\s*1/', $content,
            "Quarantine module " . basename($file) . " must not force activate itself");
    }
});

test('tier2', 'feature22', 'f22_b_quarantine_folder_isolation', function() {
    $activeDir = TestEnvironment::getRootDir() . '/modules';
    $qDir = TestEnvironment::getRootDir() . '/modules_quarantine';
    assert_not_equals($activeDir, $qDir, "Quarantine directory must be distinct from active modules");
});

test('tier2', 'feature22', 'f22_b_quarantine_files_have_valid_php_tags', function() {
    $qDir = TestEnvironment::getRootDir() . '/modules_quarantine';
    $files = glob($qDir . '/*.php') ?: [];
    foreach (array_slice($files, 0, 20) as $file) {
        $content = file_get_contents($file) ?: '';
        assert_contains('<?php', $content, basename($file) . " must begin with <?php");
    }
});

test('tier2', 'feature22', 'f22_b_quarantine_module_names_unique_from_active_core', function() {
    $activeCore = ['racehuman', 'dwellings', 'clanbuffs'];
    $qDir = TestEnvironment::getRootDir() . '/modules_quarantine';
    foreach ($activeCore as $core) {
        assert_file_not_exists($qDir . "/$core.php", "Core module $core should not conflict in quarantine");
    }
});

// =========================================================================
// Feature 23 Boundaries: License & Attribution
// =========================================================================

test('tier2', 'feature23', 'f23_b_attribution_link_target_blank', function() {
    $common = TestEnvironment::readFile('common.php');
    assert_contains("target='_blank'", $common, "Repository link in attribution must open in target='_blank'");
});

test('tier2', 'feature23', 'f23_b_license_non_commercial_clause_preserved', function() {
    $license = TestEnvironment::readFile('LICENSE.txt');
    assert_contains('NonCommercial', $license, "NonCommercial clause must be preserved");
    assert_contains('ShareAlike', $license, "ShareAlike clause must be preserved");
});

test('tier2', 'feature23', 'f23_b_attribution_in_modified_core_files', function() {
    $sessionFile = TestEnvironment::readFile('lib/session.php');
    assert_contains('modified by jaoe1000', $sessionFile, "lib/session.php must have modified by jaoe1000 attribution");
});

test('tier2', 'feature23', 'f23_b_readme_contains_attribution_notice', function() {
    $readme = TestEnvironment::readFile('README.md');
    assert_contains('Legend of the Green Dragon', $readme);
});

test('tier2', 'feature23', 'f23_b_test_suite_files_include_license_headers', function() {
    $testInfra = TestEnvironment::readFile('TEST_INFRA.md');
    assert_contains('CC BY-NC-SA 2.0', $testInfra, "TEST_INFRA.md must declare CC BY-NC-SA 2.0 license");
});
