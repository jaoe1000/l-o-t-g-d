<?php
/**
 * Advanced Specialty Module - Water Magic
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtywater_getmoduleinfo() {
    return [
        "name" => "Specialty - Water Magic",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Water Magic - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|1",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Water Magic Adze',note", 
        ],
        "prefs" => [
            "Water Magic - User Prefs,title",
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|magic", 
            "subclass" => "Subclass,enum,Unset,0,Default,1|1", 
        ]
    ];
}

function specialtywater_install() {
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
    debug("Installed 'Water Magic' specialty module.");
    return true;
}

function specialtywater_uninstall() {
    $spec = "WT"; 
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled 'Water Magic' specialty module.");
    return true;
}

function specialtywater_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "WT"; 
    $name = "Water Magic"; 
    $ccode = "`1"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    $techniques = [
        1 => ['name' => 'Dense Mist', 'cost' => 1, 'code' => 'water1'],
        2 => ['name' => 'Water Jet', 'cost' => 3, 'code' => 'water2'],
        3 => ['name' => 'Water Barrier', 'cost' => 5, 'code' => 'water3'],
        4 => ['name' => 'Water Dragon', 'cost' => 10, 'code' => 'water4'],
        5 => ['name' => 'Feeding Frenzy', 'cost' => 15, 'code' => 'water5'],
        6 => ['name' => 'Geyser Blast', 'cost' => 16, 'code' => 'water6'],
        7 => ['name' => 'Tidal Wave', 'cost' => 18, 'code' => 'water7'],
        8 => ['name' => 'Maelstrom', 'cost' => 20, 'code' => 'water8'],
    ];
    
    switch($hookname) {
        case "newday-intercept":
            specialtywater_change();
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
                    $t1 = translate_inline("Train in Water Magic"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                    addnav("", "newday.php?setspecialty=$spec$resline");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                output("`#`c`bWater Magic Unlocked`b`c`n");
                output("`@You have unlocked the secrets of Water Magic! Focus your mana and unleash your spells in combat.`n");
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
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Dense Mist')], $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses >= 3) {
                    addnav(["`@ &#149; %s`@ (3)`0", translate_inline('Water Jet')], $script."op=fight&skill=$spec&l=2", true);
                }
                if ($uses >= 5) {
                    addnav(["`@ &#149; %s`@ (5)`0", translate_inline('Water Barrier')], $script."op=fight&skill=$spec&l=3", true);
                }
                if ($uses >= 10) {
                    addnav(["`@ &#149; %s`@ (10)`0", translate_inline('Water Dragon')], $script."op=fight&skill=$spec&l=4", true);
                }
                if ($uses >= 15) {
                    addnav(["`@ &#149; %s`@ (15)`0", translate_inline('Feeding Frenzy')], $script."op=fight&skill=$spec&l=5", true);
                }
                if ($uses >= 16) {
                    addnav(["`@ &#149; %s`@ (16)`0", translate_inline('Geyser Blast')], $script."op=fight&skill=$spec&l=6", true);
                }
                if ($uses >= 18) {
                    addnav(["`@ &#149; %s`@ (18)`0", translate_inline('Tidal Wave')], $script."op=fight&skill=$spec&l=7", true);
                }
                if ($uses >= 20) {
                    addnav(["`@ &#149; %s`@ (20)`0", translate_inline('Maelstrom')], $script."op=fight&skill=$spec&l=8", true);
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
case "water1":
			apply_buff('water1',array(
				"startmsg"=>"`1`iWater Magic `&-`! Dense Mist!`i`n`qYou `\$envelop the surrounding area in a dense mist.",
				"name"=>"`1Dense Mist",
				"rounds"=>5,
				"wearoff"=>"The mist clears away.",
				"badguyatkmod"=>0.85,
				"badguydefmod"=>0.85,
				"roundmsg"=>"The mist conceals you from {badguy} a bit!",
				"schema"=>"module-specialtysystem_water"
			));
			break;
		case "water2":
			apply_buff('water2',array(
				"startmsg"=>"`1`iWater Magic `&-`! Water Jet!`i`n`qYou `\$shoot a strong stream of water at {badguy}.",
				"name"=>"`1Water Jet",
				"rounds"=>1,
				"areadamage"=>true,
				"minbadguydamage"=>15+$session['user']['level'],
				"maxbadguydamage"=>30+$session['user']['level']*2,
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from the blast!",
				"schema"=>"module-specialtysystem_water"
			));
			break;
		case "water3":
			apply_buff('water3',array(
				"startmsg"=>"`1`iWater Magic `&-`! Water Barrier!`i`n`qYou `\$create a water barrier to protect yourself from attacks.",
				"name"=>"`1Water Barrier",
				"rounds"=>5,
				"wearoff"=>"The water barrier disappears.",
				"defmod"=>1.80,
				"schema"=>"module-specialtysystem_water"
			));
			break;
		case "water4":
			apply_buff('water4',array(
				"startmsg"=>"`1`iWater Magic `&-`! Water Dragon!`i`n`qYou `\$create a huge current of water in the form of a dragon and send it towards {badguy}.",
				"name"=>"`1Water Dragon",
				"rounds"=>5,
				"areadamage"=>true,
				"wearoff"=>"The dragon returns to water.",
				"minbadguydamage"=>15+$session['user']['level'],
				"maxbadguydamage"=>25+$session['user']['level'],
				"minioncount"=>3,
				"effectmsg"=>"{badguy} suffers {damage} damage from the blast!",
				"effectnodmgmsg"=>"{badguy} manages to dodge out of the way.",
				"schema"=>"module-specialtysystem_water"
			));
			break;
		case "water5":
			apply_buff('water5',array(
				"startmsg"=>"`1`iWater Magic `&-`! Feeding Frenzy!`i`n`qYou `\$trap {badguy} in water and send five attacking sharks after them.",
				"name"=>"`1Feeding Frenzy",
				"rounds"=>5,
				"wearoff"=>"The sharks disappear.",
				"minbadguydamage"=>15+$session['user']['level'],
				"maxbadguydamage"=>25+$session['user']['level'],
				"minioncount"=>5,
				"effectmsg"=>"{badguy} suffers {damage} damage from the shark attack!",
				"effectnodmgmsg"=>"The shark miss!",
				"schema"=>"module-specialtysystem_water"
			));
			break;
		case "water6":
			apply_buff('water6',array(
				"startmsg"=>"`1`iWater Magic `&-`! Geyser Blast!`i`n`qYou `\$create a massive blast of water.",
				"name"=>"`1Geyser Blast",
				"rounds"=>5,
				"areadamage"=>true,
				"wearoff"=>"The massive blast of water washes everything away.",
				"minbadguydamage"=>40+$session['user']['level']+$session['user']['dragonkills'],
				"maxbadguydamage"=>60+$session['user']['level']+$session['user']['dragonkills'],
				"minioncount"=>1,
				"effectmsg"=>"{badguy} suffers {damage} damage from the blast!",
				"schema"=>"module-specialtysystem_water"
			));
			break;
		case "water7":
			apply_buff('water7',array(
				"startmsg"=>"`1`iWater Magic `&-`! Tidal Wave!`i`n`qYou `\$create a massive tidal wave.",
				"name"=>"`1Tidal Wave",
				"rounds"=>5,
				"areadamage"=>true,
				"wearoff"=>"You can't create anymore waves.",
				"minbadguydamage"=>20+$session['user']['level']+$session['user']['dragonkills'],
				"maxbadguydamage"=>40+$session['user']['level']+$session['user']['dragonkills'],
				"minioncount"=>5,
				"effectmsg"=>"{badguy} suffers {damage} damage from the crushing wave!",
				"effectnodmgmsg"=>"{badguy} escapes the crushing wave!",
				"schema"=>"module-specialtysystem_water"
			));
			break;
		case "water8":
			apply_buff('water8',array(
				"startmsg"=>"`1`iWater Magic `&-`! Maelstrom!`i`n`qYou `\$create an inescapable maelstrom.",
				"name"=>"`1Maelstrom",
				"rounds"=>5,
				"areadamage"=>true,
				"wearoff"=>"The maelstrom disappears.",
				"minbadguydamage"=>25+$session['user']['level']+$session['user']['dragonkills'],
				"maxbadguydamage"=>40+$session['user']['level']+$session['user']['dragonkills'],
				"minioncount"=>6,
				"effectmsg"=>"{badguy} suffers {damage} damage from drowning!",
				"effectnodmgmsg"=>"{badguy} manages to stay afloat!",
				"schema"=>"module-specialtysystem_water"
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
            $args[$spec] = "specialtywater";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;
            
        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userSpec === $spec) {
                $nstr = "`%" . specialtywater_namer("Y");
                
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function specialtywater_run() {
}

function specialtywater_change() {
    global $session;
    $spec = "WT"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtywater_namer("Y");
        
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

function specialtywater_namer($type='X') {
    $class = [];
    $class['1'] = "Water Magic";
    return $class['1'];
}
?>