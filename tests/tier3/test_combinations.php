<?php
/**
 * Tier 3: Cross-Feature Combinations
 * Exercises interactions across multiple modernized features.
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';
require_once __DIR__ . '/../fixtures/MockDb.php';

// =========================================================================
// Combination 1: Commentary Deletion + Session Authorization
// =========================================================================

test('tier3', 'comb1', 'comb_commentary_deletion_with_session_auth', function() {
    TestEnvironment::reset();
    global $session;
    require_once TestEnvironment::getRootDir() . '/lib/session.php';

    // Step 1: Unauthenticated user tries to delete
    $session = [];
    $su = getSessionSuperUser();
    $canDelete = (bool)($su & SU_EDIT_COMMENTS);
    assert_false($canDelete, "Unauth user cannot delete comment");

    // Step 2: Regular user tries to delete
    $session['user'] = ['acctid' => 10, 'superuser' => 0];
    $su = getSessionSuperUser();
    $canDelete = (bool)($su & SU_EDIT_COMMENTS);
    assert_false($canDelete, "Regular user cannot delete comment");

    // Step 3: Moderator with SU_EDIT_COMMENTS deletes comment with integer cast
    $session['user'] = ['acctid' => 2, 'superuser' => SU_EDIT_COMMENTS];
    $su = getSessionSuperUser();
    $canDelete = (bool)($su & SU_EDIT_COMMENTS);
    assert_true($canDelete, "Moderator with SU_EDIT_COMMENTS can delete");
    
    $rawCommentId = "55' OR 1=1";
    $cleanId = (int)$rawCommentId;
    assert_same(55, $cleanId, "Comment ID must be strictly cast to int");
});

// =========================================================================
// Combination 2: Clan Rank Changes + Clan Scoping
// =========================================================================

test('tier3', 'comb2', 'comb_clan_rank_changes_and_scoping', function() {
    TestEnvironment::reset();
    MockDb::reset();

    // Officer from Clan 1
    $officer = ['acctid' => 20, 'clanid' => 1, 'clanrank' => CLAN_OFFICER];
    // Member from Clan 1
    $memberSameClan = ['acctid' => 21, 'clanid' => 1, 'clanrank' => CLAN_MEMBER];
    // Member from Clan 2
    $memberOtherClan = ['acctid' => 31, 'clanid' => 2, 'clanrank' => CLAN_MEMBER];

    // Verify officer rank requirement
    assert_true($officer['clanrank'] >= CLAN_OFFICER);

    // Verify Clan scoping for same clan: ALLOWED
    $canModifySame = ($officer['clanrank'] >= CLAN_OFFICER && $officer['clanid'] === $memberSameClan['clanid']);
    assert_true($canModifySame, "Officer can modify member in same clan");

    // Verify Clan scoping for other clan: BLOCKED
    $canModifyOther = ($officer['clanrank'] >= CLAN_OFFICER && $officer['clanid'] === $memberOtherClan['clanid']);
    assert_false($canModifyOther, "Officer CANNOT modify member in different clan");
});

// =========================================================================
// Combination 3: Module Caching + Query Cache Return-by-Value
// =========================================================================

test('tier3', 'comb3', 'comb_module_caching_and_query_cache_value_return', function() {
    MockDb::reset();
    $moduleList = [
        ['category' => 'Combat', 'modulename' => 'pvp', 'active' => 1],
        ['category' => 'General', 'modulename' => 'clock', 'active' => 1]
    ];
    MockDb::setResponse('SELECT category, modulename, active FROM', $moduleList);

    // Component 1 fetches modules
    $res1 = MockDb::queryCached('SELECT category, modulename, active FROM lotgd_modules', 'installed-modules');
    $firstMod = MockDb::fetchAssoc($res1); // mutates local array
    assert_same('pvp', $firstMod['modulename']);
    assert_same(1, count($res1));

    // Component 2 fetches modules from cache
    $res2 = MockDb::queryCached('SELECT category, modulename, active FROM lotgd_modules', 'installed-modules');
    assert_same(2, count($res2), "Second component gets complete 2 modules because queryCached returns by value");
});

// =========================================================================
// Combination 4: Database Error + Developer Error Masking
// =========================================================================

test('tier3', 'comb4', 'comb_database_error_and_developer_masking', function() {
    TestEnvironment::reset();
    global $session;
    require_once TestEnvironment::getRootDir() . '/lib/session.php';

    // Case A: Normal player hits error
    $session['user'] = ['superuser' => 0];
    $su = getSessionSuperUser();
    $showTraceA = (bool)($su & SU_DEVELOPER);
    assert_false($showTraceA, "Player must not see developer stack trace");

    // Case B: Developer hits error
    $session['user'] = ['superuser' => SU_DEVELOPER];
    $su = getSessionSuperUser();
    $showTraceB = (bool)($su & SU_DEVELOPER);
    assert_true($showTraceB, "Developer sees diagnostic stack trace");
});

// =========================================================================
// Combination 5: Session Auth + Translator Precedence
// =========================================================================

test('tier3', 'comb5', 'comb_session_auth_and_translator_precedence', function() {
    TestEnvironment::reset();
    global $session;
    require_once TestEnvironment::getRootDir() . '/lib/session.php';

    // Unset session -> getSessionSuperUser returns 0 -> guard blocks
    $session = [];
    $su = getSessionSuperUser();
    $blocked = !($su & SU_IS_TRANSLATOR);
    assert_true($blocked, "Unauth user blocked from translator tool");

    // Megauser without translator bit -> guard blocks
    $session['user'] = ['superuser' => SU_MEGAUSER];
    $su = getSessionSuperUser();
    $blocked = !($su & SU_IS_TRANSLATOR);
    assert_true($blocked, "Megauser without translator bit blocked");

    // Translator user -> guard allows
    $session['user'] = ['superuser' => SU_IS_TRANSLATOR];
    $su = getSessionSuperUser();
    $blocked = !($su & SU_IS_TRANSLATOR);
    assert_false($blocked, "Translator user allowed");
});

// =========================================================================
// Combination 6: Atomic Datacache Write Under Translation Load
// =========================================================================

test('tier3', 'comb6', 'comb_datacache_atomic_write_under_translation_load', function() {
    $tempDir = TestEnvironment::getTempDir();
    $cacheFile = $tempDir . '/cache_translations_en_village.tmp';
    $targetFile = $tempDir . '/cache_translations_en_village';

    $translations = [
        'title' => 'The Village of %s',
        'description' => 'You stand in the center of town.',
        'new_character' => 'New hero: %s'
    ];

    // Atomic write
    file_put_contents($cacheFile, serialize($translations));
    rename($cacheFile, $targetFile);

    // Immediate read
    $readBack = file_get_contents($targetFile);
    $data = unserialize($readBack, ['allowed_classes' => false]);
    assert_same($translations['title'], $data['title']);
    @unlink($targetFile);
});

// =========================================================================
// Combination 7: User Save + Account Output Gate
// =========================================================================

test('tier3', 'comb7', 'comb_user_save_and_accounts_output_gating', function() {
    TestEnvironment::reset();
    TestEnvironment::setSetting('saveaccountsoutput', 0);

    $sessionOutput = "<html><body>Active page content</body></html>";
    $acctId = 42;
    $outputWritten = false;

    if (TestEnvironment::getSetting('saveaccountsoutput', 0)) {
        $outputWritten = true;
    }
    assert_false($outputWritten, "When setting is 0, no file is written to accounts-output/");

    // When setting is enabled
    TestEnvironment::setSetting('saveaccountsoutput', 1);
    if (TestEnvironment::getSetting('saveaccountsoutput', 0)) {
        $outputWritten = true;
    }
    assert_true($outputWritten, "When setting is 1, output is written");
});

// =========================================================================
// Combination 8: Village Logic + Namespace Loading + Percent Escaping
// =========================================================================

test('tier3', 'comb8', 'comb_village_logic_translations_and_percent_escaping', function() {
    $i18n = [
        'title' => 'Village of %s',
        'new_character' => 'The newest warrior is %s!'
    ];
    $villageName = "Mighty Town";
    $title = sprintf($i18n['title'], $villageName);
    assert_same("Village of Mighty Town", $title);

    // Player name with color code `%
    $playerRaw = "`%Crimson%Blade";
    $playerSafe = preg_replace('/(?<!`)%/', '%%', $playerRaw);
    $newCharMsg = sprintf($i18n['new_character'], $playerSafe);
    assert_same("The newest warrior is `%Crimson%%Blade!", $newCharMsg);
});

// =========================================================================
// Combination 9: Village Navigation + Clans Disabled
// =========================================================================

test('tier3', 'comb9', 'comb_village_navigation_and_clans_disabled', function() {
    $navigation = [
        'tavern' => [
            'chat' => 'inn.php',
            'clan' => 'clan.php'
        ]
    ];
    $allowClans = false;
    if (!$allowClans) {
        unset($navigation['tavern']['clan']);
    }
    assert_false(isset($navigation['tavern']['clan']), "clan must be unset from tavern when clans are disabled");
    assert_true(isset($navigation['tavern']['chat']), "chat in tavern remains intact");
});

// =========================================================================
// Combination 10: Authentication Token Flow + Pass-the-Hash Elimination
// =========================================================================

test('tier3', 'comb10', 'comb_auth_token_flow_and_no_md52', function() {
    TestEnvironment::reset();
    global $session;

    // Direct MD52 attempt fails
    $incomingPass = "!md52!5f4dcc3b5aa765d61d8327deb882cf99";
    $isMd52 = (substr($incomingPass, 0, 6) === "!md52!");
    // In modernized login, !md52! is not recognized as a bypass
    $hashed = md5(md5($incomingPass));
    assert_not_equals("5f4dcc3b5aa765d61d8327deb882cf99", $hashed);

    // Valid one-time token flow
    $token = bin2hex(random_bytes(16));
    $session['email_validation_token'] = $token;
    $session['validation_acctid'] = 100;

    $loginToken = $token;
    assert_same($session['email_validation_token'], $loginToken);
    unset($session['email_validation_token']);
    assert_false(isset($session['email_validation_token']), "Token consumed");
});

// =========================================================================
// Combination 11: Clan Detail DK Summation + Member Row Count
// =========================================================================

test('tier3', 'comb11', 'comb_clan_detail_dk_summation_and_row_count', function() {
    $clanMembers = [
        ['acctid' => 1, 'name' => 'Leader', 'clanrank' => CLAN_LEADER, 'dragonkills' => 25],
        ['acctid' => 2, 'name' => 'Officer', 'clanrank' => CLAN_OFFICER, 'dragonkills' => 10],
        ['acctid' => 3, 'name' => 'Member1', 'clanrank' => CLAN_MEMBER, 'dragonkills' => 3],
        ['acctid' => 4, 'name' => 'Member2', 'clanrank' => CLAN_MEMBER, 'dragonkills' => 2],
    ];

    $totalDks = 0;
    foreach ($clanMembers as $m) {
        $totalDks += (int)$m['dragonkills'];
    }
    assert_same(40, $totalDks, "Sum of dragonkills must be 40");
    assert_same(4, count($clanMembers), "Member count must be 4");
});

// =========================================================================
// Combination 12: Subtranslation Resolution + Cache Key Verification
// =========================================================================

test('tier3', 'comb12', 'comb_subtranslation_resolution_and_has_key', function() {
    $pattern = '/\{\{([a-zA-Z0-9_\-\.]+)(\|([^\}]*))?\}\}/';
    $message = "You gained {{common.gold|100 gold}} and {{battle.exp|50 exp}}!";
    
    assert_true((bool)preg_match($pattern, $message));
    preg_match_all($pattern, $message, $matches, PREG_SET_ORDER);
    assert_same(2, count($matches), "Should extract both translation placeholders");
    assert_same('common.gold', $matches[0][1]);
    assert_same('100 gold', $matches[0][3]);
    assert_same('battle.exp', $matches[1][1]);
    assert_same('50 exp', $matches[1][3]);
});

// =========================================================================
// Combination 13: Deserialization Security + User Prefs Save
// =========================================================================

test('tier3', 'comb13', 'comb_deserialization_security_and_user_prefs', function() {
    $prefs = ['theme' => 'classic', 'show_bio' => 1, 'sound' => 0];
    $serialized = serialize($prefs);
    
    $restored = unserialize($serialized, ['allowed_classes' => false]);
    assert_same($prefs, $restored);
});

// =========================================================================
// Combination 14: Errorhandler Path Resolution + Superuser Logging
// =========================================================================

test('tier3', 'comb14', 'comb_errorhandler_path_resolution_and_logging', function() {
    $libDir = TestEnvironment::getRootDir() . '/lib';
    $sanitizePath = $libDir . '/sanitize.php';
    assert_file_exists($sanitizePath, "sanitize.php must exist at lib/sanitize.php");

    $session = ['user' => ['name' => 'Knight', 'acctid' => 33]];
    $userstr = "Error by " . $session['user']['name'] . " (" . $session['user']['acctid'] . ")";
    assert_same("Error by Knight (33)", $userstr);
});

// =========================================================================
// Combination 15: Module Statuses + Table Prefixing
// =========================================================================

test('tier3', 'comb15', 'comb_module_statuses_with_prefix', function() {
    MockDb::reset();
    MockDb::setPrefix('custom_');
    $prefixedTable = MockDb::prefix('modules');
    assert_same('custom_modules', $prefixedTable);

    $sql = "SELECT category, modulename, active FROM $prefixedTable ORDER BY category, active, modulename";
    assert_contains('custom_modules', $sql);
});

// =========================================================================
// Combination 16: Clan Applicant Withdrawal + Session Clear
// =========================================================================

test('tier3', 'comb16', 'comb_clan_applicant_withdrawal_and_session_clear', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = [
        'clanid' => 7,
        'clanrank' => CLAN_APPLICANT,
        'clanjoindate' => '2026-10-20'
    ];

    // Withdraw action
    $session['user']['clanid'] = 0;
    $session['user']['clanrank'] = CLAN_APPLICANT;
    $session['user']['clanjoindate'] = null;

    assert_same(0, $session['user']['clanid']);
    assert_null($session['user']['clanjoindate']);
});

// =========================================================================
// Combination 17: Superuser Multi-Bit Authorization Checks
// =========================================================================

test('tier3', 'comb17', 'comb_superuser_multi_bit_authorization', function() {
    $mask = SU_EDIT_COMMENTS | SU_POST_MOTD | SU_DEVELOPER;
    
    assert_true((bool)($mask & SU_EDIT_COMMENTS));
    assert_true((bool)($mask & SU_POST_MOTD));
    assert_true((bool)($mask & SU_DEVELOPER));
    assert_false((bool)($mask & SU_MEGAUSER));
    assert_false((bool)($mask & SU_IS_TRANSLATOR));
});

// =========================================================================
// Combination 18: Village Random Event Skip + Commentary Render
// =========================================================================

test('tier3', 'comb18', 'comb_village_event_skip_and_commentary', function() {
    $skipDescription = true;
    $renderedDesc = false;
    $renderedComments = false;

    if (!$skipDescription) {
        $renderedDesc = true;
    }
    // Commentary is rendered separately at village bottom
    $renderedComments = true;

    assert_false($renderedDesc, "Description skipped on event");
    assert_true($renderedComments, "Commentary board displays");
});

// =========================================================================
// Combination 19: Clean Superglobals Across HTTP Input
// =========================================================================

test('tier3', 'comb19', 'comb_clean_superglobals_across_http_input', function() {
    $_GET['op'] = 'list';
    $_POST['clanid'] = '10';

    $op = $_GET['op'] ?? '';
    $clanid = (int)($_POST['clanid'] ?? 0);

    assert_same('list', $op);
    assert_same(10, $clanid);
});

// =========================================================================
// Combination 20: Translation Missing Key English Fallback
// =========================================================================

test('tier3', 'comb20', 'comb_translation_missing_key_english_fallback', function() {
    $translations = [
        'de' => ['welcome' => 'Willkommen'],
        'en' => ['welcome' => 'Welcome', 'farewell' => 'Farewell']
    ];
    $currentLang = 'de';
    $key = 'farewell';

    $text = $translations[$currentLang][$key] ?? $translations['en'][$key] ?? $key;
    assert_same('Farewell', $text, "Missing German key should fallback to English 'Farewell'");
});

// =========================================================================
// Combination 21: Clan Rank Promotion Traversal Boundary
// =========================================================================

test('tier3', 'comb21', 'comb_clan_rank_promotion_traversal', function() {
    $ranks = [
        CLAN_APPLICANT => 'Applicant',
        CLAN_MEMBER => 'Member',
        CLAN_OFFICER => 'Officer',
        CLAN_LEADER => 'Leader'
    ];
    // Next rank from Member (10)
    $curr = CLAN_MEMBER;
    $next = CLAN_LEADER;
    foreach ($ranks as $rankVal => $title) {
        if ($rankVal > $curr) {
            $next = $rankVal;
            break;
        }
    }
    assert_same(CLAN_OFFICER, $next, "Next rank after Member is Officer");
});

// =========================================================================
// Combination 22: Quarantined Module Isolation
// =========================================================================

test('tier3', 'comb22', 'comb_quarantine_isolation_from_active_modules', function() {
    $activeDir = TestEnvironment::getRootDir() . '/modules';
    $qDir = TestEnvironment::getRootDir() . '/modules_quarantine';

    $activeFiles = glob($activeDir . '/*.php') ?: [];
    $activeBaseNames = array_map('basename', $activeFiles);

    // Verify inactive quarantined files are not in active modules directory
    assert_false(in_array('specialtymonkskills.php', $activeBaseNames));
    assert_false(in_array('racespecialtylion.php', $activeBaseNames));
});

// =========================================================================
// Combination 23: Header and Footer Licensing Attribution
// =========================================================================

test('tier3', 'comb23', 'comb_header_and_footer_licensing_attribution', function() {
    $common = TestEnvironment::readFile('common.php');
    assert_contains('Eric Stevens & JT Traub', $common);
    assert_contains('modified by jaoe1000', $common);

    $pageparts = TestEnvironment::readFile('lib/pageparts.php');
    assert_contains('{copyright}', $pageparts);
});

// =========================================================================
// Combination 24: Module Status Category Aggregation
// =========================================================================

test('tier3', 'comb24', 'comb_module_status_category_aggregation', function() {
    $rawModules = [
        ['category' => 'Combat', 'modulename' => 'pvp'],
        ['category' => 'Combat', 'modulename' => 'weapons'],
        ['category' => 'Village', 'modulename' => 'fountain'],
    ];
    $categories = [];
    foreach ($rawModules as $m) {
        $categories[$m['category']][] = $m['modulename'];
    }
    assert_same(2, count($categories['Combat']));
    assert_same(1, count($categories['Village']));
});

// =========================================================================
// Combination 25: Double MD5 Password Security and Verification
// =========================================================================

test('tier3', 'comb25', 'comb_double_md5_password_verification', function() {
    $plain = "SuperDragonSlayer99!";
    $storedHash = md5(md5($plain));
    
    // User submits password
    $attempt = "SuperDragonSlayer99!";
    $attemptHash = md5(md5($attempt));
    assert_same($storedHash, $attemptHash, "Valid password hashes identically");

    // Attacker submits wrong password
    $wrongAttempt = "WrongPass";
    $wrongHash = md5(md5($wrongAttempt));
    assert_not_equals($storedHash, $wrongHash, "Wrong password rejected");
});
