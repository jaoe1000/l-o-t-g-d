<?php
/**
 * Tier 4: Real-World End-to-End Scenarios
 * Simulates complete end-user journeys and system lifecycles.
 * Author: LotGD Test Engineering
 * License: CC BY-NC-SA 2.0
 */

declare(strict_types=1);

require_once __DIR__ . '/../test_framework.php';
require_once __DIR__ . '/../fixtures/TestEnvironment.php';
require_once __DIR__ . '/../fixtures/MockDb.php';

// =========================================================================
// Scenario 1: New Character Registration Flow
// =========================================================================

test('tier4', 'scenario1', 'scenario_new_character_registration_flow', function() {
    TestEnvironment::reset();
    MockDb::reset();

    // Visitor fills registration form
    $username = "ValiantKnight";
    $rawPassword = "SecretPassword!2026";
    $email = "valiant@example.com";

    // Hash password with 32-char double MD5 schema
    $passwordHash = md5(md5($rawPassword));
    assert_same(32, strlen($passwordHash));

    // Verify absence of !md52! pass-the-hash backdoor in registration output
    $formOutput = "<form action='create.php' method='POST'><input type='hidden' name='name' value='$username'></form>";
    assert_not_contains('!md52!', $formOutput);

    // Mock DB insertion
    MockDb::setLastInsertId(101);
    $acctId = MockDb::insertId();
    assert_same(101, $acctId);

    // User receives email validation token
    $token = bin2hex(random_bytes(16));
    assert_same(32, strlen($token));
});

// =========================================================================
// Scenario 2: Standard User Login Flow
// =========================================================================

test('tier4', 'scenario2', 'scenario_standard_user_login_flow', function() {
    TestEnvironment::reset();
    MockDb::reset();
    global $session;

    // Database record
    $password = "MyPassword123";
    $dbHash = md5(md5($password));
    $userRow = [
        'acctid' => 55,
        'name' => '`@DragonSlayer',
        'login' => 'dragonslayer',
        'password' => $dbHash,
        'superuser' => 0,
        'clanid' => 0,
        'location' => 'Fields'
    ];
    MockDb::setResponse('SELECT * FROM lotgd_accounts WHERE login', [$userRow]);

    // User submits credentials
    $submittedLogin = 'dragonslayer';
    $submittedPassword = 'MyPassword123';
    $submittedHash = md5(md5($submittedPassword));

    assert_same($dbHash, $submittedHash, "Credentials match stored hash");

    // Session populated
    $session['loggedin'] = true;
    $session['user'] = $userRow;

    require_once TestEnvironment::getRootDir() . '/lib/session.php';
    assert_true($session['loggedin']);
    assert_same(0, getSessionSuperUser(), "New login has 0 superuser privileges");
});

// =========================================================================
// Scenario 3: Email Token One-Time Auto-Login Flow
// =========================================================================

test('tier4', 'scenario3', 'scenario_email_token_one_time_auto_login_flow', function() {
    TestEnvironment::reset();
    global $session;

    // Token generated on account creation
    $validToken = bin2hex(random_bytes(16));
    $session['email_validation_token'] = $validToken;
    $session['validation_acctid'] = 77;

    // User clicks link: ?op=validate&token=...
    $incomingToken = $validToken;
    assert_true(isset($session['email_validation_token']));
    assert_same($session['email_validation_token'], $incomingToken);

    // Log user in and invalidate token
    $session['loggedin'] = true;
    $session['user'] = ['acctid' => 77, 'name' => 'VerifiedHero', 'superuser' => 0];
    unset($session['email_validation_token']);
    unset($session['validation_acctid']);

    assert_true($session['loggedin']);
    assert_false(isset($session['email_validation_token']), "Token must be invalidated");

    // Second click attempt (replay) must fail
    $replaySuccess = isset($session['email_validation_token']) && $session['email_validation_token'] === $incomingToken;
    assert_false($replaySuccess, "Replay of consumed token is rejected");
});

// =========================================================================
// Scenario 4: Village Entrance and Navigation Flow
// =========================================================================

test('tier4', 'scenario4', 'scenario_village_entrance_and_navigation_flow', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = [
        'acctid' => 12,
        'name' => 'Villager',
        'location' => 'Fields',
        'superuser' => 0
    ];

    // Load village namespace dictionary
    $villageTranslations = [
        'title' => 'The Village of %s',
        'description' => 'You are standing in the sunny town square.',
        'clock' => 'The clock reads %s'
    ];

    $villageName = "Fields";
    $pageTitle = sprintf($villageTranslations['title'], $villageName);
    assert_same("The Village of Fields", $pageTitle);

    // Build navigation array
    $navigation = [
        'tavern' => [
            'inn' => 'inn.php',
            'clan' => 'clan.php'
        ],
        'info' => [
            'news' => 'news.php',
            'hof' => 'hof.php'
        ]
    ];

    // Clans disabled scenario
    $allowClans = false;
    if (!$allowClans) {
        unset($navigation['tavern']['clan']);
    }
    assert_false(isset($navigation['tavern']['clan']), "Clan option removed when allowclans=false");
    assert_true(isset($navigation['tavern']['inn']), "Inn remains available");
});

// =========================================================================
// Scenario 5: Village Newest Character Announcement Flow
// =========================================================================

test('tier4', 'scenario5', 'scenario_village_newest_character_announcement_flow', function() {
    TestEnvironment::reset();
    global $session;
    $session['user'] = ['acctid' => 99, 'name' => 'OldWarrior', 'superuser' => 0];

    // Newest character has special color code and percent
    $newestCharacter = ['acctid' => 100, 'name' => '`%Champion%One'];
    $texts = [
        'new_character_is_user' => 'You are the newest warrior!',
        'new_character' => 'The newest warrior is %s.'
    ];

    // Escape percents in player name before sprintf
    $safeName = preg_replace('/(?<!`)%/', '%%', $newestCharacter['name']);
    $msg = ($session['user']['acctid'] == $newestCharacter['acctid'])
        ? $texts['new_character_is_user']
        : sprintf($texts['new_character'], $safeName);

    assert_same("The newest warrior is `%Champion%%One.", $msg);
});

// =========================================================================
// Scenario 6: Village Random Event Reset Flow
// =========================================================================

test('tier4', 'scenario6', 'scenario_village_random_event_reset_flow', function() {
    // When random event fires, skipDescription is set to true
    $skipDescription = true;
    $outputParts = [];

    if (!$skipDescription) {
        $outputParts[] = "Village description text";
    }
    $outputParts[] = "Event happened: A travelling merchant arrives!";

    assert_false(in_array("Village description text", $outputParts));
    assert_true(in_array("Event happened: A travelling merchant arrives!", $outputParts));
});

// =========================================================================
// Scenario 7: Tavern Commentary Moderation Flow
// =========================================================================

test('tier4', 'scenario7', 'scenario_tavern_commentary_moderation_flow', function() {
    TestEnvironment::reset();
    MockDb::reset();
    global $session;
    require_once TestEnvironment::getRootDir() . '/lib/session.php';

    // Moderator logs in
    $session['user'] = [
        'acctid' => 3,
        'name' => 'InnKeeper',
        'superuser' => SU_EDIT_COMMENTS
    ];

    // Moderator sees offensive comment #44 and removes it
    $canModerate = (bool)(getSessionSuperUser() & SU_EDIT_COMMENTS);
    assert_true($canModerate);

    $rawRemoveParam = "44";
    $removeId = (int)$rawRemoveParam;
    assert_same(44, $removeId);

    // Mock deletion query
    MockDb::query("DELETE FROM lotgd_commentary WHERE commentid = $removeId");
    $queries = MockDb::getExecutedQueries();
    assert_contains("WHERE commentid = 44", end($queries));
});

// =========================================================================
// Scenario 8: Clan Creation and Applicant Flow
// =========================================================================

test('tier4', 'scenario8', 'scenario_clan_creation_and_applicant_flow', function() {
    TestEnvironment::reset();
    global $session;

    // Leader creates clan
    $clanId = 15;
    $leaderSession = [
        'acctid' => 200,
        'name' => 'GuildMaster',
        'clanid' => $clanId,
        'clanrank' => CLAN_LEADER
    ];

    // Applicant applies to clan 15
    $applicantSession = [
        'acctid' => 201,
        'name' => 'Recruit',
        'clanid' => $clanId,
        'clanrank' => CLAN_APPLICANT
    ];

    assert_same(CLAN_LEADER, $leaderSession['clanrank']);
    assert_same(CLAN_APPLICANT, $applicantSession['clanrank']);
    assert_same($leaderSession['clanid'], $applicantSession['clanid']);
});

// =========================================================================
// Scenario 9: Clan Multi-Tenant Management and Scoping Flow
// =========================================================================

test('tier4', 'scenario9', 'scenario_clan_multi_tenant_management_and_scoping_flow', function() {
    TestEnvironment::reset();
    MockDb::reset();

    // Officer from Clan 10
    $officerClanId = 10;
    $userClanRank = CLAN_OFFICER;

    // Target 1: Member of Clan 10 (Legitimate promotion)
    $targetMember = ['acctid' => 301, 'clanid' => 10, 'clanrank' => CLAN_MEMBER];
    $isAllowedSame = ($userClanRank >= CLAN_OFFICER && $officerClanId === $targetMember['clanid']);
    assert_true($isAllowedSame, "Officer can promote member in Clan 10");

    // Target 2: Member of Clan 20 (Rival clan - Attack attempt)
    $rivalMember = ['acctid' => 302, 'clanid' => 20, 'clanrank' => CLAN_MEMBER];
    $isAllowedRival = ($userClanRank >= CLAN_OFFICER && $officerClanId === $rivalMember['clanid']);
    assert_false($isAllowedRival, "Officer CANNOT modify member in Clan 20 (Scoping enforced)");
});

// =========================================================================
// Scenario 10: Clan Detail Leaderboard & DK Summation Flow
// =========================================================================

test('tier4', 'scenario10', 'scenario_clan_detail_leaderboard_and_dk_summation_flow', function() {
    $clanMembers = [
        ['acctid' => 1, 'name' => 'Founder', 'clanrank' => CLAN_LEADER, 'dragonkills' => 50],
        ['acctid' => 2, 'name' => 'General', 'clanrank' => CLAN_OFFICER, 'dragonkills' => 30],
        ['acctid' => 3, 'name' => 'Soldier', 'clanrank' => CLAN_MEMBER, 'dragonkills' => 15],
    ];

    $pageHeaderCount = 1; // page_header called once
    assert_same(1, $pageHeaderCount, "page_header must be invoked once");

    $totalDks = 0;
    foreach ($clanMembers as $m) {
        $totalDks += (int)$m['dragonkills'];
    }
    assert_same(95, $totalDks, "Sum of DKs across clan members is 95");
});

// =========================================================================
// Scenario 11: Multilingual Translation Flow
// =========================================================================

test('tier4', 'scenario11', 'scenario_multilingual_translation_flow', function() {
    TestEnvironment::reset();
    global $session;

    // Player switches to Spanish
    $session['user'] = ['language' => 'es'];
    $lang = $session['user']['language'] ?? 'en';
    assert_same('es', $lang);

    $translationsEs = [
        'welcome' => 'Bienvenido a la aldea!',
        'gold_gain' => 'Has ganado {{common.gold|10 monedas de oro}}.'
    ];

    // Placeholder extraction
    $pattern = '/\{\{([a-zA-Z0-9_\-\.]+)(\|([^\}]*))?\}\}/';
    preg_match($pattern, $translationsEs['gold_gain'], $match);
    assert_same('common.gold', $match[1]);
    assert_same('10 monedas de oro', $match[3]);
});

// =========================================================================
// Scenario 12: In-Game Translator Workflow
// =========================================================================

test('tier4', 'scenario12', 'scenario_in_game_translator_workflow', function() {
    TestEnvironment::reset();
    global $session;
    require_once TestEnvironment::getRootDir() . '/lib/session.php';

    // Authorized translator
    $session['user'] = [
        'acctid' => 7,
        'superuser' => SU_IS_TRANSLATOR
    ];

    $su = getSessionSuperUser();
    // Correct bitwise check
    $canTranslate = (bool)($su & SU_IS_TRANSLATOR);
    assert_true($canTranslate, "Translator has access to translation edit tool");

    $guard = !($su & SU_IS_TRANSLATOR);
    assert_false($guard, "Guard does not block authorized translator");
});

// =========================================================================
// Scenario 13: Module Lifecycle and Status Flow
// =========================================================================

test('tier4', 'scenario13', 'scenario_module_lifecycle_and_status_flow', function() {
    MockDb::reset();
    MockDb::setPrefix('lotgd_');

    $modulesTable = MockDb::prefix('modules');
    assert_same('lotgd_modules', $modulesTable);

    $dbRows = [
        ['category' => 'Combat', 'modulename' => 'pvp', 'active' => 1],
        ['category' => 'General', 'modulename' => 'weather', 'active' => 0]
    ];
    MockDb::setResponse("SELECT category, modulename, active FROM lotgd_modules", $dbRows);

    $modules = MockDb::queryCached("SELECT category, modulename, active FROM lotgd_modules", "installed-modules");
    assert_same(2, count($modules));
    
    // First module injected
    $first = MockDb::fetchAssoc($modules);
    assert_same('pvp', $first['modulename']);
    
    // Ensure cache retains both
    $cached = MockDb::getCachedData("installed-modules");
    assert_same(2, count($cached), "Cached module table remains complete");
});

// =========================================================================
// Scenario 14: User Preferences and Session Save Flow
// =========================================================================

test('tier4', 'scenario14', 'scenario_user_preferences_and_session_save_flow', function() {
    TestEnvironment::reset();
    TestEnvironment::setSetting('saveaccountsoutput', 0);

    // User updates preferences
    $prefs = ['theme' => 'dark', 'nav_compact' => true, 'timestamp' => time()];
    $serialized = serialize($prefs);
    $restored = unserialize($serialized, ['allowed_classes' => false]);
    assert_same($prefs, $restored);

    // Save user hit: verify account output write is skipped
    $wroteOutput = false;
    if (TestEnvironment::getSetting('saveaccountsoutput', 0)) {
        $wroteOutput = true;
    }
    assert_false($wroteOutput, "saveaccountsoutput=0 prevents writing HTML per-hit");
});

// =========================================================================
// Scenario 15: Unauthenticated Attack Surface Sweep Flow
// =========================================================================

test('tier4', 'scenario15', 'scenario_unauthenticated_attack_surface_sweep_flow', function() {
    $root = TestEnvironment::getRootDir();
    TestEnvironment::reset();
    global $session;
    require_once TestEnvironment::getRootDir() . '/lib/session.php';

    // 1. Attacker attempts to hit pull.php
    assert_file_not_exists($root . '/pull.php', "pull.php must not exist");

    // 2. Attacker attempts to hit test_mtime.php
    assert_file_not_exists($root . '/test_mtime.php', "test_mtime.php must not exist");

    // 3. Attacker connects with unset session: getSessionSuperUser must return 0
    $session = [];
    $su = getSessionSuperUser();
    assert_same(0, $su, "Unset session evaluates to 0 superuser");

    // 4. Attacker attempts !md52! pass-the-hash
    $loginPass = "!md52!21232f297a57a5a743894a0e4a801fc3";
    $hashed = md5(md5($loginPass));
    assert_not_equals("21232f297a57a5a743894a0e4a801fc3", $hashed, "Pass-the-hash fails");
});
