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
        ],
        "prefs" => [
            "Elf - User Prefs,title", 
            "fights_blessed" => "Fights blessed today,bool|0",
            "garden_foraged" => "Foraged today,bool|0",
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
    module_addhook("villagenav");
    module_addhook("weaponstext");
    module_addhook("armortext");
    module_addhook("trainingtitle");
    module_addhook("modify-master");
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
                
                set_module_pref("fights_blessed", 0);
                set_module_pref("garden_foraged", 0);
                
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

                // Override standard location nav labels
                $args['navs']['weapons'] = "Gladehaven Bowyer & Fletchery";
                $args['navs']['armor'] = "Gladehaven Leaf & Hide";
                $args['navs']['train'] = "u?Gladehaven Training Glade";
                $args['schemas']['navs']['weapons'] = "module-raceelf";
                $args['schemas']['navs']['armor'] = "module-raceelf";
                $args['schemas']['navs']['train'] = "module-raceelf";

                // Special section nav header and labels
                $args['nav_headers']['special'] = "Gladehaven Services";
                $args['navs']['garden'] = "Herb Garden";
                $args['navs']['shrine'] = "Sylvan Shrine";
                $args['schemas']['nav_headers']['special'] = "module-raceelf";
                $args['schemas']['navs']['garden'] = "module-raceelf";
                $args['schemas']['navs']['shrine'] = "module-raceelf";

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

        case "villagenav":
            if (($session['user']['location'] ?? '') === $city && ($session['user']['race'] ?? '') === $race) {
                $args['special']['garden'] = "runmodule.php?module=raceelf&op=garden";
                $args['special']['shrine'] = "runmodule.php?module=raceelf&op=shrine";
            }
            break;

        case "weaponstext":
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['weaponvalue'] * 0.75), 0);
                $args['title'] = "Gladehaven Bowyer & Fletchery";
                $args['desc'] = [
                    "`&You climb into a spacious treetop workshop. Elven fletchers and woodcarvers are crafting elegant longbows and lightweight blades.`n`n",
                    "Beautifully carved wooden racks hold graceful composite bows, silvered arrows, and slender rapiers.`n`n"
                ];
                $args['tradein'] = [
                    "`7You place your weapon on the polished wooden counter.`n",
                    [
                        "`&The master bowyer inspects it carefully. \"`#For this, I can credit you `^%s`# toward your next purchase. Elves require only the finest wood and steel.`\"`n`n",
                        $tradeinvalue,
                        $session['user']['weapon']
                    ]
                ];
            }
            break;

        case "armortext":
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['armorvalue'] * 0.75), 0);
                $args['title'] = "Gladehaven Leaf & Hide";
                $args['desc'] = [
                    "`&Rows of light leather jerkins, moon-blessed hide vests, and armors woven from resilient living leaves hang from the branches.`n`n",
                    "This armor is crafted to preserve the agility and grace of Elven defenders.`n`n"
                ];
                $args['tradein'] = [
                    "`7You show your protection to the elven tanner.`n",
                    [
                        "`&She feels the texture of your armor and nods. \"`#I will offer `^%s`# trade-in value for your `5%s`#.`\"`n`n",
                        $tradeinvalue,
                        $session['user']['armor']
                    ]
                ];
            }
            break;

        case "trainingtitle":
            if (($session['user']['location'] ?? '') === $city) {
                $args['title'] = "Gladehaven Training Glade";
            }
            break;

        case "modify-master":
            if (($session['user']['location'] ?? '') === $city) {
                $args['creaturename'] = "Faelar the Sylvan Bladesinger";
                $args['creatureweapon'] = "Elven Longbow";
                $args['creaturewin'] = "You move like a human. Slower! More grace!";
                $args['creaturelose'] = "A true warrior of the forest. Go, protect the glades.";
            }
            break;
    }
    return $args;
}

function raceelf_run() {
    global $session;
    $op = httpget('op');
    $city = get_module_setting("villagename");
    
    // Check if the user is an Elf
    if (($session['user']['race'] ?? '') !== 'Elf') {
        page_header("Access Denied");
        output("`\$Only Elves may access this location in Gladehaven.`n`n");
        addnav("Return to Gladehaven", "village.php");
        page_footer();
        return;
    }
    
    if ($op == "shrine") {
        page_header("Gladehaven Sylvan Shrine");
        output("`c`b`@Gladehaven Sylvan Shrine`b`c`n`n");
        output("`7Ancient, glowing runes run down the stone altar, surrounded by sylvan tree roots. Offering a gem here allows you to request sylvan blessings.`n`n");
        
        $subop = httpget('subop');
        $blessed = get_module_pref("fights_blessed");
        
        if ($subop == "bless") {
            if ($session['user']['gems'] < 1) {
                output("`\$You do not have any gems to offer the forest spirits.`n`n");
            } else {
                $type = httpget('type');
                if ($type == "grace") {
                    if ($blessed) {
                        output("`\$The forest spirits whisper that you have already received the Blessing of Grace today.`n`n");
                    } else {
                        $session['user']['gems'] -= 1;
                        $session['user']['turns'] += 2;
                        set_module_pref("fights_blessed", 1);
                        output("`@You offer a gem to the altar. A warm green light envelops you, granting you `^+2 extra turns`@ today!`n`n");
                        $blessed = true;
                    }
                } elseif ($type == "wards") {
                    $session['user']['gems'] -= 1;
                    apply_buff("sylvan_ward", [
                        "name" => "`@Sylvan Ward`0",
                        "defmod" => 1.25,
                        "rounds" => 40,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "schema" => "module-raceelf",
                    ]);
                    output("`@You offer a gem to the altar. Wisps of wind surround you, forming a defensive sylvan ward (+25%% defense) for 40 rounds of combat!`n`n");
                } elseif ($type == "focus") {
                    $session['user']['gems'] -= 1;
                    $session['user']['hitpoints'] = $session['user']['maxhitpoints'];
                    output("`@You offer a gem to the altar. Gentle glowing leaves touch your wounds, completely healing your body!`n`n");
                }
            }
        }
        
        output("`7You have `@%s gem(s)`7 to offer.`n`n", $session['user']['gems']);
        
        if ($session['user']['gems'] >= 1) {
            addnav("Request Blessing");
            if (!$blessed) {
                addnav("Blessing of Grace (+2 Turns today)", "runmodule.php?module=raceelf&op=shrine&subop=bless&type=grace");
            } else {
                addnav("Blessing of Grace (Already received)", "");
            }
            addnav("Blessing of Wards (+25% Defense)", "runmodule.php?module=raceelf&op=shrine&subop=bless&type=wards");
            addnav("Blessing of Focus (Full Heal)", "runmodule.php?module=raceelf&op=shrine&subop=bless&type=focus");
        }
        
        addnav("Return to Gladehaven", "village.php");
        page_footer();
    } elseif ($op == "garden") {
        page_header("Gladehaven Herb Garden");
        output("`c`b`@Gladehaven Herb Garden`b`c`n`n");
        output("`7A peaceful glade filled with sylvan flowers, glowing moss, and rare herbs. Once per day, you may forage for magical ingredients.`n`n");
        
        $foraged = get_module_pref("garden_foraged");
        $subop = httpget('subop');
        
        if ($subop == "forage") {
            if ($foraged) {
                output("`\$You have already foraged the garden today. Let the plants grow back before foraging again.`n`n");
            } else {
                set_module_pref("garden_foraged", 1);
                $foraged = true;
                $roll = e_rand(1, 10);
                if ($roll <= 4) {
                    // Dewberry (Heal 50% HP)
                    $heal = round($session['user']['maxhitpoints'] * 0.50);
                    $session['user']['hitpoints'] = min($session['user']['maxhitpoints'], $session['user']['hitpoints'] + $heal);
                    output("`@You search the canopy and discover a ripe `&Dewberry`@. You eat it, restoring `^%s`@ hitpoints!`n`n", $heal);
                } elseif ($roll <= 7) {
                    // Ironwood Bark (+20% def, 10 rounds)
                    apply_buff("ironwood_bark", [
                        "name" => "`^Ironwood Bark`0",
                        "defmod" => 1.20,
                        "rounds" => 10,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "schema" => "module-raceelf",
                    ]);
                    output("`@You forage and peel off a slice of `&Ironwood Bark`@. You chew it, hardening your skin (+20%% defense) for your next combat!`n`n");
                } elseif ($roll <= 9) {
                    // Sunpetal (+20% atk, 10 rounds)
                    apply_buff("sunpetal", [
                        "name" => "`&Sunpetal`0",
                        "atkmod" => 1.20,
                        "rounds" => 10,
                        "allowinpvp" => 1,
                        "allowintrain" => 1,
                        "schema" => "module-raceelf",
                    ]);
                    output("`@You forage and pick a glowing `&Sunpetal`@. The sweet nectar fills you with combat adrenaline (+20%% attack) for your next combat!`n`n");
                } else {
                    output("`7You search the garden but find nothing of use today.`n`n");
                }
            }
        }
        
        if (!$foraged) {
            addnav("Forage");
            addnav("Search for Herbs", "runmodule.php?module=raceelf&op=garden&subop=forage");
        } else {
            output("`&You have already foraged the herb garden today.`n`n");
        }
        
        addnav("Return to Gladehaven", "village.php");
        page_footer();
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
