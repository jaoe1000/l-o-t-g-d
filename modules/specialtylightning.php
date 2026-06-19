<?php
/**
 * Advanced Specialty Module - Rai Ninjutsu
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtylightning_getmoduleinfo() {
    return [
        "name" => "Specialty - Rai Ninjutsu",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Rai Ninjutsu - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|0",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Rai Ninjutsu Adze',note", 
        ],
        "prefs" => [
            "Rai Ninjutsu - User Prefs,title",
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|magic", 
            "subclass" => "Subclass,enum,Unset,0,Default,1|1", 
        ]
    ];
}

function specialtylightning_install() {
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
    debug("Installed 'Rai Ninjutsu' specialty module.");
    return true;
}

function specialtylightning_uninstall() {
    $spec = "LT"; 
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled 'Rai Ninjutsu' specialty module.");
    return true;
}

function specialtylightning_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "LT"; 
    $name = "Rai Ninjutsu"; 
    $ccode = "`t"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    $techniques = [
        1 => ['name' => 'Raigeki no Yoroi', 'cost' => 1, 'code' => 'lightning1'],
        2 => ['name' => 'Raikyuu', 'cost' => 3, 'code' => 'lightning2'],
        3 => ['name' => 'Raiton: Hiraishin', 'cost' => 6, 'code' => 'lightning3'],
        4 => ['name' => 'Ikazuchi no Kiba', 'cost' => 10, 'code' => 'lightning4'],
        5 => ['name' => 'Raizou Ikazuchi wo Utte', 'cost' => 15, 'code' => 'lightning5'],
        6 => ['name' => 'Raiton: Kaminari Shibari', 'cost' => 16, 'code' => 'lightning6'],
        7 => ['name' => 'Rairyuu no Tatsumaki', 'cost' => 18, 'code' => 'lightning7'],
        8 => ['name' => 'Ikazuchi Hakai', 'cost' => 23, 'code' => 'lightning8'],
    ];
    
    switch($hookname) {
        case "specialty-codex":
            $args[$spec] = [
                'name' => $name,
                'color' => $ccode,
                'desc' => 'A powerful discipline of elemental Ninjutsu.',
                'techniques' => [
                    1 => ['name' => 'Raigeki no Yoroi', 'cost' => 1, 'desc' => '"Raigeki no Yoroi!You surround yourself with electricity." Lasts 5 rounds.'],
                    2 => ['name' => 'Raikyuu', 'cost' => 3, 'desc' => '"Raikyuu!You create a ball of electrical energy and launch it at {badguy}." Deals direct damage, summons helper minion, lasts 10 rounds.'],
                    3 => ['name' => 'Raiton: Hiraishin', 'cost' => 6, 'desc' => '"Raiton: Hiraishin!You summon lightning from the sky to your hand and then shoot it at your opponent." Deals direct damage, summons helper minion.'],
                    4 => ['name' => 'Ikazuchi no Kiba', 'cost' => 10, 'desc' => '"Ikazuchi no Kiba!You send an electrical essence into the clouds, allowing you to create lightning strikes." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                    5 => ['name' => 'Raizou Ikazuchi wo Utte', 'cost' => 15, 'desc' => '"Raizou Ikazuchi wo Utte!You create several thunderbolts that cut through the ground and chase after {badguy}." Deals direct damage, summons helper minion, lasts 10 rounds.'],
                    6 => ['name' => 'Raiton: Kaminari Shibari', 'cost' => 16, 'desc' => '"Raiton: Kaminari Shibari! You create a three sided wall of electricity to bind {badguy}." Deals direct damage, summons helper minion, lasts 3 rounds.'],
                    7 => ['name' => 'Rairyuu no Tatsumaki', 'cost' => 18, 'desc' => '"Rairyuu no Tatsumaki!You spin around very quickly, forming the electricity around you into a likeness of a dragon" Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                    8 => ['name' => 'Ikazuchi Hakai', 'cost' => 23, 'desc' => '"Ikazuchi Hakai!You place your hands on the ground and send an enormous bolt of lightning that cuts through the ground towards {badguy}." Deals damage to all enemies, summons helper minion.'],
                ]
            ];
            break;

        case "newday-intercept":
            specialtylightning_change();
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
                    $t1 = translate_inline("Train in Rai Ninjutsu"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                    addnav("", "newday.php?setspecialty=$spec$resline");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                output("`#`c`bRai Ninjutsu Unlocked`b`c`n");
                output("`@You have unlocked the secrets of Rai Ninjutsu! Focus your chakra and unleash your Jutsu in combat.`n");
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
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Raigeki no Yoroi')], $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses >= 3) {
                    addnav(["`@ &#149; %s`@ (3)`0", translate_inline('Raikyuu')], $script."op=fight&skill=$spec&l=2", true);
                }
                if ($uses >= 6) {
                    addnav(["`@ &#149; %s`@ (6)`0", translate_inline('Raiton: Hiraishin')], $script."op=fight&skill=$spec&l=3", true);
                }
                if ($uses >= 10) {
                    addnav(["`@ &#149; %s`@ (10)`0", translate_inline('Ikazuchi no Kiba')], $script."op=fight&skill=$spec&l=4", true);
                }
                if ($uses >= 15) {
                    addnav(["`@ &#149; %s`@ (15)`0", translate_inline('Raizou Ikazuchi wo Utte')], $script."op=fight&skill=$spec&l=5", true);
                }
                if ($uses >= 16) {
                    addnav(["`@ &#149; %s`@ (16)`0", translate_inline('Raiton: Kaminari Shibari')], $script."op=fight&skill=$spec&l=6", true);
                }
                if ($uses >= 18) {
                    addnav(["`@ &#149; %s`@ (18)`0", translate_inline('Rairyuu no Tatsumaki')], $script."op=fight&skill=$spec&l=7", true);
                }
                if ($uses >= 23) {
                    addnav(["`@ &#149; %s`@ (23)`0", translate_inline('Ikazuchi Hakai')], $script."op=fight&skill=$spec&l=8", true);
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
case "lightning1":
			apply_buff('lightning1',array(
				"startmsg"=>"`i`tRaigeki no Yoroi!`i`n`qYou `vsurround yourself with electricity.`b",
				"name"=>"`tRaigeki no Yoroi",
				"rounds"=>5,
				"wearoff"=>"The electricity around you was neutralized.",
				"damageshield"=>0.20,
				"effectmsg"=>"{badguy} suffers {damage} damage from the electric shock!",
				"schema"=>"module-specialtysystem_lightning"
			));
			break;
		case "lightning2":
			apply_buff('lightning2',array(
				"startmsg"=>"`i`tRaikyuu!`i`n`qYou `vcreate a ball of electrical energy and launch it at {badguy}.",
				"name"=>"`tRaikyuu",
				"rounds"=>10,
				"wearoff"=>"The electricity in {badguy}'s body has neutralized.",
				"minbadguydamage"=>5,
				"maxbadguydamage"=>15,
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from the electric shock!",
				"schema"=>"module-specialtysystem_lightning"
			));
			break;
		case "lightning3":
			apply_buff('lightning3',array(
				"startmsg"=>"`i`t`^Raiton: `yHiraishin!`i`n`qYou `vsummon lightning from the sky to your hand and then shoot it at your opponent.",
				"name"=>"`^Raiton: `tHiraishin",
				"rounds"=>1,
				"minbadguydamage"=>60+$session['user']['dragonkills'],
				"maxbadguydamage"=>80+$session['user']['dragonkills'],
				"minioncount"=>1,
				"effectmsg"=>"The lightning hits {badguy}, doing {damage} damage!",
				"schema"=>"module-specialtysystem_lightning"
			));
			break;
		case "lightning4":
			apply_buff('lightning4',array(
				"startmsg"=>"`i`tIkazuchi no Kiba!`i`n`qYou `vsend an electrical essence into the clouds, allowing you to create lightning strikes.",
				"name"=>"`tIkazuchi no Kiba",
				"rounds"=>5,
				"areadamage"=>true,
				"wearoff"=>"The electrical essence in the clouds has neutralized.",
				"minbadguydamage"=>15+$session['user']['dragonkills'],
				"maxbadguydamage"=>30+$session['user']['dragonkills'],
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from the lightning strike!",
				"schema"=>"module-specialtysystem_lightning"
			));
			break;
		case "lightning5":
			apply_buff('lightning5',array(
				"startmsg"=>"`i`tRaizou Ikazuchi wo Utte!`i`n`qYou `vcreate several thunderbolts that cut through the ground and chase after {badguy}.",
				"name"=>"`tRaizou Ikazuchi wo Utte",
				"rounds"=>10,
				"wearoff"=>"The thunderbolts were neutralized.",
				"minbadguydamage"=>15+$session['user']['dragonkills'],
				"maxbadguydamage"=>30+$session['user']['dragonkills'],
				"minioncount"=>e_rand(1,4),
				"effectmsg"=>"{badguy} suffers {damage} damage from the rushing current!",
				"effectnodmgmsg"=>"{badguy} manages to dodge the thunderbolts!",
				"schema"=>"module-specialtysystem_lightning"
			));
			break;
		case "lightning6":
			apply_buff('lightning6',array(
				"startmsg"=>"`i`^Raiton`i: `^K`taminari `^S`thibari! `i`n`qYou `vcreate a three sided wall of electricity to bind {badguy}.",
				"name"=>"`^Raiton: `^K`taminari `^S`thibari",
				"rounds"=>3,
				"wearoff"=>"The wall has been broken.",
				"invulnerable"=>true,
				"minbadguydamage"=>5,
				"maxbadguydamage"=>15,
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from touching the wall!",
				"schema"=>"module-specialtysystem_lightning"
			));
			break;
		case "lightning7":
			apply_buff('lightning7',array(
				"startmsg"=>"`i`tRairyuu no Tatsumaki!`i`n`qYou `vspin around very quickly, forming the electricity around you into a likeness of a dragon's head.",
				"name"=>"`tRairyuu no Tatsumaki",
				"rounds"=>5,
				"areadamage"=>true,
				"wearoff"=>"The electrical vortex has neutralized.",
				"minbadguydamage"=>30+$session['user']['dragonkills'],
				"maxbadguydamage"=>60+$session['user']['dragonkills'],
				"minioncount"=>e_rand(2,6),
				"effectmsg"=>"{badguy} suffers {damage} damage from painful vortex of electricity!",
				"effectnodmgmsg"=>"The electric current did not have any effect on {badguy}!",
				"schema"=>"module-specialtysystem_lightning"
			));
			break;
		case "lightning8":
			apply_buff('lightning8',array(
				"startmsg"=>"`^I`tkazuchi `^H`takai!`i`n`qYou `vplace your hands on the ground and send an enormous bolt of lightning that cuts through the ground towards {badguy}.`b",
				"name"=>"`^I`tkazuchi `^H`takai",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>180+$session['user']['dragonkills'],
				"maxbadguydamage"=>220+$session['user']['dragonkills'],
				"minioncount"=>1,
				"effectmsg"=>"The bolt of lightning causes devastating destruction on its path towards {badguy}, generated with heat and power that does {damage} damage!",
				"schema"=>"module-specialtysystem_lightning"
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
            $args[$spec] = "specialtylightning";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;
            
        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userSpec === $spec) {
                $nstr = "`%" . specialtylightning_namer("Y");
                
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function specialtylightning_run() {
}

function specialtylightning_change() {
    global $session;
    $spec = "LT"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtylightning_namer("Y");
        
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

function specialtylightning_namer($type='X') {
    $class = [];
    $class['1'] = "Rai Ninjutsu";
    return $class['1'];
}
?>