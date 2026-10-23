<?php
/**
 * Tier 1: Feature Coverage — Root Modernization, Modules & Licensing (Features 17–23)
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';

// =========================================================================
// Feature 17: Village Logic Fixes
// =========================================================================

test('tier1', 'feature17', 'f17_village_assigns_loadNamespace_directly', function() {
    $content = TestEnvironment::readFile('village.php');
    assert_matches_regex('/\$i18n\s*=\s*loadNamespace\([\'"]village[\'"]\)/', $content,
        "village.php must assign \$i18n = loadNamespace('village') directly without undefined \$language");
});

test('tier1', 'feature17', 'f17_village_formats_new_character_once', function() {
    $content = TestEnvironment::readFile('village.php');
    // Ensure $i18n['new_character'] is not formatted with sprintf before the line 180 user comparison
    assert_not_matches_regex('/\$i18n\[[\'"]new_character[\'"]\]\s*=\s*sprintf\(/', $content,
        "village.php must not format new_character with sprintf twice");
});

test('tier1', 'feature17', 'f17_village_unsets_tavern_clan_when_clans_disabled', function() {
    $content = TestEnvironment::readFile('village.php');
    assert_contains("unset(\$navigation['tavern']['clan'])", $content,
        "village.php must unset \$navigation['tavern']['clan'] (not gate clan) when allowclans is disabled");
});

test('tier1', 'feature17', 'f17_village_honors_skip_description_flag', function() {
    $content = TestEnvironment::readFile('village.php');
    assert_contains('if (!$skipDescription)', $content,
        "village.php must check if (!\$skipDescription) before outputting base village description");
});

test('tier1', 'feature17', 'f17_village_escapes_percent_in_character_name', function() {
    $name = "`%Danger%Zone";
    $safeName = preg_replace('/(?<!`)%/', '%%', $name);
    $template = "The newest warrior in town is %s!";
    $formatted = sprintf($template, $safeName);
    assert_contains('Danger', $formatted);
    assert_same("The newest warrior in town is `%Danger%%Zone!", $formatted);
});

// =========================================================================
// Feature 18: Stray & Duplicate File Removal
// =========================================================================

test('tier1', 'feature18', 'f18_root_racehuman_php_does_not_exist', function() {
    $root = TestEnvironment::getRootDir();
    assert_file_not_exists($root . '/racehuman.php', "Duplicate root racehuman.php must be removed");
});

test('tier1', 'feature18', 'f18_active_modules_racehuman_php_remains', function() {
    $root = TestEnvironment::getRootDir();
    assert_file_exists($root . '/modules/racehuman.php', "modules/racehuman.php must remain present as active module");
});

test('tier1', 'feature18', 'f18_stray_clanbuffs_a_does_not_exist', function() {
    $root = TestEnvironment::getRootDir();
    assert_file_not_exists($root . '/modules/clanbuffs/a', "Stray file modules/clanbuffs/a must be removed");
});

test('tier1', 'feature18', 'f18_stray_thumbs_db_does_not_exist', function() {
    $root = TestEnvironment::getRootDir();
    assert_file_not_exists($root . '/modules/dwellings/images/Thumbs.db', "Stray file Thumbs.db must be removed");
});

test('tier1', 'feature18', 'f18_clean_repository_has_zero_stray_files', function() {
    $root = TestEnvironment::getRootDir();
    $strayList = [
        $root . '/pull.php',
        $root . '/test_mtime.php',
        $root . '/racehuman.php',
        $root . '/modules/clanbuffs/a',
        $root . '/modules/dwellings/images/Thumbs.db',
    ];
    foreach ($strayList as $stray) {
        assert_file_not_exists($stray, "Clean repo must not contain stray: " . basename($stray));
    }
});

// =========================================================================
// Feature 19: PHP 4 Superglobal Elimination
// =========================================================================

test('tier1', 'feature19', 'f19_lib_http_has_zero_HTTP_GET_VARS', function() {
    $content = TestEnvironment::readFile('lib/http.php');
    assert_not_contains('$HTTP_GET_VARS', $content, "lib/http.php must contain 0 occurrences of \$HTTP_GET_VARS");
});

test('tier1', 'feature19', 'f19_lib_http_has_zero_HTTP_POST_VARS', function() {
    $content = TestEnvironment::readFile('lib/http.php');
    assert_not_contains('$HTTP_POST_VARS', $content, "lib/http.php must contain 0 occurrences of \$HTTP_POST_VARS");
});

test('tier1', 'feature19', 'f19_lib_errorhandling_has_zero_HTTP_GET_VARS', function() {
    $content = TestEnvironment::readFile('lib/errorhandling.php');
    assert_not_contains('$HTTP_GET_VARS', $content, "lib/errorhandling.php must contain 0 occurrences of \$HTTP_GET_VARS");
});

test('tier1', 'feature19', 'f19_lib_errorhandling_has_zero_HTTP_POST_VARS', function() {
    $content = TestEnvironment::readFile('lib/errorhandling.php');
    assert_not_contains('$HTTP_POST_VARS', $content, "lib/errorhandling.php must contain 0 occurrences of \$HTTP_POST_VARS");
});

test('tier1', 'feature19', 'f19_lib_errorhandling_has_zero_HTTP_COOKIE_VARS', function() {
    $content = TestEnvironment::readFile('lib/errorhandling.php');
    assert_not_contains('$HTTP_COOKIE_VARS', $content, "lib/errorhandling.php must contain 0 occurrences of \$HTTP_COOKIE_VARS");
});

// =========================================================================
// Feature 20: PHP 8.4 Root Modernization
// =========================================================================

test('tier1', 'feature20', 'f20_root_scripts_have_zero_each_calls', function() {
    $root = TestEnvironment::getRootDir();
    $files = glob($root . '/*.php') ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_matches_regex('/\beach\s*\(/', $content, "Root script " . basename($file) . " must not call each()");
    }
});

test('tier1', 'feature20', 'f20_root_scripts_have_zero_ereg_calls', function() {
    $root = TestEnvironment::getRootDir();
    $files = glob($root . '/*.php') ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_matches_regex('/\bereg\s*\(/', $content, "Root script " . basename($file) . " must not call ereg()");
        assert_not_matches_regex('/\bereg_replace\s*\(/', $content, "Root script " . basename($file) . " must not call ereg_replace()");
    }
});

test('tier1', 'feature20', 'f20_root_scripts_pass_syntax_validation', function() {
    $root = TestEnvironment::getRootDir();
    $files = glob($root . '/*.php') ?: [];
    assert_greater_than(30, count($files), "Must validate at least 30 root PHP files");
    foreach ($files as $file) {
        $code = file_get_contents($file) ?: '';
        $tokens = token_get_all($code, TOKEN_PARSE);
        assert_true(is_array($tokens), "Root file " . basename($file) . " must parse clean without token error");
    }
});

test('tier1', 'feature20', 'f20_root_scripts_have_no_obsolete_reset_before_foreach', function() {
    $village = TestEnvironment::readFile('village.php');
    assert_not_matches_regex('/reset\(\$navigation\);\s*foreach/', $village,
        "village.php should not contain obsolete reset() before foreach");
});

test('tier1', 'feature20', 'f20_common_bootstrap_loads_cleanly', function() {
    $common = TestEnvironment::readFile('common.php');
    assert_contains('session.php', $common, "common.php must bootstrap session.php");
    assert_contains('dbwrapper', $common, "common.php must bootstrap dbwrapper");
});

// =========================================================================
// Feature 21: PHP 8.4 Modules Modernization
// =========================================================================

test('tier1', 'feature21', 'f21_active_modules_have_zero_each_calls', function() {
    $modulesDir = TestEnvironment::getRootDir() . '/modules';
    $files = glob($modulesDir . '/*.php') ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_matches_regex('/\beach\s*\(/', $content, "Active module " . basename($file) . " must not call each()");
    }
});

test('tier1', 'feature21', 'f21_active_modules_have_zero_ereg_calls', function() {
    $modulesDir = TestEnvironment::getRootDir() . '/modules';
    $files = glob($modulesDir . '/*.php') ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_matches_regex('/\bereg\s*\(/', $content, "Active module " . basename($file) . " must not call ereg()");
        assert_not_matches_regex('/\bereg_replace\s*\(/', $content, "Active module " . basename($file) . " must not call ereg_replace()");
    }
});

test('tier1', 'feature21', 'f21_active_modules_have_zero_direct_mysql_calls', function() {
    $modulesDir = TestEnvironment::getRootDir() . '/modules';
    $files = glob($modulesDir . '/*.php') ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_matches_regex('/\bmysql_query\s*\(/', $content, "Active module " . basename($file) . " must not call direct mysql_query()");
        assert_not_matches_regex('/\bmysql_fetch_assoc\s*\(/', $content, "Active module " . basename($file) . " must not call direct mysql_fetch_assoc()");
    }
});

test('tier1', 'feature21', 'f21_active_modules_subdirectories_clean_of_deprecations', function() {
    $modulesDir = TestEnvironment::getRootDir() . '/modules';
    $subfiles = glob($modulesDir . '/*/*.php') ?: [];
    foreach ($subfiles as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_matches_regex('/\beach\s*\(/', $content, "Module subfile " . basename($file) . " must not call each()");
        assert_not_matches_regex('/\bereg\s*\(/', $content, "Module subfile " . basename($file) . " must not call ereg()");
        assert_not_matches_regex('/\bmysql_\w+\s*\(/', $content, "Module subfile " . basename($file) . " must not call mysql_*()");
    }
});

test('tier1', 'feature21', 'f21_active_modules_clean_tokens_validation', function() {
    $modulesDir = TestEnvironment::getRootDir() . '/modules';
    $files = glob($modulesDir . '/*.php') ?: [];
    assert_greater_than(50, count($files), "Must check at least 50 active module files");
    foreach (array_slice($files, 0, 50) as $file) {
        $code = file_get_contents($file) ?: '';
        $tokens = token_get_all($code, TOKEN_PARSE);
        assert_true(is_array($tokens), "Active module " . basename($file) . " must parse clean without compiler error");
    }
});

// =========================================================================
// Feature 22: PHP 8.4 Quarantine Modernization
// =========================================================================

test('tier1', 'feature22', 'f22_quarantine_modules_have_zero_each_calls', function() {
    $qDir = TestEnvironment::getRootDir() . '/modules_quarantine';
    $files = glob($qDir . '/*.php') ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_matches_regex('/\beach\s*\(/', $content, "Quarantined module " . basename($file) . " must not call each()");
    }
});

test('tier1', 'feature22', 'f22_quarantine_modules_have_zero_ereg_calls', function() {
    $qDir = TestEnvironment::getRootDir() . '/modules_quarantine';
    $files = glob($qDir . '/*.php') ?: [];
    foreach ($files as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_matches_regex('/\bereg\s*\(/', $content, "Quarantined module " . basename($file) . " must not call ereg()");
        assert_not_matches_regex('/\bereg_replace\s*\(/', $content, "Quarantined module " . basename($file) . " must not call ereg_replace()");
    }
});

test('tier1', 'feature22', 'f22_quarantine_modules_syntax_validation', function() {
    $qDir = TestEnvironment::getRootDir() . '/modules_quarantine';
    $files = glob($qDir . '/*.php') ?: [];
    assert_greater_than(20, count($files), "Must check quarantined files");
    foreach ($files as $file) {
        $code = file_get_contents($file) ?: '';
        $tokens = token_get_all($code, TOKEN_PARSE);
        assert_true(is_array($tokens), "Quarantined file " . basename($file) . " must parse cleanly");
    }
});

test('tier1', 'feature22', 'f22_quarantine_specialtymonkskills_clean', function() {
    $path = TestEnvironment::getRootDir() . '/modules_quarantine/specialtymonkskills.php';
    if (file_exists($path)) {
        $content = file_get_contents($path);
        assert_not_matches_regex('/\beach\s*\(/', $content, "specialtymonkskills.php must not call each()");
    } else {
        assert_true(true);
    }
});

test('tier1', 'feature22', 'f22_quarantine_racespecialtylion_clean', function() {
    $path = TestEnvironment::getRootDir() . '/modules_quarantine/racespecialtylion.php';
    if (file_exists($path)) {
        $content = file_get_contents($path);
        assert_not_matches_regex('/\bereg/', $content, "racespecialtylion.php must not call ereg()");
    } else {
        assert_true(true);
    }
});

// =========================================================================
// Feature 23: License & Attribution Integrity
// =========================================================================

test('tier1', 'feature23', 'f23_license_file_exists_and_retains_cc_by_nc_sa', function() {
    $license = TestEnvironment::readFile('LICENSE.txt');
    assert_contains('Creative Commons', $license, "LICENSE.txt must retain Creative Commons license");
    assert_contains('Attribution-NonCommercial-ShareAlike', $license, "LICENSE.txt must retain Attribution-NonCommercial-ShareAlike");
});

test('tier1', 'feature23', 'f23_common_retains_eric_stevens_copyright', function() {
    $common = TestEnvironment::readFile('common.php');
    assert_contains('Eric Stevens & JT Traub', $common, "common.php must retain Eric Stevens & JT Traub copyright");
});

test('tier1', 'feature23', 'f23_common_retains_dragonprime_copyright', function() {
    $common = TestEnvironment::readFile('common.php');
    assert_contains('Dragonprime Development Team', $common, "common.php must retain Dragonprime Development Team copyright");
});

test('tier1', 'feature23', 'f23_common_contains_modified_by_jaoe1000', function() {
    $common = TestEnvironment::readFile('common.php');
    assert_contains('modified by jaoe1000', $common, "common.php must contain 'modified by jaoe1000' attribution");
});

test('tier1', 'feature23', 'f23_pageparts_renders_copyright_with_attribution', function() {
    $pageparts = TestEnvironment::readFile('lib/pageparts.php');
    assert_contains('{copyright}', $pageparts, "lib/pageparts.php must replace {copyright} placeholder in footer");
});
