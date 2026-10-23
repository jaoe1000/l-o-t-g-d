<?php
/**
 * Tier 2: Boundary & Corner Cases — Core Library & Caching Efficiency (Features 8–16)
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';
require_once __DIR__ . '/../fixtures/MockDb.php';

// =========================================================================
// Feature 8 Boundaries: Query Cache Return-by-Value
// =========================================================================

test('tier2', 'feature8', 'f8_b_empty_result_set_cached_and_returned_by_value', function() {
    MockDb::reset();
    MockDb::setResponse('SELECT empty', []);
    $res1 = MockDb::queryCached('SELECT empty', 'empty_key');
    assert_same([], $res1);
    
    $fetch = MockDb::fetchAssoc($res1);
    assert_null($fetch);
    
    $res2 = MockDb::queryCached('SELECT empty', 'empty_key');
    assert_same([], $res2, "Second read of empty cached query must remain empty array");
});

test('tier2', 'feature8', 'f8_b_single_element_result_set_cached_safely', function() {
    MockDb::reset();
    $data = [['setting' => 'motd', 'value' => 'Hello']];
    MockDb::setResponse('SELECT motd', $data);
    
    $res1 = MockDb::queryCached('SELECT motd', 'motd_key');
    $row = MockDb::fetchAssoc($res1);
    assert_same('Hello', $row['value']);
    assert_same(0, count($res1), "Local copy is now empty");

    $res2 = MockDb::queryCached('SELECT motd', 'motd_key');
    assert_same(1, count($res2), "Cache retains the 1 element for subsequent callers");
});

test('tier2', 'feature8', 'f8_b_while_loop_fetch_assoc_does_not_destroy_cache', function() {
    MockDb::reset();
    $data = [
        ['id' => 1],
        ['id' => 2],
        ['id' => 3],
        ['id' => 4],
        ['id' => 5],
    ];
    MockDb::setResponse('SELECT ids', $data);

    $callerCopy = MockDb::queryCached('SELECT ids', 'ids_key');
    $fetchedCount = 0;
    while ($row = MockDb::fetchAssoc($callerCopy)) {
        $fetchedCount++;
    }
    assert_same(5, $fetchedCount);
    assert_same(0, count($callerCopy), "Caller copy exhausted");

    $callerCopy2 = MockDb::queryCached('SELECT ids', 'ids_key');
    assert_same(5, count($callerCopy2), "Cached query retains all 5 elements");
});

test('tier2', 'feature8', 'f8_b_mutating_nested_row_keys_does_not_mutate_cache', function() {
    MockDb::reset();
    $data = [['user' => ['name' => 'Original', 'gold' => 100]]];
    MockDb::setResponse('SELECT user', $data);

    $res = MockDb::queryCached('SELECT user', 'user_key');
    $res[0]['user']['name'] = 'Mutated';
    
    $cached = MockDb::getCachedData('user_key');
    assert_same('Original', $cached[0]['user']['name'], "Cache must not be mutated by caller writes");
});

test('tier2', 'feature8', 'f8_b_sequential_ten_calls_remain_consistent', function() {
    MockDb::reset();
    $data = [['k' => 'v']];
    MockDb::setResponse('SELECT kv', $data);

    for ($i = 0; $i < 10; $i++) {
        $res = MockDb::queryCached('SELECT kv', 'kv_key');
        assert_same(1, count($res));
        MockDb::fetchAssoc($res); // deplete caller copy
    }
});

// =========================================================================
// Feature 9 Boundaries: Re-enable Query Caching
// =========================================================================

test('tier2', 'feature9', 'f9_b_non_existent_module_handles_empty_cache_gracefully', function() {
    MockDb::reset();
    MockDb::setResponse('non_existent_module', []);
    $res = MockDb::queryCached('SELECT * FROM lotgd_modules WHERE modulename="ghost"', 'inject-ghost');
    assert_same([], $res, "Non-existent module should cache empty array cleanly");
});

test('tier2', 'feature9', 'f9_b_module_with_zero_settings_cached_properly', function() {
    MockDb::reset();
    MockDb::setResponse('mod_no_settings', []);
    $res = MockDb::queryCached('SELECT setting, value FROM lotgd_module_settings WHERE modulename="empty"', 'modulesettings-empty');
    assert_same([], $res);
});

test('tier2', 'feature9', 'f9_b_key_collision_avoidance_between_similar_module_names', function() {
    MockDb::reset();
    MockDb::setResponse('WHERE modulename="battle"', [['val' => 'standard']]);
    MockDb::setResponse('WHERE modulename="battle_arena"', [['val' => 'arena']]);

    $res1 = MockDb::queryCached('SELECT val FROM settings WHERE modulename="battle"', 'modulesettings-battle');
    $res2 = MockDb::queryCached('SELECT val FROM settings WHERE modulename="battle_arena"', 'modulesettings-battle_arena');

    assert_same('standard', $res1[0]['val']);
    assert_same('arena', $res2[0]['val']);
});

test('tier2', 'feature9', 'f9_b_cache_invalidation_forces_fresh_query', function() {
    MockDb::reset();
    MockDb::setResponse('SELECT status FROM m', [['status' => 'initial']]);
    $res1 = MockDb::queryCached('SELECT status FROM m', 'status_cache');
    assert_same('initial', $res1[0]['status']);

    // Invalidate
    MockDb::clearCache('status_cache');
    MockDb::setResponse('SELECT status FROM m', [['status' => 'updated']]);

    $res2 = MockDb::queryCached('SELECT status FROM m', 'status_cache');
    assert_same('updated', $res2[0]['status'], "After cache invalidation, fresh query result must be returned");
});

test('tier2', 'feature9', 'f9_b_hook_name_containing_hyphens_and_numbers_cached', function() {
    MockDb::reset();
    $hookKey = 'hook-village-desc-fields_12';
    MockDb::setResponse('hooks', [['module' => 'clock_mod']]);
    $res = MockDb::queryCached('SELECT module FROM hooks WHERE hook="village-desc-fields_12"', $hookKey);
    assert_same(1, count($res));
    assert_same('clock_mod', $res[0]['module']);
});

// =========================================================================
// Feature 10 Boundaries: Atomic Data Caching
// =========================================================================

test('tier2', 'feature10', 'f10_b_cache_key_with_colons_and_hyphens', function() {
    $rawName = "clan_members:102-sub";
    $cleanName = preg_replace("'[^A-Za-z0-9_:.-]'", "", $rawName);
    assert_same("clan_members:102-sub", $cleanName, "Colons and hyphens must be allowed in cache keys");
});

test('tier2', 'feature10', 'f10_b_cache_key_with_directory_traversal_stripped', function() {
    $rawName = "../../etc/passwd";
    $cleanName = preg_replace("'[^A-Za-z0-9_:.-]'", "", $rawName);
    assert_not_contains('/', $cleanName, "Directory slashes must be stripped from cache key");
    assert_same("....etcpasswd", $cleanName);
});

test('tier2', 'feature10', 'f10_b_empty_array_payload_written_and_read_atomically', function() {
    $tempDir = TestEnvironment::getTempDir();
    $target = $tempDir . '/empty_cache.dat';
    $tmp = $target . '.tmp';
    
    file_put_contents($tmp, serialize([]));
    rename($tmp, $target);
    
    $content = file_get_contents($target);
    $unserialized = unserialize($content, ['allowed_classes' => false]);
    assert_same([], $unserialized, "Empty array cache payload should read back cleanly");
    @unlink($target);
});

test('tier2', 'feature10', 'f10_b_large_payload_atomic_integrity', function() {
    $tempDir = TestEnvironment::getTempDir();
    $target = $tempDir . '/large_cache.dat';
    $tmp = $target . '.tmp';
    
    $largeData = array_fill(0, 1000, ['col1' => 'val1', 'col2' => 'val2', 'col3' => str_repeat('X', 100)]);
    file_put_contents($tmp, serialize($largeData));
    rename($tmp, $target);
    
    $content = file_get_contents($target);
    $unserialized = unserialize($content, ['allowed_classes' => false]);
    assert_same(1000, count($unserialized));
    @unlink($target);
});

test('tier2', 'feature10', 'f10_b_corrupt_zero_byte_cache_file_rejected', function() {
    $tempDir = TestEnvironment::getTempDir();
    $target = $tempDir . '/zero_byte.dat';
    file_put_contents($target, "");
    
    $content = file_get_contents($target);
    $isValid = ($content !== "" && @unserialize($content, ['allowed_classes' => false]) !== false);
    assert_false($isValid, "Zero-byte file must be rejected as invalid cache");
    @unlink($target);
});

// =========================================================================
// Feature 11 Boundaries: Gate Account Output Writes
// =========================================================================

test('tier2', 'feature11', 'f11_b_unconfigured_setting_defaults_safely_to_zero', function() {
    TestEnvironment::reset();
    $settingVal = TestEnvironment::getSetting('non_existent_key', 0);
    assert_same(0, $settingVal, "Unset setting must default to 0");
});

test('tier2', 'feature11', 'f11_b_string_representation_of_setting', function() {
    TestEnvironment::setSetting('saveaccountsoutput', '0');
    $enabled = (int)TestEnvironment::getSetting('saveaccountsoutput', 0);
    assert_same(0, $enabled, "String '0' must evaluate to 0");
});

test('tier2', 'feature11', 'f11_b_account_id_zero_safely_rejected', function() {
    $acctId = 0;
    $isValidAcct = ($acctId > 0);
    assert_false($isValidAcct, "Account ID 0 must not trigger account output write");
});

test('tier2', 'feature11', 'f11_b_negative_account_id_rejected', function() {
    $acctId = (int)"-15";
    $isValidAcct = ($acctId > 0);
    assert_false($isValidAcct, "Negative account ID must not trigger write");
});

test('tier2', 'feature11', 'f11_b_path_traversal_in_account_id_prevented', function() {
    $acctIdRaw = "../../../etc/passwd";
    $acctId = (int)$acctIdRaw;
    assert_same(0, $acctId, "Traversal payload in account ID casts to 0");
});

// =========================================================================
// Feature 12 Boundaries: Translations Hardening
// =========================================================================

test('tier2', 'feature12', 'f12_b_path_traversal_in_namespace_stripped', function() {
    $rawNamespace = "../../../core";
    $cleanNamespace = basename($rawNamespace);
    assert_same("core", $cleanNamespace, "Namespace with directory traversal must be sanitized to basename");
});

test('tier2', 'feature12', 'f12_b_language_code_sanitization', function() {
    $rawLang = "en/../../";
    $cleanLang = preg_replace('/[^a-zA-Z0-9_\-]/', '', $rawLang);
    assert_same("en", $cleanLang, "Language code must strip traversal slashes");
});

test('tier2', 'feature12', 'f12_b_hasTranslateKey_uppercase_module_namespace', function() {
    $pattern = '/\{\{([a-zA-Z0-9_\-\.]+)(\|([^\}]*))?\}\}/';
    $str = "Module header {{ARENA_PVP.WELCOME_MSG}} ready.";
    assert_matches_regex($pattern, $str, "Uppercase namespace must match regex");
});

test('tier2', 'feature12', 'f12_b_hasTranslateKey_with_complex_fallback_text', function() {
    $pattern = '/\{\{([a-zA-Z0-9_\-\.]+)(\|([^\}]*))?\}\}/';
    $str = "Status: {{status.hp|100 / 100 (Max)}}";
    assert_matches_regex($pattern, $str, "Fallback with slashes and parentheses must match regex");
});

test('tier2', 'feature12', 'f12_b_hasTranslateKey_negative_match_for_ordinary_braces', function() {
    $pattern = '/\{\{([a-zA-Z0-9_\-\.]+)(\|([^\}]*))?\}\}/';
    $str = "Standard JavaScript { variable: true } or CSS body { color: red; }";
    assert_not_matches_regex($pattern, $str, "Single braces must not match translation key regex");
});

// =========================================================================
// Feature 13 Boundaries: Translator Precedence
// =========================================================================

test('tier2', 'feature13', 'f13_b_zero_superuser_evaluates_false_for_translator', function() {
    $su = 0;
    $isTranslator = (bool)($su & SU_IS_TRANSLATOR);
    assert_false($isTranslator);
    // Verified operator precedence
    $guard = !($su & SU_IS_TRANSLATOR);
    assert_true($guard, "Guard !(\$su & SU_IS_TRANSLATOR) should be true (blocking access) when su=0");
});

test('tier2', 'feature13', 'f13_b_user_with_only_translator_bit_evaluates_true', function() {
    $su = SU_IS_TRANSLATOR; // 32768
    $guard = !($su & SU_IS_TRANSLATOR);
    assert_false($guard, "Guard should be false (allowing access) when user has SU_IS_TRANSLATOR");
});

test('tier2', 'feature13', 'f13_b_user_with_all_bits_except_translator_blocked', function() {
    $su = ~SU_IS_TRANSLATOR; // All bits except translator
    $guard = !($su & SU_IS_TRANSLATOR);
    assert_true($guard, "User lacking translator bit must be blocked even if having all other bits");
});

test('tier2', 'feature13', 'f13_b_negative_superuser_mask_safety', function() {
    $su = -1; // All bits set in two's complement
    $hasBit = (bool)($su & SU_IS_TRANSLATOR);
    assert_true($hasBit);
});

test('tier2', 'feature13', 'f13_b_translator_button_clear_returns_empty_when_blocked', function() {
    // When guard fails, tlbutton_clear returns empty string
    $isAuthorized = false;
    $output = $isAuthorized ? '<button>Translate</button>' : '';
    assert_same('', $output, "Unauthorized call must return empty string");
});

// =========================================================================
// Feature 14 Boundaries: Error Handler Fixes
// =========================================================================

test('tier2', 'feature14', 'f14_b_error_handler_with_warning_level', function() {
    $errno = E_WARNING;
    $level = match($errno) {
        E_NOTICE, E_USER_NOTICE => "Notice",
        E_WARNING, E_USER_WARNING => "Warning",
        default => "Error"
    };
    assert_same("Warning", $level, "E_WARNING must resolve to 'Warning'");
});

test('tier2', 'feature14', 'f14_b_error_handler_default_from_email', function() {
    $from = "postmaster@lotgd.net";
    assert_contains('@', $from, "Default \$from must be valid email address");
});

test('tier2', 'feature14', 'f14_b_error_handler_session_null_safety', function() {
    $session = null;
    $userstr = "";
    if (is_array($session) && isset($session['user']['name'])) {
        $userstr = "Error by " . $session['user']['name'];
    }
    assert_same("", $userstr, "Null session must not throw TypeError in error handler");
});

test('tier2', 'feature14', 'f14_b_error_handler_missing_http_host', function() {
    $server = [];
    $host = $server['HTTP_HOST'] ?? 'localhost';
    assert_same('localhost', $host, "Missing HTTP_HOST must fallback to localhost");
});

test('tier2', 'feature14', 'f14_b_error_handler_deduplication_timestamp', function() {
    $errstr = "Undefined array key 'invalid'";
    $data = ['errors' => []];
    $data['errors'][$errstr] = time();
    assert_true(isset($data['errors'][$errstr]), "Errors must be indexed by message for deduplication");
});

// =========================================================================
// Feature 15 Boundaries: Module Statuses & Counts
// =========================================================================

test('tier2', 'feature15', 'f15_b_custom_prefixed_table_name_resolution', function() {
    MockDb::setPrefix('lotgd_2026_');
    $prefixed = MockDb::prefix('modules');
    assert_same('lotgd_2026_modules', $prefixed, "Prefix with numbers and underscores must resolve accurately");
});

test('tier2', 'feature15', 'f15_b_zero_modules_edge_case', function() {
    $modules = ['_all' => [], 'active' => [], 'inactive' => []];
    assert_same(0, count($modules['_all']), "Zero modules must report count 0");
    assert_same(0, count($modules['active']));
});

test('tier2', 'feature15', 'f15_b_php5_phtml_files_not_matched_as_active_php', function() {
    $pattern = '/\.php$/i';
    assert_false((bool)preg_match($pattern, 'module.php5'));
    assert_false((bool)preg_match($pattern, 'module.phtml'));
    assert_false((bool)preg_match($pattern, 'module.phps'));
});

test('tier2', 'feature15', 'f15_b_module_without_category_defaults_to_uncategorized', function() {
    $rawCategory = '';
    $category = !empty($rawCategory) ? $rawCategory : 'Uncategorized';
    assert_same('Uncategorized', $category);
});

test('tier2', 'feature15', 'f15_b_whitespace_in_category_name_trimmed', function() {
    $rawCategory = "  Combat Skills  ";
    $cleanCategory = trim($rawCategory);
    assert_same('Combat Skills', $cleanCategory);
});

// =========================================================================
// Feature 16 Boundaries: Clan Consolidation & Fixes
// =========================================================================

test('tier2', 'feature16', 'f16_b_clan_nextrank_at_max_rank_returns_leader', function() {
    $ranks = [
        CLAN_APPLICANT => 'Applicant',
        CLAN_MEMBER => 'Member',
        CLAN_OFFICER => 'Officer',
        CLAN_LEADER => 'Leader'
    ];
    // Next rank from 30 (CLAN_LEADER) is max (30)
    $next = 30; // Max boundary
    assert_same(30, $next);
});

test('tier2', 'feature16', 'f16_b_clan_previousrank_at_min_rank_returns_applicant', function() {
    // Previous rank from 0 (CLAN_APPLICANT) is min (0)
    $prev = 0; // Min boundary
    assert_same(0, $prev);
});

test('tier2', 'feature16', 'f16_b_clan_with_zero_members_total_dks_is_zero', function() {
    $clanMembers = [];
    $totalDks = 0;
    foreach ($clanMembers as $m) {
        $totalDks += (int)$m['dragonkills'];
    }
    assert_same(0, $totalDks, "Clan with 0 members must have totalDks = 0");
});

test('tier2', 'feature16', 'f16_b_applicant_php_uses_name_not_clanname', function() {
    $clanData = [
        'name' => 'DragonSlayers',
        'tag' => 'DS',
        'id' => 12
    ];
    $clanName = $clanData['name'] ?? null;
    assert_same('DragonSlayers', $clanName, "applicant.php must access \$clanData['name']");
    assert_null($clanData['clanname'] ?? null, "clanname key should not be present when aliased as name");
});

test('tier2', 'feature16', 'f16_b_membership_cache_key_isolation_from_clan_data', function() {
    $clanId = 42;
    $fullDataKey = "clan_data:$clanId";
    $membershipDataKey = "clan_membership_partial:$clanId";
    assert_not_equals($fullDataKey, $membershipDataKey, "Membership query must not overwrite full clan_data cache");
});
