<?php
/**
 * Advanced Race Module - Elf
 * Built from the Advanced Race Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function raceelf_getmoduleinfo() {
    return [
        "name" => "Race - Elf",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Race",
        "download" => "", 
        "settings" => [
            "Elf - Race Settings,title",
            "villagename" => "Name for the Elf city,|Gladehaven", 
            "stableowner" => "Name of the city stable-owner,|Elandir", 
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|15", 
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|0",
            "wepchange" => "Will weapons & armour change to race-specific names?,bool|1", 
        ],
        "prefs" => [
            "Elf - User Prefs,title", 
            // Empty for basic race
        ]
    ];
}

function raceelf_install() {
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
    debug("Installed 'Elf' advanced race module.");
    return true;
}

function raceelf_uninstall() {
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
    $sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Elf'";
    db_query($sql);
    if (($session['user']['race'] ?? '') === 'Elf') {
        $session['user']['race'] = RACE_UNKNOWN;
    }
    
    debug("Uninstalled 'Elf' race module.");
    return true;
}

function raceelf_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "Elf"; 
    
    $userRace = $session['user']['race'] ?? '';
    
    switch($hookname) {
        case "newday-intercept":
            raceelf_change();
            break;

        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "raceelf") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = raceelf_get_travel_mod();
                if ($travel_mod !== false) {
                    $sql = "UPDATE " . db_prefix("module_userprefs") . " SET value='" . $args['new'] . "' WHERE modulename='$travel_mod' AND setting='homecity' AND value='" . $args['old'] . "'";
                    db_query($sql);
                }
            }
            break;

        case "charstats":
            if ($userRace === $race) {
                addcharstat("Vital Info");
                addcharstat("Race", translate_inline("`@$race"));
            }
            break;

        case "chooserace":
            if (($session['user']['dragonkills'] ?? 0) >= (int)get_module_setting("dklimit")) {
                $atrans = translate_inline("High among the ancient treetop structures of %s"); 
                $rtrans = translate_inline(", where magic and nature blend in quiet harmony.`n`n");
                addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                addnav("", "newday.php?setrace=$race$resline");
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`&You embrace your elven heritage, granting you heightened reflexes, sylvan grace, and an extra turn per day!`n"); 
                $travel_mod = raceelf_get_travel_mod();
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
                raceelf_checkcity();
                
                // Elf gains +1 extra turn today
                $args['turnstoday'] = ($args['turnstoday'] ?? '') . ", Race ($race): 1";
                $session['user']['turns']++;
                
                // Apply Elven Reflexes racial buff
                apply_buff("racialbenefit", [
                    "name" => "`@Elven Reflexes`0",
                    "atkmod" => 1.05,
                    "defmod" => 1.15,
                    "badguydmgmod" => 0.90,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "rounds" => -1,
                    "schema" => "module-raceelf",
                ]);
                
                output("`n`@You awaken with sylvan grace. Your senses are razor sharp (+15% defense, +5% attack, +1 extra turn)!`n`0");
            }
            break;

        case "validlocation":
            $travel_mod = raceelf_get_travel_mod();
            if ($travel_mod !== false)
                $args[$city] = "village-$race";
            break;

        case "moderate":
            $travel_mod = raceelf_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("commentary");
                $args["village-$race"] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "travel":
            $travel_mod = raceelf_get_travel_mod();
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
            raceelf_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^Golden beams of sunlight filter down through the towering canopy of %s. Elaborate tree-houses and sylvan structures overlook the village green.`n", $city, $city];
                $args['schemas']['text'] = "module-raceelf";
                $args['description'] = ["`\$`c`b%s`b`c`n`^Golden beams of sunlight filter down through the towering canopy of %s. Elaborate tree-houses and sylvan structures overlook the village green.`n", $city, $city];
                $args['schemas']['description'] = "module-raceelf";
                $args['clock'] = "`n`7A delicate crystal sundial reads `@%s`7.`n";
                $args['schemas']['clock'] = "module-raceelf";
                
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$An engraved oak calendar reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-raceelf";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-raceelf";
                $args['sayline'] = "whispers";
                $args['schemas']['sayline'] = "module-raceelf";
                $args['talk'] = "`n`&Nearby, some sylvan residents speak in melodic tones:`n";
                $args['schemas']['talk'] = "module-raceelf";
                
                $travel_mod = raceelf_get_travel_mod();
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
                    $args['newest'] = "`n`7You are the newest resident of this sylvan haven.";
                } else {
                    $args['newest'] = "`n`7The newest resident of this sylvan haven is `&%s`7.";
                }
                $args['schemas']['newest'] = "module-raceelf";
                $args['section'] = "village-$race";
                
                $args['stablename'] = "Sylvan Stables";
                $args['schemas']['stablename'] = "module-raceelf";
                $args['gatenav'] = "Forest Paths";
                $args['schemas']['gatenav'] = "module-raceelf";
                $args['fightnav'] = "Training Glade";
                $args['schemas']['fightnav'] = "module-raceelf";
                $args['marketnav'] = "Treepath Bazaar";
                $args['schemas']['marketnav'] = "module-raceelf";
                $args['tavernnav'] = "The Dewdrop Inn";
                $args['schemas']['tavernnav'] = "module-raceelf";
                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (($session['user']['location'] ?? '') !== $city) break;
            $owner = get_module_setting("stableowner");
            
            $args['title'] = "Sylvan Stables";
            $args['schemas']['title'] = "module-raceelf";
            $args['desc'] = "`\$You stand in a beautiful stable built around a massive, living redwood tree. $owner greets you with a deep bow.";
            $args['schemas']['desc'] = "module-raceelf";
            $args['lad'] = "Sir";
            $args['schemas']['lad'] = "module-raceelf";
            $args['lass'] = "Miss";
            $args['schemas']['lass'] = "module-raceelf";
            $args['nosuchbeast'] = "`3\"`7We do not house that beast in our canopy,`3\" says $owner.`3";
            $args['schemas']['nosuchbeast'] = "module-raceelf";
            $args['finebeast'] = [
                "`3\"`7May this beast carry you swiftly through the forest shadows.`3\"`n`n",
                "`3\"`7A majestic creature for a majestic soul.`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-raceelf";
            $args['toolittle'] = "`%\"`7Alas, the forest spirits require a larger offering for this mount,`%\" $owner whispers.";
            $args['schemas']['toolittle'] = "module-raceelf";
            $args['replacemount'] = "`%You release your `^%s back to nature, taking the reins of a graceful `&%s`%.";
            $args['schemas']['replacemount'] = "module-raceelf";
            $args['newmount'] = "`@You receive a graceful, fleet-footed `&%s`@.";
            $args['schemas']['newmount'] = "module-raceelf";
            $args['nofeed'] = "`3\"`7Nature provides for all, we do not sell feed here.`3\"";
            $args['schemas']['nofeed'] = "module-raceelf";
            $args['nothungry'] = "`&%s`6 shakes its head, refusing food.";
            $args['schemas']['nothungry'] = "module-raceelf";
            $args['halfhungry'] = "`&%s`6 eats the fresh dewberries elegantly.";
            $args['schemas']['halfhungry'] = "module-raceelf";
            $args['hungry'] = "`6%s`6 eats the fresh dewberries eagerly!";
            $args['schemas']['hungry'] = "module-raceelf";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is completely satisfied.`6\"";
            $args['schemas']['mountfull'] = "module-raceelf";
            $args['nofeedgold'] = "`6\"`^You lack the gold to pay for feed.`6\"";
            $args['schemas']['nofeedgold'] = "module-raceelf";
            $args['confirmsale'] = "`n`n`6Are you sure you wish to return your steed to the sylvan forest?";
            $args['schemas']['confirmsale'] = "module-raceelf";
            $args['mountsold'] = "`6You hand over the reins, releasing the beast.";
            $args['schemas']['mountsold'] = "module-raceelf";
            $args['offer'] = "`n`n`6\"`^I can offer you `&%s`6 gold and `%%s`6 gems to return your mount to our stables,`6\" $owner offers.";
            $args['schemas']['offer'] = "module-raceelf";
            break;

        case "stablelocs":
            $travel_mod = raceelf_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userRace === $race) {
                $adjective = "Elven"; 
                $nstr = "`%" . $adjective;
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function raceelf_run() {
    // Custom shrines or building code can go here
}

function raceelf_change() {
    global $session;
    $race = "Elf"; 
    $adjective = "Elven"; 
    
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

function raceelf_checkcity() {
    global $session;
    $race = "Elf";
    $city = get_module_setting("villagename");
    
    $travel_mod = raceelf_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    return true;
}

function raceelf_get_travel_mod() {
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
