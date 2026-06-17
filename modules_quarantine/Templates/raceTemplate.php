<?php
/**
 * Advanced Race Blueprint (No Specialty/Class Mechanics)
 * Designed for PHP 8.4 Strict Compliance
 * * Easy way to customize:
 * - Replace 'racetemplate' with module name (e.g., 'racevampire')
 * - Search for '[PLACEHOLDER]' to find all text that needs customization.
 * - Search for '***CHANGE' to see structural variables that must be defined.
 */

function racetemplate_getmoduleinfo() {
    return [
        "name" => "Race - [PLACEHOLDER RACE NAME]", // ***CHANGE
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Race",
        "download" => "", 
        "settings" => [
            "[PLACEHOLDER RACE NAME] - Race Settings,title", // ***CHANGE
            "villagename" => "Name for the [PLACEHOLDER RACE NAME] city,|[PLACEHOLDER CITY NAME]", // ***CHANGE
            "stableowner" => "Name of the city stable-owner,|[PLACEHOLDER STABLE OWNER]", // ***CHANGE
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|20", // ***CHANGE
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|3",
            "wepchange" => "Will weapons & armour change to race-specific names?,bool|1", // ***CHANGE
            "IE- an 'Adze' would become an 'Elven Adze',note", 
        ],
        // UNCOMMENT THIS BLOCK TO ENFORCE MODULE DEPENDENCIES
        /*
        "requires" => [
            "alignment" => "Alignment Module, 1.0|",
            // "cities" => "Multiple Cities Module, 1.0|",
        ],
        */
        "prefs" => [
            "[PLACEHOLDER RACE NAME] - User Prefs,title", // ***CHANGE
            // Ready for any pure-race preferences you might want to track (e.g. daily abilities used)
        ]
    ];
}

function racetemplate_install() {
    module_addhook("chooserace");
    module_addhook("setrace");
    module_addhook("villagetext");
    module_addhook("stabletext");
    module_addhook("travel");
    module_addhook("charstats");
    module_addhook("validlocation");
    module_addhook("moderate");
    module_addhook("changesetting");
    module_addhook("raceminedeath");
    module_addhook("stablelocs");
    module_addhook("newday");
    module_addhook("newday-intercept");
    module_addhook("boughtweapon");
    module_addhook("boughtarmor");
    debug("Installed '[PLACEHOLDER RACE NAME]' advanced race module."); // ***CHANGE
    return true;
}

function racetemplate_uninstall() {
    global $session;
    $vname = getsetting("villagename", LOCATION_FIELDS);
    $gname = get_module_setting("villagename");
    
    // Evict players from the custom city back to the capital
    $sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
    db_query($sql);
    if (($session['user']['location'] ?? '') === $gname) {
        $session['user']['location'] = $vname;
    }
    
    // Force anyone who was this race to rechoose race
    $sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='[PLACEHOLDER RACE NAME]'"; // ***CHANGE
    db_query($sql);
    if (($session['user']['race'] ?? '') === '[PLACEHOLDER RACE NAME]') { // ***CHANGE
        $session['user']['race'] = RACE_UNKNOWN;
    }
    
    debug("Uninstalled '[PLACEHOLDER RACE NAME]' race module."); // ***CHANGE
    return true;
}

function racetemplate_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "[PLACEHOLDER RACE NAME]"; // ***CHANGE
    
    $userRace = $session['user']['race'] ?? '';
    
    switch($hookname) {
        case "newday-intercept":
            racetemplate_change();
            break;

        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "racetemplate") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = racetemplate_get_travel_mod();
                if ($travel_mod !== false) {
                    $sql = "UPDATE " . db_prefix("module_userprefs") . " SET value='" . $args['new'] . "' WHERE modulename='$travel_mod' AND setting='homecity' AND value='" . $args['old'] . "'";
                    db_query($sql);
                }
            }
            break;

        case "charstats":
            if ($userRace === $race) {
                addcharstat("Vital Info");
                addcharstat("Race", translate_inline($race));
            }
            break;

        case "chooserace":
            if (($session['user']['dragonkills'] ?? 0) >= (int)get_module_setting("dklimit")) {
                $can_select = true;

                // UNCOMMENT TO RESTRICT RACE BY ALIGNMENT
                /*
                if (is_module_active("alignment")) {
                    $player_align = get_module_pref("alignment", "alignment");
                    if ($player_align != "good") { 
                        $can_select = false;
                        output("`n`7Your soul lacks the purity required to walk the path of the %s.`n", $race);
                    }
                }
                */

                if ($can_select) {
                    $atrans = translate_inline("In the town of %s"); // ***CHANGE
                    $rtrans = translate_inline(", a place of peace and trade.`n`n"); // ***CHANGE
                    addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                    output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                    addnav("", "newday.php?setrace=$race$resline");
                }
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`&[PLACEHOLDER RACE SELECTED FLAVOR TEXT AND BUFF EXPLANATION]`n"); // ***CHANGE
                $travel_mod = racetemplate_get_travel_mod();
                if ($travel_mod !== false) {
                    if (($session['user']['dragonkills'] ?? 0) == 0 && ($session['user']['age'] ?? 0) == 0) {
                        set_module_setting("newest-$city", $session['user']['acctid'], $travel_mod);
                    }
                    set_module_pref("homecity", $city, $travel_mod);
                    if (($session['user']['age'] ?? 0) == 0)
                        $session['user']['location'] = $city;
                }
            }
            break;

        case "newday":
            if ($userRace === $race) {
                racetemplate_checkcity();
                
                // Example passive buffs to apply
                $args['turnstoday'] = ($args['turnstoday'] ?? '') . ", Race ($race): 1"; // ***CHANGE
                $session['user']['turns']++;
                
                // Uncomment to grant passive HP bonuses instead of combat abilities
                // $session['user']['maxhitpoints'] += 2;
                // $session['user']['hitpoints'] += 2;

                output("`n`&[PLACEHOLDER NEWDAY RACE BUFF TEXT]`n`0"); // ***CHANGE
            }
            break;

        case "validlocation":
            $travel_mod = racetemplate_get_travel_mod();
            if ($travel_mod !== false)
                $args[$city] = "village-$race";
            break;

        case "moderate":
            $travel_mod = racetemplate_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("commentary");
                $args["village-$race"] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "travel":
            $travel_mod = racetemplate_get_travel_mod();
            if ($travel_mod !== false) {
                $capital = getsetting("villagename", LOCATION_FIELDS);
                $hotkey = substr($city, 0, 1);
                tlschema("module-" . $travel_mod);
                $param = ($travel_mod === "villages") ? "village" : "city";
                if (($session['user']['location'] ?? '') === $capital) {
                    addnav("Safer Travel");
                    addnav(["%s?Go to %s", $hotkey, $city], "runmodule.php?module=$travel_mod&op=travel&$param=$city");
                } elseif (($session['user']['location'] ?? '') !== $city) {
                    addnav("More Dangerous Travel");
                    addnav(["%s?Go to %s", $hotkey, $city], "runmodule.php?module=$travel_mod&op=travel&$param=$city&d=1");
                }
                if (($session['user']['superuser'] ?? 0) & SU_EDIT_USERS) {
                    addnav("Superuser");
                    addnav(["%s?Go to %s", $hotkey, $city], "runmodule.php?module=$travel_mod&op=travel&$param=$city&su=1");
                }
                tlschema();
            }
            break;  

        case "villagetext":
            racetemplate_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^[PLACEHOLDER VILLAGE MAIN DESCRIPTION TEXT]`n", $city];
                $args['schemas']['text'] = "module-racetemplate";
                $args['clock'] = "`n`7A clock reads `@%s`7.`n";
                $args['schemas']['clock'] = "module-racetemplate";
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$A calendar reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-racetemplate";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-racetemplate";
                $args['sayline'] = "says";
                $args['schemas']['sayline'] = "module-racetemplate";
                $args['talk'] = "`n`&Nearby some residents stand talking:`n";
                $args['schemas']['talk'] = "module-racetemplate";
                
                $travel_mod = racetemplate_get_travel_mod();
                $new = $travel_mod !== false ? get_module_setting("newest-$city", $travel_mod) : 0;
                if ($new != 0) {
                    $sql =  "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$new'";
                    $result = db_query_cached($sql, "newest-$city");
                    $row = db_fetch_assoc($result);
                    $args['newestplayer'] = $row['name'] ?? '';
                    $args['newestid'] = $new;
                } else {
                    $args['newestplayer'] = $new;
                    $args['newestid'] = "";
                }
                
                if ($new == ($session['user']['acctid'] ?? 0)) {
                    $args['newest'] = "`n`7[PLACEHOLDER NEWEST PLAYER TEXT (SELF)].";
                } else {
                    $args['newest'] = "`n`7[PLACEHOLDER NEWEST PLAYER TEXT (OTHER)] `&%s`7.";
                }
                $args['schemas']['newest'] = "module-racetemplate";
                $args['section'] = "village-$race";
                $args['stablename'] = "[PLACEHOLDER STABLE NAME]";
                $args['schemas']['stablename'] = "module-racetemplate";
                $args['gatenav'] = "[PLACEHOLDER GATE NAV TEXT]";
                $args['schemas']['gatenav'] = "module-racetemplate";
                $args['fightnav'] = "[PLACEHOLDER FIGHT NAV TEXT]";
                $args['schemas']['fightnav'] = "module-racetemplate";
                $args['marketnav'] = "[PLACEHOLDER MARKET NAV TEXT]";
                $args['schemas']['marketnav'] = "module-racetemplate";
                $args['tavernnav'] = "[PLACEHOLDER TAVERN NAV TEXT]";
                $args['schemas']['tavernnav'] = "module-racetemplate";
                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (($session['user']['location'] ?? '') !== $city) break;
            $args['title'] = "[PLACEHOLDER STABLE NAME]";
            $args['schemas']['title'] = "module-racetemplate";
            $args['desc'] = "`\$[PLACEHOLDER STABLE DESCRIPTION FEATURING OWNER: ".get_module_setting("stableowner")."]";
            $args['schemas']['desc'] = "module-racetemplate";
            $args['lad'] = "Sir";
            $args['schemas']['lad'] = "module-racetemplate";
            $args['lass'] = "Miss";
            $args['schemas']['lass'] = "module-racetemplate";
            $args['nosuchbeast'] = "`3\"`7I don't stock that creature.`3\", says ".get_module_setting("stableowner")."`3.";
            $args['schemas']['nosuchbeast'] = "module-racetemplate";
            $args['finebeast'] = [
                "`3\"`7[PLACEHOLDER MOUNT PURCHASE QUOTE 1]`3\"`n`n",
                "`3\"`7[PLACEHOLDER MOUNT PURCHASE QUOTE 2]`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-racetemplate";
            $args['toolittle'] = "`%[PLACEHOLDER NOT ENOUGH GOLD QUOTE]";
            $args['schemas']['toolittle'] = "module-racetemplate";
            $args['replacemount'] = "`%[PLACEHOLDER REPLACE MOUNT TEXT: YOU TRADE YOUR `^%s FOR A `&%s`%]";
            $args['schemas']['replacemount'] = "module-racetemplate";
            $args['newmount'] = "`@[PLACEHOLDER NEW MOUNT TEXT: YOU RECEIVE A `&%s`@]";
            $args['schemas']['newmount'] = "module-racetemplate";
            $args['nofeed'] = "`3\"`7I don't stock feed here.`3\"";
            $args['schemas']['nofeed'] = "module-racetemplate";
            $args['nothungry'] = "`&%s`6 [PLACEHOLDER MOUNT NOT HUNGRY TEXT]";
            $args['schemas']['nothungry'] = "module-racetemplate";
            $args['halfhungry'] = "`&%s`6 [PLACEHOLDER MOUNT HALF HUNGRY TEXT]";
            $args['schemas']['halfhungry'] = "module-racetemplate";
            $args['hungry'] = "`6%s`6 [PLACEHOLDER MOUNT FULLY HUNGRY TEXT]";
            $args['schemas']['hungry'] = "module-racetemplate";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is full now.`6\"";
            $args['schemas']['mountfull'] = "module-racetemplate";
            $args['nofeedgold'] = "`6\"`^Not enough money for food.`6\"";
            $args['schemas']['nofeedgold'] = "module-racetemplate";
            $args['confirmsale'] = "`n`n`6[PLACEHOLDER CONFIRM MOUNT SALE TEXT]";
            $args['schemas']['confirmsale'] = "module-racetemplate";
            $args['mountsold'] = "`6[PLACEHOLDER MOUNT SOLD TEXT]";
            $args['schemas']['mountsold'] = "module-racetemplate";
            $args['offer'] = "`n`n`6[PLACEHOLDER MOUNT SALE OFFER TEXT]";
            $args['schemas']['offer'] = "module-racetemplate";
            break;

        case "stablelocs":
            $travel_mod = racetemplate_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userRace === $race) {
                $adjective = "[PLACEHOLDER ADJECTIVE]"; // ***CHANGE
                $nstr = "`%" . $adjective;
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function racetemplate_run() {
    // If you want a custom shrine or training building in your race's city, build it here!
}

function racetemplate_change() {
    global $session;
    $race = "[PLACEHOLDER RACE NAME]"; // ***CHANGE
    $adjective = "[PLACEHOLDER ADJECTIVE]"; // ***CHANGE: e.g. "Elven", "Dwarven"
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['race'] ?? '') === $race) {
        $nstr = "`%" . $adjective;
        
        if (!preg_match('/' . preg_quote($nstr, '/') . '/', $session['user']['weapon'] ?? '')) {
            $n = $nstr." ".($session['user']['weapon'] ?? '');
            if (strlen($n) < 50) $session['user']['weapon'] = $n;
        }
        
        if (!preg_match('/' . preg_quote($nstr, '/') . '/', $session['user']['armor'] ?? '')) {
            $n = $nstr." ".($session['user']['armor'] ?? '');
            if (strlen($n) < 50) $session['user']['armor'] = $n;
        }
    }
}

function racetemplate_checkcity() {
    global $session;
    $race = "[PLACEHOLDER RACE NAME]"; // ***CHANGE
    $city = get_module_setting("villagename");
    
    $travel_mod = racetemplate_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    return true;
}

function racetemplate_get_travel_mod() {
    if (is_module_active("villages")) {
        return "villages";
    } elseif (is_module_active("cities")) {
        return "cities";
    } elseif (is_module_active("multi-towns")) {
        return "multi-towns";
    }
    return false;
}
?>