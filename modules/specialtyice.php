<?php
/**
 * Advanced Specialty Module - Hyouton Ninjutsu
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtyice_getmoduleinfo() {
    return [
        "name" => "Specialty - Hyouton Ninjutsu",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Hyouton Ninjutsu - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|8",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Hyouton Ninjutsu Adze',note", 
        ],
        "prefs" => [
            "Hyouton Ninjutsu - User Prefs,title",
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|magic", 
            "subclass" => "Subclass,enum,Unset,0,Default,1|1", 
        ]
    ];
}

function specialtyice_install() {
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
    debug("Installed 'Hyouton Ninjutsu' specialty module.");
    return true;
}

function specialtyice_uninstall() {
    $spec = "IC"; 
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled 'Hyouton Ninjutsu' specialty module.");
    return true;
}

function specialtyice_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "IC"; 
    $name = "Hyouton Ninjutsu"; 
    $ccode = "`l"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    $techniques = [
        1 => ['name' => 'Hyourou no Jutsu', 'cost' => 1, 'code' => 'ice1'],
        2 => ['name' => 'Tsubame Fubuki', 'cost' => 3, 'code' => 'ice2'],
        3 => ['name' => 'Haryuu Mouko', 'cost' => 6, 'code' => 'ice3'],
        4 => ['name' => 'Ikkaku Hakugei', 'cost' => 8, 'code' => 'ice4'],
        5 => ['name' => 'Rouga Nadare no Jutsu', 'cost' => 12, 'code' => 'ice5'],
        6 => ['name' => 'Kokuryuu Boufuusetsu', 'cost' => 16, 'code' => 'ice6'],
        7 => ['name' => 'Souryuu Boufuusetsu', 'cost' => 20, 'code' => 'ice7'],
    ];
    
    switch($hookname) {
        case "specialty-codex":
            $args[$spec] = [
                'name' => $name,
                'color' => $ccode,
                'desc' => 'A powerful discipline of elemental Ninjutsu.',
                'techniques' => [
                    1 => ['name' => 'Hyourou no Jutsu', 'cost' => 1, 'desc' => '"Hyouton - Hyourou no Jutsu!You  trap {badguy} in ice."'],
                    2 => ['name' => 'Tsubame Fubuki', 'cost' => 3, 'desc' => '"Hyouton - Tsubame Fubuki no Jutsu!You  create a cluster of ice needles in the shape of miniature swallows." Deals direct damage, summons helper minion, lasts 5 rounds.'],
                    3 => ['name' => 'Haryuu Mouko', 'cost' => 6, 'desc' => '"Hyouton - Haryuu Mouko no Jutsu!You create a large tiger out of ice." Deals direct damage, summons helper minion, lasts 5 rounds.'],
                    4 => ['name' => 'Ikkaku Hakugei', 'cost' => 8, 'desc' => '"Hyouton - Ikkaku Hakugei!You create a massive narwhal from summoned ice that jumps up and falls back down on {badguy}." Deals damage to all enemies, summons helper minion.'],
                    5 => ['name' => 'Rouga Nadare no Jutsu', 'cost' => 12, 'desc' => '"Hyouton - Rouga Nadare no Jutsu!You create an avalanche of snow wolves." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                    6 => ['name' => 'Kokuryuu Boufuusetsu', 'cost' => 16, 'desc' => '"Hyouton - Kokuryuu Boufuusetsu!You create an icy black dragon with red eyes and shoot it towards {badguy}." Deals direct damage, summons helper minion.'],
                    7 => ['name' => 'Souryuu Boufuusetsu', 'cost' => 20, 'desc' => '"Hyouton - Souryuu Boufuusetsu!You releases two dragons of black snow that merge into a massive tornado at {badguy}." Deals direct damage, summons helper minion, lasts 3 rounds.'],
                ]
            ];
            break;

        case "newday-intercept":
            specialtyice_change();
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
                    $t1 = translate_inline("Train in Hyouton Ninjutsu"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                    addnav("", "newday.php?setspecialty=$spec$resline");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                output("`#`c`bHyouton Ninjutsu Unlocked`b`c`n");
                output("`@You have unlocked the secrets of Hyouton Ninjutsu! Focus your chakra and unleash your Jutsu in combat.`n");
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
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Hyourou no Jutsu')], $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses >= 3) {
                    addnav(["`@ &#149; %s`@ (3)`0", translate_inline('Tsubame Fubuki')], $script."op=fight&skill=$spec&l=2", true);
                }
                if ($uses >= 6) {
                    addnav(["`@ &#149; %s`@ (6)`0", translate_inline('Haryuu Mouko')], $script."op=fight&skill=$spec&l=3", true);
                }
                if ($uses >= 8) {
                    addnav(["`@ &#149; %s`@ (8)`0", translate_inline('Ikkaku Hakugei')], $script."op=fight&skill=$spec&l=4", true);
                }
                if ($uses >= 12) {
                    addnav(["`@ &#149; %s`@ (12)`0", translate_inline('Rouga Nadare no Jutsu')], $script."op=fight&skill=$spec&l=5", true);
                }
                if ($uses >= 16) {
                    addnav(["`@ &#149; %s`@ (16)`0", translate_inline('Kokuryuu Boufuusetsu')], $script."op=fight&skill=$spec&l=6", true);
                }
                if ($uses >= 20) {
                    addnav(["`@ &#149; %s`@ (20)`0", translate_inline('Souryuu Boufuusetsu')], $script."op=fight&skill=$spec&l=7", true);
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
case "ice1":
			apply_buff('ice1',array(
				"startmsg"=>"`l`i`1Hyou`vton `q-`v Hyourou no Jutsu!`i`n`1You `v trap {badguy} in ice.",
				"name"=>"`lHyourou",
				"rounds"=>1,
				"badguyatkmod"=>0,
				"roundmsg"=>"{badguy} could not attack for one round!",
				"schema"=>"module-specialtysystem_ice"
			));
			break;
		case "ice2":
			apply_buff('ice2',array(
				"startmsg"=>"`l`i`1Hyou`vton `q-`v Tsubame Fubuki no Jutsu!`i`n`1You `v create a cluster of ice needles in the shape of miniature swallows.",
				"name"=>"`1Tsubame Fubuki",
				"rounds"=>5,
				"wearoff"=>"You ran out of swallows.",
				"minbadguydamage"=>5+min(35 ,$session['user']['dragonkills']),
				"maxbadguydamage"=>15+min(75,$session['user']['dragonkills']),
				"minioncount"=>3+$session['user']['level']/5,
				"effectmsg"=>"{badguy} suffers {damage} damage from the sharp wings!",
				"effectnodmgmsg"=>"The swallows miss!",
				"schema"=>"module-specialtysystem_ice"
			));
			break;
		case "ice3":
			apply_buff('ice3',array(
				"startmsg"=>"`i`1Hyou`vton `q-`v Haryuu Mouko no Jutsu!`i`n`1You `vcreate a large tiger out of ice.",
				"name"=>"`1Haryuu Mouko",
				"rounds"=>5,
				"wearoff"=>"The ice tiger scatters.",
				"minbadguydamage"=>15+min(35,$session['user']['dragonkills']),
				"maxbadguydamage"=>30+min(75,$session['user']['dragonkills']),
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from the tiger's sharp claws!",
				"effectnodmgmsg"=>"{badguy} manages to dodge the attack!",
				"schema"=>"module-specialtysystem_ice"
			));
			break;
		case "ice4":
			apply_buff('ice4',array(
				"startmsg"=>"`i`1Hyou`vton `q-`v Ikkaku Hakugei!`i`n`1You `vcreate a massive narwhal from summoned ice that jumps up and falls back down on {badguy}.",
				"name"=>"`lIkkaku Hakugei",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>30+$session['user']['dragonkills'],
				"maxbadguydamage"=>90+$session['user']['dragonkills'],
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from the impact!",
				"effectnodmgmsg"=>"{badguy} manages to escape to safety!",
				"schema"=>"module-specialtysystem_ice"
			));
			break;
		case "ice5":
			apply_buff('ice5',array(
				"startmsg"=>"`i`1Hyou`vton `q-`v Rouga Nadare no Jutsu!`i`n`1You `vcreate an avalanche of snow wolves.",
				"name"=>"`lRouga Nadare no Jutsu",
				"rounds"=>5,
				"areadamage"=>true,
				"wearoff"=>"The avalanche has stopped.",
				"minbadguydamage"=>15+$session['user']['dragonkills'],
				"maxbadguydamage"=>50+$session['user']['dragonkills'],
				"minioncount"=>4,
				"effectmsg"=>"{badguy} suffers {damage} damage from the biting and crushing!",
				"effectnodmgmsg"=>"The wolves only rush by {badguy}!",
				"schema"=>"module-specialtysystem_ice"
			));
			break;
		case "ice6":
			apply_buff('ice6',array(
				"startmsg"=>"`i`1Hyou`vton `q-`v Kokuryuu Boufuusetsu!`i`n`1You `vcreate an icy black dragon with red eyes and shoot it towards {badguy}.",
				"name"=>"`lKokuryuu Boufuusetsu",
				"rounds"=>1,
				"minbadguydamage"=>60+$session['user']['dragonkills'],
				"maxbadguydamage"=>100+$session['user']['dragonkills'],
				"minioncount"=>3,
				"effectmsg"=>"{badguy} suffers {damage} damage as the dragon rips through the air!",
				"effectnodmgmsg"=>"{badguy} manages to avoid being torn apart!",
				"schema"=>"module-specialtysystem_ice"
			));
			break;
		case "ice7":
			apply_buff('ice7',array(
				"startmsg"=>"`i`1Hyou`vton `q-`v Souryuu Boufuusetsu!`i`n`1You `vreleases two dragons of black snow that merge into a massive tornado at {badguy}.",
				"name"=>"`lSouryuu Boufuusetsu",
				"rounds"=>3,
				"minbadguydamage"=>30+$session['user']['dragonkills'],
				"maxbadguydamage"=>80+$session['user']['dragonkills'],
				"minioncount"=>6,
				"effectmsg"=>"{badguy} suffers {damage} damage from the freezing tornado!",
				"effectnodmgmsg"=>"{badguy} manages to avoid being froze and torn to bits!",
				"schema"=>"module-specialtysystem_ice"
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
            $args[$spec] = "specialtyice";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;
            
        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userSpec === $spec) {
                $nstr = "`%" . specialtyice_namer("Y");
                
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function specialtyice_run() {
}

function specialtyice_change() {
    global $session;
    $spec = "IC"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtyice_namer("Y");
        
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

function specialtyice_namer($type='X') {
    $class = [];
    $class['1'] = "Hyouton Ninjutsu";
    return $class['1'];
}
?>