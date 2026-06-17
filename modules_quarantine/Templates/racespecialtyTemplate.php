<?php
/**
 * Master Blueprint: Race & Specialty Hybrid
 * Designed for PHP 8.4 Strict Compliance
 * * Easy way to customize:
 * - Replace 'racespecialtytemplate' with module name (e.g., 'racespecialtypaladin')
 * - Search for '[PLACEHOLDER]' to find all text that needs customization.
 * - Search for '***CHANGE' to see structural variables that must be defined.
 */

function racespecialtytemplate_getmoduleinfo() {
    return [
        "name" => "Race/Specialty - [PLACEHOLDER RACE NAME]", // ***CHANGE
        "version" => "1.0",
        "vertxtloc" => "",
        "author" => "Dragon.NDGaming", // ***CHANGE
        "category" => "Race Specialities",
        "download" => "", 
        "settings" => [
            "[PLACEHOLDER RACE NAME] - Race Settings,title", // ***CHANGE
            "villagename" => "Name for the [PLACEHOLDER RACE NAME] city,|[PLACEHOLDER CITY NAME]", // ***CHANGE
            "stableowner" => "Name of the city stable-owner,|[PLACEHOLDER STABLE OWNER]", // ***CHANGE
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|20", // ***CHANGE
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|3",
            "(some of the specialities are quite strong- the DK limit is recommended),note", 
        ],
        // UNCOMMENT THIS BLOCK TO ENFORCE MODULE DEPENDENCIES
        /*
        "requires" => [
            "alignment" => "Alignment Module, 1.0|",
            // "cities" => "Multiple Cities Module, 1.0|",
        ],
        */
        "prefs" => [
            "[PLACEHOLDER RACE NAME] - Specialty User Prefs,title", // ***CHANGE
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|", 
            "subclass" => "Sublass,enum,Unset,0,[PLACEHOLDER SUBCLASS 1],1,[PLACEHOLDER SUBCLASS 2],2,[PLACEHOLDER SUBCLASS 3],3|0", // ***CHANGE
            "newdayset" => "Newday intercept at footer...,hidden|0", 
            "pagereturn" => "Return to what page after forced specialty change?,hidden|",
            "scoundrel" => "How many times has this user attempted to have the specialty without the race?,viewonly|0", 
        ]
    ];
}

function racespecialtytemplate_install() {
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
    module_addhook("choose-specialty");
    module_addhook("set-specialty");
    module_addhook("fightnav-specialties");
    module_addhook("apply-specialties");
    module_addhook("newday");
    module_addhook("header-newday");
    module_addhook("incrementspecialty");
    module_addhook("specialtynames");
    module_addhook("specialtymodules");
    module_addhook("specialtycolor");
    module_addhook("dragonkill");
    module_addhook("newday-intercept");
    module_addhook("villagenav");
    module_addhook("weaponstext");
    module_addhook("armortext");
    module_addhook("trainingtitle");
    module_addhook("modify-master");
    debug("Installed '[PLACEHOLDER RACE NAME]' specialty and race module."); // ***CHANGE
    return true;
}

function racespecialtytemplate_uninstall() {
    $spec = "XX"; // ***CHANGE: MUST BE A UNIQUE 2-LETTER CODE
    global $session;
    $vname = getsetting("villagename", LOCATION_FIELDS);
    $gname = get_module_setting("villagename");
    
    $sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
    db_query($sql);
    if (($session['user']['location'] ?? '') == $gname)
        $session['user']['location'] = $vname;
    
    // Force anyone who was this race to rechoose race
    $sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='[PLACEHOLDER RACE NAME]'"; // ***CHANGE
    db_query($sql);
    if (($session['user']['race'] ?? '') == '[PLACEHOLDER RACE NAME]')  // ***CHANGE
        $session['user']['race'] = RACE_UNKNOWN;
    
    // Reset the specialty
    $sql = "SELECT acctid FROM ".db_prefix("accounts")." WHERE race='[PLACEHOLDER RACE NAME]' ORDER BY acctid"; // ***CHANGE
    $result = db_query($sql);
    require_once("lib/systemmail.php");
    for ($i=0; $i < db_num_rows($result); $i++) {
        $row = db_fetch_assoc($result);
        $mailmessage="[PLACEHOLDER SYSTEM MAIL FOR BANISHED RACE]"; // ***CHANGE
        systemmail($row['acctid'],"To the [PLACEHOLDER RACE NAME]s...", $mailmessage); // ***CHANGE
    }
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    debug("Uninstalled '[PLACEHOLDER RACE NAME]' specialty and race module."); // ***CHANGE
    return true;
}

function racespecialtytemplate_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "[PLACEHOLDER RACE NAME]"; // ***CHANGE
    $spec = "XX"; // ***CHANGE: SAME 2-LETTER CODE AS UNINSTALL
    $name = "[PLACEHOLDER SPECIALTY NAME]"; // ***CHANGE
    $sname = racespecialtytemplate_namer();
    $ccode = "`@"; // ***CHANGE: COLOR CODE
    
    $userRace = $session['user']['race'] ?? '';
    $userSpec = $session['user']['specialty'] ?? '';
    
    switch($hookname) {
        case "header-newday":
            if (get_module_pref("newdayset") == 1) {
                set_module_pref("newdayset", 2);
                output("`c`b`#You had a $name specialty, but not the race!`nWe'll soon fix that.`n`\$-=-=-=-`b`c`n");
            }
            break;

        case "newday-intercept":
            if (get_module_pref("newdayset") == 2) {
                set_module_pref("scoundrel", (int)get_module_pref("scoundrel") + 1);
                set_module_pref("newdayset", 0);
                $p = get_module_pref("pagereturn") ?: "village.php";
                $session['user']['restorepage'] = $p;
                set_module_pref("pagereturn", "");
                redirect($p, "Forced specialty change."); 
            }
            break;

        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "racespecialtytemplate") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = racespecialtytemplate_get_travel_mod();
                if ($travel_mod !== false) {
                    $sql = "UPDATE " . db_prefix("module_userprefs") . " SET value='" . $args['new'] . "' WHERE modulename='$travel_mod' AND setting='homecity' AND value='" . $args['old'] . "'";
                    db_query($sql);
                }
            }
            break;

        case "charstats":
            if ($userRace === $race) {
                addcharstat("Vital Info");
                addcharstat("Race", translate_inline($race));
            }
            break;

        case "chooserace":
            if (($session['user']['dragonkills'] ?? 0) >= get_module_setting("dklimit")) {
                $can_select = true;

                // UNCOMMENT TO RESTRICT RACE BY ALIGNMENT
                /*
                if (is_module_active("alignment")) {
                    $player_align = get_module_pref("alignment", "alignment");
                    if ($player_align != "good") { 
                        $can_select = false;
                        output("`n`7Your soul lacks the purity required to walk the path of the %s.`n", $race);
                    }
                }
                */

                if ($can_select) {
                    $atrans = translate_inline("In the town of %s"); // ***CHANGE
                    $rtrans = translate_inline(", a place of peace and trade.`n`n"); // ***CHANGE
                    addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                    output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                    addnav("", "newday.php?setrace=$race$resline");
                }
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`&[PLACEHOLDER RACE SELECTED FLAVOR TEXT AND BUFF EXPLANATION]`n"); // ***CHANGE
                $travel_mod = racespecialtytemplate_get_travel_mod();
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
                racespecialtytemplate_checkcity();
                
                // PHP 8.4 Defensive Fix: Initialize 'turnstoday' safely before appending
                $args['turnstoday'] = ($args['turnstoday'] ?? '') . ", Race ($race): 1"; // ***CHANGE
                
                $session['user']['turns']++;
                output("`n`&[PLACEHOLDER NEWDAY RACE BUFF TEXT]`n`0"); // ***CHANGE
                $bonus = (int)getsetting("specialtybonus", 1);
                
                if($userSpec === $spec) {
                    if ($bonus == 1) {
                        output("`n`2For your specialty in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n", $ccode, $name, $ccode, $name);
                    } else {
                        output("`n`2For your specialty in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n", $ccode, $name, $bonus, $ccode, $name);
                    }
                }
                
                $amt = (int)(get_module_pref("skill") / 3);
                if ($userSpec === $spec) $amt += $bonus;
                set_module_pref("uses", $amt);
            }
            break;

        case "validlocation":
            $travel_mod = racespecialtytemplate_get_travel_mod();
            if ($travel_mod !== false)
                $args[$city] = "village-$race";
            break;

        case "moderate":
            $travel_mod = racespecialtytemplate_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("commentary");
                $args["village-$race"] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "travel":
            $travel_mod = racespecialtytemplate_get_travel_mod();
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
            racespecialtytemplate_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^[PLACEHOLDER VILLAGE MAIN DESCRIPTION TEXT]`n", $city];
                $args['schemas']['text'] = "module-racespecialtytemplate";
                $args['description'] = ["`\$`c`b%s`b`c`n`^[PLACEHOLDER VILLAGE MAIN DESCRIPTION TEXT]`n", $city];
                $args['schemas']['description'] = "module-racespecialtytemplate";
                $args['clock'] = "`n`7A clock reads `@%s`7.`n";
                $args['schemas']['clock'] = "module-racespecialtytemplate";
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$A calendar reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-racespecialtytemplate";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-racespecialtytemplate";
                $args['sayline'] = "says";
                $args['schemas']['sayline'] = "module-racespecialtytemplate";
                $args['talk'] = "`n`&Nearby some residents stand talking:`n";
                $args['schemas']['talk'] = "module-racespecialtytemplate";
                
                $travel_mod = racespecialtytemplate_get_travel_mod();
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
                
                if ($new == $session['user']['acctid']) {
                    $args['newest'] = "`n`7[PLACEHOLDER NEWEST PLAYER TEXT (SELF)].";
                } else {
                    $args['newest'] = "`n`7[PLACEHOLDER NEWEST PLAYER TEXT (OTHER)] `&%s`7.";
                }
                $args['schemas']['newest'] = "module-racespecialtytemplate";
                $args['section'] = "village-$race";
                $args['stablename'] = "[PLACEHOLDER STABLE NAME]";
                $args['schemas']['stablename'] = "module-racespecialtytemplate";
                $args['gatenav'] = "[PLACEHOLDER GATE NAV TEXT]";
                $args['schemas']['gatenav'] = "module-racespecialtytemplate";
                $args['fightnav'] = "[PLACEHOLDER FIGHT NAV TEXT]";
                $args['schemas']['fightnav'] = "module-racespecialtytemplate";
                $args['marketnav'] = "[PLACEHOLDER MARKET NAV TEXT]";
                $args['schemas']['marketnav'] = "module-racespecialtytemplate";
                $args['tavernnav'] = "[PLACEHOLDER TAVERN NAV TEXT]";
                $args['schemas']['tavernnav'] = "module-racespecialtytemplate";

                // Override standard location nav labels
                $args['navs']['weapons'] = "[PLACEHOLDER WEAPONRY NAME]";
                $args['navs']['armor'] = "[PLACEHOLDER ARMOURY NAME]";
                $args['navs']['train'] = "u?[PLACEHOLDER TRAINING NAME]";
                $args['schemas']['navs']['weapons'] = "module-racespecialtytemplate";
                $args['schemas']['navs']['armor'] = "module-racespecialtytemplate";
                $args['schemas']['navs']['train'] = "module-racespecialtytemplate";

                // Special section nav header and labels
                $args['nav_headers']['special'] = "[PLACEHOLDER SPECIAL HEADER]";
                $args['navs']['special1'] = "[PLACEHOLDER SPECIAL LINK 1]";
                $args['navs']['special2'] = "[PLACEHOLDER SPECIAL LINK 2]";
                $args['schemas']['nav_headers']['special'] = "module-racespecialtytemplate";
                $args['schemas']['navs']['special1'] = "module-racespecialtytemplate";
                $args['schemas']['navs']['special2'] = "module-racespecialtytemplate";

                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (($session['user']['location'] ?? '') !== $city) break;
            $args['title'] = "[PLACEHOLDER STABLE NAME]";
            $args['schemas']['title'] = "module-racespecialtytemplate";
            $args['desc'] = "`\$[PLACEHOLDER STABLE DESCRIPTION FEATURING OWNER: ".get_module_setting("stableowner")."]";
            $args['schemas']['desc'] = "module-racespecialtytemplate";
            $args['lad'] = "Sir";
            $args['schemas']['lad'] = "module-racespecialtytemplate";
            $args['lass'] = "Miss";
            $args['schemas']['lass'] = "module-racespecialtytemplate";
            $args['nosuchbeast'] = "`3\"`7I don't stock that creature.`3\", says ".get_module_setting("stableowner")."`3.";
            $args['schemas']['nosuchbeast'] = "module-racespecialtytemplate";
            $args['finebeast'] = [
                "`3\"`7[PLACEHOLDER MOUNT PURCHASE QUOTE 1]`3\"`n`n",
                "`3\"`7[PLACEHOLDER MOUNT PURCHASE QUOTE 2]`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-racespecialtytemplate";
            $args['toolittle'] = "`%[PLACEHOLDER NOT ENOUGH GOLD QUOTE]";
            $args['schemas']['toolittle'] = "module-racespecialtytemplate";
            $args['replacemount'] = "`%[PLACEHOLDER REPLACE MOUNT TEXT: YOU TRADE YOUR `^%s FOR A `&%s`%]";
            $args['schemas']['replacemount'] = "module-racespecialtytemplate";
            $args['newmount'] = "`@[PLACEHOLDER NEW MOUNT TEXT: YOU RECEIVE A `&%s`@]";
            $args['schemas']['newmount'] = "module-racespecialtytemplate";
            $args['nofeed'] = "`3\"`7I don't stock feed here.`3\"";
            $args['schemas']['nofeed'] = "module-racespecialtytemplate";
            $args['nothungry'] = "`&%s`6 [PLACEHOLDER MOUNT NOT HUNGRY TEXT]";
            $args['schemas']['nothungry'] = "module-racespecialtytemplate";
            $args['halfhungry'] = "`&%s`6 [PLACEHOLDER MOUNT HALF HUNGRY TEXT]";
            $args['schemas']['halfhungry'] = "module-racespecialtytemplate";
            $args['hungry'] = "`6%s`6 [PLACEHOLDER MOUNT FULLY HUNGRY TEXT]";
            $args['schemas']['hungry'] = "module-racespecialtytemplate";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is full now.`6\"";
            $args['schemas']['mountfull'] = "module-racespecialtytemplate";
            $args['nofeedgold'] = "`6\"`^Not enough money for food.`6\"";
            $args['schemas']['nofeedgold'] = "module-racespecialtytemplate";
            $args['confirmsale'] = "`n`n`6[PLACEHOLDER CONFIRM MOUNT SALE TEXT]";
            $args['schemas']['confirmsale'] = "module-racespecialtytemplate";
            $args['mountsold'] = "`6[PLACEHOLDER MOUNT SOLD TEXT]";
            $args['schemas']['mountsold'] = "module-racespecialtytemplate";
            $args['offer'] = "`n`n`6[PLACEHOLDER MOUNT SALE OFFER TEXT]";
            $args['schemas']['offer'] = "module-racespecialtytemplate";
            break;

        case "stablelocs":
            tlschema("mounts");
            $args[$city] = sprintf_translate("City of %s", $city); 
            tlschema();
            break;

        case "dragonkill":
            set_module_pref("uses", 0);
            set_module_pref("skill", 0);
            break;

        case "choose-specialty":
            if ($userSpec === "" || $userSpec === '0') {
                if ($userRace === $race) { 
                    $can_select = true;

                    // UNCOMMENT TO RESTRICT SPECIALTY BY ALIGNMENT
                    /*
                    if (is_module_active("alignment")) {
                        $player_align = get_module_pref("alignment", "alignment");
                        if ($player_align != "evil") {
                            $can_select = false;
                            output("`n`7You do not meet the alignment requirements to learn %s.`n", $name);
                        }
                    }
                    */

                    if ($can_select) {
                        addnav("$ccode$name`0", "runmodule.php?module=racespecialtytemplate&spec=".$spec.$resline);
                        $t1 = translate_inline("[PLACEHOLDER CHOOSE SPECIALTY LINK TEXT]"); // ***CHANGE
                        $t2 = appoencode(translate_inline("$ccode$name`0"));
                        rawoutput("<a href='runmodule.php?module=racespecialtytemplate&spec=".$spec.$resline."'>$t1 ($t2)</a><br>");
                        addnav("", "runmodule.php?module=racespecialtytemplate&spec=".$spec.$resline);
                    } else {
                        set_module_pref("class", "");
                        set_module_pref("subclass", "");
                    }
                } else {
                    set_module_pref("class", "");
                    set_module_pref("subclass", "");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                $resline = httpget("resline");
                $spec = httpget("spec");
                $mc = httpget("mc");    
                $sc = httpget("sc");
                set_module_pref("class", $mc);
                set_module_pref("subclass", $sc);
                
                output("`#`c`b[PLACEHOLDER SPECIALTY SET HEADER]`b`c`n");
                output("`@[PLACEHOLDER SPECIALTY SET FLAVOR TEXT EXPLAINING THEIR POWERS]`n");
            }
            break;

        case "fightnav-specialties":
            if ($userRace === $race && $userSpec === $spec) {
                $stuff = racespecialtytemplate_texts(get_module_pref("class"), get_module_pref("subclass"));
                $uses = (int)get_module_pref("uses");
                $script = $args['script'] ?? '';
                if ($uses > 0) {
                    addnav(["$ccode ".racespecialtytemplate_namer()." (%s points)`0", $uses], "");
                }
                foreach ($stuff as $points => $rubbish) {
                    foreach ($rubbish as $buffName => $morerubbish) {
                        if ($buffName === "name") {
                            if ($uses >= $points) {
                                $morei = str_replace("`%", "`%%", $morerubbish);
                                addnav(["`@ &#149; $morei`@ (%s)`0", $points], $script."op=fight&skill=$spec&l=$points", true);
                            }
                        }
                    }
                }
            }
            break;

        case "apply-specialties":
            $skill = httpget('skill');
            $l = httpget('l');
            $stuff = racespecialtytemplate_texts(get_module_pref("class"), get_module_pref("subclass"));
            if ($skill === $spec) {
                if ((int)get_module_pref("uses") >= $l) {
                    if (isset($stuff[$l])) {
                        $buff_to_apply = $stuff[$l];
                        apply_buff($spec.$l, $buff_to_apply);
                    } else {
                        apply_buff($spec."0", [
                            "startmsg" => "Your power fizzles and does nothing.", // ***CHANGE
                            "rounds" => 1,
                            "schema" => "racespecialtytemplate"
                        ]);
                    }
                    set_module_pref("uses", (int)get_module_pref("uses") - $l);
                }
            }
            break;

        case "incrementspecialty":
            if($userSpec === $spec) {
                $new = (int)get_module_pref("skill") + 1;
                set_module_pref("skill", $new);
                $c = $args['color'] ?? '';
                output("`n%sYou gain a level in `&%s%s to `#%s%s!", $c, $name, $c, $new, $c);
                $x = $new % 3;
                if ($x == 0) {
                    output("`n`^You gain an extra use point!`n");
                    set_module_pref("uses", (int)get_module_pref("uses") + 1);
                } else {
                    if (3-$x == 1) {
                        output("`n`^Only 1 more skill level until you gain an extra use point!`n");
                    } else {
                        output("`n`^Only %s more skill levels until you gain an extra use point!`n", (3-$x));
                    }
                }
                output_notl("`0");
            }
            break;

        case "specialtynames":
            if (($session['user']['dragonkills'] ?? 0) >= get_module_setting("dklimit")) {
                $args[$spec] = translate_inline($name);
            }
            break;

        case "specialtymodules":
            $args[$spec] = "racespecialtytemplate";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;

        case "villagenav":
            if (($session['user']['location'] ?? '') === $city && ($session['user']['race'] ?? '') === $race) {
                // Add custom links here for your hybrid race/specialty to sit at the top, e.g.:
                // $args['special']['special1'] = "runmodule.php?module=racespecialtytemplate&op=special1";
            }
            break;

        case "weaponstext":
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['weaponvalue'] * 0.75), 0);
                $args['title'] = "[PLACEHOLDER WEAPON SHOP TITLE]";
                $args['desc'] = [
                    "`&[PLACEHOLDER WEAPON SHOP DESCRIPTION]`n`n"
                ];
                $args['tradein'] = [
                    "`7[PLACEHOLDER WEAPON SHOP TRADE-IN OFFER]`n`n",
                    [
                        "`&[PLACEHOLDER WEAPON SHOP TRADE-IN OFFER DETAIL WITH %s FOR VALUE AND %s FOR WEAPON NAME]`n`n",
                        $tradeinvalue,
                        $session['user']['weapon']
                    ]
                ];
            }
            break;

        case "armortext":
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['armorvalue'] * 0.75), 0);
                $args['title'] = "[PLACEHOLDER ARMOR SHOP TITLE]";
                $args['desc'] = [
                    "`&[PLACEHOLDER ARMOR SHOP DESCRIPTION]`n`n"
                ];
                $args['tradein'] = [
                    "`7[PLACEHOLDER ARMOR SHOP TRADE-IN OFFER]`n`n",
                    [
                        "`&[PLACEHOLDER ARMOR SHOP TRADE-IN OFFER DETAIL WITH %s FOR VALUE AND %s FOR ARMOR NAME]`n`n",
                        $tradeinvalue,
                        $session['user']['armor']
                    ]
                ];
            }
            break;

        case "trainingtitle":
            if (($session['user']['location'] ?? '') === $city) {
                $args['title'] = "[PLACEHOLDER TRAINING CENTER TITLE]";
            }
            break;

        case "modify-master":
            if (($session['user']['location'] ?? '') === $city) {
                $args['creaturename'] = "[PLACEHOLDER TRAINER NAME]";
                $args['creatureweapon'] = "[PLACEHOLDER TRAINER WEAPON]";
                $args['creaturewin'] = "[PLACEHOLDER TRAINER WIN MESSAGE]";
                $args['creaturelose'] = "[PLACEHOLDER TRAINER LOSE MESSAGE]";
            }
            break;
    }
    return $args;
}

function racespecialtytemplate_run() {
    global $session;
    $op = httpget('op');
    $spec = httpget('spec');
    $resline = httpget('resline');
    $race = "[PLACEHOLDER RACE NAME]"; // ***CHANGE
    
    // Check if the user is of this race to prevent other races accessing custom features
    if (($session['user']['race'] ?? '') !== $race) {
        page_header("Access Denied");
        output("`\$Only %ss may access these features.`n`n", $race);
        addnav("Return to the Village", "village.php");
        page_footer();
        return;
    }
    
    if ($op == "") {
        page_header("[PLACEHOLDER SPECIALTY MENU]"); // ***CHANGE
        output("`c`b`#[PLACEHOLDER SPECIALTY CHOICE HEADER]`b`c`n"); // ***CHANGE
        output("`@[PLACEHOLDER EXPLANATION OF SUBCLASSES AND COMBAT TREES]`n`n"); // ***CHANGE
        
        addnav("Choose Path"); // ***CHANGE
        addnav("[PLACEHOLDER SUBCLASS 1]", "newday.php?setspecialty=$spec&mc=magic&sc=1$resline"); // ***CHANGE
        addnav("[PLACEHOLDER SUBCLASS 2]", "newday.php?setspecialty=$spec&mc=melee&sc=2$resline"); // ***CHANGE
        addnav("[PLACEHOLDER SUBCLASS 3]", "newday.php?setspecialty=$spec&mc=ranging&sc=3$resline"); // ***CHANGE
    }
    page_footer();
}



function racespecialtytemplate_checkcity() {
    global $session, $SCRIPT_NAME;
    $race = "[PLACEHOLDER RACE NAME]"; // ***CHANGE
    $spec = "XX"; // ***CHANGE
    $city = get_module_setting("villagename");
    
    if (get_module_pref("subclass") != 0 && ($session['user']['specialty'] ?? '') !== $spec) {
        set_module_pref("subclass", 0);
    }
    
    $travel_mod = racespecialtytemplate_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    
    if (($session['user']['race'] ?? '') !== $race && ($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
        set_module_pref("newdayset", 1);
        set_module_pref("pagereturn", $SCRIPT_NAME);
        $session['user']['restorepage'] = "newday.php?continue=1";
        require_once("lib/forcednavigation.php");
        do_forced_nav(false, true);
        redirect("newday.php?continue=1", "Specialty set, but not the race. Forced change.");
    }
    return true;
}

function racespecialtytemplate_namer($type='X') {
    $class = [];
    
    $class['magic']['1'] = "[PLACEHOLDER FULL CLASS NAME 1]"; // ***CHANGE
    $class['melee']['2'] = "[PLACEHOLDER FULL CLASS NAME 2]"; // ***CHANGE
    $class['ranging']['3'] = "[PLACEHOLDER FULL CLASS NAME 3]"; // ***CHANGE
    
    $class['1'] = "[PLACEHOLDER ADJECTIVE 1]"; // ***CHANGE
    $class['2'] = "[PLACEHOLDER ADJECTIVE 2]"; // ***CHANGE
    $class['3'] = "[PLACEHOLDER ADJECTIVE 3]"; // ***CHANGE
    
    if ($type === "X") {
        return $class[get_module_pref("class")][get_module_pref("subclass")] ?? '';
    } else {
        return $class[get_module_pref("subclass")] ?? '';
    }
}

function racespecialtytemplate_texts($class, $subclass) {
    global $session;
    $array = [];
    
    $array['magic']['1']['1'] = [ 
        "startmsg" => "`!You cast a basic spell!",
        "name" => "`#Spell Name",
        "rounds" => 5,
        "wearoff" => "The spell fades.",
        "effectmsg" => "`&You hit `^{badguy}`& for `^{damage}`& points.",
        "atkmod" => 1.2,
    ];
    $array['magic']['1']['3'] = [ 
        "startmsg" => "`!You cast a stronger spell!",
        "name" => "`#Strong Spell",
        "rounds" => 1,
        "effectmsg" => "`&You blast `^{badguy}`& for `^{damage}`& points.",
        "badguydamage" => "(<level>*2)", 
    ];
    
    $array['melee']['2']['1'] = [ 
        "startmsg" => "`!You enter a warrior's stance!",
        "name" => "`#Warrior Stance",
        "rounds" => 5,
        "wearoff" => "You lose your focus.",
        "effectmsg" => "`&You hit `^{badguy}`& harder than normal.",
        "atkmod" => 1.3,
    ];
    
    if (($session['user']['race'] ?? '') === '[PLACEHOLDER RACE NAME]') { // ***CHANGE
        if (isset($array[$class][$subclass])) {
            foreach ($array[$class][$subclass] as $t => $d) {
                $array[$class][$subclass][$t]['schema'] = "racespecialtytemplate";
            }
            return $array[$class][$subclass];
        } else {
            return [];
        }
    } else {
        return [];
    }
}

function racespecialtytemplate_get_travel_mod() {
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