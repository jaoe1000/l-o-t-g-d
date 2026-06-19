<?php
/**
 * Advanced Specialty Module - Genjutsu
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtygenjutsu_getmoduleinfo() {
    return [
        "name" => "Specialty - Genjutsu",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Genjutsu - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|0",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Genjutsu Adze',note", 
        ],
        "prefs" => [
            "Genjutsu - User Prefs,title",
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|magic", 
            "subclass" => "Subclass,enum,Unset,0,Default,1|1", 
        ]
    ];
}

function specialtygenjutsu_install() {
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
    debug("Installed 'Genjutsu' specialty module.");
    return true;
}

function specialtygenjutsu_uninstall() {
    $spec = "GJ"; 
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled 'Genjutsu' specialty module.");
    return true;
}

function specialtygenjutsu_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "GJ"; 
    $name = "Genjutsu"; 
    $ccode = "`%"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    $techniques = [
        1 => ['name' => 'Narakumi no Jutsu', 'cost' => 1, 'code' => 'genjutsu1'],
        2 => ['name' => 'Kokoni Arazu no jutsu', 'cost' => 3, 'code' => 'genjutsu2'],
        3 => ['name' => 'Nijuu Kokoni Arazu no Jutsu', 'cost' => 6, 'code' => 'genjutsu3'],
        4 => ['name' => 'Jubaku Satsu', 'cost' => 10, 'code' => 'genjutsu4'],
        5 => ['name' => 'Suzu - Kiri', 'cost' => 15, 'code' => 'genjutsu5'],
        6 => ['name' => 'Jigoku Kouka no Jutsu', 'cost' => 18, 'code' => 'genjutsu6'],
        7 => ['name' => 'Kokuangyou no Jutsu', 'cost' => 20, 'code' => 'genjutsu7'],
    ];
    
    switch($hookname) {
        case "newday-intercept":
            specialtygenjutsu_change();
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
                    $t1 = translate_inline("Train in Genjutsu"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                    addnav("", "newday.php?setspecialty=$spec$resline");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                output("`#`c`bGenjutsu Unlocked`b`c`n");
                output("`@You have unlocked the secrets of Genjutsu! Focus your chakra and unleash your Jutsu in combat.`n");
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
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Narakumi no Jutsu')], $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses >= 3) {
                    addnav(["`@ &#149; %s`@ (3)`0", translate_inline('Kokoni Arazu no jutsu')], $script."op=fight&skill=$spec&l=2", true);
                }
                if ($uses >= 6) {
                    addnav(["`@ &#149; %s`@ (6)`0", translate_inline('Nijuu Kokoni Arazu no Jutsu')], $script."op=fight&skill=$spec&l=3", true);
                }
                if ($uses >= 10) {
                    addnav(["`@ &#149; %s`@ (10)`0", translate_inline('Jubaku Satsu')], $script."op=fight&skill=$spec&l=4", true);
                }
                if ($uses >= 15) {
                    addnav(["`@ &#149; %s`@ (15)`0", translate_inline('Suzu - Kiri')], $script."op=fight&skill=$spec&l=5", true);
                }
                if ($uses >= 18) {
                    addnav(["`@ &#149; %s`@ (18)`0", translate_inline('Jigoku Kouka no Jutsu')], $script."op=fight&skill=$spec&l=6", true);
                }
                if ($uses >= 20) {
                    addnav(["`@ &#149; %s`@ (20)`0", translate_inline('Kokuangyou no Jutsu')], $script."op=fight&skill=$spec&l=7", true);
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
case "genjutsu1":
			apply_buff('genjutsu1',array(
				"startmsg"=>"`%`iNarakumi no Jutsu`i`n`vAn imaginary circle of leaves spin around and envelop the enemy, falling away shortly after.",
				"name"=>"`%Narakumi no Jutsu",
				"rounds"=>5,
				"wearoff"=>"{badguy} snaps out of the illusion.",
				"badguyatkmod"=>0.75,
				"badguydefmod"=>0.75,
				"roundmsg"=>"{badguy} sees a horrible vision and can not move well!",
				"schema"=>"module-specialtysystem_genjutsu"
			));
			break;
		case "genjutsu2":
			apply_buff('genjutsu1',array(
				"startmsg"=>"`%`iKokoni Arazu no Jutsu`i`n`vYou disappear into the surroundings by changing the appearance of the area.",
				"name"=>"`%Kokoni Arazu no Jutsu",
				"rounds"=>5,
				"wearoff"=>"The illusion disappears.",
				"badguydefmod"=>0.60,
				"roundmsg"=>"You attacked while {badguy} was off-guard!",
				"schema"=>"module-specialtysystem_genjutsu"
			));
			break;
		case "genjutsu3":
			apply_buff('genjutsu1',array(
				"startmsg"=>"`%`iNi`5juu `%Ko`5ko`%ni `5A`%ra`5zu `%no Jutsu`i`n`vYou disappear into the surroundings and create an illusion around the area.",
				"name"=>"`%Ni`5juu `%Ko`5ko`%ni `5A`%ra`5zu `%no Jutsu",
				"rounds"=>5,
				"wearoff"=>"The illusion disappears.",
				"badguyatkmod"=>0.50,
				"badguydefmod"=>0.50,
				"roundmsg"=>"Your illusion conceals you from {badguy}!",
				"schema"=>"module-specialtysystem_genjutsu"
			));
			break;
		case "genjutsu4":
			apply_buff('genjutsu1',array(
				"startmsg"=>"`%`iJu`5ba`%ku `5Sa`%tsu`i`n`vA tree sprouts from underneath {badguy}.",
				"name"=>"`%Ju`5ba`%ku `5Sa`%tsu",
				"rounds"=>3,
				"wearoff"=>"{badguy} snaps out of the illusion.",
				"invulnerable"=>true,
				"roundmsg"=>"{badguy} is bound by the tree and is unable to harm you!",
				"schema"=>"module-specialtysystem_genjutsu"
			));
			break;
		case "genjutsu5":
			apply_buff('genjutsu1',array(
				"startmsg"=>"`%`iSuzu `5- `%Kiri`i`n`vYou disappear into a cloud of `R`brose petals`b.",
				"name"=>"`%Suzu `5- `%Kiri",
				"rounds"=>10,
				"wearoff"=>"The illusion disappears.",
				"badguyatkmod"=>0.25,
				"badguydefmod"=>0.25,
				"roundmsg"=>"Your illusion conceals you from {badguy}!",
				"schema"=>"module-specialtysystem_genjutsu"
			));
			break;
		case "genjutsu6":
			apply_buff('genjutsu1',array(
				"startmsg"=>"`%`i`%Jigoku `5Kouka `%no `5Jutsu`i`n`v`\$A large fire ball descend from the heavens and turns the area into a `4fiery hell.",
				"name"=>"`%Jigoku `5Kouka `%no `5Jutsu",
				"rounds"=>3,
				"wearoff"=>"The illusion disappears.",
				"badguydefmod"=>0,
				"roundmsg"=>"`%{badguy} ran right into your trap while trying to escape the raging flames!",
				"schema"=>"module-specialtysystem_genjutsu"
			));
			break;
		case "genjutsu7":
			apply_buff('genjutsu1',array(
				"startmsg"=>"`%`i`%Kokuangyou `5no `%Jutsu`i`n`vYou blind {badguy} with total `~`bdarkness`b.",
				"name"=>"`%Kokuangyou `5no `%Jutsu",
				"rounds"=>10,
				"wearoff"=>"{badguy} snaps out of the illusion.",
				"badguyatkmod"=>0.25,
				"badguydefmod"=>0,
				"roundmsg"=>"`%Your illusion conceals you from {badguy} totally!",
				"schema"=>"module-specialtysystem_genjutsu"
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
            $args[$spec] = "specialtygenjutsu";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;
            
        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userSpec === $spec) {
                $nstr = "`%" . specialtygenjutsu_namer("Y");
                
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function specialtygenjutsu_run() {
}

function specialtygenjutsu_change() {
    global $session;
    $spec = "GJ"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtygenjutsu_namer("Y");
        
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

function specialtygenjutsu_namer($type='X') {
    $class = [];
    $class['1'] = "Genjutsu";
    return $class['1'];
}
?>