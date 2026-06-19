<?php
/**
 * Advanced Specialty Module - Doton Ninjutsu
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtyearth_getmoduleinfo() {
    return [
        "name" => "Specialty - Doton Ninjutsu",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Doton Ninjutsu - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|3",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Doton Ninjutsu Adze',note", 
        ],
        "prefs" => [
            "Doton Ninjutsu - User Prefs,title",
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|magic", 
            "subclass" => "Subclass,enum,Unset,0,Default,1|1", 
        ]
    ];
}

function specialtyearth_install() {
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
    debug("Installed 'Doton Ninjutsu' specialty module.");
    return true;
}

function specialtyearth_uninstall() {
    $spec = "ER"; 
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled 'Doton Ninjutsu' specialty module.");
    return true;
}

function specialtyearth_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "ER"; 
    $name = "Doton Ninjutsu"; 
    $ccode = "`q"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    $techniques = [
        1 => ['name' => 'Retsudotenshou', 'cost' => 1, 'code' => 'earth1'],
        2 => ['name' => 'Doroku Gaeshi', 'cost' => 3, 'code' => 'earth2'],
        3 => ['name' => 'Iwayado Kuzushi', 'cost' => 5, 'code' => 'earth3'],
        4 => ['name' => 'Doroudoumu', 'cost' => 10, 'code' => 'earth4'],
        5 => ['name' => 'Doryuu Dango', 'cost' => 15, 'code' => 'earth5'],
        6 => ['name' => 'Doryuuheki', 'cost' => 16, 'code' => 'earth6'],
        7 => ['name' => 'Doryuudan', 'cost' => 18, 'code' => 'earth7'],
    ];
    
    switch($hookname) {
        case "newday-intercept":
            specialtyearth_change();
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
                    $t1 = translate_inline("Train in Doton Ninjutsu"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                    addnav("", "newday.php?setspecialty=$spec$resline");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                output("`#`c`bDoton Ninjutsu Unlocked`b`c`n");
                output("`@You have unlocked the secrets of Doton Ninjutsu! Focus your chakra and unleash your Jutsu in combat.`n");
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
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Retsudotenshou')], $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses >= 3) {
                    addnav(["`@ &#149; %s`@ (3)`0", translate_inline('Doroku Gaeshi')], $script."op=fight&skill=$spec&l=2", true);
                }
                if ($uses >= 5) {
                    addnav(["`@ &#149; %s`@ (5)`0", translate_inline('Iwayado Kuzushi')], $script."op=fight&skill=$spec&l=3", true);
                }
                if ($uses >= 10) {
                    addnav(["`@ &#149; %s`@ (10)`0", translate_inline('Doroudoumu')], $script."op=fight&skill=$spec&l=4", true);
                }
                if ($uses >= 15) {
                    addnav(["`@ &#149; %s`@ (15)`0", translate_inline('Doryuu Dango')], $script."op=fight&skill=$spec&l=5", true);
                }
                if ($uses >= 16) {
                    addnav(["`@ &#149; %s`@ (16)`0", translate_inline('Doryuuheki')], $script."op=fight&skill=$spec&l=6", true);
                }
                if ($uses >= 18) {
                    addnav(["`@ &#149; %s`@ (18)`0", translate_inline('Doryuudan')], $script."op=fight&skill=$spec&l=7", true);
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
case "earth1":
			apply_buff('earth1',array(
				"startmsg"=>"`i`qDo`Qton `q-`t Restudotenshou!`i`n`qYou`Q attack {badguy} with nearby rocks.",
				"name"=>"`qRetsudotenshou",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>round(max($session['user']['level'],10)/3)+1,
				"maxbadguydamage"=>round(max($session['user']['level'],10)/3)+5,
				"minioncount"=>4,
				"effectmsg"=>"{badguy} suffers {damage} damage from the rocks!",
				"effectnodmgmsg"=>"The rocks barely hit!",
				"schema"=>"module-specialtysystem_earth"
			));
			break;
		case "earth2":
			apply_buff('earth2',array(
				"startmsg"=>"`i`qDo`Qton `q-`t Doroku Gaeshi!`i`n`qYou`Q create a large wall of earth.",
				"name"=>"`qDoroku Gaeshi",
				"rounds"=>5,
				"wearoff"=>"The wall breaks.",
				"defmod"=>1.5,
				"roundmsg"=>"`qThe wall protects you.",
				"schema"=>"module-specialtysystem_earth"
			));
			break;
		case "earth3":
			apply_buff('earth3',array(
				"startmsg"=>"`i`qDo`Qton `q-`t Iwayado Kuzushi!`i`n`QRocks are dislodged from above {badguy}.",
				"name"=>"`qIwayado Kuzushi",
				"rounds"=>1,
				"areadamage"=>true,
				"maxbadguydamage"=>round($session['user']['attack']*3,0),
				"minbadguydamage"=>round($session['user']['attack']*1.5,0),
				"minioncount"=>2,
				"effectmsg"=>"{badguy} suffers {damage} damage from falling a rock!",
				"effectnodmgmsg"=>"The rock misses!",
				"schema"=>"module-specialtysystem_earth"
			));
			break;
		case "earth4":
			apply_buff('earth4',array(
				"startmsg"=>"`i`qDo`Qton `q-`t Doroudoumu!`i`n`qYou`Q trap {badguy} inside a self-repairing dome of earth.",
				"name"=>"`qDoroudoumu",
				"rounds"=>10,
				"areadamage"=>true,
				"wearoff"=>"The dome falls apart.",
				"badguyatkmod"=>0.75,
				"minbadguydamage"=>5,
				"maxbadguydamage"=>10,
				"minioncount"=>1,
				"effectmsg"=>"{badguy} loses {damage} hitpoints!",
				"schema"=>"module-specialtysystem_earth"
			));
			break;
		case "earth5":
			apply_buff('earth5',array(
				"startmsg"=>"`i`qDo`Qton `q-`t Doryuu Dango!`i`n`qYou`Q hurl a large dumpling-shaped chunk of earth the size of a mausoleum at {badguy}.",
				"name"=>"`qDoryuu Dango",
				"rounds"=>1,
				"areadamage"=>true,
				"maxbadguydamage"=>round($session['user']['dragonkills']*3+$session['user']['level'],0)+50,
				"minbadguydamage"=>round($session['user']['dragonkills']*1.5+$session['user']['level'],0)+75,
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from being crushed!",
				"schema"=>"module-specialtysystem_earth"
			));
			break;
		case "earth6":
			apply_buff('earth6',array(
				"startmsg"=>"`i`qDo`Qton `q-`t Doryuuheki!`i`n`qYou`Q spit out a stream of mud that quickly grows and solidifies into a strong protective wall.",
				"name"=>"`qDoryuuheki",
				"rounds"=>10,
				"wearoff"=>"The wall breaks.",
				"defmod"=>2,
				"badguyatkmod"=>0.5,
				"effectnodmgmsg"=>"The wall protects you from harm!",
				"schema"=>"module-specialtysystem_earth"
			));
			break;
		case "earth7":
			apply_buff('earth7',array(
				"startmsg"=>"`i`qDo`Qton `q-`t Doryuudan!`i`n`qYou`Q create a likeness of a dragon's head that launches mud balls from its mouth at {badguy}.",
				"name"=>"`qDoryuudan",
				"rounds"=>1,
				"areadamage"=>true,
				"maxbadguydamage"=>round($session['user']['dragonkills']*3+$session['user']['level'],0)+55,
				"minbadguydamage"=>round($session['user']['dragonkills']*1.5+$session['user']['level'],0)+65,
				"minioncount"=>3,
				"effectmsg"=>"The mud ball impact {badguy} for {damage} damage!",
				"effectnodmgmsg"=>"The mud ball misses!",
				"schema"=>"module-specialtysystem_earth"
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
            $args[$spec] = "specialtyearth";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;
            
        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userSpec === $spec) {
                $nstr = "`%" . specialtyearth_namer("Y");
                
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function specialtyearth_run() {
}

function specialtyearth_change() {
    global $session;
    $spec = "ER"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtyearth_namer("Y");
        
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

function specialtyearth_namer($type='X') {
    $class = [];
    $class['1'] = "Doton Ninjutsu";
    return $class['1'];
}
?>