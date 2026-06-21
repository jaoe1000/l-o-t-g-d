<?php
/*
Details:
 * Translator Ready
 * Addnews Ready
 * Mail Ready
History Log:
 * Version 1.0:
   o Master Template Merged Build: Paladin
*/

function racespecialtypaladin_getmoduleinfo(){
	$info = array(
		"name"=>"Specialty - Paladin",
		"version"=>"1.0",
		"vertxtloc"=>"",
		"author"=>"Eternal & Enderandrew (Merged by Joge)", 
		"category"=>"Specialties",
		"download"=>"", 
		"settings"=>array(
			"Paladin - Settings,title",
			"forestchance"=>"Chance to find the Paladin event in the forest?,range,0,100,1|10",
			"min_align"=>"Minimum alignment required to trigger the forest event?,int|80",
			"strip_align"=>"Alignment threshold below which the Paladin specialty is lost?,int|50",
			"gain"=>"How much Alignment is gained when specialty is chosen,int|20",
			"wepchange"=>"Will weapons & armour change names?,bool|1", 
			"IE- an 'Adze' would become a 'Divine Adze',note", 
		),
        // ENFORCED MODULE DEPENDENCIES
        "requires" => array(
            "alignment" => "Alignment Module, 1.0|",
        ),
		"prefs" => array(
			"Paladin - Specialty User Prefs,title", 
			"skill"=>"Skill points in Specialty Powers,int|0", 
			"uses"=>"Uses of Specialty Powers allowed,int|0", 
			"class"=>"Main Class,enum,Unset,,Divine,magic|", 
			"subclass"=>"Sublass,enum,Unset,0,Paladin,1|0", 
			"altar_prayed"=>"Has the player prayed at the altar today?,bool|0",
			"archives_studied"=>"Has the player studied in the archives today?,bool|0",
		),
	);
	return $info;
}

function racespecialtypaladin_install(){
	module_addhook("fightnav-specialties");
	module_addhook("apply-specialties");
	module_addhook("newday");
	module_addhook("incrementspecialty");
	module_addhook("specialtynames");
	module_addhook("specialtymodules");
	module_addhook("specialtycolor");
	module_addhook("dragonkill");
	module_addhook("newday-intercept");
	module_addhook("everyhit");
	module_addhook("villagenav");
	module_addhook("villagetext");
	
	module_addeventhook("forest", "require_once(\"modules/racespecialtypaladin.php\"); return racespecialtypaladin_test_event();");
	
	debug("Installed 'Paladin' specialty module."); 
	return true;
}

function racespecialtypaladin_uninstall(){
	$spec = "PL"; 
	global $session;
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
	db_query($sql);
	if (($session['user']['specialty'] ?? '') === $spec) {
		$session['user']['specialty'] = "";
	}
	debug("Uninstalled 'Paladin' specialty module."); 
	return true;
}

function racespecialtypaladin_dohook($hookname,$args){
	global $session,$resline,$badguy;
	$spec = "PL"; 
	$name = "Paladin Gifts"; 
	$ccode = "`#"; 
	
	switch($hookname){
		case "apply-specialties":
			$skill = httpget('skill');
			$l = httpget('l');
			$stuff = racespecialtypaladin_texts(get_module_pref("class"),get_module_pref("subclass"));
			if ($skill==$spec){
				if (get_module_pref("uses") >= $l){
					if (isset($stuff[$l])) {
						$buff_to_apply = $stuff[$l]; 
						apply_buff($spec.$l, $buff_to_apply); 
					} else {
						apply_buff($spec."0", array(
							"startmsg"=>"You call on your deity above, but they do not need your call. They were busy heeding nature's call.", 
							"rounds"=>1,
							"schema"=>"racespecialtypaladin" 
						));
					}
					set_module_pref("uses", get_module_pref("uses") - $l);
				}
			}
		break;
		case "dragonkill":
			set_module_pref("uses", 0);
			set_module_pref("skill", 0);
		break;
		case "fightnav-specialties":
			if ($session['user']['specialty'] == $spec) {
				$stuff = racespecialtypaladin_texts(get_module_pref("class"),get_module_pref("subclass"));
				$uses = get_module_pref("uses");
				$script = $args['script'];
				if ($uses > 0) {
					addnav(array("$ccode ".racespecialtypaladin_namer()." (%s points)`0", $uses), "");
				}
				foreach ($stuff as $points => $rubbish) {
					foreach ($rubbish as $name => $morerubbish) {
				 		if ($name == "name") {
				 			if ($uses >= $points) {
					 			$morei = str_replace("`%","`%%",$morerubbish);
								addnav(array("`@ &#149; $morei`@ (%s)`0", $points), 
								$script."op=fight&skill=$spec&l=$points", true);
				 			}
			 			}
			 		}
				}
			}
		break;
		case "incrementspecialty":
			if($session['user']['specialty'] == $spec) {
				$new = get_module_pref("skill") + 1;
				set_module_pref("skill", $new);
				$c = $args['color'];
				output("`n%sYou gain a level in `&%s%s to `#%s%s!",
						$c, $name, $c, $new, $c);
				$x = $new % 3;
				if ($x == 0){
					output("`n`^You gain an extra use point!`n");
					set_module_pref("uses", get_module_pref("uses") + 1);
				}else{
					if (3-$x == 1) {
						output("`n`^Only 1 more skill level until you gain an extra use point!`n");
					} else {
						output("`n`^Only %s more skill levels until you gain an extra use point!`n", (3-$x));
					}
				}
				output_notl("`0");
			}
		break;
		case "newday":
			if ($session['user']['specialty'] == $spec) {
				// Centralized Alignment Math
				$al = 0;
				$has_alignment = false;
				if (is_module_active('alignment')) {
					@require_once('modules/alignment.php'); 
					$al = function_exists('get_align') ? get_align() : 0; 
					$has_alignment = true;
				}
				
				// --- THE FALL FROM GRACE ---
				$strip_align = (int)get_module_setting("strip_align");
				if ($has_alignment && $al < $strip_align){
					// Strip Specialty instantly
					$session['user']['specialty'] = "";
					set_module_pref("uses", 0);
					set_module_pref("skill", 0);
					set_module_pref("class", "");
					set_module_pref("subclass", "");
					
					output("`n`\$`bYOUR GOD HAS FORSAKEN YOU!`b`n");
					output("`4Your evil actions have severed your connection to the divine. You awake stripped of your Paladin status and all holy powers!`0`n`n");
				} else {
					// --- SPECIALTY BONUSES (BALANCED) ---
					// Baseline Uses: Standard LotGD formula (1 base + 1 per 3 skill points)
					$bonus = getsetting("specialtybonus", 1);
					$amt = $bonus + (int)(get_module_pref("skill") / 3);
					
					// Alignment Modifiers
					if ($has_alignment && $al >= 50) {
						// Reward High Alignment (Scale reasonably)
						$paladinbonus = (int)($al / 20); 
						$amt += $paladinbonus;
						output("`n`2Your deep righteousness blesses you with `^%s`2 extra `&%s%s`2 uses for today.`n", $paladinbonus, $ccode, $name);
					} else {
						// Neutral Alignment
						output("`n`2You maintain your faith, receiving your standard `&%s%s`2 uses for today.`n", $ccode, $name);
					}
					
					set_module_pref("uses", $amt);
					set_module_pref("altar_prayed", 0);
					set_module_pref("archives_studied", 0);
				}
			}
		break;
		case "newday-intercept":
			racespecialtypaladin_change();
		break;
		case "specialtycolor":
			$args[$spec] = $ccode;
		break;
		case "specialtymodules":
			$args[$spec] = "racespecialtypaladin"; 
		break;
		case "specialtynames":
			$args[$spec] = translate_inline($name);
		break;
		case "villagetext":
			if (($session['user']['specialty'] ?? '') === $spec) {
				if (!isset($args['nav_headers'])) $args['nav_headers'] = [];
				if (!isset($args['navs'])) $args['navs'] = [];
				
				$args['nav_headers']['special'] = "Divine Services";
				$args['navs']['altar'] = "Divine Altar";
				$args['navs']['archives'] = "Order's Archives";
				$args['schemas']['nav_headers']['special'] = "module-racespecialtypaladin";
				$args['schemas']['navs']['altar'] = "module-racespecialtypaladin";
				$args['schemas']['navs']['archives'] = "module-racespecialtypaladin";
			}
		break;
		case "everyhit":
			$userSpec = $session['user']['specialty'] ?? '';
			
			if ($userSpec == $spec) {
				if (get_module_pref("class") != "magic") set_module_pref("class", "magic");
				if (get_module_pref("subclass") != "1") set_module_pref("subclass", "1");
				
				if (is_module_active('alignment')) {
					@require_once('modules/alignment.php');
					$al = function_exists('get_align') ? get_align() : 0;
					$strip_align = (int)get_module_setting("strip_align");
					
					if ($al < $strip_align) {
						$session['user']['specialty'] = "";
						
						set_module_pref("uses", 0);
						set_module_pref("skill", 0);
						set_module_pref("class", "");
						set_module_pref("subclass", "");
						
						output("`n`\$`bYOUR GOD HAS FORSAKEN YOU!`b`n");
						output("`4Your evil actions have severed your connection to the divine. You have been stripped of your Paladin status and all holy powers!`0`n`n");
					}
				}
			}
			break;
		case "villagenav":
			if (($session['user']['specialty'] ?? '') === $spec) {
				$args['special']['altar'] = "runmodule.php?module=racespecialtypaladin&op=altar";
				$args['special']['archives'] = "runmodule.php?module=racespecialtypaladin&op=archives";
			}
			break;
	}
	return $args;
}

function racespecialtypaladin_change() {
    global $session;
    $spec="PL"; 
    if (get_module_setting("wepchange")==1 && ($session['user']['specialty'] ?? '') == $spec) {
        $nstr = "`%".racespecialtypaladin_namer("Y");
        
        if (!preg_match('/' . preg_quote($nstr, '/') . '/', $session['user']['weapon'])) {
            $n = $nstr." ".$session['user']['weapon'];
            if (strlen($n) < 50) $session['user']['weapon'] = $n;
        }
        
        if (!preg_match('/' . preg_quote($nstr, '/') . '/', $session['user']['armor'])) {
            $n = $nstr." ".$session['user']['armor'];
            if (strlen($n) < 50) $session['user']['armor'] = $n;
        }
    }
}

function racespecialtypaladin_run(){
	global $session;
	$op = httpget('op');
	$subop = httpget('subop');
	
	if ($op == "altar") {
		page_header("Divine Altar");
		output("`c`b`!Cathedral Altar of Light`b`c`n`n");
		
		if ($subop == "") {
			output("`2You stand before the grand Cathedral Altar. Divine light streams down from the high cathedral windows, casting a warm glow over the holy altar.`n`n");
			output("`2Here, you may pray to show your faith, make a gold offering, or spend time helping the poor to do a good deed.`n`n");
			
			$prayed = get_module_pref("altar_prayed");
			if ($prayed) {
				output("`&You have already prayed today.`n`n");
			} else {
				addnav("Pray", "runmodule.php?module=racespecialtypaladin&op=altar&subop=pray");
			}
			
			if ($session['user']['gold'] >= 500) {
				addnav("Make Offering (500 gold)", "runmodule.php?module=racespecialtypaladin&op=altar&subop=offering");
			}
			
			if ($session['user']['turns'] >= 1) {
				addnav("Perform Good Deed (1 Turn)", "runmodule.php?module=racespecialtypaladin&op=altar&subop=deed");
			}
			
		} elseif ($subop == "pray") {
			set_module_pref("altar_prayed", 1);
			$roll = e_rand(1, 3);
			if ($roll == 1) {
				output("`2You kneel and offer a deep, sincere prayer.`n`n`@Your soul is filled with divine energy! You receive `^2 `2extra forest fights for today!`n`n");
				$session['user']['turns'] += 2;
			} elseif ($roll == 2) {
				output("`2You kneel and offer a deep, sincere prayer.`n`n`@A holy light descends upon you, completely healing all of your wounds!`n`n");
				$session['user']['hitpoints'] = $session['user']['maxhitpoints'];
			} else {
				output("`2You kneel and offer a deep, sincere prayer.`n`n`@You feel a divine blessing bolster your strength! (+25%% Attack/Defense buff today)`n`n");
				apply_buff("altarblessing", array(
					"name"=>"`!Blessing of Light`0",
					"atkmod"=>1.25,
					"defmod"=>1.25,
					"rounds"=>-1,
					"schema"=>"module-racespecialtypaladin",
				));
			}
			
		} elseif ($subop == "offering") {
			if ($session['user']['gold'] >= 500) {
				$session['user']['gold'] -= 500;
				output("`2You place 500 gold on the altar as a donation to the poor.`n`n`@Your selflessness pleases your deity! (Alignment increased)`n`n");
				if (is_module_active('alignment')) {
					$current_align = (int)get_module_pref('alignment', 'alignment');
					$new_align = min(100, $current_align + 10);
					set_module_pref('alignment', $new_align, 'alignment');
				}
			} else {
				output("`2You do not have enough gold to make this offering.`n`n");
			}
			
		} elseif ($subop == "deed") {
			if ($session['user']['turns'] >= 1) {
				$session['user']['turns']--;
				output("`2You spend some time helping the local priests tend to the needy and clean the Cathedral.`n`n`@Your hard work and humility shine brightly! (Alignment increased)`n`n");
				if (is_module_active('alignment')) {
					$current_align = (int)get_module_pref('alignment', 'alignment');
					$new_align = min(100, $current_align + 5);
					set_module_pref('alignment', $new_align, 'alignment');
				}
			} else {
				output("`2You do not have any turns left to perform a good deed.`n`n");
			}
		}
		
		addnav("Go Back", "village.php");
		page_footer();
	}
	
	if ($op == "archives") {
		page_header("Order's Archives");
		output("`c`b`!Order's Archives`b`c`n`n");
		
		if ($subop == "") {
			output("`2You enter the ancient library of the Paladin Order. Shelves upon shelves of ancient scrolls, books of wisdom, and holy scriptures surround you.`n`n");
			output("`2Here, you may spend some quality time studying the holy scriptures to grow your Paladin powers.`n`n");
			
			$studied = get_module_pref("archives_studied");
			if ($studied) {
				output("`&You have already studied the scriptures today.`n`n");
			} else {
				if ($session['user']['turns'] >= 3) {
					addnav("Study Holy Texts (3 Turns)", "runmodule.php?module=racespecialtypaladin&op=archives&subop=study");
				} else {
					output("`7You need at least 3 turns to spend time studying the texts.`n`n");
				}
			}
			
		} elseif ($subop == "study") {
			$studied = get_module_pref("archives_studied");
			if (!$studied && $session['user']['turns'] >= 3) {
				$session['user']['turns'] -= 3;
				set_module_pref("archives_studied", 1);
				
				set_module_pref('skill', (get_module_pref('skill') + 1));
				set_module_pref('uses', (get_module_pref('uses') + 1));
				
				output("`2You spend three turns carefully reading and reflecting on the holy teachings of your deity.`n`n");
				output("`@You grow in wisdom and Paladin power! (+1 Paladin Skill Level and +1 Uses today)`n`n");
			} else {
				output("`2You cannot study at the archives at this time.`n`n");
			}
		}
		
		addnav("Go Back", "village.php");
		page_footer();
	}
	
	if ($op == "accept") {
		page_header("Accept the Calling");
		
		$session['user']['specialty'] = "PL";
		set_module_pref("skill", 0, "racespecialtypaladin");
		set_module_pref("uses", 0, "racespecialtypaladin");
		set_module_pref("class", "magic", "racespecialtypaladin");
		set_module_pref("subclass", "1", "racespecialtypaladin");
		
		$gain = (int)get_module_setting("gain");
		if ($gain > 0 && is_module_active('alignment')) {
			$current_align = (int)get_module_pref('alignment', 'alignment');
			$new_align = min(100, $current_align + $gain);
			set_module_pref('alignment', $new_align, 'alignment');
		}
		
		$session['user']['specialinc'] = "";
		
		output("`2You kneel before the altar. The golden light envelopes you, filling you with divine strength and purpose.`n`n");
		output("`@Your body surges with holy energy as you accept the path of the Paladin. Protect the innocent and maintain your righteousness, or your powers will fade!`n`n");
		
		require_once("lib/addnews.php");
		addnews("%s has heard the divine call in the forest and has become a Paladin of Light!", $session['user']['name']);
		
		addnav("Return to Forest", "forest.php");
		page_footer();
	}
	
	if ($op == "decline") {
		page_header("Decline the Calling");
		
		$session['user']['specialinc'] = "";
		
		output("`2You bow respectfully but decline the offer, stating you are not ready for such a heavy burden.`n`n");
		output("`2The light slowly fades, and the forest returns to normal.`n`n");
		
		addnav("Return to Forest", "forest.php");
		page_footer();
	}
}

function racespecialtypaladin_texts($class,$subclass) {
	global $session;
	$array = array();
	
	$array['magic']['1']['1'] = array(
		"startmsg"=>"`!There is no peace without justice, and as God is your witness, you will dish it out!",
		"name"=>"`#Smite Evil",
		"rounds"=>5,
		"wearoff"=>"Divinity's blessings are powerful, yet brief.",
		"effectmsg"=>"`&With righteous fury, you smite `^{badguy}`& for `^{damage}`& points.",
		"atkmod"=>1.5,
	);
	$array['magic']['1']['2'] = array(
		"startmsg"=>"`!You can feel divine blessings protecting, and shielding you.!",
		"name"=>"`#Divine Grace",
		"rounds"=>10,
		"wearoff"=>"Your divine protection fades.",
		"effectmsg"=>"`!Your angelic protection makes you harder to hit.",
		"defmod"=>1.5,
	);
	$array['magic']['1']['3'] = array(
		"startmsg"=>"`!You often shed blood, but peace is your goal and your hands can heal as well.",
		"name"=>"`#Lay on Hands",
		"rounds"=>5,
		"wearoff"=>"You have exhausted this power.",
		"regen"=>($session['user']['level']*1.5),
		"effectmsg"=>"`!Your hands provide miraculous healing power, healing `#{damage}`! health.",
		"effectnodmgmsg"=>"`2You have no wounds to regenerate.",
	);
	$array['magic']['1']['5'] = array(
		"startmsg"=>"`!All great heroes have an equally great steed. This is yours.",
		"rounds"=>5,
		"name"=>"`#Paladin Mount",
		"minioncount"=>1,
		"damageshield"=>0.5,
		"maxbadguydamage"=>round($session['user']['attack']*1.0,0),
		"minbadguydamage"=>round($session['user']['attack']*0.5,0),
		"effectmsg"=>"`#{badguy}`! strikes at you, but your mount soaks part of the blow and also deals out `#{damage}`! damage back.",
	);
	
	if (($session['user']['specialty'] ?? '') == 'PL') { 
		if (isset($array[$class][$subclass])) {
			foreach ($array[$class][$subclass] as $t => $d) {
				$array[$class][$subclass][$t]['schema']="racespecialtypaladin"; 
			}
			return $array[$class][$subclass];
		} else {
			return array();
		}
	} else {
		return array();
	}
}

function racespecialtypaladin_namer($type='X') {
	$class = array();
	$class['magic']['1']="Paladin Gifts";
	$class['1'] = "Divine"; 
	$class['0'] = "Holy";
	
	if ($type=="X") {
		return $class[get_module_pref("class")][get_module_pref("subclass")] ?? '';
	} else {
		return $class[get_module_pref("subclass")] ?? '';
	}
}

function racespecialtypaladin_test_event() {
	global $session;
	if (($session['user']['specialty'] ?? '') === "PL") {
		return 0;
	}
	
	$min_align = (int)get_module_setting("min_align");
	$al = 0;
	if (is_module_active('alignment')) {
		@require_once('modules/alignment.php');
		$al = function_exists('get_align') ? get_align() : 0;
	} else {
		return 0;
	}
	
	if ($al < $min_align) {
		return 0;
	}
	
	return (int)get_module_setting("forestchance");
}

function racespecialtypaladin_runevent($type) {
	global $session;
	$session['user']['specialinc'] = "module:racespecialtypaladin";
	$op = httpget('op');
	
	if ($op == "" || $op == "search") {
		output("`2While wandering through a quiet, sunlit glade in the forest, a profound silence falls over the woods. ");
		output("The birds stop singing, and the wind dies down. A brilliant beam of pure, golden light breaks through the canopy, ");
		output("illuminating a stone altar covered in ancient ivy.`n`n");
		output("You feel a warm, comforting presence. A voice, resonant and clear, echoes in your mind:`n`n");
		output("`&\"Mortal, your soul shines with a purity rarely seen in these dark lands. Your heart is aligned with justice and light. ");
		output("We offer you a higher calling... become a Paladin, a champion of the divine, and protect the innocent.\"`0`n`n");
		
		$current_spec = $session['user']['specialty'] ?? '';
		if ($current_spec != "" && $current_spec != "0") {
			$specialties = modulehook("specialtynames");
			$spec_name = $specialties[$current_spec] ?? $current_spec;
			output("`b`4WARNING:`b`2 Accepting this path will replace your current specialty (`^%s`2). You will lose all progress in it and begin the path of the Paladin.`n`n", $spec_name);
		}
		
		addnav("Accept the Calling", "runmodule.php?module=racespecialtypaladin&op=accept");
		addnav("Decline the Calling", "runmodule.php?module=racespecialtypaladin&op=decline");
	}
}
?>
