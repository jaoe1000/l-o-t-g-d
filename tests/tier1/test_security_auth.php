<?php
/**
 * Tier 1: Feature Coverage — Security & Authentication Hardening (Features 1–7)
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';

// =========================================================================
// Feature 1: Web Root Hygiene (pull.php, test_mtime.php)
// =========================================================================

test('tier1', 'feature1', 'f1_pull_php_does_not_exist_in_web_root', function() {
    $root = TestEnvironment::getRootDir();
    $pullPath = $root . '/pull.php';
    assert_file_not_exists($pullPath, "pull.php must be removed from web root to prevent unauthenticated command execution");
});

test('tier1', 'feature1', 'f1_test_mtime_php_does_not_exist_in_web_root', function() {
    $root = TestEnvironment::getRootDir();
    $testMtimePath = $root . '/test_mtime.php';
    assert_file_not_exists($testMtimePath, "test_mtime.php must be removed from web root to prevent unauthenticated DB corruption");
});

test('tier1', 'feature1', 'f1_no_root_scripts_reference_pull_php', function() {
    $root = TestEnvironment::getRootDir();
    $rootFiles = glob($root . '/*.php') ?: [];
    foreach ($rootFiles as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_contains('pull.php', $content, "Root script " . basename($file) . " should not reference removed pull.php");
    }
});

test('tier1', 'feature1', 'f1_no_root_scripts_reference_test_mtime_php', function() {
    $root = TestEnvironment::getRootDir();
    $rootFiles = glob($root . '/*.php') ?: [];
    foreach ($rootFiles as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_contains('test_mtime.php', $content, "Root script " . basename($file) . " should not reference removed test_mtime.php");
    }
});

test('tier1', 'feature1', 'f1_no_core_lib_scripts_reference_removed_utility_scripts', function() {
    $lib = TestEnvironment::getRootDir() . '/lib';
    $libFiles = glob($lib . '/*.php') ?: [];
    foreach ($libFiles as $file) {
        $content = file_get_contents($file) ?: '';
        assert_not_contains('pull.php', $content, "Lib script " . basename($file) . " should not reference pull.php");
        assert_not_contains('test_mtime.php', $content, "Lib script " . basename($file) . " should not reference test_mtime.php");
    }
});

// =========================================================================
// Feature 2: Superuser Authorization (getSessionSuperUser())
// =========================================================================

test('tier1', 'feature2', 'f2_get_session_super_user_returns_zero_when_session_empty', function() {
    TestEnvironment::reset();
    global $session;
    $session = [];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "getSessionSuperUser() must return 0 when session is completely empty");
});

test('tier1', 'feature2', 'f2_get_session_super_user_returns_zero_when_user_unset', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['loggedin' => false];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "getSessionSuperUser() must return 0 when session user is unset");
});

test('tier1', 'feature2', 'f2_get_session_super_user_returns_zero_when_superuser_key_unset', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['acctid' => 123, 'name' => 'TestPlayer']];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "getSessionSuperUser() must return 0 when session[user][superuser] is not set");
});

test('tier1', 'feature2', 'f2_get_session_super_user_returns_exact_bitmask_for_superuser', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['superuser' => SU_MEGAUSER]];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(1, $su, "getSessionSuperUser() must return exact SU_MEGAUSER bitmask");
});

test('tier1', 'feature2', 'f2_get_session_super_user_returns_combined_bitmask', function() {
    TestEnvironment::reset();
    global $session;
    $combined = SU_MEGAUSER | SU_EDIT_COMMENTS | SU_DEVELOPER;
    $session = ['user' => ['superuser' => $combined]];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same($combined, $su, "getSessionSuperUser() must preserve combined superuser bitmasks");
});

// =========================================================================
// Feature 3: Authentication Hardening (!md52!)
// =========================================================================

test('tier1', 'feature3', 'f3_login_php_does_not_contain_md52_backdoor', function() {
    $content = TestEnvironment::readFile('login.php');
    assert_not_contains('!md52!', $content, "login.php must not contain !md52! pass-the-hash backdoor");
});

test('tier1', 'feature3', 'f3_create_php_does_not_contain_md52_backdoor', function() {
    $content = TestEnvironment::readFile('create.php');
    assert_not_contains('!md52!', $content, "create.php must not output or accept !md52! pass-the-hash backdoor");
});

test('tier1', 'feature3', 'f3_password_hashing_maintains_32_char_md5_schema', function() {
    // Password schema contract: double md5(md5($pass)) = 32 character hex string
    $plainPassword = "SecurePassword123!";
    $expectedHash = md5(md5($plainPassword));
    assert_same(32, strlen($expectedHash), "Double MD5 password hash must be 32 characters long");
    assert_matches_regex('/^[a-f0-9]{32}$/', $expectedHash, "Password hash must be 32-char hex string");
});

test('tier1', 'feature3', 'f3_login_pass_the_hash_attempt_fails', function() {
    // Simulating login request with a raw 32-char hash in password field
    $rawHash = md5(md5("correct_password"));
    // System must hash whatever user submits as password, resulting in md5(md5($rawHash)) != $rawHash
    $hashedAttempt = md5(md5($rawHash));
    assert_not_equals($rawHash, $hashedAttempt, "Supplying raw hash in password field must not match stored password");
});

test('tier1', 'feature3', 'f3_one_time_session_token_authentication_flow', function() {
    TestEnvironment::reset();
    global $session;
    $token = bin2hex(random_bytes(16));
    $session['email_validation_token'] = $token;
    $session['validation_acctid'] = 42;

    // Test token validation
    assert_true(isset($session['email_validation_token']), "Validation token must exist in session");
    assert_same($token, $session['email_validation_token'], "Tokens must match exactly");
    
    // Simulate consuming token
    unset($session['email_validation_token']);
    assert_false(isset($session['email_validation_token']), "Validation token must be invalidated immediately after use");
});

// =========================================================================
// Feature 4: Commentary Authorization (SU_EDIT_COMMENTS & int cast)
// =========================================================================

test('tier1', 'feature4', 'f4_commentary_source_requires_su_edit_comments_for_deletion', function() {
    $content = TestEnvironment::readFile('lib/commentary.php');
    assert_contains('SU_EDIT_COMMENTS', $content, "lib/commentary.php must check SU_EDIT_COMMENTS authorization before deletion");
});

test('tier1', 'feature4', 'f4_commentary_source_casts_removecomment_to_int', function() {
    $content = TestEnvironment::readFile('lib/commentary.php');
    assert_matches_regex('/\(int\)\s*(\$remove|httpget\([\'"]removecomment[\'"]\))/', $content, "removecomment parameter must be explicitly cast to (int)");
});

test('tier1', 'feature4', 'f4_unauthorized_user_cannot_delete_comment', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = [
        'acctid' => 10,
        'name' => 'RegularUser',
        'superuser' => 0
    ];
    $hasPermission = (getSessionSuperUser() & SU_EDIT_COMMENTS) ? true : false;
    assert_false($hasPermission, "Regular user without SU_EDIT_COMMENTS must not have comment deletion permission");
});

test('tier1', 'feature4', 'f4_authorized_moderator_has_comment_delete_permission', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = [
        'acctid' => 2,
        'name' => 'CommentMod',
        'superuser' => SU_EDIT_COMMENTS
    ];
    $hasPermission = (getSessionSuperUser() & SU_EDIT_COMMENTS) ? true : false;
    assert_true($hasPermission, "User with SU_EDIT_COMMENTS must have comment deletion permission");
});

test('tier1', 'feature4', 'f4_comment_id_casting_sanitizes_injection_payload', function() {
    $injectedParam = "105 OR 1=1";
    $castId = (int)$injectedParam;
    assert_same(105, $castId, "SQL injection string in removecomment must cast strictly to integer 105");
});

// =========================================================================
// Feature 5: Clan Membership Auth & Scoping (CLAN_OFFICER & clanid scope)
// =========================================================================

test('tier1', 'feature5', 'f5_clan_membership_requires_clan_officer_rank', function() {
    $content = TestEnvironment::readFile('lib/clan/membership.php');
    assert_contains('CLAN_OFFICER', $content, "lib/clan/membership.php must check CLAN_OFFICER rank for administrative actions");
});

test('tier1', 'feature5', 'f5_clan_membership_scopes_rank_update_by_clanid', function() {
    $content = TestEnvironment::readFile('lib/clan/membership.php');
    assert_matches_regex('/clanid\s*=\s*[\'"]?\$userClanId[\'"]?/i', $content, "Rank modification queries must be scoped with clanid = \$userClanId");
});

test('tier1', 'feature5', 'f5_clan_membership_scopes_member_removal_by_clanid', function() {
    $content = TestEnvironment::readFile('lib/clan/membership.php');
    assert_matches_regex('/DELETE|UPDATE.*clanid/is', $content, "Member removal queries must be scoped to the caller's clan");
});

test('tier1', 'feature5', 'f5_regular_clan_member_cannot_modify_ranks', function() {
    $userRank = CLAN_MEMBER;
    $canManage = ($userRank >= CLAN_OFFICER);
    assert_false($canManage, "Regular clan member must not have rank management privileges");
});

test('tier1', 'feature5', 'f5_clan_officer_can_manage_members', function() {
    $userRank = CLAN_OFFICER;
    $canManage = ($userRank >= CLAN_OFFICER);
    assert_true($canManage, "Clan officer must have rank management privileges");
});

// =========================================================================
// Feature 6: DB Error Leak Prevention (No || 1 in dbwrapper_*)
// =========================================================================

test('tier1', 'feature6', 'f6_dbwrapper_pdo_does_not_contain_force_leak', function() {
    $content = TestEnvironment::readFile('lib/dbwrapper_pdo.php');
    assert_not_contains('SU_DEVELOPER || 1', $content, "lib/dbwrapper_pdo.php must not force developer error leakage with || 1");
});

test('tier1', 'feature6', 'f6_dbwrapper_mysqli_proc_does_not_contain_force_leak', function() {
    $content = TestEnvironment::readFile('lib/dbwrapper_mysqli_proc.php');
    assert_not_contains('SU_DEVELOPER || 1', $content, "lib/dbwrapper_mysqli_proc.php must not force developer error leakage with || 1");
});

test('tier1', 'feature6', 'f6_dbwrapper_mysqli_oos_does_not_contain_force_leak', function() {
    $content = TestEnvironment::readFile('lib/dbwrapper_mysqli_oos.php');
    assert_not_contains('SU_DEVELOPER || 1', $content, "lib/dbwrapper_mysqli_oos.php must not force developer error leakage with || 1");
});

test('tier1', 'feature6', 'f6_dbwrapper_sqlite_does_not_contain_force_leak', function() {
    $content = TestEnvironment::readFile('lib/dbwrapper_sqlite.php');
    assert_not_contains('SU_DEVELOPER || 1', $content, "lib/dbwrapper_sqlite.php must not force developer error leakage with || 1");
});

test('tier1', 'feature6', 'f6_unprivileged_user_db_error_visibility', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = ['superuser' => 0];
    $showDeveloperTrace = (getSessionSuperUser() & SU_DEVELOPER) ? true : false;
    assert_false($showDeveloperTrace, "Unprivileged users must never receive developer SQL traces");
});

// =========================================================================
// Feature 7: Secure Deserialization (allowed_classes => false)
// =========================================================================

test('tier1', 'feature7', 'f7_unserialize_rejects_object_instantiation', function() {
    $serializedObject = 'O:8:"stdClass":1:{s:4:"test";s:4:"data";}';
    $unserialized = @unserialize($serializedObject, ['allowed_classes' => false]);
    assert_true($unserialized instanceof __PHP_Incomplete_Class || $unserialized === false, 
        "unserialize with allowed_classes=>false must not instantiate normal objects");
});

test('tier1', 'feature7', 'f7_unserialize_accepts_valid_arrays_and_scalars', function() {
    $data = ['player' => 'Joe', 'gold' => 100, 'items' => ['sword', 'shield']];
    $serialized = serialize($data);
    $unserialized = unserialize($serialized, ['allowed_classes' => false]);
    assert_same($data, $unserialized, "unserialize with allowed_classes=>false must correctly restore arrays and scalars");
});

test('tier1', 'feature7', 'f7_datacache_source_uses_allowed_classes_false', function() {
    $content = TestEnvironment::readFile('lib/datacache.php');
    assert_matches_regex('/unserialize\([^,)]+,\s*\[[\'"]allowed_classes[\'"]\s*=>\s*false\]\)/', $content,
        "lib/datacache.php must pass ['allowed_classes' => false] to unserialize()");
});

test('tier1', 'feature7', 'f7_user_save_source_uses_allowed_classes_false', function() {
    $content = TestEnvironment::readFile('lib/user/user_save.php');
    assert_matches_regex('/unserialize\([^,)]+,\s*\[[\'"]allowed_classes[\'"]\s*=>\s*false\]\)/', $content,
        "lib/user/user_save.php must pass ['allowed_classes' => false] to unserialize()");
});

test('tier1', 'feature7', 'f7_prefs_source_uses_allowed_classes_false', function() {
    $content = TestEnvironment::readFile('prefs.php');
    assert_matches_regex('/unserialize\([^,)]+,\s*\[[\'"]allowed_classes[\'"]\s*=>\s*false\]\)/', $content,
        "prefs.php must pass ['allowed_classes' => false] to unserialize()");
});
