<?php
/**
 * Advanced Race Module - Angel
 * Built from the Advanced Race Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function raceangel_getmoduleinfo() {
    return [
        "name" => "Race - Angel",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Race",
        "download" => "", 
        "settings" => [
            "Angel - Race Settings,title",
            "use_custom_village" => "Does this race have its own custom village?,bool|1",
            "villagename" => "Name for the Angel city,|Elysium", 
            "stableowner" => "Name of the city stable-owner,|Gabriel", 
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|10", 
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|0", 
            "training_villages" => "Villages where players of this race can train (comma-separated; leave blank to restrict to their custom village if enabled),string|",
            
            "Race Progression Settings,title",
            "levels" => "Max Race Level,int|5",
            "cre_req" => "Battle victories required to level up,int|150",
            "legacy_stat" => "Legacy Buff Stat (attack/defense/hp/gold/none),string|hp",
            "legacy_value" => "Legacy Buff Value,float|3.0",
            "unlock_req" => "Unlock Requirements (comma-separated, e.g. racehuman:5),string|racehuman:5,raceelf:5",
            "stagnation_decay" => "Stagnation victory point multiplier (0.0 to 1.0),float|0.5",
        ],
        "prefs" => [
            "Angel - User Prefs,title",
            "level" => "Current Race Level,int|0",
            "cre" => "Current Victory Points towards next level,float|0.0",
            "stagnation" => "Consecutive Dragon Kills as this race,int|0",
            "last_played_dk" => "Was this race played last DK?,bool|0",
            "fresh_soul" => "Does player have fresh soul bonus?,bool|0",
            
            "altar_blessed" => "Has player prayed at the altar today?,bool|0",
            "pool_bathed" => "Has player bathed in the pool today?,bool|0",
        ]
    ];
}

function raceangel_install() {
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
    module_addhook("villagenav");
    module_addhook("weaponstext");
    module_addhook("armortext");
    module_addhook("trainingtitle");
    module_addhook("modify-master");
    module_addhook("training-allowed-cities");
    module_addhook("dragonkill");
    module_addhook("battle-victory");
    module_addhook("pvpwin");
    module_addhook("creatureencounter");
    debug("Installed 'Angel' advanced race module.");
    return true;
}

function raceangel_uninstall() {
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
    $sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Angel'";
    db_query($sql);
    if (($session['user']['race'] ?? '') === 'Angel') {
        $session['user']['race'] = RACE_UNKNOWN;
    }
    
    debug("Uninstalled 'Angel' race module.");
    return true;
}

function raceangel_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "Angel"; 
    
    $userRace = $session['user']['race'] ?? '';
    
    switch($hookname) {
        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (get_module_setting("use_custom_village") && ($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "raceangel") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = raceangel_get_travel_mod();
                if ($travel_mod !== false) {
                    $sql = "UPDATE " . db_prefix("module_userprefs") . " SET value='" . $args['new'] . "' WHERE modulename='$travel_mod' AND setting='homecity' AND value='" . $args['old'] . "'";
                    db_query($sql);
                }
            }
            break;

        case "charstats":
            if ($userRace === $race) {
                $lvl = (int)get_module_pref("level");
                addcharstat("Vital Info");
                addcharstat("Race", translate_inline($race) . " (Lvl $lvl)");
            }
            // Display active legacies if maxed out
            $lvl = (int)get_module_pref("level");
            $max = (int)get_module_setting("levels");
            if ($lvl >= $max) {
                $stat = get_module_setting("legacy_stat");
                $val = get_module_setting("legacy_value");
                if ($stat !== "none" && $val > 0) {
                    addcharstat("Legacies");
                    $desc = "";
                    if ($stat === "attack") $desc = "+" . (($val - 1) * 100) . "% Attack";
                    elseif ($stat === "defense") $desc = "+" . (($val - 1) * 100) . "% Defense";
                    elseif ($stat === "hp") $desc = "+" . (int)$val . " Max HP";
                    elseif ($stat === "gold") $desc = "+" . (($val - 1) * 100) . "% Gold";
                    addcharstat($race . " Legacy", "`^" . $desc);
                }
            }
            break;

        case "chooserace":
            $dk_limit = (int)get_module_setting("dklimit");
            if ($dk_limit === 0 || ($session['user']['dragonkills'] ?? 0) >= $dk_limit) {
                $unlocked = raceangel_check_unlock();
                if ($unlocked) {
                    $atrans = translate_inline("Within the golden gates of %s");
                    $rtrans = translate_inline(", home to the holy Angels.`n`n");
                    addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                    output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                    addnav("", "newday.php?setrace=$race$resline");
                } else {
                    $req_text = raceangel_get_unlock_text();
                    output("`7Locked Race: `&%s`7 (Requires: `5%s`7)`n`n", $race, $req_text);
                }
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`^You embrace your angelic blood! You feel the powers of light coursing in your veins (+15%% Defense, +5%% Attack, +2 extra turns)!`n");
                
                // Determine Stagnation vs Fresh Soul
                $last_played = (bool)get_module_pref("last_played_dk");
                if ($last_played) {
                    $stg = (int)get_module_pref("stagnation") + 1;
                    set_module_pref("stagnation", $stg);
                    set_module_pref("fresh_soul", 0);
                    output("`n`7You feel a sense of routine. Your race leveling speed is stagnated by %s%%.`n", ($stg * 10));
                } else {
                    set_module_pref("stagnation", 0);
                    set_module_pref("fresh_soul", 1);
                    output("`n`@Your soul feels rejuvenated as you adopt a new form! You gain a Fresh Soul leveling speed bonus (2x points)!`n");
                }
                set_module_pref("last_played_dk", 0);

                if (get_module_setting("use_custom_village")) {
                    $travel_mod = raceangel_get_travel_mod();
                    if ($travel_mod !== false) {
                        if (($session['user']['dragonkills'] ?? 0) == 0 && ($session['user']['age'] ?? 0) == 0) {
                            set_module_setting("newest-$city", $session['user']['acctid'], $travel_mod);
                        }
                        set_module_pref("homecity", $city, $travel_mod);
                        if (($session['user']['age'] ?? 0) == 0)
                            $session['user']['location'] = $city;
                    }
                }
            }
            break;

        case "dragonkill":
            if ($userRace === $race) {
                set_module_pref("last_played_dk", 1);
            } else {
                set_module_pref("last_played_dk", 0);
            }
            set_module_pref("fresh_soul", 0);
            break;

        case "battle-victory":
        case "pvpwin":
            if ($userRace === $race) {
                $lvl = (int)get_module_pref("level");
                $max = (int)get_module_setting("levels");
                if ($lvl < $max) {
                    $gain = 1.0;
                    
                    $stg = (int)get_module_pref("stagnation");
                    if ($stg > 0) {
                        $decay = (float)get_module_setting("stagnation_decay");
                        $gain *= $decay;
                    }
                    
                    $fresh = (int)get_module_pref("fresh_soul");
                    if ($fresh > 0) {
                        $gain *= 2.0;
                    }
                    
                    $cre = (float)get_module_pref("cre") + $gain;
                    $req = (int)get_module_setting("cre_req");
                    
                    if ($cre >= $req) {
                        set_module_pref("cre", 0.0);
                        set_module_pref("level", $lvl + 1);
                        output("`n`@Your understanding of your %s heritage has reached Level %s!`n", translate_inline($race), $lvl + 1);
                        
                        if ($lvl + 1 >= $max) {
                            output("`n`^You have unlocked the Ancestral Legacy of the %s! You permanently gain the legacy bonus.`n", translate_inline($race));
                        }
                    } else {
                        set_module_pref("cre", $cre);
                    }
                }
            }
            break;

        case "creatureencounter":
            // Check legacy gold bonus
            $lvl = (int)get_module_pref("level");
            $max = (int)get_module_setting("levels");
            if ($lvl >= $max && get_module_setting("legacy_stat") === "gold") {
                $val = (float)get_module_setting("legacy_value");
                if ($val > 0.0) {
                    $args['creaturegold'] = round($args['creaturegold'] * $val);
                }
            }
            break;

        case "newday":
            // Apply standard race buffs
            if ($userRace === $race) {
                raceangel_checkcity();
                
                // Angels get +2 turns and +15% defense, +5% attack
                $args['turnstoday'] = ($args['turnstoday'] ?? '') . ", Race ($race): 2";
                $session['user']['turns'] += 2;
                
                apply_buff("racialbenefit", [
                    "name" => sprintf_translate("Angelic Grace"),
                    "defmod" => 1.15,
                    "atkmod" => 1.05,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "rounds" => -1,
                    "schema" => "module-raceangel",
                ]);
                
                // Reset daily services
                set_module_pref("altar_blessed", 0);
                set_module_pref("pool_bathed", 0);

                output("`n`^You awaken under divine rays of light in Elysium. Your soul is pure and focused (+15%% Defense, +5%% Attack, +2 extra turns).`n`0");
            }
            
            // Apply Legacy Buffs
            $lvl = (int)get_module_pref("level");
            $max = (int)get_module_setting("levels");
            if ($lvl >= $max) {
                $stat = get_module_setting("legacy_stat");
                $val = (float)get_module_setting("legacy_value");
                if ($stat === "attack" && $val > 0.0) {
                    apply_buff("legacy_" . strtolower($race) . "_atk", [
                        "name" => sprintf_translate("Legacy of the %s", translate_inline($race)),
                        "atkmod" => $val,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "rounds" => -1,
                        "schema" => "module-raceangel",
                    ]);
                } elseif ($stat === "defense" && $val > 0.0) {
                    apply_buff("legacy_" . strtolower($race) . "_def", [
                        "name" => sprintf_translate("Legacy of the %s", translate_inline($race)),
                        "defmod" => $val,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "rounds" => -1,
                        "schema" => "module-raceangel",
                    ]);
                } elseif ($stat === "hp" && $val > 0.0) {
                    $session['user']['maxhitpoints'] += (int)$val;
                    $session['user']['hitpoints'] += (int)$val;
                    if ($userRace !== $race) {
                        output("`n`@Your ancestral connection to the `&%s`@ grants you `^+%s`@ max hitpoints!`n", translate_inline($race), (int)$val);
                    }
                }
            }
            break;

        case "training-allowed-cities":
            if ($userRace === $race) {
                $cfg = trim(get_module_setting("training_villages"));
                if ($cfg !== "") {
                    $parts = explode(",", $cfg);
                    foreach ($parts as $p) {
                        $p = trim($p);
                        if ($p !== "") {
                            $args['cities'][] = $p;
                        }
                    }
                } else {
                    if (get_module_setting("use_custom_village")) {
                        $args['cities'][] = $city;
                    }
                }
            }
            break;

        case "validlocation":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = raceangel_get_travel_mod();
                if ($travel_mod !== false)
                    $args[$city] = "village-$race";
            }
            break;

        case "moderate":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = raceangel_get_travel_mod();
                if ($travel_mod !== false) {
                    tlschema("commentary");
                    $args["village-$race"] = sprintf_translate("City of %s", $city); 
                    tlschema();
                }
            }
            break;

        case "travel":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = raceangel_get_travel_mod();
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
            }
            break;  

        case "villagetext":
            if (!get_module_setting("use_custom_village")) break;
            raceangel_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^Polished marble towers shine with divine gold in %s. A quiet sanctuary of peace, where waterfalls flow with sweet spring water.`n", $city, $city];
                $args['schemas']['text'] = "module-raceangel";
                $args['description'] = ["`\$`c`b%s`b`c`n`^Polished marble towers shine with divine gold in %s. A quiet sanctuary of peace, where waterfalls flow with sweet spring water.`n", $city, $city];
                $args['schemas']['description'] = "module-raceangel";
                $args['clock'] = "`n`7A golden sundial reads `@%s`7.`n";
                $args['schemas']['clock'] = "module-raceangel";
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$A posted tablet calendar reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-raceangel";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-raceangel";
                $args['sayline'] = "chants";
                $args['schemas']['sayline'] = "module-raceangel";
                $args['talk'] = "`n`&Nearby some angelic guards stand talking:`n";
                $args['schemas']['talk'] = "module-raceangel";
                
                $travel_mod = raceangel_get_travel_mod();
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
                    $args['newest'] = "`n`7You are the newest resident of Elysium.";
                } else {
                    $args['newest'] = "`n`7The newest resident of Elysium is `&%s`7.";
                }
                $args['schemas']['newest'] = "module-raceangel";
                $args['section'] = "village-$race";
                $args['stablename'] = "Elysian Stables";
                $args['schemas']['stablename'] = "module-raceangel";
                $args['gatenav'] = "Golden Gates";
                $args['schemas']['gatenav'] = "module-raceangel";
                $args['fightnav'] = "Cathedral Glade";
                $args['schemas']['fightnav'] = "module-raceangel";
                $args['marketnav'] = "Sanctuary Square";
                $args['schemas']['marketnav'] = "module-raceangel";
                $args['tavernnav'] = "The Angel's Share";
                $args['schemas']['tavernnav'] = "module-raceangel";

                // Override standard location nav labels
                $args['navs']['weapons'] = "Elysian Armory";
                $args['navs']['armor'] = "Elysian Bulwarks";
                $args['navs']['train'] = "u?Training Cathedral";
                $args['schemas']['navs']['weapons'] = "module-raceangel";
                $args['schemas']['navs']['armor'] = "module-raceangel";
                $args['schemas']['navs']['train'] = "module-raceangel";

                // Special section nav header and labels
                $args['nav_headers']['special'] = "Elysian Services";
                $args['navs']['altar'] = "Holy Altar";
                $args['navs']['pool'] = "Ascension Pool";
                $args['schemas']['nav_headers']['special'] = "module-raceangel";
                $args['schemas']['navs']['altar'] = "module-raceangel";
                $args['schemas']['navs']['pool'] = "module-raceangel";

                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') !== $city) break;
            $owner = get_module_setting("stableowner");
            $args['title'] = "Elysian Stables";
            $args['schemas']['title'] = "module-raceangel";
            $args['desc'] = "`\$You stand in a luminous stable built from marble and glowing gold. $owner is grooming a golden-maned pegasus. He greets you with a holy bow.";
            $args['schemas']['desc'] = "module-raceangel";
            $args['lad'] = "Brother";
            $args['schemas']['lad'] = "module-raceangel";
            $args['lass'] = "Sister";
            $args['schemas']['lass'] = "module-raceangel";
            $args['nosuchbeast'] = "`3\"`7We do not house that creature in our sanctuary,`3\" says $owner.`3.";
            $args['schemas']['nosuchbeast'] = "module-raceangel";
            $args['finebeast'] = [
                "`3\"`7May this beast fly you safely under the divine light.`3\"`n`n",
                "`3\"`7A celestial creature for a celestial soul.`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-raceangel";
            $args['toolittle'] = "`%\"`7Your offering is too small for that mount,`%\" $owner whispers.";
            $args['schemas']['toolittle'] = "module-raceangel";
            $args['replacemount'] = "`%You release your `^%s back to nature, taking the reins of a graceful `&%s`%.";
            $args['schemas']['replacemount'] = "module-raceangel";
            $args['newmount'] = "`@You receive a graceful, flying `&%s`@.";
            $args['schemas']['newmount'] = "module-raceangel";
            $args['nofeed'] = "`3\"`7Divine energy sustains them. We do not sell feed here.`3\"";
            $args['schemas']['nofeed'] = "module-raceangel";
            $args['nothungry'] = "`&%s`6 shakes its head elegantly.";
            $args['schemas']['nothungry'] = "module-raceangel";
            $args['halfhungry'] = "`&%s`6 eats the fresh sunberries elegantly.";
            $args['schemas']['halfhungry'] = "module-raceangel";
            $args['hungry'] = "`6%s`6 devours the fresh sunberries eagerly!";
            $args['schemas']['hungry'] = "module-raceangel";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is completely satisfied.`6\"";
            $args['schemas']['mountfull'] = "module-raceangel";
            $args['nofeedgold'] = "`6\"`^You have no gold for sunberry feed.`6\"";
            $args['schemas']['nofeedgold'] = "module-raceangel";
            $args['confirmsale'] = "`n`n`6Are you sure you wish to return your mount?";
            $args['schemas']['confirmsale'] = "module-raceangel";
            $args['mountsold'] = "`6You hand over the reins, releasing the beast.";
            $args['schemas']['mountsold'] = "module-raceangel";
            $args['offer'] = "`n`n`6\"`^I can offer you `&%s`6 gold and `%%s`6 gems to return your mount to our stables,`6\" $owner offers.";
            $args['schemas']['offer'] = "module-raceangel";
            break;

        case "stablelocs":
            if (!get_module_setting("use_custom_village")) break;
            $travel_mod = raceangel_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "villagenav":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city && ($session['user']['race'] ?? '') === $race) {
                $args['special']['altar'] = "runmodule.php?module=raceangel&op=altar";
                $args['special']['pool'] = "runmodule.php?module=raceangel&op=pool";
            }
            break;

        case "weaponstext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['weaponvalue'] * 0.75), 0);
                $args['title'] = "Elysian Armory";
                $args['desc'] = [
                    "`&Glowing golden swords, sun-blessed bows, and white steel shields float in peaceful displays. A warm white light fills the room.`n`n"
                ];
                $args['tradein'] = [
                    "`7You place your weapon on the marble altar.`n`n",
                    [
                        "`&The archangel blacksmith inspects it. \"`#I can offer `^%s`# gold trade value toward our holy weaponry.`\"`n`n",
                        $tradeinvalue,
                        $session['user']['weapon']
                    ]
                ];
            }
            break;

        case "armortext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['armorvalue'] * 0.75), 0);
                $args['title'] = "Elysian Bulwarks";
                $args['desc'] = [
                    "`&Polished white marble plate, halo-reinforced chainmail, and feather-stitched shields line the walls.`n`n"
                ];
                $args['tradein'] = [
                    "`7You show your current protection to the armorer.`n`n",
                    [
                        "`&He feels the texture and nods. \"`#I can offer `^%s`# trade-in value for this.`\"`n`n",
                        $tradeinvalue,
                        $session['user']['armor']
                    ]
                ];
            }
            break;

        case "trainingtitle":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['title'] = "Training Cathedral";
            }
            break;

        case "modify-master":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['creaturename'] = "Uriel the Archangel";
                $args['creatureweapon'] = "Flaming Greatsword";
                $args['creaturewin'] = "Your soul lacks focus! Center your thoughts on justice!";
                $args['creaturelose'] = "You have the true light. Go, protect the innocent.";
            }
            break;
    }
    return $args;
}

function raceangel_run() {
    global $session;
    if (!get_module_setting("use_custom_village")) {
        page_header("Access Denied");
        output("`\$Custom village features are disabled for this race.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    $op = httpget('op');
    $race = "Angel";
    
    // Check if the user is an Angel
    if (($session['user']['race'] ?? '') !== $race) {
        page_header("Access Denied");
        output("`\$Only Angels may access these features.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    
    if ($op === "altar") {
        page_header("Elysian Holy Altar");
        output("`c`b`^Elysian Holy Altar`b`c`n`n");
        output("`7A glorious altar of light. Offering a gem here allows you to request sylvan blessings.`n`n");
        
        $blessed = get_module_pref("altar_blessed");
        $subop = httpget('subop');
        
        if ($subop === "bless") {
            if ($blessed) {
                output("`\$You have already received the blessing today.`n`n");
            } elseif ($session['user']['gems'] < 1) {
                output("`\$You do not have any gems to offer the spirits.`n`n");
            } else {
                $session['user']['gems'] -= 1;
                set_module_pref("altar_blessed", 1);
                $blessed = true;
                
                apply_buff("angel_grace", [
                    "name" => "`^Angelic Grace`0",
                    "defmod" => 1.15,
                    "atkmod" => 1.10,
                    "rounds" => 20,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "schema" => "module-raceangel",
                ]);
                output("`@You offer a gem to the altar. A warm white light envelops you, granting you Angelic Grace (+15%% Defense, +10%% Attack) for your next 20 rounds of combat!`n`n");
            }
        }
        
        if (!$blessed) {
            output("`7You have `@%s gem(s)`7 to offer.`n`n", $session['user']['gems']);
            if ($session['user']['gems'] >= 1) {
                addnav("Holy Altar");
                addnav("Request Blessing", "runmodule.php?module=raceangel&op=altar&subop=bless");
            }
        } else {
            output("`&You have already prayed at the altar today.`n`n");
        }
        addnav("Return to Elysium", "village.php");
        page_footer();
    }
    
    if ($op === "pool") {
        page_header("Elysian Ascension Pool");
        output("`c`b`^Elysian Ascension Pool`b`c`n`n");
        output("`7A peaceful pool filled with sweet, warm water that reflects halos. Bathing here restores your strength.`n`n");
        
        $bathed = get_module_pref("pool_bathed");
        $subop = httpget('subop');
        
        if ($subop === "bathe") {
            if ($bathed) {
                output("`\$You have already bathed today.`n`n");
            } else {
                set_module_pref("pool_bathed", 1);
                $bathed = true;
                
                // Full heal and temporary +10 max HP today
                $session['user']['hitpoints'] = $session['user']['maxhitpoints'] + 10;
                output("`@You step into the warm, glowing pool. You feel your fatigue and injuries vanish completely, and a holy aura wraps around you, granting you `^+10 temporary Max HP`@ for today!`n`n");
            }
        }
        
        if (!$bathed) {
            addnav("Ascension Pool");
            addnav("Bathe in Pool", "runmodule.php?module=raceangel&op=pool&subop=bathe");
        } else {
            output("`&You have already bathed today.`n`n");
        }
        addnav("Return to Elysium", "village.php");
        page_footer();
    }
}

function raceangel_checkcity() {
    global $session;
    if (!get_module_setting("use_custom_village")) return true;
    $race = "Angel";
    $city = get_module_setting("villagename");
    
    $travel_mod = raceangel_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    return true;
}

function raceangel_get_travel_mod() {
    if (is_module_active("villages")) {
        return "villages";
    } elseif (is_module_active("cities")) {
        return "cities";
    } elseif (is_module_active("multi-towns")) {
        return "multi-towns";
    }
    return false;
}

function raceangel_check_unlock() {
    $req = get_module_setting("unlock_req");
    if (trim($req) === "") return true;
    
    $parts = explode(",", $req);
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === "") continue;
        $sub = explode(":", $part);
        $req_mod = trim($sub[0]);
        $req_lvl = isset($sub[1]) ? (int)$sub[1] : 1;
        
        if (!is_module_active($req_mod)) return false;
        
        $player_lvl = (int)get_module_pref("level", $req_mod);
        if ($player_lvl < $req_lvl) return false;
    }
    return true;
}

function raceangel_get_unlock_text() {
    $req = get_module_setting("unlock_req");
    if (trim($req) === "") return "";
    
    $parts = explode(",", $req);
    $out = [];
    foreach ($parts as $part) {
        $part = trim($part);
        if ($part === "") continue;
        $sub = explode(":", $part);
        $req_mod = trim($sub[0]);
        $req_lvl = isset($sub[1]) ? (int)$sub[1] : 1;
        
        $info = get_module_info($req_mod);
        $name = isset($info['name']) ? str_replace("Race - ", "", $info['name']) : $req_mod;
        $out[] = "Level $req_lvl $name";
    }
    return implode(" and ", $out);
}
?>
