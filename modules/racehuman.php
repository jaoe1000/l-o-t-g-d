<?php
/**
 * Advanced Race Module - Human
 * Built from the Advanced Race Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function racehuman_getmoduleinfo() {
    return [
        "name" => "Race - Human",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Race",
        "download" => "", 
        "settings" => [
            "Human - Race Settings,title",
            "villagename" => "Name for the Human city,|Oakhaven", 
            "stableowner" => "Name of the city stable-owner,|Master Horatio", 
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|20", 
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|0",
            "wepchange" => "Will weapons & armour change to race-specific names?,bool|1", 
            "ff" => "Extra forest fights per day,int|1",
        ],
        "prefs" => [
            "Human - User Prefs,title", 
            // Empty for basic race
        ]
    ];
}

function racehuman_install() {
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
    debug("Installed 'Human' advanced race module.");
    return true;
}

function racehuman_uninstall() {
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
    $sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Human'";
    db_query($sql);
    if (($session['user']['race'] ?? '') === 'Human') {
        $session['user']['race'] = RACE_UNKNOWN;
    }
    
    debug("Uninstalled 'Human' race module.");
    return true;
}

function racehuman_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "Human"; 
    
    $userRace = $session['user']['race'] ?? '';
    
    switch($hookname) {
        case "newday-intercept":
            racehuman_change();
            break;

        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "racehuman") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = racehuman_get_travel_mod();
                if ($travel_mod !== false) {
                    $sql = "UPDATE " . db_prefix("module_userprefs") . " SET value='" . $args['new'] . "' WHERE modulename='$travel_mod' AND setting='homecity' AND value='" . $args['old'] . "'";
                    db_query($sql);
                }
            }
            break;

        case "charstats":
            if ($userRace === $race) {
                addcharstat("Vital Info");
                addcharstat("Race", translate_inline("`^$race"));
            }
            break;

        case "chooserace":
            if (($session['user']['dragonkills'] ?? 0) >= (int)get_module_setting("dklimit")) {
                $atrans = translate_inline("In the bustling trade squares of %s"); 
                $rtrans = translate_inline(", surrounded by ambitious merchants and unyielding stone walls.`n`n");
                addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                addnav("", "newday.php?setrace=$race$resline");
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`&You embrace your human heritage, granting you unparalleled adaptability and an extra forest fight per day!`n"); 
                $travel_mod = racehuman_get_travel_mod();
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
                racehuman_checkcity();
                
                $ff = (int)get_module_setting("ff");
                
                if ($ff > 0) {
                    $args['turnstoday'] = ($args['turnstoday'] ?? '') . ", Race ($race): $ff";
                    $session['user']['turns'] += $ff;
                    output("`n`^Driven by human ambition, you wake up feeling energetic and gain `%$ff`^ extra turn(s) today!`n`0");
                }
            }
            break;

        case "validlocation":
            $travel_mod = racehuman_get_travel_mod();
            if ($travel_mod !== false)
                $args[$city] = "village-$race";
            break;

        case "moderate":
            $travel_mod = racehuman_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("commentary");
                $args["village-$race"] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "travel":
            $travel_mod = racehuman_get_travel_mod();
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
            racehuman_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^The cobblestone streets of %s bustle with merchants, guards, and adventurers alike. It is a beacon of human civilization.`n", $city, $city];
                $args['schemas']['text'] = "module-racehuman";
                $args['description'] = ["`\$`c`b%s`b`c`n`^The cobblestone streets of %s bustle with merchants, guards, and adventurers alike. It is a beacon of human civilization.`n", $city, $city];
                $args['schemas']['description'] = "module-racehuman";
                $args['clock'] = "`n`7The town square clock reads `@%s`7.`n";
                $args['schemas']['clock'] = "module-racehuman";
                
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$A posted parchment calendar reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-racehuman";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-racehuman";
                $args['sayline'] = "says";
                $args['schemas']['sayline'] = "module-racehuman";
                $args['talk'] = "`n`&Nearby, some town guards stand talking:`n";
                $args['schemas']['talk'] = "module-racehuman";
                
                $travel_mod = racehuman_get_travel_mod();
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
                    $args['newest'] = "`n`7You are the newest resident of this bustling city.";
                } else {
                    $args['newest'] = "`n`7The newest resident of this bustling city is `&%s`7.";
                }
                $args['schemas']['newest'] = "module-racehuman";
                $args['section'] = "village-$race";
                
                $args['stablename'] = "Oakhaven Stables";
                $args['schemas']['stablename'] = "module-racehuman";
                $args['gatenav'] = "City Gates";
                $args['schemas']['gatenav'] = "module-racehuman";
                $args['fightnav'] = "Training Grounds";
                $args['schemas']['fightnav'] = "module-racehuman";
                $args['marketnav'] = "Merchant Square";
                $args['schemas']['marketnav'] = "module-racehuman";
                $args['tavernnav'] = "The Boar's Head Tavern";
                $args['schemas']['tavernnav'] = "module-racehuman";
                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (($session['user']['location'] ?? '') !== $city) break;
            $owner = get_module_setting("stableowner");
            
            $args['title'] = "Oakhaven Stables";
            $args['schemas']['title'] = "module-racehuman";
            $args['desc'] = "`\$The smell of hay and horses fills the air. $owner is busy brushing down a sturdy stallion.";
            $args['schemas']['desc'] = "module-racehuman";
            $args['lad'] = "Sir";
            $args['schemas']['lad'] = "module-racehuman";
            $args['lass'] = "Miss";
            $args['schemas']['lass'] = "module-racehuman";
            $args['nosuchbeast'] = "`3\"`7I don't stock that creature.`3\", says $owner.`3";
            $args['schemas']['nosuchbeast'] = "module-racehuman";
            $args['finebeast'] = [
                "`3\"`7A fine steed for a brave adventurer!`3\"`n`n",
                "`3\"`7You won't find a better mount in all the realm.`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-racehuman";
            $args['toolittle'] = "`%\"`7You seem to be a bit short on gold for that one,`%\" $owner says politely.";
            $args['schemas']['toolittle'] = "module-racehuman";
            $args['replacemount'] = "`%You trade in your `^%s for a strong `&%s`%.";
            $args['schemas']['replacemount'] = "module-racehuman";
            $args['newmount'] = "`@You hand over the gold and receive a sturdy `&%s`@.";
            $args['schemas']['newmount'] = "module-racehuman";
            $args['nofeed'] = "`3\"`7I'm currently out of feed, my apologies.`3\"";
            $args['schemas']['nofeed'] = "module-racehuman";
            $args['nothungry'] = "`&%s`6 doesn't look hungry at all.";
            $args['schemas']['nothungry'] = "module-racehuman";
            $args['halfhungry'] = "`&%s`6 eats the feed happily.";
            $args['schemas']['halfhungry'] = "module-racehuman";
            $args['hungry'] = "`6%s`6 devours the feed instantly!";
            $args['schemas']['hungry'] = "module-racehuman";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is completely full now.`6\"";
            $args['schemas']['mountfull'] = "module-racehuman";
            $args['nofeedgold'] = "`6\"`^You don't have enough money to feed your mount.`6\"";
            $args['schemas']['nofeedgold'] = "module-racehuman";
            $args['confirmsale'] = "`n`n`6Are you sure you wish to sell your mount?";
            $args['schemas']['confirmsale'] = "module-racehuman";
            $args['mountsold'] = "`6You hand over the reins and receive your gold.";
            $args['schemas']['mountsold'] = "module-racehuman";
            $args['offer'] = "`n`n`6\"`^I'll buy that beast off your hands if you're looking to sell,`6\" $owner offers.";
            $args['schemas']['offer'] = "module-racehuman";
            break;

        case "stablelocs":
            $travel_mod = racehuman_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userRace === $race) {
                $adjective = "Human"; 
                $nstr = "`%" . $adjective;
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function racehuman_run() {
    // Custom shrines or building code can go here
}

function racehuman_change() {
    global $session;
    $race = "Human"; 
    $adjective = "Human"; 
    
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

function racehuman_checkcity() {
    global $session;
    $race = "Human";
    $city = get_module_setting("villagename");
    
    $travel_mod = racehuman_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    return true;
}

function racehuman_get_travel_mod() {
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
