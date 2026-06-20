<?php
/**
 * Advanced Race Module - Demon
 * Built from the Advanced Race Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function racedemon_getmoduleinfo() {
    return [
        "name" => "Race - Demon",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Race",
        "download" => "", 
        "settings" => [
            "Demon - Race Settings,title",
            "use_custom_village" => "Does this race have its own custom village?,bool|1",
            "villagename" => "Name for the Demon city,|Avernus", 
            "stableowner" => "Name of the city stable-owner,|Belial", 
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|10", 
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|0", 
            "training_villages" => "Villages where players of this race can train (comma-separated; leave blank to restrict to their custom village if enabled),string|",
            
            "Race Progression Settings,title",
            "levels" => "Max Race Level,int|5",
            "cre_req" => "Battle victories required to level up,int|150",
            "legacy_stat" => "Legacy Buff Stat (attack/defense/hp/gold/none),string|attack",
            "legacy_value" => "Legacy Buff Value,float|1.01",
            "unlock_req" => "Unlock Requirements (comma-separated, e.g. racehuman:5),string|raceorc:5,racetroll:5",
            "stagnation_decay" => "Stagnation victory point multiplier (0.0 to 1.0),float|0.5",
        ],
        "prefs" => [
            "Demon - User Prefs,title",
            "level" => "Current Race Level,int|0",
            "cre" => "Current Victory Points towards next level,float|0.0",
            "stagnation" => "Consecutive Dragon Kills as this race,int|0",
            "last_played_dk" => "Was this race played last DK?,bool|0",
            "fresh_soul" => "Does player have fresh soul bonus?,bool|0",
            
            "altar_sacrificed" => "Has player sacrificed at the altar today?,bool|0",
            "pool_bathed" => "Has player soaked in the pool today?,bool|0",
        ]
    ];
}

function racedemon_install() {
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
    debug("Installed 'Demon' advanced race module.");
    return true;
}

function racedemon_uninstall() {
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
    $sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Demon'";
    db_query($sql);
    if (($session['user']['race'] ?? '') === 'Demon') {
        $session['user']['race'] = RACE_UNKNOWN;
    }
    
    debug("Uninstalled 'Demon' race module.");
    return true;
}

function racedemon_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "Demon"; 
    
    $userRace = $session['user']['race'] ?? '';
    
    switch($hookname) {
        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (get_module_setting("use_custom_village") && ($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "racedemon") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = racedemon_get_travel_mod();
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
                $unlocked = racedemon_check_unlock();
                if ($unlocked) {
                    $atrans = translate_inline("Into the dark volcanic gates of %s");
                    $rtrans = translate_inline(", home to the fiery Demons.`n`n");
                    addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                    output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                    addnav("", "newday.php?setrace=$race$resline");
                } else {
                    $req_text = racedemon_get_unlock_text();
                    output("`7Locked Race: `&%s`7 (Requires: `5%s`7)`n`n", $race, $req_text);
                }
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`\$You embrace your demonic heritage! Volcanic ash swirls around you as your blood boils with fury (+20%% Attack, but -10%% Defense, and +1 extra forest fight per day)!`n");
                
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
                    $travel_mod = racedemon_get_travel_mod();
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
                racedemon_checkcity();
                
                // Demons get +1 extra turn today
                $args['turnstoday'] = ($args['turnstoday'] ?? '') . ", Race ($race): 1";
                $session['user']['turns']++;
                
                apply_buff("racialbenefit", [
                    "name" => sprintf_translate("Hellfire Fury"),
                    "atkmod" => 1.20,
                    "defmod" => 0.90,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "rounds" => -1,
                    "schema" => "module-racedemon",
                ]);
                
                // Reset daily services
                set_module_pref("altar_sacrificed", 0);
                set_module_pref("pool_bathed", 0);

                output("`n`\$You wake up surrounded by volcanic heat in Avernus. Your eyes burn with wrath (+20%% Attack, -10%% Defense, +1 extra turn).`n`0");
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
                        "schema" => "module-racedemon",
                    ]);
                } elseif ($stat === "defense" && $val > 0.0) {
                    apply_buff("legacy_" . strtolower($race) . "_def", [
                        "name" => sprintf_translate("Legacy of the %s", translate_inline($race)),
                        "defmod" => $val,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "rounds" => -1,
                        "schema" => "module-racedemon",
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
                $travel_mod = racedemon_get_travel_mod();
                if ($travel_mod !== false)
                    $args[$city] = "village-$race";
            }
            break;

        case "moderate":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = racedemon_get_travel_mod();
                if ($travel_mod !== false) {
                    tlschema("commentary");
                    $args["village-$race"] = sprintf_translate("City of %s", $city); 
                    tlschema();
                }
            }
            break;

        case "travel":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = racedemon_get_travel_mod();
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
            racedemon_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^Volcanic spires rise from liquid magma pools in %s. Ash and sparks drift through the heavy, sulfurous air. Obsidian fortresses look down on the city gates.`n", $city, $city];
                $args['schemas']['text'] = "module-racedemon";
                $args['description'] = ["`\$`c`b%s`b`c`n`^Volcanic spires rise from liquid magma pools in %s. Ash and sparks drift through the heavy, sulfurous air. Obsidian fortresses look down on the city gates.`n", $city, $city];
                $args['schemas']['description'] = "module-racedemon";
                $args['clock'] = "`n`7A shadow cast by the magma glow reads `@%s`7.`n";
                $args['schemas']['clock'] = "module-racedemon";
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$A posted tablet calendar reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-racedemon";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-racedemon";
                $args['sayline'] = "hisses";
                $args['schemas']['sayline'] = "module-racedemon";
                $args['talk'] = "`n`&Nearby some demonic guardians stand talking:`n";
                $args['schemas']['talk'] = "module-racedemon";
                
                $travel_mod = racedemon_get_travel_mod();
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
                    $args['newest'] = "`n`7You are the newest resident of Avernus.";
                } else {
                    $args['newest'] = "`n`7The newest resident of Avernus is `&%s`7.";
                }
                $args['schemas']['newest'] = "module-racedemon";
                $args['section'] = "village-$race";
                $args['stablename'] = "Hellstone Stables";
                $args['schemas']['stablename'] = "module-racedemon";
                $args['gatenav'] = "Magma Gates";
                $args['schemas']['gatenav'] = "module-racedemon";
                $args['fightnav'] = "Training Ground";
                $args['schemas']['fightnav'] = "module-racedemon";
                $args['marketnav'] = "Hold Market";
                $args['schemas']['marketnav'] = "module-racedemon";
                $args['tavernnav'] = "The Pit Lounge";
                $args['schemas']['tavernnav'] = "module-racedemon";

                // Override standard location nav labels
                $args['navs']['weapons'] = "Avernus Armory";
                $args['navs']['armor'] = "Avernus Plates";
                $args['navs']['train'] = "u?Training Ground";
                $args['schemas']['navs']['weapons'] = "module-racedemon";
                $args['schemas']['navs']['armor'] = "module-racedemon";
                $args['schemas']['navs']['train'] = "module-racedemon";

                // Special section nav header and labels
                $args['nav_headers']['special'] = "Avernus Services";
                $args['navs']['altar'] = "Hell Altar";
                $args['navs']['pool'] = "Lava Pool";
                $args['schemas']['nav_headers']['special'] = "module-racedemon";
                $args['schemas']['navs']['altar'] = "module-racedemon";
                $args['schemas']['navs']['pool'] = "module-racedemon";

                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') !== $city) break;
            $owner = get_module_setting("stableowner");
            $args['title'] = "Hellstone Stables";
            $args['schemas']['title'] = "module-racedemon";
            $args['desc'] = "`\$You enter a stable filled with ash and burning sulfur. $owner is shoeing a massive nightmare with burning hooves. He snarls in welcome.";
            $args['schemas']['desc'] = "module-racedemon";
            $args['lad'] = "Beast";
            $args['schemas']['lad'] = "module-racedemon";
            $args['lass'] = "Beast";
            $args['schemas']['lass'] = "module-racedemon";
            $args['nosuchbeast'] = "`3\"`7We do not feed such creatures in our stables,`3\" says $owner.`3.";
            $args['schemas']['nosuchbeast'] = "module-racedemon";
            $args['finebeast'] = [
                "`3\"`7Let this nightmare run wild with you.`3\"`n`n",
                "`3\"`7A creature forged in the deep pit.`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-racedemon";
            $args['toolittle'] = "`%\"`7Your gold is lacking, grunt,`%\" $owner growls.";
            $args['schemas']['toolittle'] = "module-racedemon";
            $args['replacemount'] = "`%You discard your `^%s into the ash, mounting a vicious `&%s`%.";
            $args['schemas']['replacemount'] = "module-racedemon";
            $args['newmount'] = "`@You receive a vicious, burning `&%s`@.";
            $args['schemas']['newmount'] = "module-racedemon";
            $args['nofeed'] = "`3\"`7Magma feeds them. We sell no feed here.`3\"";
            $args['schemas']['nofeed'] = "module-racedemon";
            $args['nothungry'] = "`&%s`6 refuses feed, snorting smoke.";
            $args['schemas']['nothungry'] = "module-racedemon";
            $args['halfhungry'] = "`&%s`6 drinks some liquid brimstone.";
            $args['schemas']['halfhungry'] = "module-racedemon";
            $args['hungry'] = "`6%s`6 drinks the brimstone hungrily!";
            $args['schemas']['hungry'] = "module-racedemon";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is full now.`6\"";
            $args['schemas']['mountfull'] = "module-racedemon";
            $args['nofeedgold'] = "`6\"`^Not enough gold for brimstone.`6\"";
            $args['schemas']['nofeedgold'] = "module-racedemon";
            $args['confirmsale'] = "`n`n`6Are you sure you wish to return your mount?";
            $args['schemas']['confirmsale'] = "module-racedemon";
            $args['mountsold'] = "`6You hand over the reins, releasing the beast.";
            $args['schemas']['mountsold'] = "module-racedemon";
            $args['offer'] = "`n`n`6\"`^I'll offer `&%s`6 gold and `%%s`6 gems for that steed,`6\" $owner grunts.";
            $args['schemas']['offer'] = "module-racedemon";
            break;

        case "stablelocs":
            if (!get_module_setting("use_custom_village")) break;
            $travel_mod = racedemon_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "villagenav":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city && ($session['user']['race'] ?? '') === $race) {
                $args['special']['altar'] = "runmodule.php?module=racedemon&op=altar";
                $args['special']['pool'] = "runmodule.php?module=racedemon&op=pool";
            }
            break;

        case "weaponstext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['weaponvalue'] * 0.75), 0);
                $args['title'] = "Avernus Armory";
                $args['desc'] = [
                    "`&Crude obsidian axes, hellfire blades, and spiked shields carved from magma rock hang from chains. The room is filled with heat.`n`n"
                ];
                $args['tradein'] = [
                    "`7You throw your weapon on the spiked table.`n`n",
                    [
                        "`&A demon blacksmith grunts. \"`#I will give you `^%s`# gold trade value for this scrap.`\"`n`n",
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
                $args['title'] = "Avernus Plates";
                $args['desc'] = [
                    "`&Heavy black iron plate mail, horn-studded armor, and spiked tower shields line the room.`n`n"
                ];
                $args['tradein'] = [
                    "`7You show your current protection to the blacksmith.`n`n",
                    [
                        "`&He feels it and laughs. \"`#I can offer `^%s`# trade-in value toward our black iron plates.`\"`n`n",
                        $tradeinvalue,
                        $session['user']['armor']
                    ]
                ];
            }
            break;

        case "trainingtitle":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['title'] = "Training Ground";
            }
            break;

        case "modify-master":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['creaturename'] = "Azazel the Pit Lord";
                $args['creatureweapon'] = "Magma Whip";
                $args['creaturewin'] = "You have no wrath! Unleash the fires of hell!";
                $args['creaturelose'] = "You possess the true destruction of the pit. Go, unleash hell.";
            }
            break;
    }
    return $args;
}

function racedemon_run() {
    global $session;
    if (!get_module_setting("use_custom_village")) {
        page_header("Access Denied");
        output("`\$Custom village features are disabled for this race.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    $op = httpget('op');
    $race = "Demon";
    
    // Check if the user is a Demon
    if (($session['user']['race'] ?? '') !== $race) {
        page_header("Access Denied");
        output("`\$Only Demons may access these features.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    
    if ($op === "altar") {
        page_header("Avernus Hell Altar");
        output("`c`b`\$Avernus Hell Altar`b`c`n`n");
        output("`7A volcanic stone altar covered in dark red runes. Offering a gem here unleashes the fires of hell.`n`n");
        
        $sacrificed = get_module_pref("altar_sacrificed");
        $subop = httpget('subop');
        
        if ($subop === "sacrifice") {
            if ($sacrificed) {
                output("`\$You have already sacrificed today. The hellfire is satisfied.`n`n");
            } elseif ($session['user']['gems'] < 1) {
                output("`\$You do not have any gems to offer the spirits.`n`n");
            } else {
                $session['user']['gems'] -= 1;
                set_module_pref("altar_sacrificed", 1);
                $sacrificed = true;
                
                apply_buff("demon_frenzy", [
                    "name" => "`\$Hellfire Frenzy`0",
                    "atkmod" => 1.20,
                    "rounds" => 15,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "schema" => "module-racedemon",
                ]);
                output("`@You drop a gem into the Hell Altar. Volcanic fire erupts around you, filling you with absolute destruction (+20%% Attack) for your next 15 rounds of combat!`n`n");
            }
        }
        
        if (!$sacrificed) {
            output("`7You have `@%s gem(s)`7 to offer.`n`n", $session['user']['gems']);
            if ($session['user']['gems'] >= 1) {
                addnav("Hell Altar");
                addnav("Offer Gem", "runmodule.php?module=racedemon&op=altar&subop=sacrifice");
            }
        } else {
            output("`&You have already sacrificed at the Hell Altar today.`n`n");
        }
        addnav("Return to Avernus", "village.php");
        page_footer();
    }
    
    if ($op === "pool") {
        page_header("Avernus Lava Pool");
        output("`c`b`\$Avernus Lava Pool`b`c`n`n");
        output("`7A glowing pool of hot, liquid magma. Bathing here restores your demonic strength.`n`n");
        
        $bathed = get_module_pref("pool_bathed");
        $subop = httpget('subop');
        
        if ($subop === "bathe") {
            if ($bathed) {
                output("`\$You have already soaked today.`n`n");
            } else {
                set_module_pref("pool_bathed", 1);
                $bathed = true;
                
                $session['user']['hitpoints'] = $session['user']['maxhitpoints'];
                apply_buff("demon_shield", [
                    "name" => "`\$Ash Shield`0",
                    "dmgshield" => 3.0,
                    "rounds" => 15,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "schema" => "module-racedemon",
                ]);
                output("`@You submerge your body into the bubbling magma. The heat is soothing. Your wounds are completely healed and a protective ash shield (+3 damage reflected) covers you for your next 15 rounds of combat!`n`n");
            }
        }
        
        if (!$bathed) {
            addnav("Lava Pool");
            addnav("Soak in Magma", "runmodule.php?module=racedemon&op=pool&subop=bathe");
        } else {
            output("`&You have already soaked in the pool today.`n`n");
        }
        addnav("Return to Avernus", "village.php");
        page_footer();
    }
}

function racedemon_checkcity() {
    global $session;
    if (!get_module_setting("use_custom_village")) return true;
    $race = "Demon";
    $city = get_module_setting("villagename");
    
    $travel_mod = racedemon_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    return true;
}

function racedemon_get_travel_mod() {
    if (is_module_active("villages")) {
        return "villages";
    } elseif (is_module_active("cities")) {
        return "cities";
    } elseif (is_module_active("multi-towns")) {
        return "multi-towns";
    }
    return false;
}

function racedemon_check_unlock() {
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

function racedemon_get_unlock_text() {
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
