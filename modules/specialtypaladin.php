<?php
/*

This part of my D&D Specialty series.  I am doing the core D&D classes as specialties.
This specialty also has an alignment hook.  Paladins with a perfect, pure alignment get
a bonus, while those with negative alignments have no skill uses for the day.

*/

function specialtypaladin_getmoduleinfo(){
	$info = array(
		"name" => "Specialty - Paladin",
		"author" => "`!Enderandrew<br>aka T. J. Brumfield`0",
		"version" => "1.2",
		"download" => "http://dragonprime.net/users/enderwiggin/specialitypaladin.zip",
		"vertxtloc"=>"http://dragonprime.net/users/enderwiggin/",
		"category" => "Specialties",
		"description"=>"This will add a D&D inspired Paladin to the game.",
		"requires"=>array(
			"alignment" => "1.71|`1Enderandrew, http://dragonprime.net/users/enderwiggin/alignment98.zip",
		),
		"settings"=> array(
			"Specialty - Paladin Settings,title",
			"mindk"=>"How many DKs do you need before the specialty is available?,int|5",
			"cost"=>"How many points do you need before the specialty is available?,int|5",
			"gain"=>"How much Alignment is gained when specialty is chosen,int|20",
		),
		"prefs" => array(
			"Specialty - Paladin User Prefs,title",
			"skill"=>"Skill points in Paladin Senses,int|0",
			"uses"=>"Uses of Paladin Senses allowed,int|0",
		),
	);
	return $info;
}

function specialtypaladin_install(){
	$specialty="PL";
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

function specialtypaladin_uninstall(){
	// Reset the specialty of anyone who had this specialty so they get to
	// rechoose at new day
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='PL'";
	db_query($sql);
	return true;
}

function specialtypaladin_dohook($hookname,$args){
	global $session,$resline;
	tlschema("fightnav");

	$spec = "PL";
	$name = "Paladin Gifts";
	$ccode = "`#";
	$cost = get_module_setting("cost");
	$op69 = httpget('op69');
	$gain = get_module_setting("gain");

	switch ($hookname) {

	case "apply-specialties":
		$skill = httpget('skill');
		$l = httpget('l');
		if ($skill==$spec){
			if (get_module_pref("uses") >= $l){
				switch($l){
				case 1:
					apply_buff('pl1', array(
						"startmsg"=>"`!There is no peace without justice, and as God is your witness, you will dish it out!",
						"name"=>"`#Smite Evil",
						"rounds"=>5,
						"wearoff"=>"Divinity's blessings are powerful, yet brief.",
						"effectmsg"=>"`&With righteous fury, you smite `^{badguy}`& for `^{damage}`& points.",
						"atkmod"=>1.5,
						"schema"=>"specialtypaladin"
					));
					break;
				case 2:
					apply_buff('pl2', array(
						"startmsg"=>"`!You can feel divine blessings protecting, and shielding you.!",
						"name"=>"`#Divine Grace",
						"rounds"=>10,
						"wearoff"=>"Your divine protection fades.",
						"effectmsg"=>"`!Your angelic protection makes you harder to hit.",
						"defmod"=>1.5,
						"schema"=>"specialtypaladin"
					));
					break;
				case 3:
					apply_buff('pl3', array(
						"startmsg"=>"`!You often shed blood, but peace is your goal and your hands can heal as well.",
						"name"=>"`#Lay on Hands",
						"rounds"=>5,
						"wearoff"=>"You have exhausted this power.",
						"regen"=>($session['user']['level']*1.5),
						"effectmsg"=>"`!Your hands provide miraculous healing power, healing `#{damage}`! health.",
						"effectnodmgmsg"=>"`2You have no wounds to regenerate.",
						"schema"=>"specialtypaladin"
					));
					break;
				case 5:
					apply_buff('pl5', array(
						"startmsg"=>"`!All great heroes have an equally great steed.  This is yours.",
						"rounds"=>5,
						"name"=>"`#Paladin Mount",
						"minioncount"=>1,
						"damageshield"=>0.5,
						"maxbadguydamage"=>round($session['user']['attack']*2.5,0),
						"minbadguydamage"=>round($session['user']['attack']*1.5,0),
						"effectmsg"=>"`#{badguy}`! strikes at you, but your mount soaks part of the blow and also deals out `#{damage}`! damage back.",
						"schema"=>"specialtypaladin"
					));
					break;
				}
				set_module_pref("uses", get_module_pref("uses") - $l);
			}else{
				apply_buff('pl0', array(
					"startmsg"=>"You call on your deity above, but they do not need your call.  They were busy heeding nature's call.",
					"rounds"=>1,
					"schema"=>"specialtypaladin"
				));
			}
		}
		break;

	case "castlelib":
		if ($op69 == 'paladin'){
			output("You sit down and open up the Ye Olde Bible.`n");
			output("You read for a while... in the time it takes you to read you use up`n");
			output("3 Turns.`n`n");
			output("You spend some quality time with the good book of your choosen deity, and they are,`n");
			output("quite pleased.  You learn more about your deity, and how to appease them.  You grow`n");
			output("in power as a Paladin, and your deity gives you a merit badge to boot!`n");
			$session['user']['turns']-=3;
			set_module_pref('skill',(get_module_pref('skill','specialtypaladin') + 1),'specialtypaladin');
			set_module_pref('uses', get_module_pref("uses",'specialtypaladin') + 1,'specialtypaladin');
			addnav("Continue","runmodule.php?module=lonnycastle&op=library");
			}
		break;

	case "castlelibbook":
		output("Ye Olde Bible. (3 Turns)`n");
		addnav("Read a Book");
		addnav("Ye Olde Bible","runmodule.php?module=lonnycastle&op=library&op69=paladin");
		break;

	case "choose-specialty":
		if ($session['user']['dragonkills']>=get_module_setting("mindk")) {
			if ($session['user']['specialty'] == "" ||
				$session['user']['specialty'] == '0') {
				addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline");
				$t1 = translate_inline("being pompous and pious");
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
		$script = $args['script'];
		if ($uses > 0) {
			addnav(array("%s%s (%s points)`0", $ccode, $name, $uses), "");
			addnav(array("%s &#149; Smite Enemy`7 (%s)`0", $ccode, 1), 
					$script."op=fight&skill=$spec&l=1", true);
		}
		if ($uses > 1) {
			addnav(array("%s &#149; Divine Grace`7 (%s)`0", $ccode, 2),
					$script."op=fight&skill=$spec&l=2",true);
		}
		if ($uses > 2) {
			addnav(array("%s &#149; Lay on Hands`7 (%s)`0", $ccode, 3),
					$script."op=fight&skill=$spec&l=3",true);
		}
		if ($uses > 4) {
			addnav(array("%s &#149; Paladin Mount`7 (%s)`0", $ccode, 5),
					$script."op=fight&skill=$spec&l=5",true);
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
		if($session['user']['specialty'] == $spec) {
			if (is_module_active('alignment')) {
				$al = get_align();
				$paladin = ($al-49);
			}
			if($paladin > 0) {
				$bonus = getsetting("specialtybonus", 1);
					if ($bonus == 1) {
						output("`n`2For being interested in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name,$ccode,$name);
					} else {
						output("`n`2For being interested in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n",$ccode,$name,$bonus,$ccode,$name);
					}
				$amt = (int)(get_module_pref("skill") / 3);
				$paladinbonus = (int)($paladin / 10);
				if($paladinbonus > 0) {
					output("`n`2For being such a noble creature, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name);
					$amt += $paladinbonus;
				}
				if ($session['user']['specialty'] == $spec) $amt++;
				set_module_pref("uses", $amt);
			}else{
				$bonus = 0;
				output("`n`2Your power stems from righteousness which you lack so, you receive no `&%s%s`2 uses for today.`n",$ccode,$name);
			}
		}
		break;

	case "pointsdesc":
		$cost = get_module_setting("cost");
		if ($cost > 0){
			$args['count']++;
			$format = $args['format'];
			$str = translate("The Paladin Specialty is availiable upon reaching %s Dragon Kills and %s points.");
			$str = sprintf($str, get_module_setting("mindk"),$cost);
		}
		output($format, $str, true);
		break;

	case "set-specialty":
		if($session['user']['specialty'] == $spec) {
			page_header($name);
			$session['user']['donationspent'] = $session['user']['donationspent'] - $cost;
			output("`#You knew as a young child, that yours was a higher calling.  You were touched, a Messenger. ");
			output("`#Your devout faith and gifts were a blessing from God, and it was your destiny in life to ");
			output("`#carry God's message to the unwashed masses.`n");
			output("`^Well, that's part of the story anyway.`n`n");
			output("`#You're also ugly as sin, and not very good with members of the opposite sex.  If you have to ");
			output("`#go without nookie, you might as well reap some of the better benefits, such as nifty divine powers.`n`n");
			output("`^It should be noted that your power stems from righteous behavior.  So long as your morals are just, your.");
			output("`^power shall be mighty.  If you slip in your faith and judgement, your divine gifts will be yanked away...");
			if (is_module_active('alignment')) {
				set_module_pref('alignment',get_module_pref('alignment','alignment') - $gain,'alignment');
			}
		}
		break;

	case "specialtycolor":
		$args[$spec] = $ccode;
		break;

	case "specialtymodules":
		$args[$spec] = "specialtypaladin";
		break;

	case "specialtynames":
		$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
		if ($session['user']['superuser'] & SU_EDIT_USERS || $session['user']['dragonkills'] >= get_module_setting("mindk") || get_module_setting("cost") <= $pointsavailable){
			$args[$spec] = translate_inline($name);
		}
		break;
	}
	return $args;
}

function specialtypaladin_run(){
}
?>