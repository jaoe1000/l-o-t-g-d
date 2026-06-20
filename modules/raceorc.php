<?php
/**
 * Advanced Race Module - Orc
 * Built from the Advanced Race Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function raceorc_getmoduleinfo() {
    return [
        "name" => "Race - Orc",
        "version" => "1.0",
        "author" => "`4Thanatos,`2Based on Chris Vorndran's raceorc (Modernized by Dragon.NDGaming)", 
        "category" => "Race",
        "download" => "", 
        "settings" => [
            "Orc - Race Settings,title",
            "use_custom_village" => "Does this race have its own custom village?,bool|1",
            "villagename" => "Name for the Orc city,|Gurn's Hold", 
            "stableowner" => "Name of the city stable-owner,|Korg", 
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|20", 
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|0", 
            "training_villages" => "Villages where players of this race can train (comma-separated; leave blank to restrict to their custom village if enabled),string|",
            
            "Race Progression Settings,title",
            "levels" => "Max Race Level,int|5",
            "cre_req" => "Battle victories required to level up,int|150",
            "legacy_stat" => "Legacy Buff Stat (attack/defense/hp/gold/none),string|attack",
            "legacy_value" => "Legacy Buff Value,float|1.01",
            "unlock_req" => "Unlock Requirements (comma-separated, e.g. racehuman:5),string|",
            "stagnation_decay" => "Stagnation victory point multiplier (0.0 to 1.0),float|0.5",
        ],
        "prefs" => [
            "Orc - User Prefs,title",
            "level" => "Current Race Level,int|0",
            "cre" => "Current Victory Points towards next level,float|0.0",
            "stagnation" => "Consecutive Dragon Kills as this race,int|0",
            "last_played_dk" => "Was this race played last DK?,bool|0",
            "fresh_soul" => "Does player have fresh soul bonus?,bool|0",
            
            "pit_fought" => "Has player fought in the pit today?,bool|0",
            "totem_prayed" => "Has player prayed at the totem today?,bool|0",
        ]
    ];
}

function raceorc_install() {
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
    debug("Installed 'Orc' advanced race module.");
    return true;
}

function raceorc_uninstall() {
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
    $sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Orc'";
    db_query($sql);
    if (($session['user']['race'] ?? '') === 'Orc') {
        $session['user']['race'] = RACE_UNKNOWN;
    }
    
    debug("Uninstalled 'Orc' race module.");
    return true;
}

function raceorc_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "Orc"; 
    
    $userRace = $session['user']['race'] ?? '';
    
    switch($hookname) {
        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (get_module_setting("use_custom_village") && ($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "raceorc") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = raceorc_get_travel_mod();
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
                $unlocked = raceorc_check_unlock();
                if ($unlocked) {
                    $atrans = translate_inline("Within the raw iron gates of %s");
                    $rtrans = translate_inline(", home to the warmongering and proud Orcs.`n`n");
                    addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                    output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                    addnav("", "newday.php?setrace=$race$resline");
                } else {
                    $req_text = raceorc_get_unlock_text();
                    output("`7Locked Race: `&%s`7 (Requires: `5%s`7)`n`n", $race, $req_text);
                }
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`\$You embrace your orcish blood! Spikes and iron armor adorn you (+15%% Attack, but -5%% Defense), and your blood burns for battle (+1 forest fight per day)!`n");
                
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
                    $travel_mod = raceorc_get_travel_mod();
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
                raceorc_checkcity();
                
                // Orcs get +1 forest fight and +15% attack, -5% defense
                $args['turnstoday'] = ($args['turnstoday'] ?? '') . ", Race ($race): 1";
                $session['user']['turns']++;
                
                apply_buff("racialbenefit", [
                    "name" => sprintf_translate("Orcish Fury"),
                    "atkmod" => 1.15,
                    "defmod" => 0.95,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "rounds" => -1,
                    "schema" => "module-raceorc",
                ]);
                
                // Reset daily services
                set_module_pref("pit_fought", 0);
                set_module_pref("totem_prayed", 0);

                output("`n`\$You wake up with a bloodthirsty howl in Gurn's Hold. Your axes are ready (+15%% Attack, -5%% Defense, +1 forest fight).`n`0");
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
                        "schema" => "module-raceorc",
                    ]);
                } elseif ($stat === "defense" && $val > 0.0) {
                    apply_buff("legacy_" . strtolower($race) . "_def", [
                        "name" => sprintf_translate("Legacy of the %s", translate_inline($race)),
                        "defmod" => $val,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "rounds" => -1,
                        "schema" => "module-raceorc",
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
                $travel_mod = raceorc_get_travel_mod();
                if ($travel_mod !== false)
                    $args[$city] = "village-$race";
            }
            break;

        case "moderate":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = raceorc_get_travel_mod();
                if ($travel_mod !== false) {
                    tlschema("commentary");
                    $args["village-$race"] = sprintf_translate("City of %s", $city); 
                    tlschema();
                }
            }
            break;

        case "travel":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = raceorc_get_travel_mod();
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
            raceorc_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^Raw iron banners flutter from the spiked battlements of %s. The village is muddy and loud, filled with roaring beasts and grunting warriors.`n", $city, $city];
                $args['schemas']['text'] = "module-raceorc";
                $args['description'] = ["`\$`c`b%s`b`c`n`^Raw iron banners flutter from the spiked battlements of %s. The village is muddy and loud, filled with roaring beasts and grunting warriors.`n", $city, $city];
                $args['schemas']['description'] = "module-raceorc";
                $args['clock'] = "`n`7A shadow on a bloodstained dial reads `@%s`7.`n";
                $args['schemas']['clock'] = "module-raceorc";
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$A crude wooden calendar table reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-raceorc";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-raceorc";
                $args['sayline'] = "barks";
                $args['schemas']['sayline'] = "module-raceorc";
                $args['talk'] = "`n`&Nearby some clan defenders stand talking:`n";
                $args['schemas']['talk'] = "module-raceorc";
                
                $travel_mod = raceorc_get_travel_mod();
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
                    $args['newest'] = "`n`7You are the newest recruit of Gurn's Hold.";
                } else {
                    $args['newest'] = "`n`7The newest recruit of Gurn's Hold is `&%s`7.";
                }
                $args['schemas']['newest'] = "module-raceorc";
                $args['section'] = "village-$race";
                $args['stablename'] = "Bloodstone Stables";
                $args['schemas']['stablename'] = "module-raceorc";
                $args['gatenav'] = "Spiked Gates";
                $args['schemas']['gatenav'] = "module-raceorc";
                $args['fightnav'] = "Training Pit";
                $args['schemas']['fightnav'] = "module-raceorc";
                $args['marketnav'] = "Hold Market";
                $args['schemas']['marketnav'] = "module-raceorc";
                $args['tavernnav'] = "The Drunken Boar";
                $args['schemas']['tavernnav'] = "module-raceorc";

                // Override standard location nav labels
                $args['navs']['weapons'] = "Gurn's Armory & Spikes";
                $args['navs']['armor'] = "Gurn's Spiked Plates";
                $args['navs']['train'] = "u?Gurn's Training Grounds";
                $args['schemas']['navs']['weapons'] = "module-raceorc";
                $args['schemas']['navs']['armor'] = "module-raceorc";
                $args['schemas']['navs']['train'] = "module-raceorc";

                // Special section nav header and labels
                $args['nav_headers']['special'] = "Gurn's Services";
                $args['navs']['pit'] = "Gladiator Pit";
                $args['navs']['totem'] = "War Totem";
                $args['schemas']['nav_headers']['special'] = "module-raceorc";
                $args['schemas']['navs']['pit'] = "module-raceorc";
                $args['schemas']['navs']['totem'] = "module-raceorc";

                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') !== $city) break;
            $owner = get_module_setting("stableowner");
            $args['title'] = "Bloodstone Stables";
            $args['schemas']['title'] = "module-raceorc";
            $args['desc'] = "`\$You enter a muddy enclosure fenced with sharp wooden spikes. $owner is busy shoeing a giant wild boar. He glares at you.";
            $args['schemas']['desc'] = "module-raceorc";
            $args['lad'] = "Grunt";
            $args['schemas']['lad'] = "module-raceorc";
            $args['lass'] = "Grunt";
            $args['schemas']['lass'] = "module-raceorc";
            $args['nosuchbeast'] = "`3\"`7I don't stock that trash. Get out,`3\" says $owner.`3.";
            $args['schemas']['nosuchbeast'] = "module-raceorc";
            $args['finebeast'] = [
                "`3\"`7This beast is pure muscle. Ride and break some heads,`3\" $owner barks.`n`n",
                "`3\"`7Sturdy and mean, just like an orc should ride.`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-raceorc";
            $args['toolittle'] = "`%\"`7You don't have enough gold, weakling,`%\" $owner sneers.";
            $args['schemas']['toolittle'] = "module-raceorc";
            $args['replacemount'] = "`%You discard your `^%s and mount a vicious `&%s`%.";
            $args['schemas']['replacemount'] = "module-raceorc";
            $args['newmount'] = "`@You receive a vicious, growling `&%s`@.";
            $args['schemas']['newmount'] = "module-raceorc";
            $args['nofeed'] = "`3\"`7Find your own feed, grunt.`3\"";
            $args['schemas']['nofeed'] = "module-raceorc";
            $args['nothungry'] = "`&%s`6 snarls, refusing feed.";
            $args['schemas']['nothungry'] = "module-raceorc";
            $args['halfhungry'] = "`&%s`6 chews on raw meat happily.";
            $args['schemas']['halfhungry'] = "module-raceorc";
            $args['hungry'] = "`6%s`6 snaps at the meat, swallowing it whole!";
            $args['schemas']['hungry'] = "module-raceorc";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is completely satisfied.`6\"";
            $args['schemas']['mountfull'] = "module-raceorc";
            $args['nofeedgold'] = "`6\"`^Not enough gold for meat feed.`6\"";
            $args['schemas']['nofeedgold'] = "module-raceorc";
            $args['confirmsale'] = "`n`n`6Are you sure you want to sell your mount back to the stables?";
            $args['schemas']['confirmsale'] = "module-raceorc";
            $args['mountsold'] = "`6You hand over the reins and receive your gold.";
            $args['schemas']['mountsold'] = "module-raceorc";
            $args['offer'] = "`n`n`6\"`^I'll buy that beast off you for `&%s`6 gold and `%%s`6 gems,`6\" $owner grunts.";
            $args['schemas']['offer'] = "module-raceorc";
            break;

        case "stablelocs":
            if (!get_module_setting("use_custom_village")) break;
            $travel_mod = raceorc_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "villagenav":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city && ($session['user']['race'] ?? '') === $race) {
                $args['special']['pit'] = "runmodule.php?module=raceorc&op=pit";
                $args['special']['totem'] = "runmodule.php?module=raceorc&op=totem";
            }
            break;

        case "weaponstext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['weaponvalue'] * 0.75), 0);
                $args['title'] = "Gurn's Armory & Spikes";
                $args['desc'] = [
                    "`&Jagged iron blades, heavy morningstars, and spiked clubs hang from chains. The forge fires burn dark and hot.`n`n"
                ];
                $args['tradein'] = [
                    "`7You throw your weapon on the spiked table.`n`n",
                    [
                        "`&An orc armorer laughs. \"`#This toothpick is worth `^%s`# gold. Trade it in for real iron.`\"`n`n",
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
                $args['title'] = "Gurn's Spiked Plates";
                $args['desc'] = [
                    "`&Suits of heavy spiked leather, crude iron plates, and massive iron tower shields stand in piles.`n`n"
                ];
                $args['tradein'] = [
                    "`7You show your current protection to the blacksmith.`n`n",
                    [
                        "`&He kicks it and nods. \"`#I'll give you `^%s`# gold for this scrap.`\"`n`n",
                        $tradeinvalue,
                        $session['user']['armor']
                    ]
                ];
            }
            break;

        case "trainingtitle":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['title'] = "Gurn's Training Grounds";
            }
            break;

        case "modify-master":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['creaturename'] = "Morgrok the Berserker";
                $args['creatureweapon'] = "Spiked Iron Cleaver";
                $args['creaturewin'] = "You fight like a weakling! Go back to the mud!";
                $args['creaturelose'] = "You have the true fury of the clan. Go, blood and honor!";
            }
            break;
    }
    return $args;
}

function raceorc_run() {
    global $session;
    if (!get_module_setting("use_custom_village")) {
        page_header("Access Denied");
        output("`\$Custom village features are disabled for this race.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    $op = httpget('op');
    $race = "Orc";
    
    // Check if the user is an Orc
    if (($session['user']['race'] ?? '') !== $race) {
        page_header("Access Denied");
        output("`\$Only Orcs may access these features.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    
    if ($op === "pit") {
        page_header("Gurn's Gladiator Pit");
        output("`c`b`\$Gurn's Gladiator Pit`b`c`n`n");
        output("`7A deep pit surrounded by roaring orcs. Inside, fighters duel in the mud. \"`#Step in and prove your worth, grunt,`\" a master calls.`n`n");
        
        $fought = get_module_pref("pit_fought");
        $subop = httpget('subop');
        
        if ($subop === "fight") {
            if ($fought) {
                output("`\$You have already fought in the pit today. Let your wounds scab over.`n`n");
            } else {
                set_module_pref("pit_fought", 1);
                $fought = true;
                
                $roll = e_rand(1, 100);
                if ($roll <= 50) {
                    $gold_won = 100;
                    $session['user']['gold'] += $gold_won;
                    output("`@You jump into the pit! You trade brutal blows and knock your opponent flat in the mud! The crowd cheers and throws you `^%s gold`@!`n`n", $gold_won);
                } else {
                    $session['user']['hitpoints'] = max(1, $session['user']['hitpoints'] - 5);
                    apply_buff("pit_injury", [
                        "name" => "`\$Gladiator Bruises`0",
                        "defmod" => 0.95,
                        "rounds" => 5,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "schema" => "module-raceorc",
                    ]);
                    output("`@You jump into the pit! A massive gladiator slams you into the wooden fence, leaving you bruised and sore. You lose `^5 hitpoints`@ and take a defensive penalty (-5%% Defense) for your next 5 rounds of combat!`n`n");
                }
            }
        }
        
        if (!$fought) {
            addnav("Gladiator Pit");
            addnav("Enter Pit Duel", "runmodule.php?module=raceorc&op=pit&subop=fight");
        } else {
            output("`&You have already entered the pit today.`n`n");
        }
        addnav("Return to Gurn's Hold", "village.php");
        page_footer();
    }
    
    if ($op === "totem") {
        page_header("Gurn's War Totem");
        output("`c`b`\$Gurn's War Totem`b`c`n`n");
        output("`7A massive totem carved from wood and skulls. A shaman stands before it, throwing bones into a fire. \"`#Offer your blood to the ancestors, and feel their wrath!`\"`n`n");
        
        $prayed = get_module_pref("totem_prayed");
        $subop = httpget('subop');
        
        if ($subop === "pray") {
            if ($prayed) {
                output("`\$You have already offered your blood today.`n`n");
            } else {
                // Sacrifice 10% of current HP
                $sac = round($session['user']['hitpoints'] * 0.10);
                $session['user']['hitpoints'] = max(1, $session['user']['hitpoints'] - $sac);
                set_module_pref("totem_prayed", 1);
                $prayed = true;
                
                apply_buff("totem_cry", [
                    "name" => "`\$Ancestral War Cry`0",
                    "atkmod" => 1.15,
                    "rounds" => 15,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "schema" => "module-raceorc",
                ]);
                output("`@You slice your palm and smear blood on the skull totem. The spirits scream in your mind, filling you with absolute bloodlust (+15%% Attack) for your next 15 rounds of combat!`n`n");
            }
        }
        
        if (!$prayed) {
            addnav("War Totem");
            addnav("Sacrifice Blood", "runmodule.php?module=raceorc&op=totem&subop=pray");
        } else {
            output("`&You have already smeared blood on the totem today.`n`n");
        }
        addnav("Return to Gurn's Hold", "village.php");
        page_footer();
    }
}

function raceorc_checkcity() {
    global $session;
    if (!get_module_setting("use_custom_village")) return true;
    $race = "Orc";
    $city = get_module_setting("villagename");
    
    $travel_mod = raceorc_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    return true;
}

function raceorc_get_travel_mod() {
    if (is_module_active("villages")) {
        return "villages";
    } elseif (is_module_active("cities")) {
        return "cities";
    } elseif (is_module_active("multi-towns")) {
        return "multi-towns";
    }
    return false;
}

function raceorc_check_unlock() {
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

function raceorc_get_unlock_text() {
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
