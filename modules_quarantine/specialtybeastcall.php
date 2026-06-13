<?php

function specialtybeastcall_getmoduleinfo(){
	$info = array(
		"name" => "Specialty - Beast Calling",
		"author" => "Chris Vorndran",
		"version" => "1.01",
		"category" => "Specialties",
		"download"=>"http://dragonprime.net/index.php?module=Downloads;sa=dlview;id=46",
		"vertxtloc"=>"http://dragonprime.net/users/Sichae/",
		"description"=>"Specialty that allows a user to summon the power of Beasts to their aid.",
		"settings"=> array(
			"Specialty - Beast Calling Settings,title",
			"mindk"=>"How many DKs do you need before the specialty is available?,int|0",
			"cost"=>"How many points do you need before the specialty is available?,int|0",
      ),
      "prefs" => array(
			"Specialty - Beast Calling User Prefs,title",
			"skill"=>"Skill points in Beast Calling,int|0",
			"uses"=>"Uses of Beast Calling allowed,int|0",
		),
	);
	return $info;
}

function specialtybeastcall_install(){
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

function specialtybeastcall_uninstall(){
	// Reset the specialty of anyone who had this specialty so they get to
	// rechoose at new day
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='BC'";
	db_query($sql);
	return true;
}

function specialtybeastcall_dohook($hookname,$args){
	global $session,$resline;
	
	$spec = "BC";
	$name = translate_inline("Beast Calling");
	$ccode = "`@";
	
	switch ($hookname) {
		case "pointsdesc":
			$args['count']++;
			$format = $args['format'];
			$str = translate("The Beast Calling Specialty is availiable upon reaching %s Dragon Kills and %s points.");
			$str = sprintf($str, get_module_setting("mindk"),
			get_module_setting("cost"));
			output($format, $str, true);
			break;
		case "dragonkill":
			set_module_pref("uses", 0);
			set_module_pref("skill", 0);
			break;
		case "choose-specialty":
			if ($session['user']['specialty'] == "" || $session['user']['specialty'] == '0') {
				$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
				if ($session['user']['dragonkills'] < get_module_setting("mindk") || get_module_setting("cost") > $pointsavailable) break;
				addnav("$ccode$name`0","newday.php?setspecialty=$spec$resline");
				$t1 = translate_inline("For years before you were here, many folk of the woods, have adapted the art of Beast Calling.");
				$t2 = appoencode(translate_inline("$ccode$name`0"));
				rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
				addnav("","newday.php?setspecialty=$spec$resline");
			}
			break;
		case "set-specialty":
			if($session['user']['specialty'] == $spec) {
				page_header($name);
				$session['user']['donationspent'] = $session['user']['donationspent'] - $cost;
				output("`@All along the river you used to walk, noticing the bears, and the deer, that came there to drink. ");
				output("Over the years, your senses became more tuned to listen on their subtle vibrations and noises. ");
				output("Whilst you spent more and more time with them, you learned how to talk back.");
				output("Talking back to them, allowed you to call them at any point, any time. ");
				output("Since that event, your life has never been the same... ");
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
			$args[$spec] = "specialtybeastcall";
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
				}
				
				else {
					if (3-$x == 1) {
						output("`n`^Only 1 more skill level until you gain an extra use point!`n");
					}
					
					else {
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
				}else{
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
				addnav(array("%s &#149; %s`7 (%s)`0",$ccode, translate_inline("Swarm of Flies"), 1), 
				$script."op=fight&skill=$spec&l=1", true);
			}
			if ($uses > 1) {
				addnav(array("%s &#149; %s`7 (%s)`0",$ccode, translate_inline("Phoenix"), 2),
				$script."op=fight&skill=$spec&l=2",true);
			}
			if ($uses > 2) {
				addnav(array("%s &#149; %s`7 (%s)`0",$ccode, translate_inline("Spirit Wolf"), 3),
				$script."op=fight&skill=$spec&l=3",true);
			}
			if ($uses > 4) {
				addnav(array("%s &#149; %s`7 (%s)`0",$ccode, translate_inline("Dragon"), 5),
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
							apply_buff('bc1',
								array(
									"startmsg"=>"`@You close your eyes, shrieking a high-pitched wail!",
									"name"=>"`@Swarm of Flies",
									"rounds"=>5,
									"wearoff"=>"`2The cloud of flies vanishes ... you can see through them to the `\${badguy}`2.",
									"minioncount"=>round(get_module_pref("skill")/3),
									"minbadguydamage"=>0,
									"maxbadguydamage"=>1,
									"effectmsg"=>"`2A fly hits {badguy}`2 for `^{damage}`2 damage.",
									"effectnodmgmsg"=>"`2A fly tries to hit {badguy}`2 but `\$MISSES`2!",
									"schema"=>"module-specialtybeastcall"
								)
							);
							break;
						case 2:
							apply_buff('bc2',
								array(
									"startmsg"=>"`2You close your eyes, humming a high, melodic tune...",
									"name"=>"`@Phoenix",
									"rounds"=>5,
									"wearoff"=>"You have stopped being healed from the `@Phoenix",
									"regen"=>ceil($session['user']['level']/2),
									"effectmsg"=>"`2You regenerate for {damage} health, because of the `@Phoenix.",
									"effectnodmgmsg"=>"`2You have no wounds to regenerate.",
									"schema"=>"module-specialtybeastcall"
								)
							);
							break;
						case 3:
							apply_buff('bc3'
								,array(
									"startmsg"=>"`2You close your eyes, humming a low, dulcet tune...",
									"name"=>"`@Spirit Wolf",
									"rounds"=>5,
									"wearoff"=>"The vines begin to disappear, leaving {badguy} quite angered.",
									"atkmod"=>(e_rand(2,3)/2),
									"defmod"=>(e_rand(2,3)/2),
									"roundmsg"=>"`2You strike {badguy}, knowing your `@Spirit Wolf `2is backing you!", 
									"schema"=>"module-specialtybeastcall"
								)
							);
							break;
						case 5:
							apply_buff('bc5'
								,array(
									"startmsg"=>"`2You close your eyes, then emit a powerful roar!",
									"name"=>"`@Dragon",
									"rounds"=>3,
									"wearoff"=>"The Dragon disappears ... flying off into the night...",
									"minion"=>1,
									"invulnerable"=>1,
									"roundmsg"=>"The Dragon defends you with it's Mighty Strength!",
									"schema"=>"module-specialtybeastcall"
								)
							);
						break;
					}
					set_module_pref("uses", get_module_pref("uses") - $l);
				}else{
					apply_buff('ar0',
						array(
							"startmsg"=>"You are unable to find your Arcane Training Kit and therefore, can not draw any runes!",
							"rounds"=>1,
							"schema"=>"module-specialtybeastcall"
						)
					);
				}
			}
			break;
		}
	return $args;
}

?>