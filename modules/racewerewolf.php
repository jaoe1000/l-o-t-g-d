<?php
/**
 * Advanced Race Module - Werewolf
 * Built from the Advanced Race Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function racewerewolf_getmoduleinfo() {
    return [
        "name" => "Race - Werewolf",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Race",
        "download" => "", 
        "settings" => [
            "Werewolf - Race Settings,title",
            "use_custom_village" => "Does this race have its own custom village?,bool|1",
            "villagename" => "Name for the Werewolf city,|Lupine Vale", 
            "stableowner" => "Name of the city stable-owner,|Fang", 
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|15", 
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|0", 
            "training_villages" => "Villages where players of this race can train (comma-separated; leave blank to restrict to their custom village if enabled),string|",
            
            "Race Progression Settings,title",
            "levels" => "Max Race Level,int|5",
            "cre_req" => "Battle victories required to level up,int|150",
            "legacy_stat" => "Legacy Buff Stat (attack/defense/hp/gold/none),string|defense",
            "legacy_value" => "Legacy Buff Value,float|1.01",
            "unlock_req" => "Unlock Requirements (comma-separated, e.g. racehuman:5),string|raceorc:3,racetroll:3",
            "stagnation_decay" => "Stagnation victory point multiplier (0.0 to 1.0),float|0.5",
        ],
        "prefs" => [
            "Werewolf - User Prefs,title",
            "level" => "Current Race Level,int|0",
            "cre" => "Current Victory Points towards next level,float|0.0",
            "stagnation" => "Consecutive Dragon Kills as this race,int|0",
            "last_played_dk" => "Was this race played last DK?,bool|0",
            "fresh_soul" => "Does player have fresh soul bonus?,bool|0",
            
            "moon_prayed" => "Has player prayed at the moon pool today?,bool|0",
            "shrine_visited" => "Has player visited the feral shrine today?,bool|0",
        ]
    ];
}

function racewerewolf_install() {
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
    debug("Installed 'Werewolf' advanced race module.");
    return true;
}

function racewerewolf_uninstall() {
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
    $sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Werewolf'";
    db_query($sql);
    if (($session['user']['race'] ?? '') === 'Werewolf') {
        $session['user']['race'] = RACE_UNKNOWN;
    }
    
    debug("Uninstalled 'Werewolf' race module.");
    return true;
}

function racewerewolf_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "Werewolf"; 
    
    $userRace = $session['user']['race'] ?? '';
    
    switch($hookname) {
        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (get_module_setting("use_custom_village") && ($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "racewerewolf") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = racewerewolf_get_travel_mod();
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
                $unlocked = racewerewolf_check_unlock();
                if ($unlocked) {
                    $atrans = translate_inline("Under the thick canopies of %s");
                    $rtrans = translate_inline(", home to the beastly Werewolves.`n`n");
                    addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                    output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                    addnav("", "newday.php?setrace=$race$resline");
                } else {
                    $req_text = racewerewolf_get_unlock_text();
                    output("`7Locked Race: `&%s`7 (Requires: `5%s`7)`n`n", $race, $req_text);
                }
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`2You embrace your lycanthrope blood! Your animal instincts sharpen (+15%% Attack, +10%% Defense, and +2 forest fights per day)!`n");
                
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
                    $travel_mod = racewerewolf_get_travel_mod();
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
                racewerewolf_checkcity();
                
                // Werewolves get +2 forest fights and +15% attack, +10% defense
                $args['turnstoday'] = ($args['turnstoday'] ?? '') . ", Race ($race): 2";
                $session['user']['turns'] += 2;
                
                apply_buff("racialbenefit", [
                    "name" => sprintf_translate("Lycanthrope Fury"),
                    "atkmod" => 1.15,
                    "defmod" => 1.10,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "rounds" => -1,
                    "schema" => "module-racewerewolf",
                ]);
                
                // Reset daily services
                set_module_pref("moon_prayed", 0);
                set_module_pref("shrine_visited", 0);

                output("`n`2You awaken under the full moon in Lupine Vale. The wolf within howls (+15%% Attack, +10%% Defense, +2 forest fights).`n`0");
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
                        "schema" => "module-racewerewolf",
                    ]);
                } elseif ($stat === "defense" && $val > 0.0) {
                    apply_buff("legacy_" . strtolower($race) . "_def", [
                        "name" => sprintf_translate("Legacy of the %s", translate_inline($race)),
                        "defmod" => $val,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "rounds" => -1,
                        "schema" => "module-racewerewolf",
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
                $travel_mod = racewerewolf_get_travel_mod();
                if ($travel_mod !== false)
                    $args[$city] = "village-$race";
            }
            break;

        case "moderate":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = racewerewolf_get_travel_mod();
                if ($travel_mod !== false) {
                    tlschema("commentary");
                    $args["village-$race"] = sprintf_translate("City of %s", $city); 
                    tlschema();
                }
            }
            break;

        case "travel":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = racewerewolf_get_travel_mod();
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
            racewerewolf_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^Wooden log structures sit nested inside the dark, moonlit pine trees of %s. Banners of claw marks hang over the pathways, and wolf howls echo in the distance.`n", $city, $city];
                $args['schemas']['text'] = "module-racewerewolf";
                $args['description'] = ["`\$`c`b%s`b`c`n`^Wooden log structures sit nested inside the dark, moonlit pine trees of %s. Banners of claw marks hang over the pathways, and wolf howls echo in the distance.`n", $city, $city];
                $args['schemas']['description'] = "module-racewerewolf";
                $args['clock'] = "`n`7A shadow cast by the bright moon reads `@%s`7.`n";
                $args['schemas']['clock'] = "module-racewerewolf";
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$A posted pine calendar reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-racewerewolf";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-racewerewolf";
                $args['sayline'] = "snarls";
                $args['schemas']['sayline'] = "module-racewerewolf";
                $args['talk'] = "`n`&Nearby some wolf defenders stand talking:`n";
                $args['schemas']['talk'] = "module-racewerewolf";
                
                $travel_mod = racewerewolf_get_travel_mod();
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
                    $args['newest'] = "`n`7You are the newest cub of Lupine Vale.";
                } else {
                    $args['newest'] = "`n`7The newest cub of Lupine Vale is `&%s`7.";
                }
                $args['schemas']['newest'] = "module-racewerewolf";
                $args['section'] = "village-$race";
                $args['stablename'] = "Lupine Stables";
                $args['schemas']['stablename'] = "module-racewerewolf";
                $args['gatenav'] = "Vale Paths";
                $args['schemas']['gatenav'] = "module-racewerewolf";
                $args['fightnav'] = "Training Paddock";
                $args['schemas']['fightnav'] = "module-racewerewolf";
                $args['marketnav'] = "Vale Clearing";
                $args['schemas']['marketnav'] = "module-racewerewolf";
                $args['tavernnav'] = "The Howling Cave";
                $args['schemas']['tavernnav'] = "module-racewerewolf";

                // Override standard location nav labels
                $args['navs']['weapons'] = "Lupine Forge & Fangs";
                $args['navs']['armor'] = "Lupine Pelt & Hide";
                $args['navs']['train'] = "u?Lupine Training Paddock";
                $args['schemas']['navs']['weapons'] = "module-racewerewolf";
                $args['schemas']['navs']['armor'] = "module-racewerewolf";
                $args['schemas']['navs']['train'] = "module-racewerewolf";

                // Special section nav header and labels
                $args['nav_headers']['special'] = "Lupine Services";
                $args['navs']['pool'] = "Moon Pool";
                $args['navs']['shrine'] = "Feral Shrine";
                $args['schemas']['nav_headers']['special'] = "module-racewerewolf";
                $args['schemas']['navs']['pool'] = "module-racewerewolf";
                $args['schemas']['navs']['shrine'] = "module-racewerewolf";

                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') !== $city) break;
            $owner = get_module_setting("stableowner");
            $args['title'] = "Lupine Stables";
            $args['schemas']['title'] = "module-racewerewolf";
            $args['desc'] = "`\$You enter a paddock under the forest canopy. $owner is feeding raw meat to a massive, snarling dire wolf. He nods to you.";
            $args['schemas']['desc'] = "module-racewerewolf";
            $args['lad'] = "Brother";
            $args['schemas']['lad'] = "module-racewerewolf";
            $args['lass'] = "Sister";
            $args['schemas']['lass'] = "module-racewerewolf";
            $args['nosuchbeast'] = "`3\"`7We don't stock that beast in our pack,`3\" says $owner.`3.";
            $args['schemas']['nosuchbeast'] = "module-racewerewolf";
            $args['finebeast'] = [
                "`3\"`7Let this creature carry you swiftly through the trees.`3\"`n`n",
                "`3\"`7A strong steed of the pack.`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-racewerewolf";
            $args['toolittle'] = "`%\"`7You don't have enough gold for this steed,`%\" $owner growls.";
            $args['schemas']['toolittle'] = "module-racewerewolf";
            $args['replacemount'] = "`%You release your `^%s back to the wild, taking the reins of a strong `&%s`%.";
            $args['schemas']['replacemount'] = "module-racewerewolf";
            $args['newmount'] = "`@You receive a strong, fleet-footed `&%s`@.";
            $args['schemas']['newmount'] = "module-racewerewolf";
            $args['nofeed'] = "`3\"`7We do not sell feed here. Let it hunt.`3\"";
            $args['schemas']['nofeed'] = "module-racewerewolf";
            $args['nothungry'] = "`&%s`6 shakes its head.";
            $args['schemas']['nothungry'] = "module-racewerewolf";
            $args['halfhungry'] = "`&%s`6 eats the fresh meat pieces.";
            $args['schemas']['halfhungry'] = "module-racewerewolf";
            $args['hungry'] = "`6%s`6 devours the fresh meat eagerly!";
            $args['schemas']['hungry'] = "module-racewerewolf";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is full now.`6\"";
            $args['schemas']['mountfull'] = "module-racewerewolf";
            $args['nofeedgold'] = "`6\"`^Not enough gold for meat feed.`6\"";
            $args['schemas']['nofeedgold'] = "module-racewerewolf";
            $args['confirmsale'] = "`n`n`6Are you sure you wish to return your steed to the wild?";
            $args['schemas']['confirmsale'] = "module-racewerewolf";
            $args['mountsold'] = "`6You release the beast back to the pack.";
            $args['schemas']['mountsold'] = "module-racewerewolf";
            $args['offer'] = "`n`n`6\"`^I can offer you `&%s`6 gold and `%%s`6 gems to return your mount to our stables,`6\" $owner offers.";
            $args['schemas']['offer'] = "module-racewerewolf";
            break;

        case "stablelocs":
            if (!get_module_setting("use_custom_village")) break;
            $travel_mod = racewerewolf_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "villagenav":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city && ($session['user']['race'] ?? '') === $race) {
                $args['special']['pool'] = "runmodule.php?module=racewerewolf&op=pool";
                $args['special']['shrine'] = "runmodule.php?module=racewerewolf&op=shrine";
            }
            break;

        case "weaponstext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['weaponvalue'] * 0.75), 0);
                $args['title'] = "Lupine Forge & Fangs";
                $args['desc'] = [
                    "`&Crude claws, obsidian-tipped daggers, and heavy spiked maces line the logs. The shop is filled with wild lupine markings.`n`n"
                ];
                $args['tradein'] = [
                    "`7You throw your weapon on the counter.`n`n",
                    [
                        "`&A weaponsmith sniffs it. \"`#I can credit you `^%s`# gold for this scrap.`\"`n`n",
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
                $args['title'] = "Lupine Pelt & Hide";
                $args['desc'] = [
                    "`&Sturdy wolf skins, moon-blessed leather vests, and heavy shields carved from ironwood trees stand in piles.`n`n"
                ];
                $args['tradein'] = [
                    "`7You show your protection to the stitcher.`n`n",
                    [
                        "`&He feels it and nods. \"`#I can offer `^%s`# trade-in value toward our wolf pelts.`\"`n`n",
                        $tradeinvalue,
                        $session['user']['armor']
                    ]
                ];
            }
            break;

        case "trainingtitle":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['title'] = "Lupine Training Paddock";
            }
            break;

        case "modify-master":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['creaturename'] = "Ulfgar the Packleader";
                $args['creatureweapon'] = "Obsidian Battleaxe";
                $args['creaturewin'] = "You fight with no animal instincts! Unleash the wolf within!";
                $args['creaturelose'] = "You are a true alpha of the pack. Go, protect the vale.";
            }
            break;
    }
    return $args;
}

function racewerewolf_run() {
    global $session;
    if (!get_module_setting("use_custom_village")) {
        page_header("Access Denied");
        output("`\$Custom village features are disabled for this race.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    $op = httpget('op');
    $race = "Werewolf";
    
    // Check if the user is a Werewolf
    if (($session['user']['race'] ?? '') !== $race) {
        page_header("Access Denied");
        output("`\$Only Werewolves may access these features.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    
    if ($op === "pool") {
        page_header("Lupine Moon Pool");
        output("`c`b`2Lupine Moon Pool`b`c`n`n");
        output("`7A clear, glowing pond reflecting the full moon's light. \"`#Kneel and pray to the moon, brother, and feel the hunt call you!`\"`n`n");
        
        $prayed = get_module_pref("moon_prayed");
        $subop = httpget('subop');
        
        if ($subop === "pray") {
            if ($prayed) {
                output("`\$You have already prayed to the moon today.`n`n");
            } else {
                $session['user']['turns'] += 2;
                set_module_pref("moon_prayed", 1);
                $prayed = true;
                
                output("`@You kneel and pray to the reflecting moon pool. A silver mist surrounds you, sending feral energy through your muscles! You gain `^+2 extra forest fights`@ today!`n`n");
            }
        }
        
        if (!$prayed) {
            addnav("Moon Pool");
            addnav("Pray to the Moon Pool", "runmodule.php?module=racewerewolf&op=pool&subop=pray");
        } else {
            output("`&You have already prayed to the moon pool today.`n`n");
        }
        addnav("Return to Lupine Vale", "village.php");
        page_footer();
    }
    
    if ($op === "shrine") {
        page_header("Lupine Feral Shrine");
        output("`c`b`2Lupine Feral Shrine`b`c`n`n");
        output("`7A crude shrine made of massive wolf skulls and ironwood roots. Offering a gem here summons a spectral dire wolf to fight by your side.`n`n");
        
        $visited = get_module_pref("shrine_visited");
        $subop = httpget('subop');
        
        if ($subop === "summon") {
            if ($visited) {
                output("`\$You have already visited the feral shrine today.`n`n");
            } elseif ($session['user']['gems'] < 1) {
                output("`\$You do not have any gems to offer the spirits.`n`n");
            } else {
                $session['user']['gems'] -= 1;
                set_module_pref("shrine_visited", 1);
                $visited = true;
                
                apply_buff("feral_companion", [
                    "name" => "`2Feral Companion`0",
                    "dmgshield" => 3.0,
                    "rounds" => 15,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "schema" => "module-racewerewolf",
                ]);
                output("`@You offer a gem to the feral shrine. A spectral wolf emerges from the mist, snarling, and bounds by your side, granting you a Damage Shield (+3 damage reflected) for your next 15 rounds of combat!`n`n");
            }
        }
        
        if (!$visited) {
            output("`7You have `@%s gem(s)`7 to offer.`n`n", $session['user']['gems']);
            if ($session['user']['gems'] >= 1) {
                addnav("Feral Shrine");
                addnav("Offer Gem for Companion", "runmodule.php?module=racewerewolf&op=shrine&subop=summon");
            }
        } else {
            output("`&You have already visited the feral shrine today.`n`n");
        }
        addnav("Return to Lupine Vale", "village.php");
        page_footer();
    }
}

function racewerewolf_checkcity() {
    global $session;
    if (!get_module_setting("use_custom_village")) return true;
    $race = "Werewolf";
    $city = get_module_setting("villagename");
    
    $travel_mod = racewerewolf_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    return true;
}

function racewerewolf_get_travel_mod() {
    if (is_module_active("villages")) {
        return "villages";
    } elseif (is_module_active("cities")) {
        return "cities";
    } elseif (is_module_active("multi-towns")) {
        return "multi-towns";
    }
    return false;
}

function racewerewolf_check_unlock() {
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

function racewerewolf_get_unlock_text() {
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
