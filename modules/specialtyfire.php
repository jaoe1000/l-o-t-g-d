<?php
/**
 * Advanced Specialty Module - Katon Ninjutsu
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtyfire_getmoduleinfo() {
    return [
        "name" => "Specialty - Katon Ninjutsu",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Katon Ninjutsu - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|6",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Katon Ninjutsu Adze',note", 
        ],
        "prefs" => [
            "Katon Ninjutsu - User Prefs,title",
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|magic", 
            "subclass" => "Subclass,enum,Unset,0,Default,1|1", 
        ]
    ];
}

function specialtyfire_install() {
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
    debug("Installed 'Katon Ninjutsu' specialty module.");
    return true;
}

function specialtyfire_uninstall() {
    $spec = "FR"; 
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled 'Katon Ninjutsu' specialty module.");
    return true;
}

function specialtyfire_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "FR"; 
    $name = "Katon Ninjutsu"; 
    $ccode = "`$"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    $techniques = [
        1 => ['name' => 'Gōkakyū no Jutsu', 'cost' => 1, 'code' => 'fire1'],
        2 => ['name' => 'Hōsenka no Jutsu', 'cost' => 3, 'code' => 'fire2'],
        3 => ['name' => 'Ryūka no Jutsu', 'cost' => 6, 'code' => 'fire3'],
        4 => ['name' => 'Haisekishō', 'cost' => 10, 'code' => 'fire4'],
        5 => ['name' => '`\$Kar`qyū `\$En`qdan', 'cost' => 15, 'code' => 'fire5'],
        6 => ['name' => '`\$Kar`qyū`Qdan', 'cost' => 18, 'code' => 'fire6'],
        7 => ['name' => '`@Gamayo `\$Emu`qdan', 'cost' => 11, 'code' => 'gamaemudan'],
    ];
    
    switch($hookname) {
        case "specialty-codex":
            $args[$spec] = [
                'name' => $name,
                'color' => $ccode,
                'desc' => 'A powerful discipline of elemental Ninjutsu.',
                'techniques' => [
                    1 => ['name' => 'Gōkakyū no Jutsu', 'cost' => 1, 'desc' => '"Katon - Gōkakyū no Jutsu!You utilize your chakra and exhale a large ball of fire from your mouth." Deals damage to all enemies, summons helper minion.'],
                    2 => ['name' => 'Hōsenka no Jutsu', 'cost' => 3, 'desc' => '"Katon - Hōsenka!You  send multiple balls of fire at {badguy}." Deals direct damage, summons helper minion, lasts 5 rounds.'],
                    3 => ['name' => 'Ryūka no Jutsu', 'cost' => 6, 'desc' => '"Katon - Ryūka no Jutsu!You breath out a burst of flame towards {badguy}." Deals damage to all enemies, summons helper minion.'],
                    4 => ['name' => 'Haisekishō', 'cost' => 10, 'desc' => '"Katon - Haisekishō!You breathe out a cloud of superheated ash." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                    5 => ['name' => '`\$Kar`qyū `\$En`qdan', 'cost' => 15, 'desc' => '"Katon - Karyū Endan! You shoot an enormous ball of flame in the shape of a dragon from your mouth at {badguy}." Deals damage to all enemies, summons helper minion.'],
                    6 => ['name' => '`\$Kar`qyū`Qdan', 'cost' => 18, 'desc' => '"Katon - Karyūdan, You create a likeness of a dragon" Deals damage to all enemies, summons helper minion.'],
                    7 => ['name' => '`@Gamayo `\$Emu`qdan', 'cost' => 11, 'desc' => '"Katon -  Gamayou Emudan!You sent a fire blast from your mouth to Gamabunta" Deals damage to all enemies, summons helper minion, lasts 10 rounds.'],
                ]
            ];
            break;

        case "newday-intercept":
            specialtyfire_change();
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
                    $t1 = translate_inline("Train in Katon Ninjutsu"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                    addnav("", "newday.php?setspecialty=$spec$resline");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                output("`#`c`bKaton Ninjutsu Unlocked`b`c`n");
                output("`@You have unlocked the secrets of Katon Ninjutsu! Focus your chakra and unleash your Jutsu in combat.`n");
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
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Gōkakyū no Jutsu')], $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses >= 3) {
                    addnav(["`@ &#149; %s`@ (3)`0", translate_inline('Hōsenka no Jutsu')], $script."op=fight&skill=$spec&l=2", true);
                }
                if ($uses >= 6) {
                    addnav(["`@ &#149; %s`@ (6)`0", translate_inline('Ryūka no Jutsu')], $script."op=fight&skill=$spec&l=3", true);
                }
                if ($uses >= 10) {
                    addnav(["`@ &#149; %s`@ (10)`0", translate_inline('Haisekishō')], $script."op=fight&skill=$spec&l=4", true);
                }
                if ($uses >= 15) {
                    addnav(["`@ &#149; %s`@ (15)`0", translate_inline('`\$Kar`qyū `\$En`qdan')], $script."op=fight&skill=$spec&l=5", true);
                }
                if ($uses >= 18) {
                    addnav(["`@ &#149; %s`@ (18)`0", translate_inline('`\$Kar`qyū`Qdan')], $script."op=fight&skill=$spec&l=6", true);
                }
                if ($uses >= 11 && $session['user']['hashorse']==3) {
                    addnav(["`@ &#149; %s`@ (11)`0", translate_inline('`@Gamayo `\$Emu`qdan')], $script."op=fight&skill=$spec&l=7", true);
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
case "fire1":
			apply_buff('fire1',array(
				"startmsg"=>"`i`qKa`\$ton `q-`\$ Gōkakyū no Jutsu!`i`n`qYou `\$utilize your chakra and exhale a large ball of `4fire `\$from your mouth.",
				"name"=>"`\$Katon `4- `\$Gōkakyū `4no `\$Jutsu",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>15,
				"maxbadguydamage"=>20,
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from burns!",
				"schema"=>"module-specialtysystem_fire"
			));
			break;
		case "fire2":
			apply_buff('fire2',array(
				"startmsg"=>"`i`qKa`\$ton `q-`\$ Hōsenka!`i`n`qYou `\$ send multiple balls of `4fire `\$at {badguy}.",
				"name"=>"`\$Katon `4- `\$Hōsenka `4no `\$Jutsu",
				"rounds"=>5,
				"minbadguydamage"=>$session['user']['level'],
				"maxbadguydamage"=>$session['user']['level']+5,
				"minioncount"=>floor($session['user']['level']/3+1),
				"effectmsg"=>"{badguy} suffers {damage} damage from burns!",
				"effectnodmgmsg"=>"The fire ball misses {badguy}.",
				"schema"=>"module-specialtysystem_fire"
			));
			break;
		case "fire3":
			apply_buff('fire3',array(
				"startmsg"=>"`i`qKa`\$ton `q-`\$ Ryūka no Jutsu!`i`n`qYou `\$breath out a burst of flame towards {badguy}.",
				"name"=>"`\$Katon `4- `\$Ryūka `4no `\$Jutsu",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>15+$session['user']['dragonkills'],
				"maxbadguydamage"=>40+$session['user']['dragonkills'],
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from burns!",
				"schema"=>"module-specialtysystem_fire"
			));
			break;
		case "fire4":
			apply_buff('fire4',array(
				"startmsg"=>"`i`qKa`\$ton `q-`\$ Hai`~seki`tshō!`i`n`qYou `\$breathe out a cloud of superheated ash.",
				"name"=>"`\$Katon `4- `\$Ha`4i`~seki`tshō",
				"rounds"=>5,
				"areadamage"=>true,
				"wearoff"=>"The ash was blown away by the wind.",
				"minbadguydamage"=>10+$session['user']['dragonkills'],
				"maxbadguydamage"=>20+$session['user']['dragonkills'],
				"minioncount"=>5,
				"effectmsg"=>"{badguy} suffers {damage} damage from burns!",
				"effectnodmgmsg"=>"{badguy} manages to protect itself from the ash.",
				"schema"=>"module-specialtysystem_fire"
			));
			break;
		case "fire5":
			apply_buff('fire6',array(
				"startmsg"=>"`\$Katon - `4Ka`\$ryū `4En`\$dan! You shoot an enormous ball of flame in the shape of a dragon from your mouth at {badguy}.",
				"name"=>"`\$Katon - `4Ka`\$ryū `4En`\$dan",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>90+$session['user']['dragonkills'],
				"maxbadguydamage"=>150+$session['user']['dragonkills'],
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from burns!",
				"schema"=>"module-specialtysystem_fire"
			));
			break;
		case "fire6":
			apply_buff('fire7',array(
				"startmsg"=>"`\$Katon - `4Ka`\$ryū`4dan, You create a likeness of a dragon's head out of mud and ignite the mud balls that it launches from its mouth.",
				"name"=>"`\$Katon - `4Ka`\$ryū `4En`\$dan",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>90+$session['user']['dragonkills'],
				"maxbadguydamage"=>120+$session['user']['dragonkills'],
				"minioncount"=>3,
				"effectmsg"=>"{badguy} suffers {damage} damage from the devastating attack!",
				"effectnodmgmsg"=>"The flaming mud ball misses {badguy}.",
				"schema"=>"module-specialtysystem_fire"
			));
			break;
		case "gamaemudan":
			apply_buff('gamayoemudan',array(
				"startmsg"=>"`i`qKa`\$ton `q-`\$  `\$Gama`qyou `\$Emu`qdan!`i`n`qYou `\$sent a fire blast from your mouth to Gamabunta's oil-blast... and engulf {badguy} in flames!",
				"name"=>"`\$Gama`qyou `\$Emu`qdan",
				"rounds"=>10,
				"wearoff"=>"The fire extinguishes and only the ash remains.",
				"areadamage"=>true,
				"minbadguydamage"=>7+$session['user']['dragonkills'],
				"maxbadguydamage"=>5+$session['user']['level']*3+$session['user']['dragonkills'],
				"minioncount"=>3,
				"effectmsg"=>"`q{badguy}`q suffers {damage} damage!",
				"effectnodmgmsg"=>"`qYou only produce hot air.",
				"schema"=>"module-specialtysystem_fire"
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
            $args[$spec] = "specialtyfire";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;
            
        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userSpec === $spec) {
                $nstr = "`%" . specialtyfire_namer("Y");
                
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function specialtyfire_run() {
}

function specialtyfire_change() {
    global $session;
    $spec = "FR"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtyfire_namer("Y");
        
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

function specialtyfire_namer($type='X') {
    $class = [];
    $class['1'] = "Katon Ninjutsu";
    return $class['1'];
}
?>