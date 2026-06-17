<?php
/**
 * Advanced Hybrid Race/Specialty Module - Reptile
 * Built from the Master Blueprint: Race & Specialty Hybrid
 * Designed for PHP 8.4 Strict Compliance
 */

function racespecialtyreptile_getmoduleinfo() {
    return [
        "name" => "Race/Specialty - Reptile", 
        "version" => "1.0",
        "vertxtloc" => "",
        "author" => "Dragon.NDGaming", 
        "category" => "Race Specialities",
        "download" => "", 
        "settings" => [
            "Reptile - Race Settings,title", 
            "villagename" => "Name for the Reptile city,|Sslyther", 
            "stableowner" => "Name of the Reptile city stable-owner,|Sslippery Ssid", 
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|20", 
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|0",
            "(some of the specialities are quite strong- the DK limit is recommended),note",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Lizard Adze',note", 
        ],
        "prefs" => [
            "Reptile - Specialty User Prefs,title", 
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|", 
            "subclass" => "Subclass,enum,Unset,0,Lizard,1,Snake,2,Amphibian,3|0", 
            "newdayset" => "Newday intercept at footer...,hidden|0", 
            "pagereturn" => "Return to what page after forced specialty change?,hidden|",
            "scoundrel" => "How many times has this user attempted to have the specialty without the race?,viewonly|0", 
        ]
    ];
}

function racespecialtyreptile_install() {
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
    module_addhook("boughtweapon");
    module_addhook("boughtarmor");
    debug("Installed 'Reptile' specialty and race module.");
    return true;
}

function racespecialtyreptile_uninstall() {
    $spec = "RP"; 
    global $session;
    $vname = getsetting("villagename", LOCATION_FIELDS);
    $gname = get_module_setting("villagename");
    
    $sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
    db_query($sql);
    if (($session['user']['location'] ?? '') == $gname)
        $session['user']['location'] = $vname;
    
    // Force anyone who was this race to rechoose race
    $sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Reptile'"; 
    db_query($sql);
    if (($session['user']['race'] ?? '') == 'Reptile')  
        $session['user']['race'] = RACE_UNKNOWN;
    
    // Reset the specialty
    $sql = "SELECT acctid FROM ".db_prefix("accounts")." WHERE race='Reptile' ORDER BY acctid"; 
    $result = db_query($sql);
    require_once("lib/systemmail.php");
    for ($i=0; $i < db_num_rows($result); $i++) {
        $row = db_fetch_assoc($result);
        $mailmessage="The Reptiles have returned to their swamps. Your cold-blooded gifts have faded."; 
        systemmail($row['acctid'],"To the Reptiles...", $mailmessage); 
    }
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    debug("Uninstalled 'Reptile' specialty and race module."); 
    return true;
}

function racespecialtyreptile_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "Reptile"; 
    $spec = "RP"; 
    $name = "Reptile Adaptations"; 
    $sname = racespecialtyreptile_namer();
    $ccode = "`@"; 
    
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
            racespecialtyreptile_change();
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
            if (($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "racespecialtyreptile") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = racespecialtyreptile_get_travel_mod();
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
            if (($session['user']['dragonkills'] ?? 0) >= (int)get_module_setting("dklimit")) {
                $can_select = true;

                if ($can_select) {
                    $atrans = translate_inline("Deep within the mist-veiled swamp pathways of %s"); 
                    $rtrans = translate_inline(", under the watchful eyes of cold-blooded scaled dwellers.`n`n"); 
                    addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                    output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                    addnav("", "newday.php?setrace=$race$resline");
                }
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`&As a Reptile, you possess tough scales and dynamic adaptations tied to your specialized path.`n"); 
                $travel_mod = racespecialtyreptile_get_travel_mod();
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
                racespecialtyreptile_checkcity();
                
                $args['turnstoday'] = ($args['turnstoday'] ?? '') . ", Race ($race): 1"; 
                
                $session['user']['turns']++;
                output("`n`&Your cold-blooded biology adapts to the new day (+1 extra turn)!`n`0"); 
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
            $travel_mod = racespecialtyreptile_get_travel_mod();
            if ($travel_mod !== false)
                $args[$city] = "village-$race";
            break;

        case "moderate":
            $travel_mod = racespecialtyreptile_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("commentary");
                $args["village-$race"] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "travel":
            $travel_mod = racespecialtyreptile_get_travel_mod();
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
            racespecialtyreptile_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^Tucked away in the humid marshlands, the wooden piers and bridges of %s creak underfoot. Mists hover over the dark waters.`n", $city, $city];
                $args['schemas']['text'] = "module-racespecialtyreptile";
                $args['description'] = ["`\$`c`b%s`b`c`n`^Tucked away in the humid marshlands, the wooden piers and bridges of %s creak underfoot. Mists hover over the dark waters.`n", $city, $city];
                $args['schemas']['description'] = "module-racespecialtyreptile";
                $args['clock'] = "`n`7A water clock drips quietly, showing `@%s`7.`n";
                $args['schemas']['clock'] = "module-racespecialtyreptile";
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$A slate calendar reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-racespecialtyreptile";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-racespecialtyreptile";
                $args['sayline'] = "hisses";
                $args['schemas']['sayline'] = "module-racespecialtyreptile";
                $args['talk'] = "`n`&Nearby some cold-blooded residents stand talking in hushed tones:`n";
                $args['schemas']['talk'] = "module-racespecialtyreptile";
                
                $travel_mod = racespecialtyreptile_get_travel_mod();
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
                    $args['newest'] = "`n`7You are the newest hatchling in this swamp city.";
                } else {
                    $args['newest'] = "`n`7The newest hatchling is `&%s`7.";
                }
                $args['schemas']['newest'] = "module-racespecialtyreptile";
                $args['section'] = "village-$race";
                $args['stablename'] = "Sslyther Serpents";
                $args['schemas']['stablename'] = "module-racespecialtyreptile";
                $args['gatenav'] = "Marsh Paths";
                $args['schemas']['gatenav'] = "module-racespecialtyreptile";
                $args['fightnav'] = "Combative Swamps";
                $args['schemas']['fightnav'] = "module-racespecialtyreptile";
                $args['marketnav'] = "Floating Market";
                $args['schemas']['marketnav'] = "module-racespecialtyreptile";
                $args['tavernnav'] = "The Muddy Bog Tavern";
                $args['schemas']['tavernnav'] = "module-racespecialtyreptile";
                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (($session['user']['location'] ?? '') !== $city) break;
            $args['title'] = "Sslyther Serpents";
            $args['schemas']['title'] = "module-racespecialtyreptile";
            $args['desc'] = "`\$You enter a muddy enclosure. ".get_module_setting("stableowner")."`\$ slithers up to greet you.";
            $args['schemas']['desc'] = "module-racespecialtyreptile";
            $args['lad'] = "Sir";
            $args['schemas']['lad'] = "module-racespecialtyreptile";
            $args['lass'] = "Miss";
            $args['schemas']['lass'] = "module-racespecialtyreptile";
            $args['nosuchbeast'] = "`3\"`7I don't stock that beast in these swamps.`3\", says ".get_module_setting("stableowner")."`3.";
            $args['schemas']['nosuchbeast'] = "module-racespecialtyreptile";
            $args['finebeast'] = [
                "`3\"`7A cold-blooded mount for a cold-blooded fighter.`3\"`n`n",
                "`3\"`7This beast will glide through any swamp.`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-racespecialtyreptile";
            $args['toolittle'] = "`%\"`7You lack the mud-gems needed for that one,`%\" says ".get_module_setting("stableowner").".";
            $args['schemas']['toolittle'] = "module-racespecialtyreptile";
            $args['replacemount'] = "`%You trade in your `^%s for a sleek `&%s`%.";
            $args['schemas']['replacemount'] = "module-racespecialtyreptile";
            $args['newmount'] = "`@You receive a sleek, swamp-ready `&%s`@.";
            $args['schemas']['newmount'] = "module-racespecialtyreptile";
            $args['nofeed'] = "`3\"`7We don't stock feed here, try the bogs.`3\"";
            $args['schemas']['nofeed'] = "module-racespecialtyreptile";
            $args['nothungry'] = "`&%s`6 turns its snout away.";
            $args['schemas']['nothungry'] = "module-racespecialtyreptile";
            $args['halfhungry'] = "`&%s`6 snaps up half the swamp-flies.";
            $args['schemas']['halfhungry'] = "module-racespecialtyreptile";
            $args['hungry'] = "`6%s`6 gulps down the feed instantly!";
            $args['schemas']['hungry'] = "module-racespecialtyreptile";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is completely full.`6\"";
            $args['schemas']['mountfull'] = "module-racespecialtyreptile";
            $args['nofeedgold'] = "`6\"`^Not enough money for food.`6\"";
            $args['schemas']['nofeedgold'] = "module-racespecialtyreptile";
            $args['confirmsale'] = "`n`n`6Are you sure you wish to sell this steed?";
            $args['schemas']['confirmsale'] = "module-racespecialtyreptile";
            $args['mountsold'] = "`6You release the mount to the swamps.";
            $args['schemas']['mountsold'] = "module-racespecialtyreptile";
            $args['offer'] = "`n`n`6\"`^I can offer you `&%s`6 gold and `%%s`6 gems for that mount,`6\" says ".get_module_setting("stableowner").".";
            $args['schemas']['offer'] = "module-racespecialtyreptile";
            break;

        case "stablelocs":
            $travel_mod = racespecialtyreptile_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "dragonkill":
            set_module_pref("uses", 0);
            set_module_pref("skill", 0);
            break;

        case "choose-specialty":
            if ($userSpec === "" || $userSpec === '0') {
                if ($userRace === $race) { 
                    $can_select = true;

                    if ($can_select) {
                        addnav("$ccode$name`0", "runmodule.php?module=racespecialtyreptile&spec=".$spec.$resline);
                        $t1 = translate_inline("Adapting your biology to survive the swamps"); 
                        $t2 = appoencode(translate_inline("$ccode$name`0"));
                        rawoutput("<a href='runmodule.php?module=racespecialtyreptile&spec=".$spec.$resline."'>$t1 ($t2)</a><br>");
                        addnav("", "runmodule.php?module=racespecialtyreptile&spec=".$spec.$resline);
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
                
                output("`#`c`bReptilian Adaptations Unlocked`b`c`n");
                output("`@Your blood runs cold. Your body shifts, adapting to the specialized path of a Lizard, Snake, or Amphibian!`n");
            }
            break;

        case "fightnav-specialties":
            if ($userRace === $race && $userSpec === $spec) {
                $stuff = racespecialtyreptile_texts(get_module_pref("class"), get_module_pref("subclass"));
                $uses = (int)get_module_pref("uses");
                $script = $args['script'] ?? '';
                if ($uses > 0) {
                    addnav(["$ccode ".racespecialtyreptile_namer()." (%s points)`0", $uses], "");
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
            $l = (int)httpget('l');
            $stuff = racespecialtyreptile_texts(get_module_pref("class"), get_module_pref("subclass"));
            if ($skill === $spec) {
                if ((int)get_module_pref("uses") >= $l) {
                    if (isset($stuff[$l])) {
                        $buff_to_apply = $stuff[$l];
                        apply_buff($spec.$l, $buff_to_apply);
                    } else {
                        apply_buff($spec."0", [
                            "startmsg" => "Your scales dry up. Nothing happens.", 
                            "rounds" => 1,
                            "schema" => "racespecialtyreptile"
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
            if (($session['user']['dragonkills'] ?? 0) >= (int)get_module_setting("dklimit")) {
                $args[$spec] = translate_inline($name);
            }
            break;

        case "specialtymodules":
            $args[$spec] = "racespecialtyreptile";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;

        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userRace === $race && $userSpec === $spec) {
                $nstr = "`%".racespecialtyreptile_namer("Y");
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function racespecialtyreptile_run() {
    global $session;
    $op = httpget('op');
    $spec = httpget('spec');
    $resline = httpget('resline');
    
    if ($op == "") {
        page_header("Choose Reptile Specialty Path"); 
        output("`c`b`#Choose your Reptilian Adaptation Path`b`c`n"); 
        output("`@As a Reptile, you can adapt your physiology to three paths. Choose your subclass path below:`n`n"); 
        
        addnav("Choose Path"); 
        addnav("Lizard Path (Magic)", "newday.php?setspecialty=$spec&mc=magic&sc=1$resline"); 
        addnav("Snake Path (Melee)", "newday.php?setspecialty=$spec&mc=melee&sc=2$resline"); 
        addnav("Amphibian Path (Ranging)", "newday.php?setspecialty=$spec&mc=ranging&sc=3$resline"); 
    }
    page_footer();
}

function racespecialtyreptile_change() {
    global $session;
    $race = "Reptile"; 
    $spec = "RP"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['race'] ?? '') === $race && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".racespecialtyreptile_namer("Y");
        
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

function racespecialtyreptile_checkcity() {
    global $session, $SCRIPT_NAME;
    $race = "Reptile"; 
    $spec = "RP"; 
    $city = get_module_setting("villagename");
    
    if (get_module_pref("subclass") != 0 && ($session['user']['specialty'] ?? '') !== $spec) {
        set_module_pref("subclass", 0);
    }
    
    $travel_mod = racespecialtyreptile_get_travel_mod();
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

function racespecialtyreptile_namer($type='X') {
    $class = [];
    
    $class['magic']['1'] = "Lizard Magic"; 
    $class['melee']['2'] = "Snake Striking"; 
    $class['ranging']['3'] = "Amphibian Ranging"; 
    
    $class['1'] = "Lizard"; 
    $class['2'] = "Snake"; 
    $class['3'] = "Amphibian"; 
    
    if ($type === "X") {
        return $class[get_module_pref("class")][get_module_pref("subclass")] ?? '';
    } else {
        return $class[get_module_pref("subclass")] ?? '';
    }
}

function racespecialtyreptile_texts($class, $subclass) {
    global $session;
    $array = [];
    $rounds = 5;
    
    // Subclass 1: Lizard (Magic)
    $array['magic']['1']['1'] = [ 
        "startmsg" => "`@You spit a glob of corrosive acid at {badguy}! It melts their armor and weapons.`",
        "name" => "`@Acid Spit",
        "rounds" => $rounds,
        "badguyatkmod" => 0.70,
        "badguydefmod" => 0.70,
        "wearoff" => "The acid dissolves.",
    ];
    $array['magic']['1']['3'] = [ 
        "startmsg" => "`@You secrete a thick, healing slime over your wounds.`",
        "name" => "`@Regeneration",
        "rounds" => $rounds,
        "regen" => (int)($session['user']['level'] ?? 1) * 2,
        "effectmsg" => "`@The slime heals you for {damage} health.`",
        "effectnodmgmsg" => "`@You have no wounds to regenerate.`",
        "wearoff" => "The slime dries up.",
    ];
    
    // Subclass 2: Snake (Melee)
    $array['melee']['2']['1'] = [ 
        "startmsg" => "`@You strike {badguy} with your venomous fangs!`",
        "name" => "`@Venomous Strike",
        "rounds" => $rounds,
        "badguydefmod" => 0.80,
        "minioncount" => 1,
        "minbadguydamage" => 5,
        "maxbadguydamage" => 15,
        "effectmsg" => "`@The poison burns {badguy} for {damage} damage!`",
        "effectnodmgmsg" => "`@The poison fails to take hold.`",
        "wearoff" => "The poison runs its course.",
    ];
    $array['melee']['2']['3'] = [ 
        "startmsg" => "`@You wrap your tail tightly around {badguy}, crushing their ribs!`",
        "name" => "`@Tail Constrict",
        "rounds" => $rounds,
        "badguyatkmod" => 0.50,
        "atkmod" => 1.20,
        "wearoff" => "You release {badguy}.",
    ];
    
    // Subclass 3: Amphibian (Ranging)
    $array['ranging']['3']['1'] = [ 
        "startmsg" => "`@You whip your tongue out at {badguy}, snapping them back!`",
        "name" => "`@Tongue Lash",
        "rounds" => 1,
        "atkmod" => 1.50,
        "badguydamage" => (int)($session['user']['level'] ?? 1) * 3,
        "effectmsg" => "`@Your tongue hits {badguy} for {damage} damage!`",
    ];
    $array['ranging']['3']['3'] = [ 
        "startmsg" => "`@You blend into the surrounding vegetation, becoming invisible!`",
        "name" => "`@Chameleon Camo",
        "rounds" => $rounds,
        "defmod" => 1.60,
        "wearoff" => "Your skin returns to its normal color.",
    ];
    
    if (($session['user']['race'] ?? '') === 'Reptile') { 
        if (isset($array[$class][$subclass])) {
            foreach ($array[$class][$subclass] as $t => $d) {
                $array[$class][$subclass][$t]['schema'] = "racespecialtyreptile";
            }
            return $array[$class][$subclass];
        }
    }
    return [];
}

function racespecialtyreptile_get_travel_mod() {
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
