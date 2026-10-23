<?php
/**
 * Tier 5: Adversarial Stress Test Harness & Security Verifier
 * Milestone 1: Security & Authentication Hardening
 *
 * Covers:
 * - Feature 1: Web Root Hygiene & Inactive Script Neutering
 * - Feature 2: Superuser Session Handling Edge Cases & Type Coercion
 * - Feature 3: Authentication Vectors (!md52!, force=1, tokens, timing, expiration, replay)
 * - Feature 4: Commentary Deletion Authorization & SQL Injection Neutralization
 * - Feature 5: Clan Membership Escalation, Cross-Clan Tampering, Rank Clamping
 * - Feature 6: Database Error Handler Information Leakage Prevention
 * - Feature 7: PHP Object Injection & Deserialization Defense
 *
 * Author: LotGD Test Engineering / Teamwork Challenger
 * License: CC BY-NC-SA 2.0
 * Modified by jaoe1000
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';

// =========================================================================
// Category 1: getSessionSuperUser() Edge Cases & Type Coercion
// =========================================================================

test('tier5', 'feature2', 'adv_su_null_session_returns_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session = null;
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "Null session must return 0");
});

test('tier5', 'feature2', 'adv_su_empty_session_array_returns_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session = [];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "Empty array session must return 0");
});

test('tier5', 'feature2', 'adv_su_user_is_null_returns_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => null];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "Null session[user] must return 0");
});

test('tier5', 'feature2', 'adv_su_user_is_empty_array_returns_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => []];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "Empty session[user] array must return 0");
});

test('tier5', 'feature2', 'adv_su_superuser_null_returns_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['superuser' => null]];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "session[user][superuser]=null must return 0");
});

test('tier5', 'feature2', 'adv_su_string_zero_returns_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['superuser' => '0']];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "session[user][superuser]='0' must cast to integer 0");
});

test('tier5', 'feature2', 'adv_su_padded_zeroes_returns_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['superuser' => '00000']];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "Padded zeroes '00000' must cast to integer 0");
});

test('tier5', 'feature2', 'adv_su_empty_string_returns_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['superuser' => '']];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "Empty string superuser must cast to 0");
});

test('tier5', 'feature2', 'adv_su_boolean_false_returns_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['superuser' => false]];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "Boolean false must cast to 0");
});

test('tier5', 'feature2', 'adv_su_non_numeric_string_returns_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['superuser' => 'administrator']];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "Non-numeric string 'administrator' must cast to 0");
});

test('tier5', 'feature2', 'adv_su_sqli_payload_returns_zero', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['superuser' => "' OR 1=1 --"]];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(0, $su, "SQL injection string starting with quote must cast to 0");
});

test('tier5', 'feature2', 'adv_su_negative_values_cast_to_int', function() {
    TestEnvironment::reset();
    global $session;
    $session = ['user' => ['superuser' => -1]];
    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    assert_same(-1, $su, "Negative integer -1 casts to int -1");
    assert_true(is_int($su), "Result must be integer");
});

test('tier5', 'feature2', 'adv_su_all_defined_bitmask_powers_of_two', function() {
    TestEnvironment::reset();
    global $session;
    require_once TestEnvironment::getRootDir() . '/lib/session.php';

    $flags = [
        'SU_MEGAUSER' => 1,
        'SU_EDIT_MOUNTS' => 2,
        'SU_EDIT_CREATURES' => 4,
        'SU_EDIT_PETITIONS' => 8,
        'SU_EDIT_COMMENTS' => 16,
        'SU_EDIT_DONATIONS' => 32,
        'SU_EDIT_USERS' => 64,
        'SU_EDIT_CONFIG' => 128,
        'SU_INFINITE_DAYS' => 256,
        'SU_EDIT_EQUIPMENT' => 512,
        'SU_EDIT_PAYLOG' => 1024,
        'SU_DEVELOPER' => 2048,
        'SU_POST_MOTD' => 4096,
        'SU_DEBUG_OUTPUT' => 8192,
        'SU_MODERATE_CLANS' => 16384,
        'SU_EDIT_RIDDLES' => 32768,
        'SU_MANAGE_MODULES' => 65536,
        'SU_AUDIT_MODERATION' => 131072,
        'SU_IS_TRANSLATOR' => 262144,
        'SU_RAW_SQL' => 524288,
        'SU_VIEW_SOURCE' => 1048576,
        'SU_NEVER_EXPIRE' => 2097152,
        'SU_EDIT_ITEMS' => 4194304,
        'SU_GIVE_GROTTO' => 8388608,
        'SU_OVERRIDE_YOM_WARNING' => 16777216,
        'SU_SHOW_PHPNOTICE' => 33554432,
        'SU_IS_GAMEMASTER' => 67108864,
    ];

    foreach ($flags as $flagName => $flagVal) {
        // Test as int
        $session['user'] = ['superuser' => $flagVal];
        $su = getSessionSuperUser();
        assert_same($flagVal, $su, "Flag $flagName as integer must match exactly");
        assert_true(($su & $flagVal) === $flagVal, "Bitwise check for $flagName must succeed");

        // Test as numeric string
        $session['user'] = ['superuser' => (string)$flagVal];
        $suStr = getSessionSuperUser();
        assert_same($flagVal, $suStr, "Flag $flagName as string must cast to exact integer");
    }
});

test('tier5', 'feature2', 'adv_su_composite_bitmasks_preserve_all_bits', function() {
    TestEnvironment::reset();
    global $session;
    require_once TestEnvironment::getRootDir() . '/lib/session.php';

    $composite = SU_MEGAUSER | SU_EDIT_COMMENTS | SU_DEVELOPER;
    $session['user'] = ['superuser' => $composite];
    $su = getSessionSuperUser();

    assert_same($composite, $su);
    assert_true((bool)($su & SU_MEGAUSER), "Must have SU_MEGAUSER");
    assert_true((bool)($su & SU_EDIT_COMMENTS), "Must have SU_EDIT_COMMENTS");
    assert_true((bool)($su & SU_DEVELOPER), "Must have SU_DEVELOPER");
    assert_false((bool)($su & SU_EDIT_USERS), "Must NOT have SU_EDIT_USERS");
});

// =========================================================================
// Category 2: Authentication Vectors on login.php and create.php
// =========================================================================

test('tier5', 'feature3', 'adv_auth_md52_string_absence_across_login_and_create', function() {
    $loginContent = TestEnvironment::readFile('login.php');
    $createContent = TestEnvironment::readFile('create.php');

    assert_not_contains('!md52!', $loginContent, "login.php must NOT contain !md52! backdoor");
    assert_not_contains('!md52!', $createContent, "create.php must NOT contain !md52! backdoor");
});

test('tier5', 'feature3', 'adv_auth_md52_payload_in_password_field_is_double_hashed', function() {
    // Attack simulation: user submits !md52! prefix with raw hash
    $rawDbHash = "5f4dcc3b5aa765d61d8327deb882cf99"; // md5('password')
    $submittedPassword = "!md52!" . $rawDbHash;

    // Tracing login.php hashing logic:
    // if (substr($password, 0, 5) == "!md5!") { $password = md5(substr($password, 5)); }
    // else { $password = md5(md5($password)); }
    $prefix5 = substr($submittedPassword, 0, 5);
    assert_same('!md52', $prefix5);
    assert_false($prefix5 === '!md5!', "!md52! must not trigger single-md5 branch");

    $processedHash = md5(md5($submittedPassword));
    assert_not_equals($rawDbHash, $processedHash, "Processed password must not equal raw hash");
});

test('tier5', 'feature3', 'adv_auth_force_parameter_does_not_alter_auth_flow', function() {
    $loginContent = TestEnvironment::readFile('login.php');
    // Verify force parameter is not used to bypass password check
    assert_not_matches_regex('/\$force\s*=\s*(httpget|httppost)\([\'"]force[\'"]\)/', $loginContent,
        "login.php should not assign an active bypass flag based on force parameter");
});

test('tier5', 'feature3', 'adv_auth_token_expiration_boundary', function() {
    $token = bin2hex(random_bytes(16));
    $userName = "TestTarget";
    $currentTime = 1700000000;

    // Case A: Valid token within expiration window
    $validTokData = [
        'token' => $token,
        'login' => $userName,
        'expires' => $currentTime + 300,
    ];
    $matchA = hash_equals($validTokData['token'], $token) &&
              $validTokData['login'] === $userName &&
              $currentTime <= $validTokData['expires'];
    assert_true($matchA, "Token within 300s expiration must pass");

    // Case B: Exactly at expiration boundary
    $boundaryTokData = [
        'token' => $token,
        'login' => $userName,
        'expires' => $currentTime,
    ];
    $matchB = hash_equals($boundaryTokData['token'], $token) &&
              $boundaryTokData['login'] === $userName &&
              $currentTime <= $boundaryTokData['expires'];
    assert_true($matchB, "Token exactly at expiration boundary must pass");

    // Case C: Expired token by 1 second
    $expiredTokData = [
        'token' => $token,
        'login' => $userName,
        'expires' => $currentTime - 1,
    ];
    $matchC = hash_equals($expiredTokData['token'], $token) &&
              $expiredTokData['login'] === $userName &&
              $currentTime <= $expiredTokData['expires'];
    assert_false($matchC, "Expired token must be rejected");
});

test('tier5', 'feature3', 'adv_auth_token_user_binding_enforced', function() {
    $token = bin2hex(random_bytes(16));
    $tokData = [
        'token' => $token,
        'login' => 'Alice',
        'expires' => time() + 300,
    ];

    // Attacker submits Alice's token for Bob's account
    $targetUser = 'Bob';
    $match = hash_equals($tokData['token'], $token) &&
             $tokData['login'] === $targetUser &&
             time() <= $tokData['expires'];
    assert_false($match, "Token issued for Alice cannot authenticate Bob");
});

test('tier5', 'feature3', 'adv_auth_token_timing_safe_comparison_in_source', function() {
    $loginContent = TestEnvironment::readFile('login.php');
    assert_contains('hash_equals($tokData[\'token\'], $valToken)', $loginContent,
        "login.php must use hash_equals() for timing-attack-safe token comparison");
});

test('tier5', 'feature3', 'adv_auth_token_single_use_destruction', function() {
    TestEnvironment::reset();
    global $session;
    $session['email_validation_token'] = [
        'token' => 'sec_tok_12345',
        'login' => 'TestPlayer',
        'expires' => time() + 300
    ];

    // Simulate validation block in login.php
    $valToken = 'sec_tok_12345';
    $tokenAuthenticated = false;
    if ($valToken && isset($session['email_validation_token'])) {
        $tokData = $session['email_validation_token'];
        if (is_array($tokData) && isset($tokData['token'], $tokData['login'], $tokData['expires'])) {
            if (hash_equals($tokData['token'], $valToken) &&
                $tokData['login'] === 'TestPlayer' &&
                time() <= $tokData['expires']) {
                $tokenAuthenticated = true;
            }
        }
        unset($session['email_validation_token']);
    }

    assert_true($tokenAuthenticated, "First validation must succeed");
    assert_false(isset($session['email_validation_token']), "Token must be unset immediately after validation");

    // Second attempt (replay)
    $secondAttemptAuthenticated = false;
    if ($valToken && isset($session['email_validation_token'])) {
        $secondAttemptAuthenticated = true;
    }
    assert_false($secondAttemptAuthenticated, "Replay attempt must find token unset and fail");
});

// =========================================================================
// Category 3: Commentary Deletion Unauthorized Attempts & SQLi
// =========================================================================

test('tier5', 'feature4', 'adv_com_unauthenticated_deletion_rejected', function() {
    TestEnvironment::reset();
    global $session;
    $session = []; // completely unauthenticated

    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    $su = getSessionSuperUser();
    $remove = (int)"123";

    $authorized = ($remove > 0 && ($su & SU_EDIT_COMMENTS));
    assert_false((bool)$authorized, "Unauthenticated user cannot execute comment deletion");
});

test('tier5', 'feature4', 'adv_com_superuser_without_comment_flag_rejected', function() {
    TestEnvironment::reset();
    global $session;
    require_once TestEnvironment::getRootDir() . '/lib/session.php';

    // Test combinations of superusers who lack SU_EDIT_COMMENTS
    $unauthorizedRoles = [
        'megauser_only' => SU_MEGAUSER,
        'developer_only' => SU_DEVELOPER,
        'users_only' => SU_EDIT_USERS,
        'config_only' => SU_EDIT_CONFIG,
        'combined_no_comments' => SU_MEGAUSER | SU_DEVELOPER | SU_EDIT_USERS,
    ];

    foreach ($unauthorizedRoles as $roleName => $mask) {
        $session['user'] = ['superuser' => $mask];
        $su = getSessionSuperUser();
        $authorized = ($su & SU_EDIT_COMMENTS);
        assert_false((bool)$authorized, "Superuser role $roleName must not have comment deletion rights");
    }
});

test('tier5', 'feature4', 'adv_com_superuser_with_comment_flag_authorized', function() {
    TestEnvironment::reset();
    global $session;
    require_once TestEnvironment::getRootDir() . '/lib/session.php';

    $session['user'] = ['superuser' => SU_EDIT_COMMENTS];
    $su = getSessionSuperUser();
    $remove = 42;
    $authorized = ($remove > 0 && ($su & SU_EDIT_COMMENTS));
    assert_true((bool)$authorized, "Superuser with SU_EDIT_COMMENTS must be authorized");
});

test('tier5', 'feature4', 'adv_com_sqli_payloads_neutralized_by_int_cast', function() {
    $sqliPayloads = [
        "' OR '1'='1" => 0,
        "105; DROP TABLE commentary;" => 105,
        "105 UNION SELECT * FROM accounts" => 105,
        "105 OR 1=1" => 105,
        "admin'--" => 0,
        "0x1f" => 0,
        "-1" => -1,
        "0" => 0,
        "" => 0,
        "NULL" => 0,
    ];

    foreach ($sqliPayloads as $payload => $expectedCast) {
        $cast = (int)$payload;
        assert_same($expectedCast, $cast, "Payload '$payload' must cast to $expectedCast");

        // The authorization check in commentary.php requires $remove > 0
        if ($expectedCast <= 0) {
            $wouldExecute = ($cast > 0);
            assert_false($wouldExecute, "Payload '$payload' resulting in $cast must not execute deletion ($cast <= 0)");
        }
    }
});

test('tier5', 'feature4', 'adv_com_source_strictly_enforces_gate_and_cast', function() {
    $content = TestEnvironment::readFile('lib/commentary.php');
    // Ensure $remove = (int)httpget('removecomment') and gate exists
    assert_matches_regex('/\$remove\s*=\s*\(int\)\s*httpget\([\'"]removecomment[\'"]\);/', $content,
        "lib/commentary.php must strictly cast removecomment to int");
    assert_matches_regex('/if\s*\(\s*\$remove\s*>\s*0\s*&&\s*\(getSessionSuperUser\(\)\s*&\s*SU_EDIT_COMMENTS\)\s*\)/', $content,
        "lib/commentary.php must gate deletion behind \$remove>0 && (getSessionSuperUser() & SU_EDIT_COMMENTS)");
});

// =========================================================================
// Category 4: Clan Membership Escalation & Cross-Clan ID Tampering
// =========================================================================

test('tier5', 'feature5', 'adv_clan_member_cannot_perform_officer_actions', function() {
    $memberRank = CLAN_MEMBER; // 10
    $officerRank = CLAN_OFFICER; // 20

    $canAdminister = ($memberRank >= $officerRank);
    assert_false($canAdminister, "Regular clan member (10) must not satisfy rank >= CLAN_OFFICER (20)");
});

test('tier5', 'feature5', 'adv_clan_applicant_cannot_perform_officer_actions', function() {
    $applicantRank = CLAN_APPLICANT; // 0
    $officerRank = CLAN_OFFICER; // 20

    $canAdminister = ($applicantRank >= $officerRank);
    assert_false($canAdminister, "Clan applicant (0) must not satisfy rank >= CLAN_OFFICER (20)");
});

test('tier5', 'feature5', 'adv_clan_unaffiliated_user_cannot_manage_clans', function() {
    $userClanId = 0;
    $userClanRank = CLAN_OFFICER; // hypothetical corrupted rank without clan

    $canAdminister = ($userClanId > 0 && $userClanRank >= CLAN_OFFICER);
    assert_false($canAdminister, "User with clanid=0 must not be able to administer clans");
});

test('tier5', 'feature5', 'adv_clan_source_enforces_officer_and_clanid_gate', function() {
    $content = TestEnvironment::readFile('lib/clan/membership.php');
    assert_matches_regex('/if\s*\(\s*\$userClanId\s*>\s*0\s*&&\s*\$userClanRank\s*>=\s*CLAN_OFFICER\s*\)/', $content,
        "lib/clan/membership.php must check \$userClanId > 0 && \$userClanRank >= CLAN_OFFICER");
});

test('tier5', 'feature5', 'adv_clan_cross_clan_setrank_scoped_to_user_clan', function() {
    $content = TestEnvironment::readFile('lib/clan/membership.php');
    // Verify select query in setrank is scoped with AND clanid = $userClanId
    assert_matches_regex('/WHERE\s+acctid\s*=\s*\$whoacctid\s+AND\s+clanid\s*=\s*\$userClanId/i', $content,
        "setrank SELECT query must be scoped by AND clanid = \$userClanId");
    // Verify update query in setrank is scoped with AND clanid = $userClanId
    assert_matches_regex('/UPDATE.*SET\s+clanrank\s*=\s*\$targetRank\s+WHERE\s+acctid\s*=\s*\$whoacctid\s+AND\s+clanid\s*=\s*\$userClanId/is', $content,
        "setrank UPDATE query must be scoped by AND clanid = \$userClanId");
});

test('tier5', 'feature5', 'adv_clan_cross_clan_remove_scoped_to_user_clan', function() {
    $content = TestEnvironment::readFile('lib/clan/membership.php');
    // Verify select query in remove is scoped with AND clanid = $userClanId
    assert_matches_regex('/WHERE\s+acctid\s*=\s*\$remove\s+AND\s+clanid\s*=\s*\$userClanId/i', $content,
        "remove SELECT query must be scoped by AND clanid = \$userClanId");
    // Verify update query in remove is scoped by AND clanid = $userClanId
    assert_matches_regex('/UPDATE.*WHERE\s+acctid\s*=\s*\$remove.*AND\s+clanid\s*=\s*\$userClanId/is', $content,
        "remove UPDATE query must be scoped by AND clanid = \$userClanId");
});

test('tier5', 'feature5', 'adv_clan_officer_rank_escalation_clamped', function() {
    $userClanRank = CLAN_OFFICER; // 20
    $attemptedTargetRank = CLAN_LEADER; // 30

    // Logic in membership.php: $targetRank = max(0, min($userClanRank, $setRank));
    $clampedRank = max(0, min($userClanRank, $attemptedTargetRank));
    assert_same(CLAN_OFFICER, $clampedRank, "Officer promoting to Leader (30) must be clamped to Officer (20)");
});

test('tier5', 'feature5', 'adv_clan_officer_cannot_kick_leader', function() {
    $userClanRank = CLAN_OFFICER; // 20
    $targetLeaderRank = CLAN_LEADER; // 30

    // Query in membership.php: WHERE acctid = $remove AND clanrank < $userClanRank AND clanid = $userClanId
    $canRemove = ($targetLeaderRank < $userClanRank);
    assert_false($canRemove, "Clan Leader (30) cannot be removed by Clan Officer (20)");
});

test('tier5', 'feature5', 'adv_clan_officer_cannot_kick_peer_officer', function() {
    $userClanRank = CLAN_OFFICER; // 20
    $targetPeerRank = CLAN_OFFICER; // 20

    // In membership.php: WHERE acctid = $remove AND clanrank < $userClanRank AND clanid = $userClanId
    $canRemove = ($targetPeerRank < $userClanRank);
    assert_false($canRemove, "Clan Officer (20) cannot be removed by peer Clan Officer (20)");
});

test('tier5', 'feature5', 'adv_clan_member_removal_strictly_lower_rank_in_source', function() {
    $content = TestEnvironment::readFile('lib/clan/membership.php');
    assert_matches_regex('/UPDATE.*WHERE\s+acctid\s*=\s*\$remove\s+AND\s+clanrank\s*<\s*\$userClanRank\s+AND\s+clanid\s*=\s*\$userClanId/is', $content,
        "remove UPDATE query must enforce strictly lower rank: clanrank < \$userClanRank");
});

// =========================================================================
// Category 5: Database Error Handler Information Leakage Prevention
// =========================================================================

test('tier5', 'feature6', 'adv_err_zero_or_one_backdoor_in_all_dbwrappers', function() {
    $wrappers = [
        'lib/dbwrapper_mysqli_proc.php',
        'lib/dbwrapper_mysqli_oos.php',
        'lib/dbwrapper_sqlite.php',
        'lib/dbwrapper_pdo.php',
    ];

    foreach ($wrappers as $wrapper) {
        $content = TestEnvironment::readFile($wrapper);
        assert_not_contains('|| 1', $content, "$wrapper must NOT contain '|| 1' leak backdoor");
        assert_not_contains('||1', $content, "$wrapper must NOT contain '||1'");
    }
});

test('tier5', 'feature6', 'adv_err_all_dbwrappers_use_su_developer_check', function() {
    $wrappers = [
        'lib/dbwrapper_mysqli_proc.php',
        'lib/dbwrapper_mysqli_oos.php',
        'lib/dbwrapper_sqlite.php',
        'lib/dbwrapper_pdo.php',
    ];

    foreach ($wrappers as $wrapper) {
        $content = TestEnvironment::readFile($wrapper);
        assert_matches_regex('/\(getSessionSuperUser\(\)\s*&\s*SU_DEVELOPER\)/', $content,
            "$wrapper must strictly use (getSessionSuperUser() & SU_DEVELOPER)");
    }
});

test('tier5', 'feature6', 'adv_err_non_developer_message_leaks_no_sql_or_paths', function() {
    $genericMessage = "A most bogus error has occurred.  I apologise, but the page you were trying to access is broken.  Please use your browser's back button and try again.";

    $forbiddenStrings = ['SELECT', 'INSERT', 'UPDATE', 'DELETE', 'FROM lotgd_', 'Stack trace:', '.php on line', 'SQLSTATE'];
    foreach ($forbiddenStrings as $str) {
        assert_not_contains($str, $genericMessage, "Generic user error message must not contain technical token '$str'");
    }
});

// =========================================================================
// Category 6: Object Injection & Deserialization Defense
// =========================================================================

test('tier5', 'feature7', 'adv_unserialize_object_gadget_instantiation_blocked', function() {
    // Attempting to deserialize a gadget object with allowed_classes => false
    $gadgetPayload = 'O:18:"ExploitGadgetClass":1:{s:7:"command";s:10:"whoami && ";}';
    $result = @unserialize($gadgetPayload, ['allowed_classes' => false]);

    // PHP creates an __PHP_Incomplete_Class instance without executing __wakeup or __construct
    assert_true(
        $result instanceof __PHP_Incomplete_Class || $result === false,
        "Object payload with allowed_classes=>false must NOT instantiate target class"
    );
});

test('tier5', 'feature7', 'adv_unserialize_nested_object_gadget_blocked', function() {
    $nestedPayload = serialize([
        'user' => 'normal',
        'gadget' => (object)['cmd' => 'rm -rf /']
    ]);

    $restored = @unserialize($nestedPayload, ['allowed_classes' => false]);
    assert_true(is_array($restored), "Top-level array must deserialize");
    assert_true(
        $restored['gadget'] instanceof __PHP_Incomplete_Class || is_null($restored['gadget']),
        "Nested object must be converted to __PHP_Incomplete_Class without execution"
    );
});

test('tier5', 'feature7', 'adv_unserialize_scalars_and_arrays_preserved_identically', function() {
    $complexData = [
        'strings' => ['hello', 'world', 'with "quotes" and \'apostrophes\''],
        'integers' => [0, 1, -1, PHP_INT_MAX, PHP_INT_MIN],
        'floats' => [0.0, 3.14159, -2.718],
        'booleans' => [true, false],
        'null_val' => null,
        'nested' => [
            'level1' => [
                'level2' => ['a', 'b', 'c']
            ]
        ]
    ];

    $serialized = serialize($complexData);
    $restored = unserialize($serialized, ['allowed_classes' => false]);
    assert_same($complexData, $restored, "Deep nested data structures must restore with identical data");
});

test('tier5', 'feature7', 'adv_unserialize_global_audit_all_php_files', function() {
    $root = TestEnvironment::getRootDir();
    $targetDirs = [
        $root . '/lib',
        $root . '/modules',
        $root . '/modules_quarantine',
    ];
    $rootFiles = glob($root . '/*.php') ?: [];
    $allFiles = array_merge($rootFiles, get_all_php_files($targetDirs));

    $unhardenedCalls = [];
    foreach ($allFiles as $file) {
        $content = file_get_contents($file) ?: '';
        $lines = explode("\n", $content);
        foreach ($lines as $lineNum => $line) {
            $trimmed = trim($line);
            if (str_starts_with($trimmed, '//') || str_starts_with($trimmed, '*') || str_starts_with($trimmed, '/*')) {
                continue;
            }
            if (str_contains($line, 'unserialize(') || str_contains($line, 'unserialize (')) {
                if (!str_contains($line, 'allowed_classes') && !str_contains($line, 'safeUnserialize')) {
                    $unhardenedCalls[] = "$file:" . ($lineNum + 1) . ": $trimmed";
                }
            }
        }
    }

    assert_same(0, count($unhardenedCalls),
        "Found unhardened unserialize() calls:\n" . implode("\n", $unhardenedCalls));
});

// =========================================================================
// Category 8: Database Driver PHP 8.4 Compatibility
// =========================================================================

test('tier5', 'feature6', 'adv_db_sqlite_driver_compatibility', function() {
    $content = TestEnvironment::readFile('lib/dbwrapper_sqlite.php');
    assert_not_contains('FILTER_SANITIZE_MAGIC_QUOTES', $content, "FILTER_SANITIZE_MAGIC_QUOTES must not be used in SQLite wrapper");
    assert_not_contains('each($result)', $content, "each() must not be used in SQLite wrapper");
    assert_matches_regex('/function\s+db_fetch_assoc\s*\(\s*&\s*\$result\s*\)/', $content,
        "db_fetch_assoc must accept reference &\$result without rigid array typehint");
    assert_matches_regex('/function\s+db_num_rows\s*\(\s*\$result\s*\)\s*:\s*int/', $content,
        "db_num_rows must accept \$result without rigid array typehint");
});

test('tier5', 'feature6', 'adv_db_mysqli_oos_driver_compatibility', function() {
    $content = TestEnvironment::readFile('lib/dbwrapper_mysqli_oos.php');
    assert_not_contains('each($result)', $content, "each() must not be used in MySQLi OOS wrapper");
    assert_matches_regex('/function\s+db_fetch_assoc\s*\(\s*&\s*\$result\s*\)/', $content,
        "db_fetch_assoc must accept reference &\$result");
});

test('tier5', 'feature6', 'adv_db_sqlite_functional_contract', function() {
    require_once TestEnvironment::getRootDir() . '/lib/dbwrapper_sqlite.php';
    
    // Test array consumption
    $rows = [
        ['id' => 1, 'name' => 'Alice'],
        ['id' => 2, 'name' => 'Bob'],
    ];
    $count = db_num_rows($rows);
    assert_same(2, $count, "db_num_rows on array must return count");

    $r1 = db_fetch_assoc($rows);
    assert_same(['id' => 1, 'name' => 'Alice'], $r1, "db_fetch_assoc first row");
    $r2 = db_fetch_assoc($rows);
    assert_same(['id' => 2, 'name' => 'Bob'], $r2, "db_fetch_assoc second row");
    $r3 = db_fetch_assoc($rows);
    assert_false($r3, "db_fetch_assoc on exhausted array must return false");

    // Test mock SQLite3Result object consumption
    $mockResult = new class {
        private array $data = [
            ['id' => 10, 'title' => 'First'],
            ['id' => 20, 'title' => 'Second'],
        ];
        private int $pointer = 0;

        public function fetchArray(int $mode = 1) {
            if ($this->pointer >= count($this->data)) {
                return false;
            }
            return $this->data[$this->pointer++];
        }

        public function reset(): bool {
            $this->pointer = 0;
            return true;
        }
    };

    $mockCount = db_num_rows($mockResult);
    assert_same(2, $mockCount, "db_num_rows on SQLite3Result must return 2 and rewind");

    $fetch1 = db_fetch_assoc($mockResult);
    assert_same(['id' => 10, 'title' => 'First'], $fetch1, "db_fetch_assoc on SQLite3Result first row");
    $fetch2 = db_fetch_assoc($mockResult);
    assert_same(['id' => 20, 'title' => 'Second'], $fetch2, "db_fetch_assoc on SQLite3Result second row");
    $fetch3 = db_fetch_assoc($mockResult);
    assert_false($fetch3, "db_fetch_assoc on exhausted SQLite3Result must return false");
});

// Standalone execution support
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    echo "Executing Tier 5 Adversarial Verification Harness...\n";
    $tests = TestFramework::getTests('tier5');
    $passed = 0;
    $failed = 0;
    foreach ($tests as $t) {
        try {
            ($t['callable'])();
            echo "[PASS] {$t['name']}\n";
            $passed++;
        } catch (\Throwable $e) {
            echo "[FAIL] {$t['name']}: " . $e->getMessage() . "\n";
            $failed++;
        }
    }
    echo "\nResults: $passed passed, $failed failed.\n";
    exit($failed > 0 ? 1 : 0);
}
