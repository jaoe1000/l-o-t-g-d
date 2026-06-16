<?php
/**
 * Advanced Specialty Blueprint (No Race/City Mechanics)
 * Designed for PHP 8.4 Strict Compliance
 * * Easy way to customize:
 * - Replace 'specialtytemplate' with module name (e.g., 'specialtymage')
 * - Search for '[PLACEHOLDER]' to find all text that needs customization.
 * - Search for '***CHANGE' to see structural variables that must be defined.
 */

function specialtytemplate_getmoduleinfo() {
    return [
        "name" => "Specialty - [PLACEHOLDER SPECIALTY NAME]", // ***CHANGE
        "version" => "1.0",
        "author" => "Dragon.NDGaming", // ***CHANGE
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "[PLACEHOLDER SPECIALTY NAME] - Settings,title", // ***CHANGE
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|3",
            "wepchange" => "Will weapons & armour change names?,bool|1", // ***CHANGE
            "IE- an 'Adze' would become a '[PLACEHOLDER SUBCLASS] Adze',note", 
        ],
        "prefs" => [
            "[PLACEHOLDER SPECIALTY NAME] - User Prefs,title", // ***CHANGE
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|", 
            "subclass" => "Sublass,enum,Unset,0,[PLACEHOLDER SUBCLASS 1],1,[PLACEHOLDER SUBCLASS 2],2,[PLACEHOLDER SUBCLASS 3],3|0", // ***CHANGE
        ]
    ];
}

function specialtytemplate_install() {
    module_addhook("choose-specialty");
    module_addhook("set-specialty");
    module_addhook("fightnav-specialties");
    module_addhook("apply-specialties");
    module_addhook("newday");
    module_addhook("incrementspecialty");
    module_addhook("specialtynames");
    module_addhook("specialtymodules");
    module_addhook("specialtycolor");
    module_addhook("dragonkill");
    module_addhook("newday-intercept");
	module_addhook("boughtweapon");
	module_addhook("boughtarmor");
    debug("Installed '[PLACEHOLDER SPECIALTY NAME]' specialty module."); // ***CHANGE
    return true;
}

function specialtytemplate_uninstall() {
    $spec = "XX"; // ***CHANGE: MUST BE A UNIQUE 2-LETTER CODE
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled '[PLACEHOLDER SPECIALTY NAME]' specialty module."); // ***CHANGE
    return true;
}

function specialtytemplate_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "XX"; // ***CHANGE: SAME 2-LETTER CODE AS UNINSTALL
    $name = "[PLACEHOLDER SPECIALTY NAME]"; // ***CHANGE
    $ccode = "`@"; // ***CHANGE: COLOR CODE
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    switch($hookname) {
        case "newday-intercept":
            specialtytemplate_change();
            break;

        case "newday":
            if ($userSpec === $spec) {
                $bonus = (int)getsetting("specialtybonus", 1);
                
                if ($bonus == 1) {
                    output("`n`2For your specialty in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n", $ccode, $name, $ccode, $name);
                } else {
                    output("`n`2For your specialty in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n", $ccode, $name, $bonus, $ccode, $name);
                }
                
                $amt = (int)(get_module_pref("skill") / 3);
                set_module_pref("uses", $amt + $bonus);
            }
            break;

        case "dragonkill":
            set_module_pref("uses", 0);
            set_module_pref("skill", 0);
            break;

        case "choose-specialty":
            if ($userSpec === "" || $userSpec === '0') {
                $can_select = true;

                if (($session['user']['dragonkills'] ?? 0) < get_module_setting("dklimit")) {
                    $can_select = false;
                }

                // UNCOMMENT TO RESTRICT SPECIALTY BY ALIGNMENT
                /*
                if (is_module_active("alignment")) {
                    $player_align = get_module_pref("alignment", "alignment");
                    if ($player_align != "evil") {
                        $can_select = false;
                    }
                }
                */

                if ($can_select) {
                    addnav("$ccode$name`0", "runmodule.php?module=specialtytemplate&spec=".$spec.$resline);
                    $t1 = translate_inline("[PLACEHOLDER CHOOSE SPECIALTY LINK TEXT]"); // ***CHANGE
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='runmodule.php?module=specialtytemplate&spec=".$spec.$resline."'>$t1 ($t2)</a><br>");
                    addnav("", "runmodule.php?module=specialtytemplate&spec=".$spec.$resline);
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
            if ($userSpec === $spec) {
                $stuff = specialtytemplate_texts(get_module_pref("class"), get_module_pref("subclass"));
                $uses = (int)get_module_pref("uses");
                $script = $args['script'] ?? '';
                
                if ($uses > 0) {
                    addnav(["$ccode ".specialtytemplate_namer()." (%s points)`0", $uses], "");
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
            $stuff = specialtytemplate_texts(get_module_pref("class"), get_module_pref("subclass"));
            
            if ($skill === $spec) {
                if ((int)get_module_pref("uses") >= $l) {
                    if (isset($stuff[$l])) {
                        $buff_to_apply = $stuff[$l];
                        apply_buff($spec.$l, $buff_to_apply);
                    } else {
                        apply_buff($spec."0", [
                            "startmsg" => "Your power fizzles and does nothing.", // ***CHANGE
                            "rounds" => 1,
                            "schema" => "specialtytemplate"
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
            $args[$spec] = "specialtytemplate";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;
		case "boughtweapon":
		case "boughtarmor":
			if (get_module_setting("wepchange") == 1 && ($session['user']['race'] ?? '') === $race) {
				$nstr = "`%" . racespecialtytemplate_namer("Y");
				$item = ($hookname === 'boughtweapon') ? 'weapon' : 'armor';
        
			// Prevent double stacking if the player buys multiple items in one day
			if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
				$args['name'] = $nstr . " " . $args['name'];
			}
			}
			break;
    }
    return $args;
}

function specialtytemplate_run() {
    global $session;
    $op = httpget('op');
    $spec = httpget('spec');
    $resline = httpget('resline');
    
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

function specialtytemplate_change() {
    global $session;
    $spec = "XX"; // ***CHANGE
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtytemplate_namer("Y");
        
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

function specialtytemplate_namer($type='X') {
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

function specialtytemplate_texts($class, $subclass) {
    global $session;
    $array = [];
    $spec = "XX"; // ***CHANGE
    
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
    // --- EXPANDED BUFF MECHANIC EXAMPLES ---
	/*
	$array['magic']['1']['4'] = [ 
		"startmsg" => "`!You cast a shield spell!",
		"name" => "`#Mana Shield",
		"rounds" => 5,
		"wearoff" => "Your magical barrier shatters.",
		"defmod" => 1.5, // Defense multiplier
		"damagemod" => 0.5, // Reduces incoming damage by half
	];

	$array['magic']['1']['5'] = [ 
		"startmsg" => "`!You drain the enemy's life force!",
		"name" => "`#Vampiric Touch",
		"rounds" => 3,
		"lifetap" => 0.5, // Heals player for 50% of the damage they deal
	];

	$array['magic']['1']['6'] = [
		"startmsg" => "`!You cast a rejuvenation spell!",
		"name" => "`#Healing Aura",
		"rounds" => 5,
		"regen" => "<level>", // Heals the player every round based on their level
	];
	*/
	
    $array['melee']['2']['1'] = [ 
        "startmsg" => "`!You enter a warrior's stance!",
        "name" => "`#Warrior Stance",
        "rounds" => 5,
        "wearoff" => "You lose your focus.",
        "effectmsg" => "`&You hit `^{badguy}`& harder than normal.",
        "atkmod" => 1.3,
    ];
    
    if (($session['user']['specialty'] ?? '') === $spec) { 
        if (isset($array[$class][$subclass])) {
            foreach ($array[$class][$subclass] as $t => $d) {
                $array[$class][$subclass][$t]['schema'] = "specialtytemplate";
            }
            return $array[$class][$subclass];
        }
    }
    
    return [];
}
?>