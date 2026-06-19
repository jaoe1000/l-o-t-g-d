<?php
/**
 * Advanced Specialty Module - Restoration Magic
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtymedical_getmoduleinfo() {
    return [
        "name" => "Specialty - Restoration Magic",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Restoration Magic - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|4",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Restoration Magic Adze',note", 
        ],
        "prefs" => [
            "Restoration Magic - User Prefs,title",
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|magic", 
            "subclass" => "Subclass,enum,Unset,0,Default,1|1", 
        ]
    ];
}

function specialtymedical_install() {
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
    debug("Installed 'Restoration Magic' specialty module.");
    return true;
}

function specialtymedical_uninstall() {
    $spec = "MD"; 
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled 'Restoration Magic' specialty module.");
    return true;
}

function specialtymedical_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "MD"; 
    $name = "Restoration Magic"; 
    $ccode = "`!"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    $techniques = [
        1 => ['name' => 'Restoration', 'cost' => 1, 'code' => 'medical1'],
        2 => ['name' => 'Noxious Mist', 'cost' => 2, 'code' => 'medical2'],
        3 => ['name' => 'Life Siphon', 'cost' => 5, 'code' => 'medical3'],
        4 => ['name' => 'Synaptic Shock', 'cost' => 8, 'code' => 'medical4'],
        5 => ['name' => 'Rejuvenation', 'cost' => 15, 'code' => 'medical5'],
        6 => ['name' => 'Cripple', 'cost' => 16, 'code' => 'medical6'],
        7 => ['name' => 'Sundering Blow', 'cost' => 18, 'code' => 'medical7'],
        8 => ['name' => 'Ascendant Recovery', 'cost' => 20, 'code' => 'medical8'],
    ];
    
    switch($hookname) {
        case "newday-intercept":
            specialtymedical_change();
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
                    $t1 = translate_inline("Train in Restoration Magic"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                    addnav("", "newday.php?setspecialty=$spec$resline");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                output("`#`c`bRestoration Magic Unlocked`b`c`n");
                output("`@You have unlocked the secrets of Restoration Magic! Focus your mana and unleash your spells in combat.`n");
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
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Restoration')], $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses >= 2) {
                    addnav(["`@ &#149; %s`@ (2)`0", translate_inline('Noxious Mist')], $script."op=fight&skill=$spec&l=2", true);
                }
                if ($uses >= 5) {
                    addnav(["`@ &#149; %s`@ (5)`0", translate_inline('Life Siphon')], $script."op=fight&skill=$spec&l=3", true);
                }
                if ($uses >= 8) {
                    addnav(["`@ &#149; %s`@ (8)`0", translate_inline('Synaptic Shock')], $script."op=fight&skill=$spec&l=4", true);
                }
                if ($uses >= 15) {
                    addnav(["`@ &#149; %s`@ (15)`0", translate_inline('Rejuvenation')], $script."op=fight&skill=$spec&l=5", true);
                }
                if ($uses >= 16) {
                    addnav(["`@ &#149; %s`@ (16)`0", translate_inline('Cripple')], $script."op=fight&skill=$spec&l=6", true);
                }
                if ($uses >= 18) {
                    addnav(["`@ &#149; %s`@ (18)`0", translate_inline('Sundering Blow')], $script."op=fight&skill=$spec&l=7", true);
                }
                if ($uses >= 20) {
                    addnav(["`@ &#149; %s`@ (20)`0", translate_inline('Ascendant Recovery')], $script."op=fight&skill=$spec&l=8", true);
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
case "medical1":
			apply_buff('medical1',array(
				"startmsg"=>"`!`iRestoration!`i`n`tYou concentrate healing magic to the palm of your hand.",
				"name"=>"`!Restoration",
				"rounds"=>5,
				"wearoff"=>"You stopped healing yourself.",
				"regen"=>$session['user']['level']+1,
				"effectmsg"=>"You regenerate for {damage} health.",
				"effectnodmgmsg"=>"You have no wounds to heal.",
				"schema"=>"module-specialtysystem_medical"
			));
			break;
		case "medical2":
			apply_buff('medical2',array(
				"startmsg"=>"`!`iNoxious Mist!`i`n`tYou blow a cloud of poison gas at the enemy.",
				"name"=>"`@Noxious Mist",
				"rounds"=>5,
				"wearoff"=>"The poison gas clears away.",
				"areadamage"=>true,
				"minbadguydamage"=>5+min(50,$session['user']['dragonkills']*3),
				"maxbadguydamage"=>10+min(100,$session['user']['dragonkills']*3),
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from poisoning!",
				"schema"=>"module-specialtysystem_medical"
			));
			break;
		case "medical3":
			apply_buff('medical3',array(
				"startmsg"=>"`!`iLife Siphon!`i`n`tYou grab on to {badguy} and begin absorbing life essence.",
				"name"=>"`!Life Siphon",
				"rounds"=>5,
				"wearoff"=>"You stopped absorbing life essence.",
				"regen"=>$session['user']['level']*2,
				"badguyatkmod"=>0.60,
				"effectmsg"=>"{badguy} loses energy and is unable to attack well!",
				"effectnodmgmsg"=>"{badguy} loses energy and is unable to attack well!",
				"schema"=>"module-specialtysystem_medical"
			));
			break;
		case "medical4":
			apply_buff('medical4',array(
				"startmsg"=>"`!`iSynaptic Shock!`i`n`tYou convert a small portion of mana into electricity and hit the enemy's brain stem.",
				"name"=>"`!Synaptic Shock",
				"rounds"=>10,
				"wearoff"=>"{badguy}'s movement returned to normal.",
				"badguyatkmod"=>0.60,
				"badguydefmod"=>0.60,
				"roundmsg"=>"{badguy} could not move properly!",
				"schema"=>"module-specialtysystem_medical"
			));
			break;
		case "medical5":
			apply_buff('medical5',array(
				"startmsg"=>"`!`iRejuvenation!`i`n`tYou concentrate strong healing magic to the palm of your hand.",
				"name"=>"`!Rejuvenation",
				"rounds"=>12,
				"wearoff"=>"You stopped regenerating.",
				"regen"=>round($session['user']['maxhitpoints']/10),
				"effectmsg"=>"You regenerate for {damage} health.",
				"effectnodmgmsg"=>"You have no wounds to regenerate.",
				"schema"=>"module-specialtysystem_medical"
			));
			break;
		case "medical6":
			apply_buff('medical6',array(
				"startmsg"=>"`!`iCripple!`i`n`tYou focus your magic into a spectral blade and damage the enemy's muscles.",
				"name"=>"`!Cripple",
				"rounds"=>10,
				"wearoff"=>"{badguy}'s damaged muscles have healed.",
				"badguyatkmod"=>0.25,
				"effectnodmgmsg"=>"{badguy} could not attack well!",
				"schema"=>"module-specialtysystem_medical"
			));
			break;
		case "medical7":
			apply_buff('medical7',array(
				"startmsg"=>"`!`iSundering Blow!`i`n`tYou focus your magic into a spectral blade and damage the enemy's organs.",
				"name"=>"`!Sundering Blow",
				"rounds"=>10,
				"wearoff"=>"{badguy}'s damaged organs have healed.",
				"badguydefmod"=>0.25,
				"roundmsg"=>"{badguy} can not defend well!",
				"schema"=>"module-specialtysystem_medical"
			));
			break;
		case "medical8":
		if ($session['user']['hitpoints']<$session['user']['maxhitpoints']) {
   $session['user']['hitpoints']=$session['user']['maxhitpoints'];
}
			apply_buff('medical8',array(
				"startmsg"=>"`!`iAscendant Recovery!`i`n`tYou release all the magic you have stored up and heal all your wounds almost instantaneously.",
				"name"=>"`!Ascendant Recovery",
				"rounds"=>10,
				"wearoff"=>"You have exhausted all of your energy.",
				"defmod"=>3.0,
				"schema"=>"module-specialtysystem_medical"
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
            $args[$spec] = "specialtymedical";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;
            
        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userSpec === $spec) {
                $nstr = "`%" . specialtymedical_namer("Y");
                
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function specialtymedical_run() {
}

function specialtymedical_change() {
    global $session;
    $spec = "MD"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtymedical_namer("Y");
        
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

function specialtymedical_namer($type='X') {
    $class = [];
    $class['1'] = "Restoration Magic";
    return $class['1'];
}
?>