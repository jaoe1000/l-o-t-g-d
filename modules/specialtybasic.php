<?php
/**
 * Advanced Specialty Module - Basic Ninjutsu
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtybasic_getmoduleinfo() {
    return [
        "name" => "Specialty - Basic Ninjutsu",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Basic Ninjutsu - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|0",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Basic Ninjutsu Adze',note", 
        ],
        "prefs" => [
            "Basic Ninjutsu - User Prefs,title",
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|magic", 
            "subclass" => "Subclass,enum,Unset,0,Default,1|1", 
        ]
    ];
}

function specialtybasic_install() {
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
    debug("Installed 'Basic Ninjutsu' specialty module.");
    return true;
}

function specialtybasic_uninstall() {
    $spec = "BS"; 
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled 'Basic Ninjutsu' specialty module.");
    return true;
}

function specialtybasic_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "BS"; 
    $name = "Basic Ninjutsu"; 
    $ccode = "`v"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    $techniques = [
        1 => ['name' => 'Kawarimi no Jutsu', 'cost' => 1, 'code' => 'basic1'],
        2 => ['name' => 'Kakuremino no Jutsu', 'cost' => 1, 'code' => 'basic2'],
        3 => ['name' => 'Bunshin no Jutsu', 'cost' => 1, 'code' => 'basic3'],
    ];
    
    switch($hookname) {
        case "newday-intercept":
            specialtybasic_change();
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
                    $t1 = translate_inline("Train in Basic Ninjutsu"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                    addnav("", "newday.php?setspecialty=$spec$resline");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                output("`#`c`bBasic Ninjutsu Unlocked`b`c`n");
                output("`@You have unlocked the secrets of Basic Ninjutsu! Focus your chakra and unleash your Jutsu in combat.`n");
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
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Kawarimi no Jutsu')], $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses >= 1) {
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Kakuremino no Jutsu')], $script."op=fight&skill=$spec&l=2", true);
                }
                if ($uses >= 1) {
                    addnav(["`@ &#149; %s`@ (1)`0", translate_inline('Bunshin no Jutsu')], $script."op=fight&skill=$spec&l=3", true);
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
case "basic1":
			apply_buff('basic1',array(
				"startmsg"=>"`v`iKawarimi no Jutsu!`i`n`tYou `qswitch places with a nearby item but can neither attack nor defend.",
				"name"=>"`vKawarimi no Jutsu",
				"rounds"=>1,
				"badguyatkmod"=>max(0.1,0.5-($u['intelligence']/100)),
				"badguydefmod"=>0,
				"atkmod"=>0,
				"schema"=>"module-specialtysystem_basic"
			));
			break;
		case "basic2":
			apply_buff('basic2',array(
				"startmsg"=>"`v`iKakuremino no Jutsu!`i`n`tYou `qmake yourself invisible to get into a better attack position.",
				"name"=>"`vKakuremino no Jutsu",
				"rounds"=>2,
				"wearoff"=>"You fail to hide any longer.",
				"badguyatkmod"=>0.8,
				"roundmsg"=>"{badguy} is not completely sure where you are!",
				"schema"=>"module-specialtysystem_basic"
			));
			break;
		case "basic3":
			/*apply_buff('basic3',array( //to be revised
				"startmsg"=>"`v`iBunshin no Jutsu!`i`n`tYou `qcreate some clones to cover up your current position",
				"name"=>"`vBunshin no Jutsu",
				"rounds"=>5,
				"wearoff"=>"Your bunshins crumble.",
				"defmod"=>1.1,
				"roundmsg"=>"Your bunshin cover up for you a bit!",
				"schema"=>"module-specialtysystem_basic"
			));*/
			$amount=min(5,round($u['intelligence']/7));
			$comp=array(
				"name"=>$session['user']['name'].translate_inline(" Bunshin"),
				"attack"=>0,
				"defense"=>0,
				"hitpoints"=>5,
				"maxhitpoints"=>5,
				"companionactive"=>1,
				"jointext"=>'',
				"cannotdie"=>0,
				"cannotbehealed"=>1,
				"dyingtext"=>"*poof*",
				"expireafterfight"=>1,
				"abilities"=>array(
					"fight"=>0,
					"heal"=>0,
					"magic"=>0,
					"defend"=>1,
					),
				"schema"=>"module-specialtysystem_basic"
			);
			for ($i=1;$i<=$amount;$i++) {
				$new=$comp;
				$new['name'].=" #".$i;
				apply_companion(sanitize($new['name']),$new,true);
			}
			break;
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
            $args[$spec] = "specialtybasic";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;
            
        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userSpec === $spec) {
                $nstr = "`%" . specialtybasic_namer("Y");
                
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function specialtybasic_run() {
}

function specialtybasic_change() {
    global $session;
    $spec = "BS"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtybasic_namer("Y");
        
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

function specialtybasic_namer($type='X') {
    $class = [];
    $class['1'] = "Basic Ninjutsu";
    return $class['1'];
}
?>