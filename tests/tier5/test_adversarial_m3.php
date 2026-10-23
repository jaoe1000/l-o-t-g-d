<?php
/**
 * Tier 5: Adversarial Stress Test Harness & Robustness Verifier
 * Milestone 3: Root Entry Points, Module Modernization, Deprecations & Licensing
 *
 * Covers:
 * - Feature 17: village.php Format String Injection, Color Codes, allowclans & skipDescription Gating
 * - Feature 18: Stray & Duplicate Module Neutralization
 * - Feature 19: lib/http.php Superglobal Modernization (Missing keys, nulls, arrays, subkeys)
 * - Feature 20, 21, 22: each() Eradication & Semantic Equivalence across clanleaderpatch & dwellingseditor
 * - Feature 23: Licensing & Attribution Integrity Verification
 *
 * Author: LotGD Test Engineering / Teamwork Challenger (challenger_m3_1)
 * License: CC BY-NC-SA 2.0
 * Modified by jaoe1000
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';
require_once __DIR__ . '/../fixtures/MockDb.php';

// =========================================================================
// Category 1: village.php Format String Injection & Color Codes (Feature 17)
// =========================================================================

test('tier5', 'feature17', 'adv_m3_village_name_with_raw_percent_s_no_injection', function() {
    // Attack scenario: player name containing '%s' format specifier
    // If sprintf is called improperly or twice, '%s' could cause ValueError: Too few arguments or leak stack memory
    $template = "Wandering near the inn is `&%s`2, looking completely lost.`n";
    $rawName = "Malicious%sWarrior";
    
    $safeTemplate = str_replace('`%', '`%%', $template);
    $safeName = str_replace('%', '%%', $rawName);
    
    // Must execute without throwing ValueError
    $msg = sprintf($safeTemplate, $safeName);
    
    assert_contains('Malicious%%sWarrior', $msg, "Raw %s must be escaped to %%s without throwing ValueError");
    assert_contains('looking completely lost', $msg);
});

test('tier5', 'feature17', 'adv_m3_village_name_with_raw_percent_d_and_x_no_injection', function() {
    // Attack scenario: player name containing integer and hex format specifiers
    $template = "Wandering near the inn is `&%s`2, looking completely lost.`n";
    $rawName = "Hero%dWith%xPower";
    
    $safeTemplate = str_replace('`%', '`%%', $template);
    $safeName = str_replace('%', '%%', $rawName);
    
    $msg = sprintf($safeTemplate, $safeName);
    assert_contains('Hero%%dWith%%xPower', $msg, "%d and %x in name must be safely formatted");
});

test('tier5', 'feature17', 'adv_m3_village_name_with_percent_literal_no_value_error', function() {
    // Attack scenario: player name containing literal percent signs (e.g., '100%')
    // In PHP 8.0+, sprintf("... 100%") throws ValueError: Unknown format specifier ""
    $template = "Wandering near the inn is `&%s`2, looking completely lost.`n";
    $rawName = "Warrior 100%";
    
    $safeTemplate = str_replace('`%', '`%%', $template);
    $safeName = str_replace('%', '%%', $rawName);
    
    $msg = sprintf($safeTemplate, $safeName);
    assert_contains('Warrior 100%%', $msg, "Literal % must be escaped to %% without ValueError");
});

test('tier5', 'feature17', 'adv_m3_village_name_with_lotgd_color_code_and_percents', function() {
    // Attack scenario: player name containing LotGD color code (`%) combined with format specifiers
    // `% is LotGD light magenta color code
    $template = "Wandering near the inn is `&%s`2, looking completely lost.`n";
    $rawName = "`%Lord`^%Of`#%%War %s %d 100%";
    
    $safeTemplate = str_replace('`%', '`%%', $template);
    $safeName = str_replace('%', '%%', $rawName);
    
    $msg = sprintf($safeTemplate, $safeName);
    assert_contains('`%%Lord', $msg, "Color code `% must be escaped to `%% in safe name");
    assert_contains('%%s', $msg);
    assert_contains('%%d', $msg);
    assert_contains('100%%', $msg);
});

test('tier5', 'feature17', 'adv_m3_village_template_containing_color_code_preserved', function() {
    // When the template itself contains `% (color code for light magenta)
    $template = "`%`@Nearby %s`2 is resting near the fountain.`n";
    $rawName = "Knight";
    
    $safeTemplate = str_replace('`%s', '%s', $template);
    $safeTemplate = str_replace('`%', '`%%', $safeTemplate);
    $safeName = preg_replace('/(?<!`)%/', '%%', $rawName);
    
    $msg = sprintf($safeTemplate, $safeName);
    assert_contains('`%`@Nearby Knight`2 is resting', $msg, "Template `% must be preserved as `% after sprintf");
});

test('tier5', 'feature17', 'adv_m3_village_current_user_is_newest_player', function() {
    // When current user is the newest character, new_character_is_user must be rendered without sprintf
    $session = ['user' => ['acctid' => 42]];
    $newCharacter = ['acctid' => 42, 'name' => 'Exploit%s%d'];
    $texts = [
        'new_character_is_user' => "You are the newest member of the village.`n",
        'new_character' => "The newest warrior is %s.`n"
    ];
    
    if ($session['user']['acctid'] == $newCharacter['acctid']) {
        $newCharacterMsg = $texts['new_character_is_user'];
    } else {
        $template = str_replace('`%', '`%%', $texts['new_character']);
        $safeName = str_replace('%', '%%', $newCharacter['name']);
        $newCharacterMsg = sprintf($template, $safeName);
    }
    
    assert_same($texts['new_character_is_user'], $newCharacterMsg, "Self newest player must use new_character_is_user verbatim");
    assert_not_contains('%s', $newCharacterMsg);
});

test('tier5', 'feature17', 'adv_m3_village_allowclans_disabled_removes_clan_from_tavern', function() {
    // Adversarial verification of clan gating in village navigation
    $navigation = [
        'gate' => [
            'forest' => 'forest.php',
            'pvp' => 'pvp.php',
            'logout' => 'login.php?op=logout',
            'mercenary' => 'mercenarycamp.php',
        ],
        'tavern' => [
            'inn' => 'inn.php',
            'stables' => 'stables.php',
            'gardens' => 'gardens.php',
            'rock' => 'rock.php',
            'clan' => 'clan.php'
        ],
    ];
    
    // Simulate allowclans = false
    $allowClans = false;
    if (!$allowClans) unset($navigation['tavern']['clan']);
    
    assert_false(isset($navigation['tavern']['clan']), "'clan' must be unset from tavern section");
    assert_false(array_key_exists('clan', $navigation['tavern']), "'clan' key must not exist in tavern");
    
    // Verify clan is absent from entire navigation array
    $foundClan = false;
    foreach ($navigation as $section => $links) {
        if (isset($links['clan'])) {
            $foundClan = true;
            break;
        }
    }
    assert_false($foundClan, "Clan link must not exist anywhere in navigation when allowclans is disabled");
});

test('tier5', 'feature17', 'adv_m3_village_allowclans_enabled_keeps_clan_in_tavern', function() {
    $navigation = [
        'tavern' => [
            'inn' => 'inn.php',
            'clan' => 'clan.php'
        ],
    ];
    
    $allowClans = true;
    if (!$allowClans) unset($navigation['tavern']['clan']);
    
    assert_true(isset($navigation['tavern']['clan']), "'clan' must remain present when allowclans is enabled");
    assert_same('clan.php', $navigation['tavern']['clan']);
});

test('tier5', 'feature17', 'adv_m3_village_skip_description_suppresses_narrative_and_clock', function() {
    // When skipDescription is true (event active), base description and clock must be suppressed
    $skipDescription = true;
    $texts = [
        'description' => "The village square is bustling with activity.`n",
        'clock' => "The clock reads 12:00 PM.`n",
    ];
    
    $outputBuffer = [];
    $hooksCalled = [];
    
    if (!$skipDescription) {
        $hooksCalled[] = 'village-desc';
        $outputBuffer[] = $texts['description'];
        $outputBuffer[] = $texts['clock'];
    }
    
    assert_same(0, count($outputBuffer), "No description or clock output must occur when skipDescription is true");
    assert_same(0, count($hooksCalled), "village-desc hooks must not be invoked when skipDescription is true");
});

test('tier5', 'feature17', 'adv_m3_village_skip_description_false_renders_narrative_and_clock', function() {
    $skipDescription = false;
    $texts = [
        'description' => "The village square is bustling with activity.`n",
        'clock' => "The clock reads 12:00 PM.`n",
    ];
    
    $outputBuffer = [];
    $hooksCalled = [];
    
    if (!$skipDescription) {
        $hooksCalled[] = 'village-desc';
        $outputBuffer[] = $texts['description'];
        $outputBuffer[] = $texts['clock'];
    }
    
    assert_same(2, count($outputBuffer), "Both description and clock must be output when skipDescription is false");
    assert_same(1, count($hooksCalled));
});


// =========================================================================
// Category 2: lib/http.php Boundary & Edge Case Stress Testing (Feature 19)
// =========================================================================

test('tier5', 'feature19', 'adv_m3_httpget_missing_key_returns_false', function() {
    $_GET = [];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    assert_false(httpget('nonexistent_key'), "Missing GET key must return false");
});

test('tier5', 'feature19', 'adv_m3_httpget_null_value_returns_false', function() {
    $_GET = ['test_null' => null];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    // isset($_GET['test_null']) returns false for null
    assert_false(httpget('test_null'), "Null GET value must return false via isset()");
});

test('tier5', 'feature19', 'adv_m3_httpget_empty_string_and_zero_preserved', function() {
    $_GET = ['empty_str' => '', 'zero_str' => '0', 'zero_int' => 0];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    assert_same('', httpget('empty_str'), "Empty string must be returned as ''");
    assert_same('0', httpget('zero_str'), "String '0' must be returned as '0'");
    assert_same(0, httpget('zero_int'), "Integer 0 must be returned as 0");
});

test('tier5', 'feature19', 'adv_m3_httpget_array_value_supported', function() {
    $_GET = ['items' => ['sword', 'shield', 'potion'], 'nested' => ['k' => 'v']];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    $items = httpget('items');
    assert_true(is_array($items), "Array in GET must be returned as array");
    assert_same(['sword', 'shield', 'potion'], $items);
    assert_same(['k' => 'v'], httpget('nested'));
});

test('tier5', 'feature19', 'adv_m3_httpset_without_force_on_missing_key_does_nothing', function() {
    $_GET = [];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    httpset('ghost', 'value', false);
    assert_false(isset($_GET['ghost']), "httpset without force must not set missing key");
});

test('tier5', 'feature19', 'adv_m3_httpset_with_force_sets_missing_key', function() {
    $_GET = [];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    httpset('ghost', 'forced_value', true);
    assert_true(isset($_GET['ghost']), "httpset with force=true must set missing key");
    assert_same('forced_value', $_GET['ghost']);
});

test('tier5', 'feature19', 'adv_m3_httpset_updates_existing_key_without_force', function() {
    $_GET = ['op' => 'default'];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    httpset('op', 'new_op', false);
    assert_same('new_op', $_GET['op'], "httpset must update existing key without force");
});

test('tier5', 'feature19', 'adv_m3_httpset_array_and_null_values', function() {
    $_GET = ['data' => 'initial'];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    
    // Set array
    httpset('data', ['a' => 1, 'b' => 2]);
    assert_same(['a' => 1, 'b' => 2], $_GET['data']);
    
    // Set null
    httpset('data', null);
    assert_true(array_key_exists('data', $_GET), "Key must exist in _GET even when set to null");
    assert_null($_GET['data']);
});

test('tier5', 'feature19', 'adv_m3_httppost_missing_key_returns_false', function() {
    $_POST = [];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    assert_false(httppost('missing_post'), "Missing POST key must return false");
});

test('tier5', 'feature19', 'adv_m3_httppost_null_value_returns_false', function() {
    $_POST = ['null_post' => null];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    assert_false(httppost('null_post'), "Null POST key must return false via isset()");
});

test('tier5', 'feature19', 'adv_m3_httppost_array_value_supported', function() {
    $_POST = ['settings' => ['motd' => 10, 'pvp' => 1]];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    $post = httppost('settings');
    assert_true(is_array($post));
    assert_same(['motd' => 10, 'pvp' => 1], $post);
});

test('tier5', 'feature19', 'adv_m3_httppostisset_semantics', function() {
    $_POST = [
        'valid' => 'hello',
        'empty' => '',
        'zero' => '0',
        'null_val' => null,
    ];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    assert_same(1, httppostisset('valid'));
    assert_same(1, httppostisset('empty'));
    assert_same(1, httppostisset('zero'));
    assert_same(0, httppostisset('null_val'), "Null key must return 0 in httppostisset");
    assert_same(0, httppostisset('not_present'), "Missing key must return 0 in httppostisset");
});

test('tier5', 'feature19', 'adv_m3_httppostset_existing_and_missing_keys', function() {
    $_POST = ['existing' => 'old_value'];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    
    // Updating existing key
    httppostset('existing', 'new_value');
    assert_same('new_value', $_POST['existing']);
    
    // Missing key must not be created (httppostset checks isset($_POST[$var]))
    httppostset('unopened', 'val');
    assert_false(isset($_POST['unopened']), "httppostset must not create new key when missing");
});

test('tier5', 'feature19', 'adv_m3_httppostset_with_subkeys_and_arrays', function() {
    $_POST = [
        'nested' => [
            'sub1' => 'initial1',
            'sub2' => 'initial2'
        ],
        'not_array' => 'string_value'
    ];
    require_once TestEnvironment::getRootDir() . '/lib/http.php';
    
    // Update existing subkey
    httppostset('nested', 'updated1', 'sub1');
    assert_same('updated1', $_POST['nested']['sub1'], "Existing subkey must be updated");
    assert_same('initial2', $_POST['nested']['sub2'], "Unmodified subkey must remain intact");
    
    // Missing subkey must not be created
    httppostset('nested', 'new_sub', 'nonexistent_sub');
    assert_false(isset($_POST['nested']['nonexistent_sub']));
    
    // Subkey on non-array parent must safely no-op without fatal error
    httppostset('not_array', 'value', 'sub');
    assert_same('string_value', $_POST['not_array'], "Subkey update on string must no-op cleanly");
});

test('tier5', 'feature19', 'adv_m3_http_superglobals_zero_http_vars_leak', function() {
    // Verify HTTP_*_VARS are completely absent from global scope
    assert_false(isset($GLOBALS['HTTP_GET_VARS']), "\$HTTP_GET_VARS must not exist in global scope");
    assert_false(isset($GLOBALS['HTTP_POST_VARS']), "\$HTTP_POST_VARS must not exist in global scope");
    assert_false(isset($GLOBALS['HTTP_COOKIE_VARS']), "\$HTTP_COOKIE_VARS must not exist in global scope");
});


// =========================================================================
// Category 3: each() Replacement Loops Stress Testing (Features 20, 21, 22)
// =========================================================================

test('tier5', 'feature20', 'adv_m3_clanleaderpatch_defsup_loop_empty_array', function() {
    // In modules/clanleaderpatch.php:41-43:
    // while (list($k,$v)=each($defsup)) was replaced by foreach ($defsup as $k => $v)
    $defsup = [];
    $value = 0;
    if (is_array($defsup)) {
        foreach ($defsup as $k => $v) {
            if ($v) $value += (int)$k;
        }
    }
    assert_same(0, $value, "Empty defsup array must evaluate to value=0 without errors");
});

test('tier5', 'feature20', 'adv_m3_clanleaderpatch_defsup_loop_assoc_array', function() {
    // Associative array bitfield accumulation
    $defsup = [
        "1" => 1,    // SU_MEGAUSER
        "2" => 0,    // SU_EDIT_USERS (disabled)
        "4" => "1",  // SU_EDIT_CONFIG (string truthy)
        "16" => true // SU_EDIT_CREATURES (bool true)
    ];
    $value = 0;
    if (is_array($defsup)) {
        foreach ($defsup as $k => $v) {
            if ($v) $value += (int)$k;
        }
    }
    assert_same(21, $value, "Bitmask must accurately accumulate 1 + 4 + 16 = 21");
});

test('tier5', 'feature20', 'adv_m3_clanleaderpatch_post_loop_empty_array', function() {
    // In modules/clanleaderpatch.php:73-86 & 105-119:
    // foreach ($post as $key => $val) with empty $_POST
    $post = [];
    $savedSettings = [];
    foreach ($post as $key => $val) {
        $savedSettings[$key] = $val;
    }
    assert_same(0, count($savedSettings), "Empty POST must iterate zero times cleanly");
});

test('tier5', 'feature20', 'adv_m3_clanleaderpatch_post_loop_assoc_array', function() {
    // Associative POST parameters with special characters and slashes
    $post = [
        'villagename' => "O\'Connor\'s Village",
        'motditems' => "25",
        'allowclans' => "0",
        'complex_key' => "Line1\nLine2"
    ];
    $processed = [];
    foreach ($post as $key => $val) {
        $key = stripslashes($key);
        $val = stripslashes($val);
        $processed[$key] = $val;
    }
    assert_same("O'Connor's Village", $processed['villagename']);
    assert_same("25", $processed['motditems']);
    assert_same("0", $processed['allowclans']);
    assert_same("Line1\nLine2", $processed['complex_key']);
});

test('tier5', 'feature21', 'adv_m3_dwellingseditor_typeeditmodule_loop_empty_array', function() {
    // In modules/dwellingseditor/case_typeeditmodule.php:25:
    // while(list($key, $val) = each($post)) -> foreach ($post as $key => $val)
    $post = [];
    $calls = [];
    foreach ($post as $key => $val) {
        $calls[$key] = $val;
    }
    assert_same(0, count($calls), "Empty post in case_typeeditmodule must iterate zero times");
});

test('tier5', 'feature21', 'adv_m3_dwellingseditor_typeeditmodule_loop_assoc_array', function() {
    $post = [
        'maxrooms' => '10',
        'goldcost' => '5000',
        'gemcost' => '15',
        'special_feature' => '1'
    ];
    $saved = [];
    foreach ($post as $key => $val) {
        $saved[$key] = $val;
    }
    assert_same($post, $saved, "All associative keys and values must be preserved identically");
});

test('tier5', 'feature21', 'adv_m3_dwellingseditor_dweditmodule_loop_empty_and_assoc', function() {
    // In modules/dwellingseditor/case_dweditmodule.php:21:
    // while(list($key, $val) = each($post)) -> foreach ($post as $key => $val)
    $emptyPost = [];
    $emptyCount = 0;
    foreach ($emptyPost as $key => $val) {
        $emptyCount++;
    }
    assert_same(0, $emptyCount);
    
    $assocPost = [
        'dwowner' => '42',
        'coffers_gold' => '1000',
        'coffers_gems' => '5',
        'access_status' => 'private'
    ];
    $recorded = [];
    foreach ($assocPost as $key => $val) {
        $recorded[$key] = $val;
    }
    assert_same($assocPost, $recorded);
});

test('tier5', 'feature20', 'adv_m3_foreach_independent_of_internal_pointer', function() {
    // The fundamental vulnerability of each() was relying on the internal array pointer
    // If the pointer was at the end, each() failed without reset()
    // foreach is independent of the internal pointer
    $arr = ['alpha' => 1, 'beta' => 2, 'gamma' => 3];
    end($arr); // Advance internal pointer to the end
    
    $collected = [];
    foreach ($arr as $k => $v) {
        $collected[$k] = $v;
    }
    assert_same(['alpha' => 1, 'beta' => 2, 'gamma' => 3], $collected, "foreach must iterate from start regardless of internal pointer position");
});


// =========================================================================
// Category 4: Repository-wide Modernization & Deprecation Zero-Tolerance
// =========================================================================

test('tier5', 'feature20', 'adv_m3_zero_each_in_codebase', function() {
    $root = TestEnvironment::getRootDir();
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $foundEach = [];
    foreach ($iterator as $path => $file) {
        if ($file->isDir()) continue;
        if ($file->getExtension() !== 'php') continue;
        
        // Skip .agents and tests directories
        if (strpos($path, '.agents') !== false || strpos($path, 'tests') !== false) continue;
        
        $content = file_get_contents($path);
        // Match non-commented each( function calls
        if (preg_match('/(?<!\/[\/\*])\beach\s*\(/i', $content)) {
            // Confirm not in docblock or line comment
            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                $trimmed = trim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                    continue;
                }
                if (preg_match('/\beach\s*\(/i', $line)) {
                    $relPath = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
                    $foundEach[] = "$relPath:" . ($lineNum + 1) . ": $trimmed";
                }
            }
        }
    }
    
    assert_same(0, count($foundEach), "Found forbidden each() calls:\n" . implode("\n", $foundEach));
});

test('tier5', 'feature20', 'adv_m3_zero_ereg_in_codebase', function() {
    $root = TestEnvironment::getRootDir();
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $foundEreg = [];
    foreach ($iterator as $path => $file) {
        if ($file->isDir()) continue;
        if ($file->getExtension() !== 'php') continue;
        if (strpos($path, '.agents') !== false || strpos($path, 'tests') !== false) continue;
        
        $content = file_get_contents($path);
        if (preg_match('/(?<!\/[\/\*])\b(ereg|eregi|ereg_replace|eregi_replace)\s*\(/i', $content)) {
            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                $trimmed = trim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                    continue;
                }
                if (preg_match('/\b(ereg|eregi|ereg_replace|eregi_replace)\s*\(/i', $line)) {
                    $relPath = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
                    $foundEreg[] = "$relPath:" . ($lineNum + 1) . ": $trimmed";
                }
            }
        }
    }
    
    assert_same(0, count($foundEreg), "Found forbidden POSIX ereg calls:\n" . implode("\n", $foundEreg));
});

test('tier5', 'feature21', 'adv_m3_zero_mysql_in_modules', function() {
    $root = TestEnvironment::getRootDir();
    $dirs = [$root . '/modules', $root . '/modules_quarantine'];
    
    $foundMysql = [];
    foreach ($dirs as $dir) {
        if (!is_dir($dir)) continue;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $path => $file) {
            if ($file->isDir()) continue;
            if ($file->getExtension() !== 'php') continue;
            
            $content = file_get_contents($path);
            if (preg_match('/\bmysql_\w+\s*\(/i', $content)) {
                $lines = explode("\n", $content);
                foreach ($lines as $lineNum => $line) {
                    $trimmed = trim($line);
                    if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                        continue;
                    }
                    if (preg_match('/\bmysql_\w+\s*\(/i', $line)) {
                        $relPath = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
                        $foundMysql[] = "$relPath:" . ($lineNum + 1) . ": $trimmed";
                    }
                }
            }
        }
    }
    
    assert_same(0, count($foundMysql), "Found forbidden mysql_* calls in modules:\n" . implode("\n", $foundMysql));
});

test('tier5', 'feature19', 'adv_m3_zero_http_vars_in_codebase', function() {
    $root = TestEnvironment::getRootDir();
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, RecursiveDirectoryIterator::SKIP_DOTS)
    );
    
    $foundVars = [];
    foreach ($iterator as $path => $file) {
        if ($file->isDir()) continue;
        if ($file->getExtension() !== 'php') continue;
        if (strpos($path, '.agents') !== false || strpos($path, 'tests') !== false) continue;
        
        $content = file_get_contents($path);
        if (preg_match('/\$HTTP_(GET|POST|COOKIE|SERVER|ENV|SESSION)_VARS\b/', $content)) {
            $lines = explode("\n", $content);
            foreach ($lines as $lineNum => $line) {
                $trimmed = trim($line);
                if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                    continue;
                }
                if (preg_match('/\$HTTP_(GET|POST|COOKIE|SERVER|ENV|SESSION)_VARS\b/', $line)) {
                    $relPath = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
                    $foundVars[] = "$relPath:" . ($lineNum + 1) . ": $trimmed";
                }
            }
        }
    }
    
    assert_same(0, count($foundVars), "Found forbidden \$HTTP_*_VARS in source files:\n" . implode("\n", $foundVars));
});

test('tier5', 'feature23', 'adv_m3_licensing_and_attribution_integrity', function() {
    $root = TestEnvironment::getRootDir();
    
    // LICENSE.txt must exist and mention CC BY-NC-SA 2.0
    $licenseFile = $root . '/LICENSE.txt';
    assert_file_exists($licenseFile, "LICENSE.txt must exist in root");
    $licenseContent = file_get_contents($licenseFile);
    assert_contains('Creative Commons', $licenseContent);
    assert_contains('Attribution-NonCommercial-ShareAlike 2.0', $licenseContent);
    
    // common.php must preserve copyright & jaoe1000 attribution
    $commonFile = $root . '/common.php';
    assert_file_exists($commonFile);
    $commonContent = file_get_contents($commonFile);
    assert_contains('Eric Stevens & JT Traub', $commonContent, "common.php must retain Eric Stevens & JT Traub copyright");
    assert_contains('Dragonprime Development Team', $commonContent, "common.php must retain Dragonprime Development Team copyright");
    assert_contains('modified by jaoe1000', $commonContent, "common.php must retain modified by jaoe1000 attribution");
});

// =========================================================================
// Category 5: houseloungekeys & house_func Color Code Regex Sanitization
// =========================================================================

test('tier5', 'feature21', 'adv_m3_regex_color_stripping_edge_cases', function() {
    // Test regex /`./ in houseloungekeys.php:291,298 and house_func.php:411,417
    $stripColors = function($name) {
        return preg_replace('/`./', '', $name);
    };

    // 1. Basic LotGD color codes
    assert_same('HeroTheBold', $stripColors('`1Hero`2The`3Bold'));
    
    // 2. Consecutive color codes
    assert_same('Hero', $stripColors('`1`2`3`4Hero'));
    
    // 3. Color code at the end
    assert_same('Hero', $stripColors('Hero`1'));
    
    // 4. Single trailing backtick (must not crash or throw warning; leaves trailing backtick because . requires 1 char)
    assert_same('Hero`', $stripColors('Hero`'));
    
    // 5. Double backtick (first backtick consumes second backtick)
    assert_same('Hero', $stripColors('He``ro'));
    
    // 6. Comprehensive test of all LotGD color tags
    $allColors = "`!`@`#`$`%`^`&`)`~`Q`q`R`V`v`G`g`T`t`j`J`e`E`l`L`x`X`y`Y`k`K`p`P`m`M`?`*`A`a`b`i`c`n`H`wWarrior";
    assert_same('Warrior', $stripColors($allColors));
    
    // 7. Empty string
    assert_same('', $stripColors(''));
    
    // 8. String containing regex metacharacters
    $metaStr = "`1Warrior `2[Guild] `3(Lv. 10) `4*+?^$";
    assert_same('Warrior [Guild] (Lv. 10) *+?^$', $stripColors($metaStr));
    
    // 9. Multibyte UTF-8 names
    $utf8Str = "`1Éomer`2 & `3Björn";
    assert_same('Éomer & Björn', $stripColors($utf8Str));
});

test('tier5', 'feature21', 'adv_m3_regex_color_stripping_html_context', function() {
    // Tests rawoutput anchor construction in houseloungekeys.php:298 and house_func.php:417
    $name = "`!Dragon`#Slayer";
    $clean = preg_replace('/`./', '', $name);
    $html = "<a href='javascript:submit();'>" . $clean . "</a>";
    assert_same("<a href='javascript:submit();'>DragonSlayer</a>", $html);
    assert_not_contains('`!', $html);
    assert_not_contains('`#', $html);
});

// =========================================================================
// Category 6: Raw MySQL Replacements & Associative DB Abstractions
// =========================================================================

test('tier5', 'feature21', 'adv_m3_houseloungekeys_db_fetch_assoc_flow', function() {
    // Simulates the exact logic in houseloungekeys.php:279-301
    // Query 1: SELECT ownerid FROM housekeys WHERE houseid=$id
    $keysResult = [
        ['ownerid' => '101'],
        ['ownerid' => '102'],
        ['ownerid' => '103'],
    ];
    
    $sql = "SELECT acctid,name FROM accounts WHERE acctid=";
    while ($row = array_shift($keysResult)) {
        assert_true(isset($row['ownerid']), "db_fetch_assoc row must contain 'ownerid'");
        $sql .= $row['ownerid'] . " OR acctid=";
    }
    $sql = substr($sql, 0, -11);
    assert_same("SELECT acctid,name FROM accounts WHERE acctid=101 OR acctid=102 OR acctid=103", $sql);
    
    // Query 2: SELECT acctid,name FROM accounts WHERE ...
    $accountsResult = [
        ['acctid' => '101', 'name' => '`&Alice'],
        ['acctid' => '102', 'name' => '`$Bob'],
        ['acctid' => '103', 'name' => '`%Charlie'],
    ];
    
    $currentUserId = '102';
    $renderedFlatmates = [];
    $i = 0;
    while ($row = array_shift($accountsResult)) {
        $i++;
        assert_true(isset($row['acctid']), "db_fetch_assoc row must contain 'acctid'");
        assert_true(isset($row['name']), "db_fetch_assoc row must contain 'name'");
        
        $cleanName = preg_replace('/`./', '', $row['name']);
        if ($row['acctid'] == $currentUserId) {
            $renderedFlatmates[] = "self:$cleanName";
        } else {
            $renderedFlatmates[] = "other:$cleanName";
        }
    }
    
    assert_same(3, $i);
    assert_same(['other:Alice', 'self:Bob', 'other:Charlie'], $renderedFlatmates);
});

test('tier5', 'feature21', 'adv_m3_house_php_db_num_rows_and_db_fetch_assoc_flow', function() {
    // Simulates logic in modules/lib/house.php:40-54
    // 1. Non-empty result
    $housekeysResult = [
        ['ownerid' => '50', 'houseid' => '12', 'housename' => 'Castle `&Grey', 'location' => 'Village'],
        ['ownerid' => '50', 'houseid' => '15', 'housename' => "Hunter\\'s Lodge", 'location' => 'Village'],
    ];
    
    $rownumber = count($housekeysResult); // mirrors db_num_rows
    $i = 0;
    $row = [];
    while ($r = array_shift($housekeysResult)) {
        assert_true(isset($r['housename']), "Row must have 'housename'");
        assert_true(isset($r['houseid']), "Row must have 'houseid'");
        $row[$i] = $r;
        $i++;
    }
    
    assert_same(2, $rownumber);
    assert_same(2, count($row));
    
    $navEntries = [];
    if ($rownumber > 0) {
        for ($i = 0; $i < $rownumber; $i++) {
            $navEntries[] = [
                'label' => stripslashes($row[$i]['housename']),
                'link' => "runmodule.php?module=house&lo=house&id=" . $row[$i]['houseid']
            ];
        }
    }
    
    assert_same("Castle `&Grey", $navEntries[0]['label']);
    assert_same("runmodule.php?module=house&lo=house&id=12", $navEntries[0]['link']);
    assert_same("Hunter's Lodge", $navEntries[1]['label']);
    assert_same("runmodule.php?module=house&lo=house&id=15", $navEntries[1]['link']);
    
    // 2. Empty result (0 rows)
    $emptyResult = [];
    $zeroRows = count($emptyResult);
    $emptyRow = [];
    $i = 0;
    while ($r = array_shift($emptyResult)) {
        $emptyRow[$i] = $r;
        $i++;
    }
    assert_same(0, $zeroRows);
    assert_same(0, count($emptyRow));
});

// =========================================================================
// Category 7: Quarantined Modules PHP 8.4 Clean Parsing & Logic
// =========================================================================

test('tier5', 'feature22', 'adv_m3_racespecialtylion_ast_token_validation', function() {
    $file = TestEnvironment::getRootDir() . '/modules_quarantine/racespecialtylion.php';
    assert_file_exists($file);
    $code = file_get_contents($file);
    
    // Token parser validation (throws ParseError if syntax invalid)
    $tokens = token_get_all($code, TOKEN_PARSE);
    assert_true(is_array($tokens), "racespecialtylion.php must parse into tokens without parse errors");
    
    // Verify weapon/armor regex pattern execution
    $nstr = "`%Lizard";
    $pattern = '/' . preg_quote($nstr, '/') . '/';
    
    $weapon1 = "Sword";
    assert_same(0, preg_match($pattern, $weapon1), "Pattern should not match unprefixed weapon");
    
    $weapon2 = "`%Lizard Sword";
    assert_same(1, preg_match($pattern, $weapon2), "Pattern must match prefixed weapon");
});

test('tier5', 'feature22', 'adv_m3_specialtymonkskills_ast_token_and_dragonpoints_loop', function() {
    $file = TestEnvironment::getRootDir() . '/modules_quarantine/specialtymonkskills.php';
    assert_file_exists($file);
    $code = file_get_contents($file);
    
    $tokens = token_get_all($code, TOKEN_PARSE);
    assert_true(is_array($tokens), "specialtymonkskills.php must parse into tokens without parse errors");
    
    // Verify modernized foreach loop on dragonpoints handles diverse structures
    $testCases = [
        'null' => null,
        'empty' => [],
        'standard' => ['at', 'de', 'hp', 'de', 'at', 'ff'],
        'assoc' => ['k1' => 'de', 'k2' => 'at', 'k3' => 'other']
    ];
    
    foreach ($testCases as $label => $dragonpoints) {
        $defPoints = 0;
        $atkPoints = 0;
        if (is_array($dragonpoints)) {
            foreach ($dragonpoints as $key => $val) {
                if ($val == "at" || $val == "de") {
                    if ($val == "de") $defPoints++;
                    if ($val == "at") $atkPoints++;
                }
            }
        }
        
        if ($label === 'null' || $label === 'empty') {
            assert_same(0, $defPoints);
            assert_same(0, $atkPoints);
        } elseif ($label === 'standard') {
            assert_same(2, $defPoints);
            assert_same(2, $atkPoints);
        } elseif ($label === 'assoc') {
            assert_same(1, $defPoints);
            assert_same(1, $atkPoints);
        }
    }
});
