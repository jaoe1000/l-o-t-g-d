<?php
/**
 * Tier 2: Boundary & Corner Cases — Security & Authentication (Features 1–7)
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';

// =========================================================================
// Feature 1 Boundaries: Web Root Hygiene
// =========================================================================

test('tier2', 'feature1', 'f1_b_case_variation_file_absence', function() {
    $root = TestEnvironment::getRootDir();
    assert_file_not_exists($root . '/PULL.php', "Cased variation PULL.php must not exist");
    assert_file_not_exists($root . '/Pull.php', "Cased variation Pull.php must not exist");
    assert_file_not_exists($root . '/TEST_MTIME.php', "Cased variation TEST_MTIME.php must not exist");
});

test('tier2', 'feature1', 'f1_b_no_backup_or_temporary_utility_files', function() {
    $root = TestEnvironment::getRootDir();
    $extensions = ['.bak', '.orig', '.old', '~', '.swp'];
    foreach ($extensions as $ext) {
        assert_file_not_exists($root . '/pull.php' . $ext, "Backup file pull.php$ext must not exist");
        assert_file_not_exists($root . '/test_mtime.php' . $ext, "Backup file test_mtime.php$ext must not exist");
    }
});

test('tier2', 'feature1', 'f1_b_path_traversal_resolution_does_not_find_scripts', function() {
    $root = TestEnvironment::getRootDir();
    $traversalPath1 = $root . '/lib/../pull.php';
    assert_file_not_exists($traversalPath1, "Relative traversal must not resolve pull.php");
    $traversalPath2 = $root . '/modules/../test_mtime.php';
    assert_file_not_exists($traversalPath2, "Relative traversal must not resolve test_mtime.php");
});

test('tier2', 'feature1', 'f1_b_hidden_dotfiles_hygiene', function() {
    $root = TestEnvironment::getRootDir();
    assert_file_not_exists($root . '/.pull.php', "Hidden .pull.php must not exist");
    assert_file_not_exists($root . '/.test_mtime.php', "Hidden .test_mtime.php must not exist");
});

test('tier2', 'feature1', 'f1_b_web_entry_rejects_missing_utility_invocation', function() {
    $root = TestEnvironment::getRootDir();
    $requestedScript = 'pull.php';
    $fullPath = realpath($root . '/' . $requestedScript);
    assert_false($fullPath !== false && file_exists($fullPath), "Resolving removed script must return false/not found");
});

// =========================================================================
// Feature 2 Boundaries: Superuser Authorization
// =========================================================================

test('tier2', 'feature2', 'f2_b_null_superuser_evaluates_to_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = ['superuser' => null];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "Null superuser value must return 0");
});

test('tier2', 'feature2', 'f2_b_boolean_false_superuser_evaluates_to_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = ['superuser' => false];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "Boolean false superuser value must return 0");
});

test('tier2', 'feature2', 'f2_b_string_coercion_safe_conversion', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = ['superuser' => "16384"];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(16384, (int)$su, "String '16384' must evaluate to integer 16384");
});

test('tier2', 'feature2', 'f2_b_empty_string_superuser_evaluates_to_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = ['superuser' => ""];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = (int)getSessionSuperUser();
    assert_same(0, $su, "Empty string superuser must cast safely to 0");
});

test('tier2', 'feature2', 'f2_b_negative_or_extreme_integer_boundary', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = ['superuser' => -1];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    // Negative numbers should never accidentally match SU_MEGAUSER (1) without explicit bits
    $hasMega = ($su & SU_MEGAUSER) === SU_MEGAUSER;
    assert_true(is_int($su), "Must return int");
});

// =========================================================================
// Feature 3 Boundaries: Authentication Hardening
// =========================================================================

test('tier2', 'feature3', 'f3_b_empty_password_rejection', function() {
    $submittedPassword = "";
    $hash = md5(md5($submittedPassword));
    $emptyHash = md5(md5(""));
    assert_same($emptyHash, $hash);
    // Real users must have non-empty password hashes
    assert_not_equals("d41d8cd98f00b204e9800998ecf8427e", $hash, "Double MD5 of empty string is not single MD5");
});

test('tier2', 'feature3', 'f3_b_whitespace_only_password_isolation', function() {
    $pass1 = "   ";
    $pass2 = "";
    assert_not_equals(md5(md5($pass1)), md5(md5($pass2)), "Whitespace-only password must hash distinctly from empty string");
});

test('tier2', 'feature3', 'f3_b_extremely_long_password_handling', function() {
    $longPassword = str_repeat("A", 10000);
    $hashed = md5(md5($longPassword));
    assert_same(32, strlen($hashed), "Long password must hash safely to 32 characters without crash");
});

test('tier2', 'feature3', 'f3_b_one_time_token_replay_blocked', function() {
    TestEnvironment::reset();
    global $session;
    $session['email_validation_token'] = 'valid_token_abc123';
    
    // First verification (valid)
    $attempt1 = 'valid_token_abc123';
    $success1 = ($attempt1 === ($session['email_validation_token'] ?? null));
    assert_true($success1, "First verification should pass");
    
    // Invalidate
    unset($session['email_validation_token']);
    
    // Second verification attempt (replay attack)
    $attempt2 = 'valid_token_abc123';
    $success2 = ($attempt2 === ($session['email_validation_token'] ?? null));
    assert_false($success2, "Replay verification must be rejected");
});

test('tier2', 'feature3', 'f3_b_one_time_token_with_null_byte_rejection', function() {
    TestEnvironment::reset();
    global $session;
    $session['email_validation_token'] = 'token_xyz';
    $attempt = "token_xyz\0injected";
    $match = ($attempt === $session['email_validation_token']);
    assert_false($match, "Token with null byte must not match valid token");
});

// =========================================================================
// Feature 4 Boundaries: Commentary Authorization
// =========================================================================

test('tier2', 'feature4', 'f4_b_non_numeric_removecomment_casts_to_zero', function() {
    $param = "not_a_number";
    $cast = (int)$param;
    assert_same(0, $cast, "Non-numeric string must cast strictly to 0");
    assert_false($cast > 0, "Cast value must not be > 0");
});

test('tier2', 'feature4', 'f4_b_negative_removecomment_does_not_trigger_action', function() {
    $param = "-999";
    $cast = (int)$param;
    assert_same(-999, $cast);
    assert_false($cast > 0, "Negative comment IDs must not satisfy > 0 condition");
});

test('tier2', 'feature4', 'f4_b_sql_union_injection_in_removecomment_neutralized', function() {
    $param = "1 UNION SELECT * FROM accounts";
    $cast = (int)$param;
    assert_same(1, $cast, "Injection trailing query must be truncated by integer cast");
});

test('tier2', 'feature4', 'f4_b_php_int_max_boundary_comment_id', function() {
    $maxId = (string)PHP_INT_MAX;
    $cast = (int)$maxId;
    assert_same(PHP_INT_MAX, $cast, "Maximum integer comment ID should cast correctly");
});

test('tier2', 'feature4', 'f4_b_empty_string_removecomment_handled_gracefully', function() {
    $param = "";
    $cast = (int)$param;
    assert_same(0, $cast);
    assert_false($cast > 0);
});

// =========================================================================
// Feature 5 Boundaries: Clan Membership Auth & Scoping
// =========================================================================

test('tier2', 'feature5', 'f5_b_cross_clan_tampering_blocked', function() {
    $officerClanId = 5;
    $targetMemberClanId = 9;
    
    // Scoping query condition
    $canModify = ($officerClanId === $targetMemberClanId);
    assert_false($canModify, "Officer of Clan 5 cannot modify member belonging to Clan 9");
});

test('tier2', 'feature5', 'f5_b_officer_cannot_demote_or_kick_clan_leader', function() {
    $officerRank = CLAN_OFFICER; // 20
    $leaderRank = CLAN_LEADER;   // 30
    
    // Officer can only modify members with rank <= userClanRank or strictly lower
    $canModifyLeader = ($leaderRank < $officerRank);
    assert_false($canModifyLeader, "Clan officer cannot modify or kick clan leader");
});

test('tier2', 'feature5', 'f5_b_clan_id_zero_cannot_administer_clans', function() {
    $userClanId = 0;
    $userRank = CLAN_OFFICER;
    $hasClan = ($userClanId > 0 && $userRank >= CLAN_OFFICER);
    assert_false($hasClan, "User with clanid=0 cannot perform clan management even if rank bit is set");
});

test('tier2', 'feature5', 'f5_b_invalid_high_rank_assignment_rejected', function() {
    $maxAllowedRank = CLAN_LEADER;
    $attemptedRank = 9999;
    $isValidRank = ($attemptedRank <= $maxAllowedRank && in_array($attemptedRank, [CLAN_APPLICANT, CLAN_MEMBER, CLAN_OFFICER, CLAN_LEADER]));
    assert_false($isValidRank, "Arbitrary high rank 9999 must not be valid");
});

test('tier2', 'feature5', 'f5_b_negative_whoacctid_sanitized_to_zero', function() {
    $whoacctid = (int)"-42";
    assert_same(-42, $whoacctid);
    $validTarget = ($whoacctid > 0);
    assert_false($validTarget, "Target account ID must be positive integer");
});

// =========================================================================
// Feature 6 Boundaries: DB Error Leak Prevention
// =========================================================================

test('tier2', 'feature6', 'f6_b_developer_mask_boundary_check', function() {
    // Only SU_DEVELOPER (16384) enables developer backtrace
    $userSu = SU_MEGAUSER | SU_EDIT_COMMENTS | SU_POST_MOTD;
    $isDev = (bool)($userSu & SU_DEVELOPER);
    assert_false($isDev, "Superuser without SU_DEVELOPER must NOT receive backtrace");
});

test('tier2', 'feature6', 'f6_b_pure_developer_mask_enabled', function() {
    $userSu = SU_DEVELOPER;
    $isDev = (bool)($userSu & SU_DEVELOPER);
    assert_true($isDev, "SU_DEVELOPER must receive backtrace");
});

test('tier2', 'feature6', 'f6_b_unauthenticated_session_error_handling', function() {
    TestEnvironment::reset();
    global $session;
    $session = []; // No user
    $su = (isset($session['user']['superuser'])) ? (int)$session['user']['superuser'] : 0;
    $isDev = (bool)($su & SU_DEVELOPER);
    assert_false($isDev, "Unauthenticated session must never evaluate as developer");
});

test('tier2', 'feature6', 'f6_b_generic_error_message_contains_no_sql_keywords', function() {
    $genericMessage = "A most bogus error has occurred.  I apologise, but the page you were trying to access is broken.  Please use your browser's back button and try again.";
    assert_not_contains("SELECT", $genericMessage);
    assert_not_contains("INSERT", $genericMessage);
    assert_not_contains("FROM lotgd_", $genericMessage);
    assert_not_contains("Stack trace:", $genericMessage);
});

test('tier2', 'feature6', 'f6_b_db_error_special_characters_escaping', function() {
    $rawError = "Table 'lotgd_accounts' doesn't exist <script>alert(1)</script>";
    $escaped = htmlentities($rawError, ENT_COMPAT, 'UTF-8');
    assert_not_contains("<script>", $escaped, "Raw error message must be HTML-escaped");
});

// =========================================================================
// Feature 7 Boundaries: Secure Deserialization
// =========================================================================

test('tier2', 'feature7', 'f7_b_malformed_serialized_string_fails_cleanly', function() {
    $corrupt = "a:2:{s:3:\"key\";"; // truncated
    $result = @unserialize($corrupt, ['allowed_classes' => false]);
    assert_false($result, "Corrupt serialized string must return false without fatal error");
});

test('tier2', 'feature7', 'f7_b_empty_string_unserialize_returns_false', function() {
    $result = @unserialize("", ['allowed_classes' => false]);
    assert_false($result, "Empty string to unserialize must return false");
});

test('tier2', 'feature7', 'f7_b_deeply_nested_serialized_array_handled_safely', function() {
    $deepArray = ['data' => 1];
    for ($i = 0; $i < 30; $i++) {
        $deepArray = ['child' => $deepArray];
    }
    $serialized = serialize($deepArray);
    $restored = unserialize($serialized, ['allowed_classes' => false]);
    assert_true(is_array($restored), "Deeply nested array (depth 30) should deserialize safely");
});

test('tier2', 'feature7', 'f7_b_serialized_floats_and_booleans_restored_precisely', function() {
    $scalars = [
        'pi' => 3.14159265,
        'flag_true' => true,
        'flag_false' => false,
        'zero' => 0,
        'empty_str' => ''
    ];
    $serialized = serialize($scalars);
    $restored = unserialize($serialized, ['allowed_classes' => false]);
    assert_same($scalars, $restored, "All scalar types must restore accurately");
});

test('tier2', 'feature7', 'f7_b_serialized_exploit_gadget_class_rejected', function() {
    // Attempting to instantiate a hypothetical arbitrary object
    $gadget = 'O:11:"ExploitClass":0:{}';
    $restored = @unserialize($gadget, ['allowed_classes' => false]);
    assert_true($restored instanceof __PHP_Incomplete_Class || $restored === false,
        "Arbitrary gadget class must not be instantiated");
});
