<?php
/**
 * Advanced Specialty Module - Fuuton Ninjutsu
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtywind_getmoduleinfo() {
    return [
        "name" => "Specialty - Fuuton Ninjutsu",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Fuuton Ninjutsu - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|12",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Fuuton Ninjutsu Adze',note", 
        ],
        "prefs" => [
            "Fuuton Ninjutsu - User Prefs,title",
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|magic", 
            "subclass" => "Subclass,enum,Unset,0,Default,1|1", 
        ]
    ];
}

function specialtywind_install() {
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
    debug("Installed 'Fuuton Ninjutsu' specialty module.");
    return true;
}

function specialtywind_uninstall() {
    $spec = "WN"; 
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled 'Fuuton Ninjutsu' specialty module.");
    return true;
}

function specialtywind_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "WN"; 
    $name = "Fuuton Ninjutsu"; 
    $ccode = "`2"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    $techniques = [
        1 => ['name' => 'Kaze no Yoroi', 'cost' => 1, 'code' => 'wind1'],
        2 => ['name' => 'Kamaitachi no Jutsu', 'cost' => 3, 'code' => 'wind2'],
        3 => ['name' => 'Kaze Kiri', 'cost' => 6, 'code' => 'wind3'],
        4 => ['name' => 'Daikamaitachi no Jutsu', 'cost' => 10, 'code' => 'wind4'],
        5 => ['name' => 'Kaze no Yaiba', 'cost' => 15, 'code' => 'wind5'],
        6 => ['name' => 'Fuuton - Tatsu no Oshigoto', 'cost' => 18, 'code' => 'wind6'],
        7 => ['name' => 'Renkuudan', 'cost' => 20, 'code' => 'renkuudan'],
    ];
    
    switch($hookname) {
        case "newday-intercept":
            specialtywind_change();
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
                    $t1 = translate_inline("Train in Fuuton Ninjutsu"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                    addnav("", "newday.php?setspecialty=$spec$resline");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                output("`#`c`bFuuton Ninjutsu Unlocked`b`c`n");
                output("`@You have unlocked the secrets of Fuuton Ninjutsu! Focus your chakra and unleash your Jutsu in combat.`n");
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
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Kaze no Yoroi')], $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses >= 3) {
                    addnav(["`@ &#149; %s`@ (3)`0", translate_inline('Kamaitachi no Jutsu')], $script."op=fight&skill=$spec&l=2", true);
                }
                if ($uses >= 6) {
                    addnav(["`@ &#149; %s`@ (6)`0", translate_inline('Kaze Kiri')], $script."op=fight&skill=$spec&l=3", true);
                }
                if ($uses >= 10) {
                    addnav(["`@ &#149; %s`@ (10)`0", translate_inline('Daikamaitachi no Jutsu')], $script."op=fight&skill=$spec&l=4", true);
                }
                if ($uses >= 15) {
                    addnav(["`@ &#149; %s`@ (15)`0", translate_inline('Kaze no Yaiba')], $script."op=fight&skill=$spec&l=5", true);
                }
                if ($uses >= 18) {
                    addnav(["`@ &#149; %s`@ (18)`0", translate_inline('Fuuton - Tatsu no Oshigoto')], $script."op=fight&skill=$spec&l=6", true);
                }
                if ($uses >= 20 && $session['user']['hashorse']==12) {
                    addnav(["`@ &#149; %s`@ (20)`0", translate_inline('Renkuudan')], $script."op=fight&skill=$spec&l=7", true);
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
case "wind1":
			apply_buff('wind1',array(
				"startmsg"=>"`i`2Kaze no Yoroi!`i`n`qYou `gcreate a barrier of wind 
around yourself.`b",
				"name"=>"`2Kaze no Yoroi",
				"rounds"=>5,
				"wearoff"=>"The wind that surrounds you settles.",
				"defmod"=>1.05,
				"roundmsg"=>"You are protected by the wind!",
				"schema"=>"module-specialtysystem_wind"
			));
			break;
		case "wind2":
			apply_buff('wind2',array(
				"startmsg"=>"`i`2Kamaitachi no Jutsu!`i`n`qYou `gswing your weapon and 
create a windstorm that slices through everything in the area.",
				"name"=>"`2Kamaitachi `gno `2Jutsu",
				"rounds"=>3,
				"wearoff"=>"The windstorm settles.",
				"areadamage"=>true,
				"minbadguydamage"=>5+$session['user']['dragonkills'],
				"maxbadguydamage"=>15+$session['user']['dragonkills'],
				"minioncount"=>3,
				"effectmsg"=>"{badguy} suffers {damage} damage from cuts!",
				"schema"=>"module-specialtysystem_wind"
			));
			break;
		case "wind3":
			apply_buff('wind3',array(
				"startmsg"=>"`i`2K`ga`2z`ge `2K`gi`2r`gi!`i`n`qYou `gswing your weapon 
and send a blade of wind at {badguy}.",
				"name"=>"`2K`ga`2z`ge `2K`gi`2r`gi",
				"rounds"=>1,
				"minbadguydamage"=>15+$session['user']['dragonkills'],
				"maxbadguydamage"=>45+$session['user']['dragonkills'],
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from the cutting wind!",
				"schema"=>"module-specialtysystem_wind"
			));
			break;
		case "wind4":
			apply_buff('wind4',array(
				"startmsg"=>"`i`2Daikamaitachi no Jutsu!`i`n`qYou `gswing your weapon 
and create a huge windstorm that slices through everything in the area.",
				"name"=>"`2Daikamaitachi `gno `2Jutsu",
				"rounds"=>5,
				"wearoff"=>"The windstorm settles.",
				"areadamage"=>true,
				"minbadguydamage"=>15+$session['user']['dragonkills'],
				"maxbadguydamage"=>30+$session['user']['dragonkills'],
				"minioncount"=>3,
				"effectmsg"=>"{badguy} suffers {damage} damage from cuts!",
				"schema"=>"module-specialtysystem_wind"
			));
			break;
		case "wind5":
			apply_buff('wind5',array(
				"startmsg"=>"`i`2Kaze `gno `2Yaiba!`i`n`qYou `gsend an unstoppable blade 
of wind at {badguy}.",
				"name"=>"`2Kaze `gno `2Yaiba",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>80+$session['user']['dragonkills'],
				"maxbadguydamage"=>120+$session['user']['dragonkills'],
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from the cutting wind!",
				"schema"=>"module-specialtysystem_wind"
			));
			break;
		case "wind6":
			apply_buff('wind6',array(
				"startmsg"=>"`i`gFuuton - `2Tatsu `gno `2Oshigoto!`i`n`qYou `gsummon a 
powerful tornado from the sky.",
				"name"=>"`gFuuton - `2Tatsu `gno `2Oshigoto",
				"rounds"=>5,
				"wearoff"=>"The tornado settles.",
				"areadamage"=>true,
				"minbadguydamage"=>50+$session['user']['dragonkills'],
				"maxbadguydamage"=>90+$session['user']['dragonkills'],
				"minioncount"=>5,
				"effectmsg"=>"{badguy} suffers {damage} damage from being torn apart by 
the violent wind!",
				"schema"=>"module-specialtysystem_wind"
			));
			break;
		case "renkuudan":
			apply_buff('renkuudan',array(
				"startmsg"=>"`i`lRen`gku`ldan!`i`n`q`6Shukaku `ltakes a deep breath 
and shoots a large ball of compressed air and chakra at {badguy}!",
				"name"=>"`lRen`gku`ldan",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>120+$session['user']['dragonkills']*5,
				"maxbadguydamage"=>160+$session['user']['dragonkills']*5,
				"minioncount"=>1,
				"effectmsg"=>"`q{badguy}`q suffers {damage} damage!",
				"schema"=>"module-specialtysystem_wind"
			));
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
            $args[$spec] = "specialtywind";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;
            
        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userSpec === $spec) {
                $nstr = "`%" . specialtywind_namer("Y");
                
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function specialtywind_run() {
}

function specialtywind_change() {
    global $session;
    $spec = "WN"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtywind_namer("Y");
        
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

function specialtywind_namer($type='X') {
    $class = [];
    $class['1'] = "Fuuton Ninjutsu";
    return $class['1'];
}
?>