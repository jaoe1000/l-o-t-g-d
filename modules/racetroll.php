<?php
/**
 * Advanced Race Module - Troll
 * Built from the Advanced Race Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function racetroll_getmoduleinfo() {
    return [
        "name" => "Race - Troll",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Race",
        "download" => "", 
        "settings" => [
            "Troll - Race Settings,title",
            "use_custom_village" => "Does this race have its own custom village?,bool|1",
            "villagename" => "Name for the Troll city,|Mirewood", 
            "stableowner" => "Name of the city stable-owner,|Grog", 
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|20", 
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|0", 
            "training_villages" => "Villages where players of this race can train (comma-separated; leave blank to restrict to their custom village if enabled),string|",
            
            "Race Progression Settings,title",
            "levels" => "Max Race Level,int|5",
            "cre_req" => "Battle victories required to level up,int|150",
            "legacy_stat" => "Legacy Buff Stat (attack/defense/hp/gold/none),string|hp",
            "legacy_value" => "Legacy Buff Value,float|2.0",
            "unlock_req" => "Unlock Requirements (comma-separated, e.g. racehuman:5),string|",
            "stagnation_decay" => "Stagnation victory point multiplier (0.0 to 1.0),float|0.5",
        ],
        "prefs" => [
            "Troll - User Prefs,title",
            "level" => "Current Race Level,int|0",
            "cre" => "Current Victory Points towards next level,float|0.0",
            "stagnation" => "Consecutive Dragon Kills as this race,int|0",
            "last_played_dk" => "Was this race played last DK?,bool|0",
            "fresh_soul" => "Does player have fresh soul bonus?,bool|0",
            
            "mud_bathed" => "Has player taken a mud bath today?,bool|0",
            "doctor_visited" => "Has player visited the witch doctor today?,bool|0",
        ]
    ];
}

function racetroll_install() {
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
    debug("Installed 'Troll' advanced race module.");
    return true;
}

function racetroll_uninstall() {
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
    $sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Troll'";
    db_query($sql);
    if (($session['user']['race'] ?? '') === 'Troll') {
        $session['user']['race'] = RACE_UNKNOWN;
    }
    
    debug("Uninstalled 'Troll' race module.");
    return true;
}

function racetroll_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "Troll"; 
    
    $userRace = $session['user']['race'] ?? '';
    
    switch($hookname) {
        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (get_module_setting("use_custom_village") && ($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "racetroll") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = racetroll_get_travel_mod();
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
                $unlocked = racetroll_check_unlock();
                if ($unlocked) {
                    $atrans = translate_inline("In the swamp canopies of %s");
                    $rtrans = translate_inline(", home to the wild and regenerating Trolls.`n`n");
                    addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                    output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                    addnav("", "newday.php?setrace=$race$resline");
                } else {
                    $req_text = racetroll_get_unlock_text();
                    output("`7Locked Race: `&%s`7 (Requires: `5%s`7)`n`n", $race, $req_text);
                }
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`2You embrace your trollish heritage! Your skin turns mossy (+10%% Defense, +5 Life Tap), but your lack of commerce cuts forest gold gains by 25%%!`n");
                
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
                    $travel_mod = racetroll_get_travel_mod();
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
            // Apply Troll gold penalty
            if ($userRace === $race) {
                $args['creaturegold'] = round($args['creaturegold'] * 0.75);
            }
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
                racetroll_checkcity();
                
                apply_buff("racialbenefit", [
                    "name" => sprintf_translate("Trollish Regeneration"),
                    "defmod" => 1.10,
                    "lifetap" => 5,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "rounds" => -1,
                    "schema" => "module-racetroll",
                ]);
                
                // Reset daily services
                set_module_pref("mud_bathed", 0);
                set_module_pref("doctor_visited", 0);

                output("`n`2You awaken in the swamp canopy of Mirewood. Your skin tingles as it regenerates (+10%% Defense, +5 Life Tap, -25%% gold).`n`0");
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
                        "schema" => "module-racetroll",
                    ]);
                } elseif ($stat === "defense" && $val > 0.0) {
                    apply_buff("legacy_" . strtolower($race) . "_def", [
                        "name" => sprintf_translate("Legacy of the %s", translate_inline($race)),
                        "defmod" => $val,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "rounds" => -1,
                        "schema" => "module-racetroll",
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
                $travel_mod = racetroll_get_travel_mod();
                if ($travel_mod !== false)
                    $args[$city] = "village-$race";
            }
            break;

        case "moderate":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = racetroll_get_travel_mod();
                if ($travel_mod !== false) {
                    tlschema("commentary");
                    $args["village-$race"] = sprintf_translate("City of %s", $city); 
                    tlschema();
                }
            }
            break;

        case "travel":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = racetroll_get_travel_mod();
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
            racetroll_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^Mossy logs and hanging swamp vines stretch across %s. The village is humid and swampy, with mist drifting over the wood houses.`n", $city, $city];
                $args['schemas']['text'] = "module-racetroll";
                $args['description'] = ["`\$`c`b%s`b`c`n`^Mossy logs and hanging swamp vines stretch across %s. The village is humid and swampy, with mist drifting over the wood houses.`n", $city, $city];
                $args['schemas']['description'] = "module-racetroll";
                $args['clock'] = "`n`7A glowing crystal sundial read in the mist `@%s`7.`n";
                $args['schemas']['clock'] = "module-racetroll";
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$A calendar carved on a thick cypress stump reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-racetroll";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-racetroll";
                $args['sayline'] = "growls";
                $args['schemas']['sayline'] = "module-racetroll";
                $args['talk'] = "`n`&Nearby some swamp defenders stand talking:`n";
                $args['schemas']['talk'] = "module-racetroll";
                
                $travel_mod = racetroll_get_travel_mod();
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
                    $args['newest'] = "`n`7You are the newest resident of Mirewood.";
                } else {
                    $args['newest'] = "`n`7The newest resident of Mirewood is `&%s`7.";
                }
                $args['schemas']['newest'] = "module-racetroll";
                $args['section'] = "village-$race";
                $args['stablename'] = "Swamp Stables";
                $args['schemas']['stablename'] = "module-racetroll";
                $args['gatenav'] = "Swamp Paths";
                $args['schemas']['gatenav'] = "module-racetroll";
                $args['fightnav'] = "Training Mudglade";
                $args['schemas']['fightnav'] = "module-racetroll";
                $args['marketnav'] = "Moss Market";
                $args['schemas']['marketnav'] = "module-racetroll";
                $args['tavernnav'] = "The Dewy Log";
                $args['schemas']['tavernnav'] = "module-racetroll";

                // Override standard location nav labels
                $args['navs']['weapons'] = "Mirewood Bone & Spear";
                $args['navs']['armor'] = "Mirewood Moss & Hide";
                $args['navs']['train'] = "u?Mirewood Training Glade";
                $args['schemas']['navs']['weapons'] = "module-racetroll";
                $args['schemas']['navs']['armor'] = "module-racetroll";
                $args['schemas']['navs']['train'] = "module-racetroll";

                // Special section nav header and labels
                $args['nav_headers']['special'] = "Mirewood Services";
                $args['navs']['mud'] = "Mud Bath";
                $args['navs']['doctor'] = "Witch Doctor";
                $args['schemas']['nav_headers']['special'] = "module-racetroll";
                $args['schemas']['navs']['mud'] = "module-racetroll";
                $args['schemas']['navs']['doctor'] = "module-racetroll";

                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') !== $city) break;
            $owner = get_module_setting("stableowner");
            $args['title'] = "Swamp Stables";
            $args['schemas']['title'] = "module-racetroll";
            $args['desc'] = "`\$You stand in a stable built from wet logs and swamp vines. $owner is busy bathing a giant toad. He chuckles warmly.";
            $args['schemas']['desc'] = "module-racetroll";
            $args['lad'] = "Beast";
            $args['schemas']['lad'] = "module-racetroll";
            $args['lass'] = "Beast";
            $args['schemas']['lass'] = "module-racetroll";
            $args['nosuchbeast'] = "`3\"`7Grog don't have that monster here.`3\", says $owner.`3.";
            $args['schemas']['nosuchbeast'] = "module-racetroll";
            $args['finebeast'] = [
                "`3\"`7This beast runs fast in the mud.`3\"`n`n",
                "`3\"`7A good swamp creature. Ride it well.`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-racetroll";
            $args['toolittle'] = "`%\"`7You don't have enough shiny gold for Grog,`%\" $owner grunts.";
            $args['schemas']['toolittle'] = "module-racetroll";
            $args['replacemount'] = "`%You exchange your `^%s for a swamp-born `&%s`%.";
            $args['schemas']['replacemount'] = "module-racetroll";
            $args['newmount'] = "`@You receive a swamp-born `&%s`@.";
            $args['schemas']['newmount'] = "module-racetroll";
            $args['nofeed'] = "`3\"`7No feed here. Let it eat swamp flies.`3\"";
            $args['schemas']['nofeed'] = "module-racetroll";
            $args['nothungry'] = "`&%s`6 looks full, croaking happily.";
            $args['schemas']['nothungry'] = "module-racetroll";
            $args['halfhungry'] = "`&%s`6 eats the swamp grubs happily.";
            $args['schemas']['halfhungry'] = "module-racetroll";
            $args['hungry'] = "`6%s`6 devours the swamp grubs in one bite!";
            $args['schemas']['hungry'] = "module-racetroll";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is completely satisfied.`6\"";
            $args['schemas']['mountfull'] = "module-racetroll";
            $args['nofeedgold'] = "`6\"`^You have no gold for grubs.`6\"";
            $args['schemas']['nofeedgold'] = "module-racetroll";
            $args['confirmsale'] = "`n`n`6Are you sure you want to sell your mount back to Grog?";
            $args['schemas']['confirmsale'] = "module-racetroll";
            $args['mountsold'] = "`6You hand over the mount and get your gold.";
            $args['schemas']['mountsold'] = "module-racetroll";
            $args['offer'] = "`n`n`6\"`^Grog will buy that for `&%s`6 gold and `%%s`6 gems,`6\" $owner offers.";
            $args['schemas']['offer'] = "module-racetroll";
            break;

        case "stablelocs":
            if (!get_module_setting("use_custom_village")) break;
            $travel_mod = racetroll_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "villagenav":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city && ($session['user']['race'] ?? '') === $race) {
                $args['special']['mud'] = "runmodule.php?module=racetroll&op=mud";
                $args['special']['doctor'] = "runmodule.php?module=racetroll&op=doctor";
            }
            break;

        case "weaponstext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['weaponvalue'] * 0.75), 0);
                $args['title'] = "Mirewood Bone & Spear";
                $args['desc'] = [
                    "`&Carved bones, heavy wood clubs, and copper-tipped spears hang from vines. The shop is humid and smells of moss.`n`n"
                ];
                $args['tradein'] = [
                    "`7You lay your weapon on a cypress stump.`n`n",
                    [
                        "`&A troll blacksmith sniffs it. \"`#Grog can give `^%s`# gold for this shiny metal.`\"`n`n",
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
                $args['title'] = "Mirewood Moss & Hide";
                $args['desc'] = [
                    "`&Crude hides stitched together, woven moss vests, and thick log shields stand in piles.`n`n"
                ];
                $args['tradein'] = [
                    "`7You show your current protection to the hide stitcher.`n`n",
                    [
                        "`&He feels it and grunts. \"`#Good hide. Worth `^%s`# gold in trade.`\"`n`n",
                        $tradeinvalue,
                        $session['user']['armor']
                    ]
                ];
            }
            break;

        case "trainingtitle":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['title'] = "Mirewood Training Glade";
            }
            break;

        case "modify-master":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['creaturename'] = "Krazek the Swamp Master";
                $args['creatureweapon'] = "Thick Log Spear";
                $args['creaturewin'] = "You fight with no skin! Harder! Harder!";
                $args['creaturelose'] = "You possess the regeneration of the swamp. Go, protect the mire.";
            }
            break;
    }
    return $args;
}

function racetroll_run() {
    global $session;
    if (!get_module_setting("use_custom_village")) {
        page_header("Access Denied");
        output("`\$Custom village features are disabled for this race.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    $op = httpget('op');
    $race = "Troll";
    
    // Check if the user is a Troll
    if (($session['user']['race'] ?? '') !== $race) {
        page_header("Access Denied");
        output("`\$Only Trolls may access these features.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    
    if ($op === "mud") {
        page_header("Mirewood Mud Bath");
        output("`c`b`2Mirewood Mud Bath`b`c`n`n");
        output("`7A warm, bubbling pool of thick, nutrient-rich swamp mud. \"`#Soak in the mud, grunt. It heals all wounds and thickens your skin!`\"`n`n");
        
        $bathed = get_module_pref("mud_bathed");
        $subop = httpget('subop');
        
        if ($subop === "bathe") {
            if ($bathed) {
                output("`\$You have already taken a mud bath today. Too much mud will clog your ears.`n`n");
            } else {
                set_module_pref("mud_bathed", 1);
                $bathed = true;
                
                // Full heal and temporary Max HP buff (+5 max HP today)
                $session['user']['hitpoints'] = $session['user']['maxhitpoints'] + 5;
                output("`@You sink deep into the warm mud bath. You feel your cuts close and your skin harden as you absorb the swamp's energies! You are completely healed and gain `^+5 temporary Max HP`@ for today!`n`n");
            }
        }
        
        if (!$bathed) {
            addnav("Mud Bath");
            addnav("Take Mud Bath", "runmodule.php?module=racetroll&op=mud&subop=bathe");
        } else {
            output("`&You have already bathed today.`n`n");
        }
        addnav("Return to Mirewood", "village.php");
        page_footer();
    }
    
    if ($op === "doctor") {
        page_header("Mirewood Witch Doctor");
        output("`c`b`2Mirewood Witch Doctor`b`c`n`n");
        output("`7A mysterious hut filled with dried roots, skulls, and boiling cauldrons. The witch doctor laughs as you enter. \"`#Want some swamp juice, big troll?`\"`n`n");
        
        $visited = get_module_pref("doctor_visited");
        $subop = httpget('subop');
        
        if ($subop === "buy") {
            $type = httpget('type');
            if ($visited) {
                output("`\$You have already bought a brew today. The cauldron needs time to ferment.`n`n");
            } else {
                if ($type === "stew" && $session['user']['gold'] >= 30) {
                    $session['user']['gold'] -= 30;
                    set_module_pref("doctor_visited", 1);
                    $visited = true;
                    $session['user']['hitpoints'] = min($session['user']['maxhitpoints'], $session['user']['hitpoints'] + 20);
                    output("`@You buy a bowl of Troll Stew for 30 gold. You swallow it down. It tastes like dirt, but it restores `^20 hitpoints`@!`n`n");
                } elseif ($type === "brew" && $session['user']['gold'] >= 100) {
                    $session['user']['gold'] -= 100;
                    set_module_pref("doctor_visited", 1);
                    $visited = true;
                    apply_buff("doctor_ironbark", [
                        "name" => "`2Ironbark Brew`0",
                        "dmgshield" => 3.0,
                        "rounds" => 15,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "schema" => "module-racetroll",
                    ]);
                    output("`@You buy an Ironbark Brew for 100 gold. You drink the thick green sludge. Your skin hardens into wood, granting you a Damage Shield (+3 damage reflected) for your next 15 rounds of combat!`n`n");
                } else {
                    output("`\$You don't have enough gold for that brew.`n`n");
                }
            }
        }
        
        if (!$visited) {
            addnav("Purchase Brew");
            addnav("Troll Stew (30 gold, Heals 20 HP)", "runmodule.php?module=racetroll&op=doctor&subop=buy&type=stew");
            addnav("Ironbark Brew (100 gold, +3 Damage Shield)", "runmodule.php?module=racetroll&op=doctor&subop=buy&type=brew");
        } else {
            output("`&You have already purchased a brew today.`n`n");
        }
        addnav("Return to Mirewood", "village.php");
        page_footer();
    }
}

function racetroll_checkcity() {
    global $session;
    if (!get_module_setting("use_custom_village")) return true;
    $race = "Troll";
    $city = get_module_setting("villagename");
    
    $travel_mod = racetroll_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    return true;
}

function racetroll_get_travel_mod() {
    if (is_module_active("villages")) {
        return "villages";
    } elseif (is_module_active("cities")) {
        return "cities";
    } elseif (is_module_active("multi-towns")) {
        return "multi-towns";
    }
    return false;
}

function racetroll_check_unlock() {
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

function racetroll_get_unlock_text() {
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
