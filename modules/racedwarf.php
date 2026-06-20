<?php
/**
 * Advanced Race Module - Dwarf
 * Built from the Advanced Race Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function racedwarf_getmoduleinfo() {
    return [
        "name" => "Race - Dwarf",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Race",
        "download" => "", 
        "settings" => [
            "Dwarf - Race Settings,title",
            "use_custom_village" => "Does this race have its own custom village?,bool|1",
            "villagename" => "Name for the Dwarf city,|Kharad-Dur", 
            "stableowner" => "Name of the city stable-owner,|Gimli", 
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|5", 
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|0", 
            "training_villages" => "Villages where players of this race can train (comma-separated; leave blank to restrict to their custom village if enabled),string|",
            
            "Race Progression Settings,title",
            "levels" => "Max Race Level,int|5",
            "cre_req" => "Battle victories required to level up,int|150",
            "legacy_stat" => "Legacy Buff Stat (attack/defense/hp/gold/none),string|gold",
            "legacy_value" => "Legacy Buff Value,float|1.02",
            "unlock_req" => "Unlock Requirements (comma-separated, e.g. racehuman:5),string|",
            "stagnation_decay" => "Stagnation victory point multiplier (0.0 to 1.0),float|0.5",
        ],
        "prefs" => [
            "Dwarf - User Prefs,title",
            "level" => "Current Race Level,int|0",
            "cre" => "Current Victory Points towards next level,float|0.0",
            "stagnation" => "Consecutive Dragon Kills as this race,int|0",
            "last_played_dk" => "Was this race played last DK?,bool|0",
            "fresh_soul" => "Does player have fresh soul bonus?,bool|0",
            
            "tempered_today" => "Has player tempered equipment today?,bool|0",
            "polished_today" => "Has player polished a gem today?,bool|0",
        ]
    ];
}

function racedwarf_install() {
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
    debug("Installed 'Dwarf' advanced race module.");
    return true;
}

function racedwarf_uninstall() {
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
    $sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Dwarf'";
    db_query($sql);
    if (($session['user']['race'] ?? '') === 'Dwarf') {
        $session['user']['race'] = RACE_UNKNOWN;
    }
    
    debug("Uninstalled 'Dwarf' race module.");
    return true;
}

function racedwarf_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "Dwarf"; 
    
    $userRace = $session['user']['race'] ?? '';
    
    switch($hookname) {
        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (get_module_setting("use_custom_village") && ($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "racedwarf") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = racedwarf_get_travel_mod();
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
                $unlocked = racedwarf_check_unlock();
                if ($unlocked) {
                    $atrans = translate_inline("Deep in the subterranean strongholds of %s");
                    $rtrans = translate_inline(", home to the noble and fierce Dwarves.`n`n");
                    addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                    output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                    addnav("", "newday.php?setrace=$race$resline");
                } else {
                    $req_text = racedwarf_get_unlock_text();
                    output("`7Locked Race: `&%s`7 (Requires: `5%s`7)`n`n", $race, $req_text);
                }
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`#You embrace your dwarven heritage! Your skin hardens (+10%% Defense) and you gain a keen eye for mining treasure.`n");
                
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
                    $travel_mod = racedwarf_get_travel_mod();
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
                racedwarf_checkcity();
                
                // Dwarves get +10% gold from fights today
                apply_buff("racialbenefit", [
                    "name" => sprintf_translate("Dwarfen Might"),
                    "defmod" => 1.10,
                    "allowinpvp" => 1,
                    "allowintrain" => 1,
                    "rounds" => -1,
                    "schema" => "module-racedwarf",
                ]);
                
                // Reset daily services
                set_module_pref("tempered_today", 0);
                set_module_pref("polished_today", 0);

                output("`n`#You awaken in the mountain hold of Kharad-Dur. Your resolve is solid as granite (+10%% Defense).`n`0");
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
                        "schema" => "module-racedwarf",
                    ]);
                } elseif ($stat === "defense" && $val > 0.0) {
                    apply_buff("legacy_" . strtolower($race) . "_def", [
                        "name" => sprintf_translate("Legacy of the %s", translate_inline($race)),
                        "defmod" => $val,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "rounds" => -1,
                        "schema" => "module-racedwarf",
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
                $travel_mod = racedwarf_get_travel_mod();
                if ($travel_mod !== false)
                    $args[$city] = "village-$race";
            }
            break;

        case "moderate":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = racedwarf_get_travel_mod();
                if ($travel_mod !== false) {
                    tlschema("commentary");
                    $args["village-$race"] = sprintf_translate("City of %s", $city); 
                    tlschema();
                }
            }
            break;

        case "travel":
            if (get_module_setting("use_custom_village")) {
                $travel_mod = racedwarf_get_travel_mod();
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
            racedwarf_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^Granite walls rise high above the deep cavern floor of %s. Heavy iron structures, roaring forge chimneys, and solid stone halls surround the marketplace.`n", $city, $city];
                $args['schemas']['text'] = "module-racedwarf";
                $args['description'] = ["`\$`c`b%s`b`c`n`^Granite walls rise high above the deep cavern floor of %s. Heavy iron structures, roaring forge chimneys, and solid stone halls surround the marketplace.`n", $city, $city];
                $args['schemas']['description'] = "module-racedwarf";
                $args['clock'] = "`n`7A massive iron water-clock reads `@%s`7.`n";
                $args['schemas']['clock'] = "module-racedwarf";
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$An engraved stone tablet calendar reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-racedwarf";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-racedwarf";
                $args['sayline'] = "grunts";
                $args['schemas']['sayline'] = "module-racedwarf";
                $args['talk'] = "`n`&Nearby some dwarven miners stand talking:`n";
                $args['schemas']['talk'] = "module-racedwarf";
                
                $travel_mod = racedwarf_get_travel_mod();
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
                    $args['newest'] = "`n`7You are the newest resident of this mountain hold.";
                } else {
                    $args['newest'] = "`n`7The newest resident of this mountain hold is `&%s`7.";
                }
                $args['schemas']['newest'] = "module-racedwarf";
                $args['section'] = "village-$race";
                $args['stablename'] = "Mountain Stables";
                $args['schemas']['stablename'] = "module-racedwarf";
                $args['gatenav'] = "Subterranean Shafts";
                $args['schemas']['gatenav'] = "module-racedwarf";
                $args['fightnav'] = "Underground Arena";
                $args['schemas']['fightnav'] = "module-racedwarf";
                $args['marketnav'] = "Cavern Bazaar";
                $args['schemas']['marketnav'] = "module-racedwarf";
                $args['tavernnav'] = "The Stone Hearth Inn";
                $args['schemas']['tavernnav'] = "module-racedwarf";

                // Override standard location nav labels
                $args['navs']['weapons'] = "Kharad-Dur Forge & Steel";
                $args['navs']['armor'] = "Kharad-Dur Bulwark & Plate";
                $args['navs']['train'] = "u?Kharad-Dur Training Arena";
                $args['schemas']['navs']['weapons'] = "module-racedwarf";
                $args['schemas']['navs']['armor'] = "module-racedwarf";
                $args['schemas']['navs']['train'] = "module-racedwarf";

                // Special section nav header and labels
                $args['nav_headers']['special'] = "Kharad-Dur Services";
                $args['navs']['forge'] = "Deep Forge";
                $args['navs']['gemcutter'] = "Gemcutter's Shop";
                $args['schemas']['nav_headers']['special'] = "module-racedwarf";
                $args['schemas']['navs']['forge'] = "module-racedwarf";
                $args['schemas']['navs']['gemcutter'] = "module-racedwarf";

                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') !== $city) break;
            $owner = get_module_setting("stableowner");
            $args['title'] = "Mountain Stables";
            $args['schemas']['title'] = "module-racedwarf";
            $args['desc'] = "`\$You stand in a stable carved from deep stone, with heavy iron lanterns lighting the pens. $owner greets you with a stout nod.";
            $args['schemas']['desc'] = "module-racedwarf";
            $args['lad'] = "Lad";
            $args['schemas']['lad'] = "module-racedwarf";
            $args['lass'] = "Lass";
            $args['schemas']['lass'] = "module-racedwarf";
            $args['nosuchbeast'] = "`3\"`7We don't stock that kind of critter in these tunnels.`3\", says $owner.`3.";
            $args['schemas']['nosuchbeast'] = "module-racedwarf";
            $args['finebeast'] = [
                "`3\"`7This beast has sturdy legs, perfect for cavern treks.`3\"`n`n",
                "`3\"`7Treat it well and it'll carry you through the worst depths.`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-racedwarf";
            $args['toolittle'] = "`%\"`7You don't have enough coin for that mount,`%\" $owner grunts.";
            $args['schemas']['toolittle'] = "module-racedwarf";
            $args['replacemount'] = "`%You trade in your `^%s for a sturdy `&%s`%.";
            $args['schemas']['replacemount'] = "module-racedwarf";
            $args['newmount'] = "`@You receive a strong, sure-footed `&%s`@.";
            $args['schemas']['newmount'] = "module-racedwarf";
            $args['nofeed'] = "`3\"`7We're out of feed. Mine your own lichen.`3\"";
            $args['schemas']['nofeed'] = "module-racedwarf";
            $args['nothungry'] = "`&%s`6 refuses the feed, looking satisfied.";
            $args['schemas']['nothungry'] = "module-racedwarf";
            $args['halfhungry'] = "`&%s`6 chews on the cave mushrooms contentedly.";
            $args['schemas']['halfhungry'] = "module-racedwarf";
            $args['hungry'] = "`6%s`6 eats the mushrooms greedily!";
            $args['schemas']['hungry'] = "module-racedwarf";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is stuffed.`6\"";
            $args['schemas']['mountfull'] = "module-racedwarf";
            $args['nofeedgold'] = "`6\"`^Not enough gold for mushroom feed.`6\"";
            $args['schemas']['nofeedgold'] = "module-racedwarf";
            $args['confirmsale'] = "`n`n`6Are you sure you want to sell your mount back to the stables?";
            $args['schemas']['confirmsale'] = "module-racedwarf";
            $args['mountsold'] = "`6You hand over the reins and receive your gold.";
            $args['schemas']['mountsold'] = "module-racedwarf";
            $args['offer'] = "`n`n`6\"`^I can offer you `&%s`6 gold and `%%s`6 gems for that mount,`6\" $owner grunts.";
            $args['schemas']['offer'] = "module-racedwarf";
            break;

        case "stablelocs":
            if (!get_module_setting("use_custom_village")) break;
            $travel_mod = racedwarf_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "villagenav":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city && ($session['user']['race'] ?? '') === $race) {
                $args['special']['forge'] = "runmodule.php?module=racedwarf&op=forge";
                $args['special']['gemcutter'] = "runmodule.php?module=racedwarf&op=gemcutter";
            }
            break;

        case "weaponstext":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['weaponvalue'] * 0.75), 0);
                $args['title'] = "Kharad-Dur Forge & Steel";
                $args['desc'] = [
                    "`&Heavy bellows roar and iron anvils ring as dwarven armorers hammer out thick axes and warhammers.`n`n"
                ];
                $args['tradein'] = [
                    "`7You place your weapon on the stone slab counter.`n`n",
                    [
                        "`&The smith grunts, inspecting the balance. \"`#I'll give you `^%s`# gold in trade toward some proper dwarven steel.`\"`n`n",
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
                $args['title'] = "Kharad-Dur Bulwark & Plate";
                $args['desc'] = [
                    "`&Mounds of thick iron plates, heavy chainmail, and stone-reinforced shields line the dark cavern walls.`n`n"
                ];
                $args['tradein'] = [
                    "`7You show your current protection to the master tanner.`n`n",
                    [
                        "`&He bangs a hammer against your armor and nods. \"`#Not bad. Worth `^%s`# in trade-in for real dwarven bulwarks.`\"`n`n",
                        $tradeinvalue,
                        $session['user']['armor']
                    ]
                ];
            }
            break;

        case "trainingtitle":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['title'] = "Kharad-Dur Training Arena";
            }
            break;

        case "modify-master":
            if (!get_module_setting("use_custom_village")) break;
            if (($session['user']['location'] ?? '') === $city) {
                $args['creaturename'] = "Korgan the Ironclad";
                $args['creatureweapon'] = "Double-Headed Greataxe";
                $args['creaturewin'] = "You swing your axe like a human child! Put some back into it!";
                $args['creaturelose'] = "By the ancestors... you fight with the strength of the mountain. Go, protect the deeps.";
            }
            break;
    }
    return $args;
}

function racedwarf_run() {
    global $session;
    if (!get_module_setting("use_custom_village")) {
        page_header("Access Denied");
        output("`\$Custom village features are disabled for this race.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    $op = httpget('op');
    $race = "Dwarf";
    
    // Check if the user is a Dwarf
    if (($session['user']['race'] ?? '') !== $race) {
        page_header("Access Denied");
        output("`\$Only Dwarves may access these features.`n`n");
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    
    if ($op === "forge") {
        page_header("Kharad-Dur Deep Forge");
        output("`c`b`#Kharad-Dur Deep Forge`b`c`n`n");
        output("`7Ancient heat rises from the volcanic cracks in the floor. Dwarven blacksmiths are tempering weaponry and shields to perfection.`n`n");
        
        $tempered = get_module_pref("tempered_today");
        $subop = httpget('subop');
        
        if ($subop === "temper") {
            if ($tempered) {
                output("`\$You have already tempered your equipment today. Blacksmiths need to tend to the volcanic bellows.`n`n");
            } elseif ($session['user']['gold'] < 50) {
                output("`\$You don't have enough gold on hand (50 gold required) to pay the smiths.`n`n");
            } else {
                $session['user']['gold'] -= 50;
                set_module_pref("tempered_today", 1);
                $tempered = true;
                
                $roll = e_rand(1, 2);
                if ($roll == 1) {
                    apply_buff("tempered_axe", [
                        "name" => "`#Volcanic Weapon Tempering`0",
                        "atkmod" => 1.10,
                        "rounds" => 15,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "schema" => "module-racedwarf",
                    ]);
                    output("`@You pay 50 gold. The blacksmith heats your weapon in volcanic magma, adding a sharp, red-glowing edge (+10%% Attack) for your next 15 rounds of combat!`n`n");
                } else {
                    apply_buff("tempered_shield", [
                        "name" => "`#Iron Shield Tempering`0",
                        "defmod" => 1.10,
                        "rounds" => 15,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "schema" => "module-racedwarf",
                    ]);
                    output("`@You pay 50 gold. The blacksmith reinforces your armor with iron studs, hardening it (+10%% Defense) for your next 15 rounds of combat!`n`n");
                }
            }
        }
        
        if (!$tempered) {
            addnav("Deep Forge");
            addnav("Temper Equipment (50 gold)", "runmodule.php?module=racedwarf&op=forge&subop=temper");
        } else {
            output("`&You have already tempered your gear today.`n`n");
        }
        addnav("Return to Kharad-Dur", "village.php");
        page_footer();
    }
    
    if ($op === "gemcutter") {
        page_header("Kharad-Dur Gemcutter's Shop");
        output("`c`b`#Kharad-Dur Gemcutter's Shop`b`c`n`n");
        output("`7A skilled gemcutter stands at a workbench covered in magnifying lenses and diamond-tipped chisels. \"`#Bring me your gold and I'll polish some raw mountain stones for you.`\"`n`n");
        
        $polished = get_module_pref("polished_today");
        $subop = httpget('subop');
        
        if ($subop === "polish") {
            if ($polished) {
                output("`\$You have already polished a stone today. Let the gemcutter rest his eyes.`n`n");
            } elseif ($session['user']['gold'] < 200) {
                output("`\$You do not have the 200 gold required to purchase a raw stone.`n`n");
            } else {
                $session['user']['gold'] -= 200;
                set_module_pref("polished_today", 1);
                $polished = true;
                
                $roll = e_rand(1, 100);
                if ($roll <= 30) {
                    $session['user']['gems'] += 1;
                    output("`@You pay 200 gold. The gemcutter carefully chips away the rough crust... `^Success! `7You receive a beautifully polished `&gem`7!`n`n");
                } else {
                    $session['user']['gold'] += 50;
                    output("`7You pay 200 gold. The gemcutter chips away the stone... but it fractures! The pieces are worthless except as decorative gravel. He refunds you `^50 gold`7 for the scrap.`n`n");
                }
            }
        }
        
        if (!$polished) {
            addnav("Gemcutter");
            addnav("Polish Mountain Stone (200 gold)", "runmodule.php?module=racedwarf&op=gemcutter&subop=polish");
        } else {
            output("`&You have already had a stone polished today.`n`n");
        }
        addnav("Return to Kharad-Dur", "village.php");
        page_footer();
    }
}

function racedwarf_checkcity() {
    global $session;
    if (!get_module_setting("use_custom_village")) return true;
    $race = "Dwarf";
    $city = get_module_setting("villagename");
    
    $travel_mod = racedwarf_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    return true;
}

function racedwarf_get_travel_mod() {
    if (is_module_active("villages")) {
        return "villages";
    } elseif (is_module_active("cities")) {
        return "cities";
    } elseif (is_module_active("multi-towns")) {
        return "multi-towns";
    }
    return false;
}

function racedwarf_check_unlock() {
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

function racedwarf_get_unlock_text() {
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
