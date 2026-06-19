<?php
/**
 * Advanced Specialty Module - Suna Ninjutsu
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtysand_getmoduleinfo() {
    return [
        "name" => "Specialty - Suna Ninjutsu",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Suna Ninjutsu - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|6",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Suna Ninjutsu Adze',note", 
        ],
        "prefs" => [
            "Suna Ninjutsu - User Prefs,title",
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|magic", 
            "subclass" => "Subclass,enum,Unset,0,Default,1|1", 
        ]
    ];
}

function specialtysand_install() {
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
    debug("Installed 'Suna Ninjutsu' specialty module.");
    return true;
}

function specialtysand_uninstall() {
    $spec = "SD"; 
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled 'Suna Ninjutsu' specialty module.");
    return true;
}

function specialtysand_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "SD"; 
    $name = "Suna Ninjutsu"; 
    $ccode = "`6"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    $techniques = [
        1 => ['name' => 'Suna Shuriken', 'cost' => 1, 'code' => 'sand1'],
        2 => ['name' => 'Suna no Yoroi', 'cost' => 3, 'code' => 'sand2'],
        3 => ['name' => 'Suna Bunshin', 'cost' => 5, 'code' => 'sand3'],
        4 => ['name' => 'Suna no Tate', 'cost' => 10, 'code' => 'sand4'],
        5 => ['name' => 'Ryuusa Bakuryuu', 'cost' => 15, 'code' => 'sand5'],
        6 => ['name' => 'Sabaku Kyuu', 'cost' => 18, 'code' => 'sand6'],
        7 => ['name' => 'Sabaku Sousou', 'cost' => 20, 'code' => 'sand7'],
    ];
    
    switch($hookname) {
        case "specialty-codex":
            $args[$spec] = [
                'name' => $name,
                'color' => $ccode,
                'desc' => 'A powerful discipline of elemental Ninjutsu.',
                'techniques' => [
                    1 => ['name' => 'Suna Shuriken', 'cost' => 1, 'desc' => '"Suna Shuriken!You throw shuriken made from sand." Deals damage to all enemies, summons helper minion.'],
                    2 => ['name' => 'Suna no Yoroi', 'cost' => 3, 'desc' => '"Suna no Yoroi!You cover yourself in a compacted layer of sand, providing you with additional defense." Boosts defense, lasts 5 rounds.'],
                    3 => ['name' => 'Suna Bunshin', 'cost' => 5, 'desc' => '"Suna Bunshin!You create a clone out of sand." Deals direct damage, summons helper minion, lasts 10 rounds.'],
                    4 => ['name' => 'Suna no Tate', 'cost' => 10, 'desc' => '"Suna no Tate!You surround and protect yourself with sand." Boosts attack power, boosts defense, lasts 10 rounds.'],
                    5 => ['name' => 'Ryuusa Bakuryuu', 'cost' => 15, 'desc' => '"Ryuusa Bakuryuu!You create a massive amount of sand and send it towards {badguy} in the form of a wave." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                    6 => ['name' => 'Sabaku Kyuu', 'cost' => 18, 'desc' => '"Sabaku Kyuu!You cover {badguy}" Lasts 5 rounds.'],
                    7 => ['name' => 'Sabaku Sousou', 'cost' => 20, 'desc' => '"Sabaku Sousou!You cover {badguy}" Deals direct damage, summons helper minion.'],
                ]
            ];
            break;

        case "newday-intercept":
            specialtysand_change();
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

                if (($session['user']['dragonkills'] ?? 0) < (int)get_module_setting("dklimit")) {
                    $can_select = false;
                }

                if ($can_select) {
                    addnav("$ccode$name`0", "newday.php?setspecialty=".$spec."$resline");
                    $t1 = translate_inline("Train in Suna Ninjutsu"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                    addnav("", "newday.php?setspecialty=$spec$resline");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                output("`#`c`bSuna Ninjutsu Unlocked`b`c`n");
                output("`@You have unlocked the secrets of Suna Ninjutsu! Focus your chakra and unleash your Jutsu in combat.`n");
            }
            break;

        case "fightnav-specialties":
            if ($userSpec === $spec) {
                $uses = (int)get_module_pref("uses");
                $script = $args['script'] ?? '';
                
                if ($uses > 0) {
                    addnav(["$ccode $name (%s points)`0", $uses], "");
                }
                
                if ($uses >= 1) {
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Suna Shuriken')], $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses >= 3) {
                    addnav(["`@ &#149; %s`@ (3)`0", translate_inline('Suna no Yoroi')], $script."op=fight&skill=$spec&l=2", true);
                }
                if ($uses >= 5) {
                    addnav(["`@ &#149; %s`@ (5)`0", translate_inline('Suna Bunshin')], $script."op=fight&skill=$spec&l=3", true);
                }
                if ($uses >= 10) {
                    addnav(["`@ &#149; %s`@ (10)`0", translate_inline('Suna no Tate')], $script."op=fight&skill=$spec&l=4", true);
                }
                if ($uses >= 15) {
                    addnav(["`@ &#149; %s`@ (15)`0", translate_inline('Ryuusa Bakuryuu')], $script."op=fight&skill=$spec&l=5", true);
                }
                if ($uses >= 18) {
                    addnav(["`@ &#149; %s`@ (18)`0", translate_inline('Sabaku Kyuu')], $script."op=fight&skill=$spec&l=6", true);
                }
                if ($uses >= 20) {
                    addnav(["`@ &#149; %s`@ (20)`0", translate_inline('Sabaku Sousou')], $script."op=fight&skill=$spec&l=7", true);
                }
            }
            break;

        case "apply-specialties":
            $skill = httpget('skill');
            $l = (int)httpget('l');
            
            if ($skill === $spec) {
                $cost = $techniques[$l]['cost'] ?? 0;
                if ((int)get_module_pref("uses") >= $cost) {
                    $u = &$session['user']; // define $u for compatibility with raw buffs
                    $code = $techniques[$l]['code'] ?? '';
                    switch ($code) {
case "sand1":
			apply_buff('sand1',array(
				"startmsg"=>"`6`iSuna Shuriken`i!`n`qYou `Qthrow shuriken made from sand.",
				"name"=>"`6Suna Shuriken",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>5+min($session['user']['dragonkills'],25),
				"maxbadguydamage"=>10+min($session['user']['dragonkills'],25),
				"minioncount"=>3,
				"effectmsg"=>"{badguy} suffers {damage} damage from the shuriken!",
				"effectnodmgmsg"=>"The shuriken misses!",
				"schema"=>"module-specialtysystem_sand"
			));
			break;
		case "sand2":
			apply_buff('sand2',array(
				"startmsg"=>"`6`iSuna no Yoroi`i!`n`qYou `Qcover yourself in a compacted layer of sand, providing you with additional defense.",
				"name"=>"`6Suna no Yoroi",
				"rounds"=>5,
				"wearoff"=>"The compacted layer of sand falls apart.",
				"defmod"=>2,
				"roundmsg"=>"Your sand armor covers you!",
				"schema"=>"module-specialtysystem_sand"
			));
			break;
		case "sand3": //to be revised
			apply_buff('sand3',array(
				"startmsg"=>"`6`iSuna Bunshin`i!`n`qYou `Qcreate a clone out of sand.",
				"name"=>"`6Suna Bunshin",
				"rounds"=>10,
				"wearoff"=>"The clone falls apart.",
				"minbadguydamage"=>5+$session['user']['dragonkills'],
				"maxbadguydamage"=>15+$session['user']['dragonkills'],
				"minioncount"=>1,
				"effectmsg"=>"Your clone hit {badguy} for {damage} damage!",
				"effectnodmgmsg"=>"Your clone misses!",
				"schema"=>"module-specialtysystem_sand"
			));
			break;
		case "sand4":
			apply_buff('sand4',array(
				"startmsg"=>"`6`iSuna no Tate`i!`n`qYou `Qsurround and protect yourself with sand.",
				"name"=>"`6Suna no Tate",
				"rounds"=>10,
				"wearoff"=>"You lost the protection of the sand.",
				"atkmod"=>0.5,
				"defmod"=>3,
				"roundmsg"=>"Your shield gives you ample protection",
				"schema"=>"module-specialtysystem_sand"
			));
			break;
		case "sand5":
			apply_buff('sand5',array(
				"startmsg"=>"`6`iRyuusa Bakuryuu`i!`n`qYou `Qcreate a massive amount of sand and send it towards {badguy} in the form of a wave.",
				"name"=>"`6Ryuusa Bakuryuu",
				"rounds"=>5,
				"areadamage"=>true,
				"wearoff"=>"The wave left the entire forest covered in sand.",
				"minbadguydamage"=>30+$session['user']['dragonkills'],
				"maxbadguydamage"=>60+$session['user']['dragonkills'],
				"minioncount"=>6,
				"effectmsg"=>"{badguy} was swallowed by the sand and suffers {damage} damage!",
				"effectnodmgmsg"=>"The sand only rushes by {badguy}!",
				"schema"=>"module-specialtysystem_sand"
			));
			break;
		case "sand6":
			apply_buff('sand6',array(
				"startmsg"=>"`6`iSabaku Kyuu`i!`n`qYou `Qcover {badguy}'s entire body in sand.",
				"name"=>"`6Sabaku Kyuu",
				"rounds"=>5,
				"wearoff"=>"The sand falls apart.",
				"badguyatkmod"=>0,
				"badguydefmod"=>0,
				"roundmsg"=>"The sand renders {badguy} immobile!",
				"schema"=>"module-specialtysystem_sand"
			));
			break;
		case "sand7":
			apply_buff('sand7',array(
				"startmsg"=>"`6`iSabaku Sousou`i!`n`qYou `Qcover {badguy}'s entire body in sand and implode, crushing whatever is within",
				"name"=>"`6Sabaku Sousou",
				"rounds"=>1,
				"minbadguydamage"=>150+$session['user']['dragonkills']*4,
				"maxbadguydamage"=>180+$session['user']['dragonkills']*4,
				"minioncount"=>1,
				"effectmsg"=>"{badguy} was crushed and suffers {damage} damage!",
				"schema"=>"module-specialtysystem_sand"
			));
			break;
                    }
                    set_module_pref("uses", (int)get_module_pref("uses") - $cost);
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
            $args[$spec] = "specialtysand";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;
            
        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userSpec === $spec) {
                $nstr = "`%" . specialtysand_namer("Y");
                
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function specialtysand_run() {
}

function specialtysand_change() {
    global $session;
    $spec = "SD"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtysand_namer("Y");
        
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

function specialtysand_namer($type='X') {
    $class = [];
    $class['1'] = "Suna Ninjutsu";
    return $class['1'];
}
?>