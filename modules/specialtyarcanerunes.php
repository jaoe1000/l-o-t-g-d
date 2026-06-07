<?php

function specialtyarcanerunes_getmoduleinfo(){
	$info = array(
		"name" => "Specialty - Arcane Runes",
		"author" => "Chris Vorndran",
		"version" => "1.01",
		"category" => "Specialties",
		"download"=>"http://dragonprime.net/index.php?module=Downloads;sa=dlview;id=45",
		"vertxtloc"=>"http://dragonprime.net/users/Sichae/",
		"description"=>"Specialty that allows a user to use the power of runes to help them in battle.",
		"settings"=> array(
			"Specialty - Arcane Runes Settings,title",
			"mindk"=>"How many DKs do you need before the specialty is available?,int|0",
			"cost"=>"How many points do you need before the specialty is available?,int|0",
      ),
      "prefs" => array(
			"Specialty - Arcane Runes User Prefs,title",
			"skill"=>"Skill points in Arcane Runes,int|0",
			"uses"=>"Uses of Arcane Runes allowed,int|0",
		),
	);
	return $info;
}

function specialtyarcanerunes_install(){
	module_addhook("choose-specialty");
	module_addhook("set-specialty");
	module_addhook("fightnav-specialties");
	module_addhook("apply-specialties");
	module_addhook("newday");
	module_addhook("incrementspecialty");
	module_addhook("specialtynames");
	module_addhook("specialtycolor");
	module_addhook("specialtymodules");
	module_addhook("dragonkill");
	module_addhook("pointsdesc");
	return true;
}

function specialtyarcanerunes_uninstall(){
	// Reset the specialty of anyone who had this specialty so they get to
	// rechoose at new day
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='AR' OR specialty='AS'";
	db_query($sql);
	return true;
}

function specialtyarcanerunes_dohook($hookname,$args){
	global $session,$resline;
	
	$spec = "AR";
	$name = translate_inline("Arcane Runes");
	$ccode = "`6";
	
	switch ($hookname) {
		case "pointsdesc":
			$args['count']++;
			$format = $args['format'];
			$str = translate("The Arcane Specialty is availiable upon reaching %s Dragon Kills and %s points.");
			$str = sprintf($str, get_module_setting("mindk"),
			get_module_setting("cost"));
			output($format, $str, true);
			break;
		case "dragonkill":
			set_module_pref("uses", 0);
			set_module_pref("skill", 0);
			break;
		case "choose-specialty":
			if ($session['user']['specialty'] == "" || $session['user']['specialty'] == '0'){
				$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
				if ($session['user']['dragonkills'] < get_module_setting("mindk") 
					|| get_module_setting("cost") > $pointsavailable) break;
				addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline");
				$t1 = translate_inline("From the beginning of time, masters of the Arcane Runes, have had utter control of all.");
				$t2 = appoencode(translate_inline("$ccode$name`0"));
				rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
				addnav("","newday.php?setspecialty=$spec$resline");
			}
			break;
		case "set-specialty":
			if($session['user']['specialty'] == $spec) {
				page_header($name);
				$session['user']['donationspent'] = $session['user']['donationspent'] - $cost;
				output("`6Amongst the toys of childhood, you had found a small rune, with some mysterious markings upon them. ");
				output("After a while, you began to see the power of these stones and were able to use them for your own benefit. ");
				output("During a great battle of your youth, you acquired the ability to create and mark your own Runes. ");
				output("You saw the most wondrous things you had ever seen, when you watched as your army and friends were able to best your opposition. ");
				output("Since that battle, your life has never been the same... ");
			}
			break;
		case "specialtycolor":
			$args[$spec] = $ccode;
			break;
		case "specialtynames":
			$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
			if ($session['user']['superuser'] & SU_EDIT_USERS || $session['user']['dragonkills'] >= get_module_setting("mindk") || get_module_setting("cost") <= $pointsavailable){
			$args[$spec] = $name;
			}
			break;
		case "specialtymodules":
			$args[$spec] = "specialtyarcanerunes";
			break;
		case "incrementspecialty":
			if($session['user']['specialty'] == $spec) {
				$new = get_module_pref("skill") + 1;
				set_module_pref("skill", $new);
				$c = $args['color'];
				output("`n%sYou gain a level in `&%s%s to `#%s%s!", $c, $name, $c, $new, $c);
				$x = $new % 3;
				
				if ($x == 0) {
					output("`n`^You gain an extra use point!`n");
					set_module_pref("uses", get_module_pref("uses") + 1);
				}else {
					if (3-$x == 1) {
						output("`n`^Only 1 more skill level until you gain an extra use point!`n");
					}else {
						output("`n`^Only %s more skill levels until you gain an extra use point!`n", (3-$x));
					}
				}
				output_notl("`0");
			}
			break;
		case "newday":
			$bonus = getsetting("specialtybonus", 1);
	
			if($session['user']['specialty'] == $spec) {
				if ($bonus) {
					output("`n`3For being interested in %s%s`3, you gain `^1`3 extra use of `&%s%s`3 for today.`n",$ccode,$name,$ccode,$name);
				}else {
					output("`n`3For being interested in %s%s`3, you gain `^%s`3 extra uses of `&%s%s`3 for today.`n",$ccode,$name,$bonus,$ccode,$name);
				}
			}
			
			$amt = (int)(get_module_pref("skill") / 3);
			if ($session['user']['specialty'] == $spec) $amt++;
			set_module_pref("uses", $amt);
			break;
		case "fightnav-specialties":
			$uses = get_module_pref("uses");
			$script = $args['script'];
			
			if ($uses > 0) {
				addnav(array("$ccode$name (%s points)`0", $uses), "");
				addnav(array("%s &#149; %s`7 (%s)`0",$ccode, translate_inline("Odin's Illusion Rune"), 1), 
				$script."op=fight&skill=$spec&l=1", true);
			}
			if ($uses > 1) {
				addnav(array("%s &#149; %s`7 (%s)`0",$ccode, translate_inline("Binding Rune"), 2),
				$script."op=fight&skill=$spec&l=2",true);
			}
			if ($uses > 2) {
				addnav(array("%s &#149; %s`7 (%s)`0",$ccode, translate_inline("Ogastafur Rune"), 3),
				$script."op=fight&skill=$spec&l=3",true);
			}
			if ($uses > 4) {
				addnav(array("%s &#149; %s`7 (%s)`0",$ccode, translate_inline("End of Strife Rune"), 5), 
				$script."op=fight&skill=$spec&l=5",true);
			}
			break;
		case "apply-specialties":
			$skill = httpget('skill');
			$l = httpget('l');
			
			if ($skill == $spec){
				if (get_module_pref("uses") >= $l){
					switch($l){
						case 1:
							apply_buff('ar1',
								array(
									"startmsg"=>"`6You draw out a small rune upon a rock, and watch as an illusion of yourself appears.",
									"name"=>"`6Odin's Illusion Rune",
									"rounds"=>5,
									"wearoff"=>"Your illusion disappates ... you can see through it to {badguy}.",
									"minioncount"=>1,
									"minbadguydamage"=>round($session['user']['level']/3,0),
									"maxbadguydamage"=>round($session['user']['level']/2,0)+5,
									"effectmsg"=>"`)Your illusion hits {badguy}`) for `^{damage}`) damage.",
									"effectnodmgmsg"=>"`)Your illusion tries to hit {badguy}`) but `\$MISSES`)!",
									"schema"=>"module-specialtyarcanerunes"
								)
							);
							break;
						case 2:
							apply_buff('ar2',
								array(
									"startmsg"=>"`6You quickly draw out a rune, and watch as 4 vines pass around {badguy} and hold him tight!",
									"name"=>"`6Binding Rune",
									"rounds"=>5,
									"wearoff"=>"The vines begin to disappear, leaving {badguy} quite angered.",
									"badguydefmod"=>e_rand(2,4)/10,
									"badguyatkmod"=>e_rand(2,4)/10,
									"roundmsg"=>"You strike {badguy}, knowing that it can't strike back!", 
									"schema"=>"module-specialtyarcanerunes"
								)
							);
							break;
						case 3:
							apply_buff('ar3'
								,array(
									"startmsg"=>"`6Your rune carving skills are put to the test, as you begin to carve out a difficult rune into your hand!",
									"name"=>"`6Ogastafur Rune",
									"rounds"=>5,
									"wearoff"=>"The rune fades from your hands, and you feel less powerful.",
									"atkmod"=>1.45,
									"defmod"=>1.45,
									"roundmsg"=>"The rune burns into your hand, bolstering your attack and defense!",
									"schema"=>"module-specialtyarcanerunes"
								)
							);
							break;
						case 5:
							apply_buff('ar5'
								,array(
									"startmsg"=>"`6Your rune carving skills are put to the test, as you begin to carve out the most difficult rune into your hand!",
									"name"=>"`6End of Strife Rune",
									"rounds"=>5,
									"wearoff"=>"The rune fades from your hands, and you feel less powerful.",
									"lifetap"=>1.6,
									"roundmsg"=>"You begin to feel power being returned, as you strike {badguy} blow for blow!",
									"schema"=>"module-specialtyarcanerunes"
								)
							);
							break;
						}
					set_module_pref("uses", get_module_pref("uses") - $l);
				}else {
					apply_buff('ar0',
						array(
							"startmsg"=>"You are unable to find your Arcane Training Kit and therefore, can not draw any runes!",
							"rounds"=>1,
							"schema"=>"module-specialtyarcanerunes"
						)
					);
				}
			}
			break;
		}
	return $args;
}
?>
