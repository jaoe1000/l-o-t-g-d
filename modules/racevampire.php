<?php
/**
 * Advanced Race Module - Vampire
 * Built from the Advanced Race Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function racevampire_getmoduleinfo() {
    return [
        "name" => "Race - Vampire",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Race",
        "download" => "", 
        "settings" => [
            "Vampire - Race Settings,title",
            "use_custom_village" => "Does this race have its own custom village?,bool|1",
            "villagename" => "Name for the Vampire city,|Shadowspire", 
            "stableowner" => "Name of the city stable-owner,|Vlad", 
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|10", 
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|0", 
            "training_villages" => "Villages where players of this race can train (comma-separated; leave blank to restrict to their custom village if enabled),string|",
            
            "Race Progression Settings,title",
            "levels" => "Max Race Level,int|5",
            "cre_req" => "Battle victories required to level up,int|150",
            "legacy_stat" => "Legacy Buff Stat (attack/defense/hp/gold/none),string|hp",
            "legacy_value" => "Legacy Buff Value,float|3.0",
            "unlock_req" => "Unlock Requirements (comma-separated, e.g. racehuman:5),string|raceorc:3,racetroll:3",
            "stagnation_decay" => "Stagnation victory point multiplier (0.0 to 1.0),float|0.5",
        ],
        "prefs" => [
            "Vampire - User Prefs,title",
            "level" => "Current Race Level,int|0",
            "cre" => "Current Victory Points towards next level,float|0.0",
            "stagnation" => "Consecutive Dragon Kills as this race,int|0",
            "last_played_dk" => "Was this race played last DK?,bool|0",
            "fresh_soul" => "Does player have fresh soul bonus?,bool|0",
            
            "altar_sacrificed" => "Has player sacrificed at the altar today?,bool|0",
            "coffin_slept" => "Has player slept in the coffin today?,bool|0",
        ]
    ];
}

function racevampire_install() {
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
    debug("Installed 'Vampire' advanced race module.");
    return true;
}

function racevampire_uninstall() {
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
    $sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Vampire'";
    db_query($sql);
    if (($session['user']['race'] ?? '') === 'Vampire') {
        $session['user']['race'] = RACE_UNKNOWN;
    }
    
    debug("Uninstalled 'Vampire' race module.");
    return true;
}

function racevampire_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "Vampire"; 
    
    $userRace = $session['user']['race'] ?? '';
    
    switch($hookname) {
        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (get_module_setting("use_custom_village") && ($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "racevampire") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = racevampire_get_travel_mod();
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
                $unlocked = racevampire_check_unlock();
                if ($unlocked) {
                    $atrans = translate_inline("Within the dark spires of %s");
                    $rtrans = translate_inline(", home to the immortal Vampires.`n`n");
                    addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                    output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                    addnav("", "newday.php?setrace=$race$resline");
                } else {
                    $req_text = racevampire_get_unlock_text();
                    output("`7Locked Race: `&%s`7 (Requires: `5%s`7)`n`n", $race, $req_text);
                }
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`9You embrace your vampiric blood! You possess inhuman strength and nocturnal grace (+15%% Attack, +5%% Defense, +1 extra turn, and +5 Life Tap)!`n");
                
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
                    $travel_mod = racevampire_get_travel_mod();
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
                racevampire_checkcity();
                
                // Vampires get +1 extra turn and a solid combat buff with lifetap
                $args['turnstoday'] = ($args['turnstoday'] ?? '') . ", Race ($race): 1";
                $session['user']['turns']++;
                
                apply_buff("racialbenefit", [
                    "name" => sprintf_translate("Vampiric Hunger"),
                    "atkmod" => 1.15,
                    "defmod" => 1.05,
                    "lifetap" => 5,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "rounds" => -1,
                    "schema" => "module-racevampire",
                ]);
                
                // Reset daily services
                set_module_pref("altar_sacrificed", 0);
                set_module_pref("coffin_slept", 0);

                output("`n`9You rise from your dark rest in Shadowspire. Your fangs throb with hunger (+15%% Attack, +5%% Defense, +1 extra turn, +5 Life Tap).`n`0");
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
                        "schema" => "module-racevampire",
                    ]);
                } elseif ($stat === "defense" && $val > 0.0) {
                    apply_buff("legacy_" . strtolower($race) . "_def", [
                        "name" => sprintf_translate("Legacy of the %s", translate_inline($race)),
                        "defmod" => $val,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "rounds" => -1,
                        "schema" => "module-racevampire",
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
                $travel_mod = racevampire_get_travel_mod();
                if ($travel_mod !== false)
                    $args[$city] = "village-$race";
            }
            break;

        case "moderate":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = racevampire_get_travel_mod();
                if ($travel_mod !== false) {
                    tlschema("commentary");
                    $args["village-$race"] = sprintf_translate("City of %s", $city); 
                    tlschema();
                }
            }
            break;

        case "travel":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = racevampire_get_travel_mod();
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
            racevampire_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^Spired towers climb into the dark night sky of %s. The streets are cobbled and silent, lit by pale purple gaslamps. Fog hangs thick on the ground.`n", $city, $city];
                $args['schemas']['text'] = "module-racevampire";
                $args['description'] = ["`\$`c`b%s`b`c`n`^Spired towers climb into the dark night sky of %s. The streets are cobbled and silent, lit by pale purple gaslamps. Fog hangs thick on the ground.`n", $city, $city];
                $args['schemas']['description'] = "module-racevampire";
                $args['clock'] = "`n`7A gothic iron clocktower tolls, reading `@%s`7.`n";
                $args['schemas']['clock'] = "module-racevampire";
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$A calendar carved on a dark marble monument reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-racevampire";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-racevampire";
                $args['sayline'] = "whispers";
                $args['schemas']['sayline'] = "module-racevampire";
                $args['talk'] = "`n`&Nearby some elegant residents stand whispering:`n";
                $args['schemas']['talk'] = "module-racevampire";
                
                $travel_mod = racevampire_get_travel_mod();
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
                    $args['newest'] = "`n`7You are the newest resident of this dark sanctuary.";
                } else {
                    $args['newest'] = "`n`7The newest resident of this dark sanctuary is `&%s`7.";
                }
                $args['schemas']['newest'] = "module-racevampire";
                $args['section'] = "village-$race";
                $args['stablename'] = "Shadowspire Stables";
                $args['schemas']['stablename'] = "module-racevampire";
                $args['gatenav'] = "Dark Spires Paths";
                $args['schemas']['gatenav'] = "module-racevampire";
                $args['fightnav'] = "Training Crypt";
                $args['schemas']['fightnav'] = "module-racevampire";
                $args['marketnav'] = "Spire Plaza";
                $args['schemas']['marketnav'] = "module-racevampire";
                $args['tavernnav'] = "The Bloodlust Lounge";
                $args['schemas']['tavernnav'] = "module-racevampire";

                // Override standard location nav labels
                $args['navs']['weapons'] = "Shadowspire Crucible";
                $args['navs']['armor'] = "Shadowspire Tailoring";
                $args['navs']['train'] = "u?Shadowspire Training Crypt";
                $args['schemas']['navs']['weapons'] = "module-racevampire";
                $args['schemas']['navs']['armor'] = "module-racevampire";
                $args['schemas']['navs']['train'] = "module-racevampire";

                // Special section nav header and labels
                $args['nav_headers']['special'] = "Shadowspire Services";
                $args['navs']['altar'] = "Blood Altar";
                $args['navs']['coffin'] = "Crypt Coffin";
                $args['schemas']['nav_headers']['special'] = "module-racevampire";
                $args['schemas']['navs']['altar'] = "module-racevampire";
                $args['schemas']['navs']['coffin'] = "module-racevampire";

                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') !== $city) break;
            $owner = get_module_setting("stableowner");
            $args['title'] = "Shadowspire Stables";
            $args['schemas']['title'] = "module-racevampire";
            $args['desc'] = "`\$You enter a cold, dimly lit stable draped in cobwebs. $owner is grooming a dark, bat-winged stallion. He greets you silently.";
            $args['schemas']['desc'] = "module-racevampire";
            $args['lad'] = "Lord";
            $args['schemas']['lad'] = "module-racevampire";
            $args['lass'] = "Lady";
            $args['schemas']['lass'] = "module-racevampire";
            $args['nosuchbeast'] = "`3\"`7I do not stock that beast,`3\" says $owner.`3.";
            $args['schemas']['nosuchbeast'] = "module-racevampire";
            $args['finebeast'] = [
                "`3\"`7May this creature fly you safely through the night.`3\"`n`n",
                "`3\"`7A majestic companion of the dark.`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-racevampire";
            $args['toolittle'] = "`%\"`7Your coin is lacking, my Lord,`%\" $owner whispers.";
            $args['schemas']['toolittle'] = "module-racevampire";
            $args['replacemount'] = "`%You release your `^%s into the fog, taking the reins of a dark `&%s`%.";
            $args['schemas']['replacemount'] = "module-racevampire";
            $args['newmount'] = "`@You receive a dark, bat-winged `&%s`@.";
            $args['schemas']['newmount'] = "module-racevampire";
            $args['nofeed'] = "`3\"`7Nature feeds them. We sell no feed here.`3\"";
            $args['schemas']['nofeed'] = "module-racevampire";
            $args['nothungry'] = "`&%s`6 looks satisfied.";
            $args['schemas']['nothungry'] = "module-racevampire";
            $args['halfhungry'] = "`&%s`6 licks some fresh blood drops.";
            $args['schemas']['halfhungry'] = "module-racevampire";
            $args['hungry'] = "`6%s`6 drinks the fresh blood hungrily!";
            $args['schemas']['hungry'] = "module-racevampire";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is completely satisfied.`6\"";
            $args['schemas']['mountfull'] = "module-racevampire";
            $args['nofeedgold'] = "`6\"`^You have no gold for blood feed.`6\"";
            $args['schemas']['nofeedgold'] = "module-racevampire";
            $args['confirmsale'] = "`n`n`6Are you sure you wish to release your mount into the night?";
            $args['schemas']['confirmsale'] = "module-racevampire";
            $args['mountsold'] = "`6You hand over the reins, releasing the beast.";
            $args['schemas']['mountsold'] = "module-racevampire";
            $args['offer'] = "`n`n`6\"`^I can offer `&%s`6 gold and `%%s`6 gems for that steed,`6\" $owner offers.";
            $args['schemas']['offer'] = "module-racevampire";
            break;

        case "stablelocs":
            if (!get_module_setting("use_custom_village")) break;
            $travel_mod = racevampire_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "villagenav":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city && ($session['user']['race'] ?? '') === $race) {
                $args['special']['altar'] = "runmodule.php?module=racevampire&op=altar";
                $args['special']['coffin'] = "runmodule.php?module=racevampire&op=coffin";
            }
            break;

        case "weaponstext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['weaponvalue'] * 0.75), 0);
                $args['title'] = "Shadowspire Crucible";
                $args['desc'] = [
                    "`&Intricately carved dark steel blades and obsidian-hilted rapiers stand in quiet displays. A cold violet fire burns in the forge.`n`n"
                ];
                $args['tradein'] = [
                    "`7You place your weapon on the marble display.`n`n",
                    [
                        "`&The weaponmaster bows. \"`#I will value this piece at `^%s`# gold in trade.`\"`n`n",
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
                $args['title'] = "Shadowspire Tailoring";
                $args['desc'] = [
                    "`&Velvet capes, dark leather coats reinforced with iron ribs, and gargoyle-adorned shields stand in rows.`n`n"
                ];
                $args['tradein'] = [
                    "`7You show your current protection to the tailor.`n`n",
                    [
                        "`&She feels it with long fingers. \"`#I can offer `^%s`# gold in trade for our dark silk protective wear.`\"`n`n",
                        $tradeinvalue,
                        $session['user']['armor']
                    ]
                ];
            }
            break;

        case "trainingtitle":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['title'] = "Shadowspire Training Crypt";
            }
            break;

        case "modify-master":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['creaturename'] = "Lady Carmilla";
                $args['creatureweapon'] = "Obsidian Rapier";
                $args['creaturewin'] = "You are too slow, fledgling. Feast on my lessons!";
                $args['creaturelose'] = "A formidable predator of the night. Go, rule the darkness.";
            }
            break;
    }
    return $args;
}

function racevampire_run() {
    global $session;
    if (!get_module_setting("use_custom_village")) {
        page_header("Access Denied");
        output("`\$Custom village features are disabled for this race.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    $op = httpget('op');
    $race = "Vampire";
    
    // Check if the user is a Vampire
    if (($session['user']['race'] ?? '') !== $race) {
        page_header("Access Denied");
        output("`\$Only Vampires may access these features.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    
    if ($op === "altar") {
        page_header("Shadowspire Blood Altar");
        output("`c`b`9Shadowspire Blood Altar`b`c`n`n");
        output("`7A stone basin filled with dark red fluid, surrounded by black candles. \"`#Offer a gem to the ancient spirits, and they shall grant you extra turns!`\"`n`n");
        
        $sacrificed = get_module_pref("altar_sacrificed");
        $subop = httpget('subop');
        
        if ($subop === "sacrifice") {
            if ($sacrificed) {
                output("`\$You have already sacrificed today. The spirits are satisfied.`n`n");
            } elseif ($session['user']['gems'] < 1) {
                output("`\$You do not have any gems to offer the spirits.`n`n");
            } else {
                $session['user']['gems'] -= 1;
                $session['user']['turns'] += 1;
                set_module_pref("altar_sacrificed", 1);
                $sacrificed = true;
                
                output("`@You drop a gem into the dark blood altar. The candles flare violet, and your body surges with immortal vigor! You gain `^+1 extra turn`@ today!`n`n");
            }
        }
        
        if (!$sacrificed) {
            output("`7You have `@%s gem(s)`7 to offer.`n`n", $session['user']['gems']);
            if ($session['user']['gems'] >= 1) {
                addnav("Blood Altar");
                addnav("Sacrifice Gem (+1 Turn)", "runmodule.php?module=racevampire&op=altar&subop=sacrifice");
            }
        } else {
            output("`&You have already sacrificed at the altar today.`n`n");
        }
        addnav("Return to Shadowspire", "village.php");
        page_footer();
    }
    
    if ($op === "coffin") {
        page_header("Shadowspire Crypt Coffin");
        output("`c`b`9Shadowspire Crypt Coffin`b`c`n`n");
        output("`7A lined obsidian coffin lies open in a quiet, dark alcove. Sleeping here will regenerate your undead flesh.`n`n");
        
        $slept = get_module_pref("coffin_slept");
        $subop = httpget('subop');
        
        if ($subop === "sleep") {
            if ($slept) {
                output("`\$You have already rested in the coffin today.`n`n");
            } else {
                set_module_pref("coffin_slept", 1);
                $slept = true;
                
                $session['user']['hitpoints'] = $session['user']['maxhitpoints'];
                apply_buff("coffin_vision", [
                    "name" => "`9Nocturnal Reflexes`0",
                    "defmod" => 1.10,
                    "rounds" => 15,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "schema" => "module-racevampire",
                ]);
                output("`@You climb into the coffin, closing the heavy lid. You rest in absolute darkness. When you emerge, your wounds are completely healed and your nocturnal senses are sharp (+10%% Defense) for your next 15 rounds of combat!`n`n");
            }
        }
        
        if (!$slept) {
            addnav("Crypt Coffin");
            addnav("Sleep in Coffin", "runmodule.php?module=racevampire&op=coffin&subop=sleep");
        } else {
            output("`&You have already rested in the coffin today.`n`n");
        }
        addnav("Return to Shadowspire", "village.php");
        page_footer();
    }
}

function racevampire_checkcity() {
    global $session;
    if (!get_module_setting("use_custom_village")) return true;
    $race = "Vampire";
    $city = get_module_setting("villagename");
    
    $travel_mod = racevampire_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    return true;
}

function racevampire_get_travel_mod() {
    if (is_module_active("villages")) {
        return "villages";
    } elseif (is_module_active("cities")) {
        return "cities";
    } elseif (is_module_active("multi-towns")) {
        return "multi-towns";
    }
    return false;
}

function racevampire_check_unlock() {
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

function racevampire_get_unlock_text() {
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
