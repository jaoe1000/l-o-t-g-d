<?php
/**
 * Advanced Specialty Module - Sand Magic
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtysand_getmoduleinfo() {
    return [
        "name" => "Specialty - Sand Magic",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Sand Magic - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|6",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Sand Magic Adze',note", 
        ],
        "prefs" => [
            "Sand Magic - User Prefs,title",
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
    debug("Installed 'Sand Magic' specialty module.");
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
    
    debug("Uninstalled 'Sand Magic' specialty module.");
    return true;
}

function specialtysand_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "SD"; 
    $name = "Sand Magic"; 
    $ccode = "`6"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    $techniques = [
        1 => ['name' => 'Sand Daggers', 'cost' => 1, 'code' => 'sand1'],
        2 => ['name' => 'Earthen Shell', 'cost' => 3, 'code' => 'sand2'],
        3 => ['name' => 'Sand Clone', 'cost' => 5, 'code' => 'sand3'],
        4 => ['name' => 'Aegis of Sand', 'cost' => 10, 'code' => 'sand4'],
        5 => ['name' => 'Quicksand Torrent', 'cost' => 15, 'code' => 'sand5'],
        6 => ['name' => 'Desert Tomb', 'cost' => 18, 'code' => 'sand6'],
        7 => ['name' => 'Sand Crush', 'cost' => 20, 'code' => 'sand7'],
    ];
    
    switch($hookname) {
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
                    $t1 = translate_inline("Train in Sand Magic"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                    addnav("", "newday.php?setspecialty=$spec$resline");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                output("`#`c`bSand Magic Unlocked`b`c`n");
                output("`@You have unlocked the secrets of Sand Magic! Focus your mana and unleash your spells in combat.`n");
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
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Sand Daggers')], $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses >= 3) {
                    addnav(["`@ &#149; %s`@ (3)`0", translate_inline('Earthen Shell')], $script."op=fight&skill=$spec&l=2", true);
                }
                if ($uses >= 5) {
                    addnav(["`@ &#149; %s`@ (5)`0", translate_inline('Sand Clone')], $script."op=fight&skill=$spec&l=3", true);
                }
                if ($uses >= 10) {
                    addnav(["`@ &#149; %s`@ (10)`0", translate_inline('Aegis of Sand')], $script."op=fight&skill=$spec&l=4", true);
                }
                if ($uses >= 15) {
                    addnav(["`@ &#149; %s`@ (15)`0", translate_inline('Quicksand Torrent')], $script."op=fight&skill=$spec&l=5", true);
                }
                if ($uses >= 18) {
                    addnav(["`@ &#149; %s`@ (18)`0", translate_inline('Desert Tomb')], $script."op=fight&skill=$spec&l=6", true);
                }
                if ($uses >= 20) {
                    addnav(["`@ &#149; %s`@ (20)`0", translate_inline('Sand Crush')], $script."op=fight&skill=$spec&l=7", true);
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
				"startmsg"=>"`6`iSand Daggers`i!`n`qYou `Qthrow sharp daggers made from sand.",
				"name"=>"`6Sand Daggers",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>5+min($session['user']['dragonkills'],25),
				"maxbadguydamage"=>10+min($session['user']['dragonkills'],25),
				"minioncount"=>3,
				"effectmsg"=>"{badguy} suffers {damage} damage from the daggers!",
				"effectnodmgmsg"=>"The daggers miss!",
				"schema"=>"module-specialtysystem_sand"
			));
			break;
		case "sand2":
			apply_buff('sand2',array(
				"startmsg"=>"`6`iEarthen Shell`i!`n`qYou `Qcover yourself in a compacted shell of sand, providing you with additional defense.",
				"name"=>"`6Earthen Shell",
				"rounds"=>5,
				"wearoff"=>"The compacted layer of sand falls apart.",
				"defmod"=>2,
				"roundmsg"=>"Your sand armor covers you!",
				"schema"=>"module-specialtysystem_sand"
			));
			break;
		case "sand3": //to be revised
			apply_buff('sand3',array(
				"startmsg"=>"`6`iSand Clone`i!`n`qYou `Qcreate a simulacrum out of sand.",
				"name"=>"`6Sand Clone",
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
				"startmsg"=>"`6`iAegis of Sand`i!`n`qYou `Qsurround and protect yourself with a shifting aegis of sand.",
				"name"=>"`6Aegis of Sand",
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
				"startmsg"=>"`6`iQuicksand Torrent`i!`n`qYou `Qcreate a massive amount of sand and send it towards {badguy} in a crushing wave.",
				"name"=>"`6Quicksand Torrent",
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
				"startmsg"=>"`6`iDesert Tomb`i!`n`qYou `Qencase {badguy}'s entire body in heavy sand.",
				"name"=>"`6Desert Tomb",
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
				"startmsg"=>"`6`iSand Crush`i!`n`qYou `Qcover {badguy}'s entire body in sand and implode it, crushing whatever is within.",
				"name"=>"`6Sand Crush",
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
    $class['1'] = "Sand Magic";
    return $class['1'];
}
?>