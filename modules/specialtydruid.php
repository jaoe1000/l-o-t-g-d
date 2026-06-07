<?php
/*

This is part of a series of specialties I'm making to recreate the core D&D classes in LoGD.

-- Enderandrew


*/

function specialtydruid_getmoduleinfo(){
	$info = array(
		"name" => "Specialty - Druid",
		"author" => "`!Enderandrew",
		"version" => "1.01",
		"download" => "http://dragonprime.net/users/enderwiggin/specialitydruid.zip",
		"category" => "Specialties",
		"description"=>"This adds a D&D inspired Druid to the game.",
		"vertxtloc"=>"http://dragonprime.net/users/enderwiggin/",
		"prefs" => array(
			"Specialty - Druid User Prefs,title",
			"skill"=>"Skill points in Druid Spells,int|0",
			"uses"=>"Uses of Druid Spells allowed,int|0",
		),
		"settings"=> array(
			"Specialty - Druid Settings,title",
			"mindk"=>"How many DKs do you need before the specialty is available?,int|5",
			"cost"=>"How many points do you need before the specialty is available?,int|5",
		),
	);
	return $info;
}

function specialtydruid_install(){
	$specialty="DR";
	module_addhook("apply-specialties");
	module_addhook("castlelib");
	module_addhook("castlelibbook");
	module_addhook("choose-specialty");
	module_addhook("dragonkill");
	module_addhook("fightnav-specialties");
	module_addhook("incrementspecialty");
	module_addhook("newday");
	module_addhook("pointsdesc");
	module_addhook("set-specialty");
	module_addhook("specialtycolor");
	module_addhook("specialtymodules");
	module_addhook("specialtynames");
	return true;
}

function specialtydruid_uninstall(){
	// Reset the specialty of anyone who had this specialty so they get to
	// rechoose at new day
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='DR'";
	db_query($sql);
	return true;
}

function specialtydruid_dohook($hookname,$afis){
	global $session,$resline;
	tlschema("fightnav");

	$spec = "DR";
	$name = "Druid Spells";
	$ccode = "`6";
	$cost = get_module_setting("cost");
	$op69 = httpget('op69');

	switch ($hookname) {

	case "apply-specialties":
		$skill = httpget('skill');
		$l = httpget('l');
		if ($skill==$spec){
			if (get_module_pref("uses") >= $l){
				switch($l){
				case 1:
					apply_buff('dr1', array(
						"startmsg"=>"`6You summon the cute and fluffy animals around you, and ask them to fight to the death.",
						"name"=>"`^Animal Companion",
						"rounds"=>5,
						"wearoff"=>"The animals are distracted and scamper off.",
						"minioncount"=>1,
						"minbadguydamage"=>round(get_module_pref("skill")/3,0),
						"maxbadguydamage"=>round(get_module_pref("skill")/2,0)+3,
						"effectmsg"=>"Your animal companions nibble on {badguy} for {damage} damage!",
						"effectnodmgmsg"=>"Your animal was distracted and missed {badguy}!",
						"schema"=>"specialtydruid"
					));
					break;
				case 2:
					apply_buff('dr2', array(
						"startmsg"=>"`6You cast the `^Barkskin`6 spell, and your skin hardens, providing you with defense!",
						"name"=>"`^Barkskin",
						"rounds"=>5,
						"defmod"=>1.6,
						"schema"=>"specialtydruid"
					));
					break;
				case 3:
					apply_buff('dr3', array(
						"startmsg"=>"`6Your knowledge of nature spirits allows you to shape shift by casting `^Wild Shape`6!",
						"name"=>"`^Wild Shape",
						"rounds"=>5,
						"wearoff"=>"You slip back into your shape as the spirit leaves you.",
						"effectmsg"=>"`^Your animal claws strike {badguy} for {damage} damage.",
						"effectnodmgmsg"=>"`^You strike at {badguy}, but miss completely!",
						"atkmod"=>1.75,
						"defmod"=>1.75,
						"schema"=>"specialtydruid"
					));
					break;
				case 5:
					apply_buff('dr5', array(
						"startmsg"=>"`6You call upon the most powerful of nature spirits and summon `^Elementals`6!",
						"name"=>"`^Elemental Swarm",
						"rounds"=>5,
						"wearoff"=>"`6The `^Elementals`6 don't belong on this plane, and return to their planes.",
						"minioncount"=>3,
						"minbadguydamage"=>round(get_module_pref("skill")/3,0),
						"maxbadguydamage"=>round(get_module_pref("skill")/2,0)+3,
						"effectmsg"=>"`6The `^Elementals`6 smash {badguy} for {damage} damage!",
						"effectnodmgmsg"=>"`6The `^Elementals`6 completely miss!",
						"schema"=>"specialtydruid"
					));
					break;
				}
				set_module_pref("uses", get_module_pref("uses") - $l);
			}else{
				apply_buff('dr0', array(
					"startmsg"=>"`^You reach out to the nature spirits and get a busy signal.",
					"rounds"=>1,
					"schema"=>"specialtydruid"
				));
			}
		}
		break;

	case "castlelib":
		if ($op69 == 'druid'){
			output("You sit down and open up the Ye Olde Bible.`n");
			output("You read for a while... in the time it takes you to read you use up`n");
			output("3 Turns.`n`n");
			output("You pick up a book you thought was on animation, since everybody loves cut little`n");
			output("cartoons, but this book is on Animism, the theory that everything has a spirit in`n");
			output("it.  You use this knowledge to gain power as a Druid!`n");
			$session['user']['turns']-=3;
			set_module_pref('skill',(get_module_pref('skill','specialtydruid') + 1),'specialtydruid');
			set_module_pref('uses', get_module_pref("uses",'specialtydruid') + 1,'specialtydruid');
			addnav("Continue","runmodule.php?module=lonnycastle&op=library");
			}
		break;

	case "castlelibbook":
		output("Animism 4 Dummies. (3 Turns)`n");
		addnav("Read a Book");
		addnav("Animism 4 Dummies","runmodule.php?module=lonnycastle&op=library&op69=druid");
		break;

	case "choose-specialty":
		if ($session['user']['dragonkills']>=get_module_setting("mindk")) {
			if ($session['user']['specialty'] == "" ||
				$session['user']['specialty'] == '0') {
				addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline");
				$t1 = translate_inline("Playing with all the furry animals");
				$t2 = appoencode(translate_inline("$ccode$name`0"));
				rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
				addnav("","newday.php?setspecialty=$spec$resline");
			}
		}
		break;
		
	case "dragonkill":
		set_module_pref("uses", 0);
		set_module_pref("skill", 0);
		break;

	case "fightnav-specialties":
		$uses = get_module_pref("uses");
		$script = $afis['script'];
		if ($uses > 0) {
			addnav(array("%s%s (%s points)`0", $ccode, $name, $uses), "");
			addnav(array("%s &#149; Animal Companion`7 (%s)`0", $ccode, 1), 
					$script."op=fight&skill=$spec&l=1", true);
		}
		if ($uses > 1) {
			addnav(array("%s &#149; Barkskin`7 (%s)`0", $ccode, 2),
					$script."op=fight&skill=$spec&l=2",true);
		}
		if ($uses > 2) {
			addnav(array("%s &#149; Wild Shape`7 (%s)`0", $ccode, 3),
					$script."op=fight&skill=$spec&l=3",true);
		}
		if ($uses > 4) {
			addnav(array("%s &#149; Elemental Swarm`7 (%s)`0", $ccode, 5),
					$script."op=fight&skill=$spec&l=5",true);
		}
		break;

	case "incrementspecialty":
		if($session['user']['specialty'] == $spec) {
			$new = get_module_pref("skill") + 1;
			set_module_pref("skill", $new);
			$c = $afis['color'];
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
		$bonus = getsetting("specialtybonus", 1);
		if($session['user']['specialty'] == $spec) {
			if ($bonus == 1) {
				output("`n`2For being interested in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name,$ccode,$name);
			} else {
				output("`n`2For being interested in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n",$ccode,$name,$bonus,$ccode,$name);
			}
		}
		$amt = (int)(get_module_pref("skill") / 3);
		if ($session['user']['specialty'] == $spec) $amt++;
		set_module_pref("uses", $amt);
		break;

	case "pointsdesc":
		$cost = get_module_setting("cost");
		if ($cost > 0){
			$afis['count']++;
			$format = $afis['format'];
			$str = translate("The Druid Specialty is availiable upon reaching %s Dragon Kills and %s points.");
			$str = sprintf($str, get_module_setting("mindk"),$cost);
		}
		output($format, $str, true);
		break;

	case "set-specialty":
		if($session['user']['specialty'] == $spec) {
			page_header($name);
			$session['user']['donationspent'] = $session['user']['donationspent'] + $cost;
			output("Growing up as a child, all your friends were training to become knights and warriors, while ");
			output("you felt there was more to life than swinging a a sword, or a rake, around.  Well, maybe ");
			output("these kids weren't so much your friends as bullies who beat you up.  But they did take the ");
			output("time to notice you and give you wedgies.  Eventually you realized your true friends were ");
			output("the animals you tended to.  You were especially close with the sheep, but that's another ");
			output("story for another day.`n`n");
			output("One day the animals started talking to you.  And either that means you are a crazy bastard, ");
			output("or you've become a powerful Druid...`n`n");
		}
		break;

	case "specialtycolor":
		$afis[$spec] = $ccode;
		break;

	case "specialtymodules":
		$afis[$spec] = "specialtydruid";
		break;

	case "specialtynames":
		$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
		if ($session['user']['superuser'] & SU_EDIT_USERS || $session['user']['dragonkills'] >= get_module_setting("mindk") || get_module_setting("cost") <= $pointsavailable){
			$afis[$spec] = translate_inline($name);
		}
		break;
	}
	return $afis;
}

function specialtydruid_run(){
}
?>