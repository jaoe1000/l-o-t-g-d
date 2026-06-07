<?php
/*
Details:
 * This is a Specialty Module
 * It contains a Ranger
History Log:
 v1.0:
 o Seems to be Stable
 v1.1:
 o Buffs fixed by XChrisX - thanks:)
*/
function specialtyranger_getmoduleinfo()  {
	$info = array(
		"name"=>"Specialty - Ranger",
		"author"=>"`@CortalUX `&-`# Buffs fixed by XChrisX",
		"version"=>"1.0",
		"vertxtloc"=>"http://dragonprime.net/users/CortalUX/",
		"download"=>"http://dragonprime.net/users/CortalUX/specialtyranger.zip",
		"category"=>"Specialties",
		"settings"=>array(
			"Specialty - Ranger Settings,title",
			"mindk"=>"How many DKs do you need before the specialty is available?,int|5",
			"cost"=>"How many points do you need before the specialty is available?,int|5",
		),
		"prefs"=>array(
			"Specialty - Ranger User Prefs,title",
			"skill"=>"Skill points in Ranger Senses,int|0",
			"uses"=>"Uses of Ranger Senses allowed,int|0",
		),
	);
	return $info;
}

function specialtyranger_install(){
	$specialty="RS";
	module_addhook("choose-specialty");
	module_addhook("set-specialty");
	module_addhook("fightnav-specialties");
	module_addhook("apply-specialties");
	module_addhook("newday");
	module_addhook("incrementspecialty");
	module_addhook("specialtynames");
	module_addhook("specialtycolor");
	module_addhook("dragonkill");
	module_addhook("specialtymodules");
	module_addhook("pointsdesc");
	return true;
}

function specialtyranger_uninstall(){
	// Reset the specialty of anyone who had this specialty so they get to
	// rechoose at new day
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='RS'";
	db_query($sql);
	return true;
}

function specialtyranger_dohook($hookname,$args){
	global $session,$resline;
	$spec = "RS";
	$name = "Ranger Senses";
	$ccode = "`@";

	switch ($hookname) {
		case "dragonkill":
			set_module_pref("uses", 0);
			set_module_pref("skill", 0);
		break;
		case "pointsdesc":
			$cost = get_module_setting("cost");
			if ($cost > 0){
				$args['count']++;
				$format = $args['format'];
				$str = translate("The Ranger Specialty is availiable upon reaching %s Dragon Kills and %s points.");
				$str = sprintf($str, get_module_setting("mindk"),$cost);
			}
			output($format, $str, true);
		break;
		case "specialtymodule":
			$args[$spec] = "specialtyranger";
		break;
		case "choose-specialty":
			if ($session['user']['specialty'] == "" || $session['user']['specialty'] == '0') {
				if ($session['user']['dragonkills'] < (int)get_module_setting("mindk") || (int)get_module_setting("cost") > $session['user']['donation']) break;
					addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline");
					$t1 = translate_inline("Living in tune with nature");
					$t2 = appoencode(translate_inline("$ccode$name`0"));
					rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
					addnav("","newday.php?setspecialty=$spec$resline");
			}
		break;
		case "set-specialty":
			if($session['user']['specialty'] == $spec) {
				page_header($name);
				output("`@Your `&mind`@ was covered with a great `7cloud`@ until you matured.");
				output("You seemed to `!awake`@ one day, and you looked around you, to see Wolven brethren looking solemnly at you.");
				output("You lived for a time among them, but your heart yearned for more, so you went to search for your own kind, but upon meeting them, you repudiated them, for they were fighting with nature and this alarmed you.");
				output("You set yourself apart, and became `%other`@. ");
				output("`^A Ranger.`@ Running to a secluded part of a nearby forest, you wept into a grassy meadow, and gasped as others like you walked up, and howled like wolves.");
				output("From then onwards, you all hunted as one, and you learnt the ways of the Ranger.`n`n");
				output("`@You `^lost `\$rancor`@ for your `%blood`@ kind, and decided that if you could not accept the world, you would change it.");
				output("Into a hidden, pure glade you wandered, stepping through a mighty waterfall, to enter the world once more...");
			}
		break;
		case "specialtycolor":
			$args[$spec] = $ccode;
		break;
		case "specialtynames":
			$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
			if ($session['user']['superuser'] & SU_EDIT_USERS || $session['user']['dragonkills'] >= get_module_setting("mindk") || get_module_setting("cost") <= $pointsavailable){
				$args[$spec] = translate_inline($name);
			}
		break;
		case "incrementspecialty":
			if($session['user']['specialty'] == $spec) {
				$new = get_module_pref("skill") + 1;
				set_module_pref("skill", $new);
				$specialties = modulehook("specialtynames",	array(""=>translate_inline("Unspecified")));
				$c = $args['color'];
				output("`n%sYou gain a level in `&%s%s to `#%s%s!",
						$c, $specialties[$session['user']['specialty']], $c, $new, $c);
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
			$specialties = modulehook("specialtynames",	array(""=>translate_inline("Unspecified")));
			if($session['user']['specialty'] == $spec) {
				if ($bonus == 1) {
					output("`n`2For being interested in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode, $specialties[$session['user']['specialty']], $ccode, $specialties[$session['user']['specialty']]);
				} else {
					output("`n`2For being interested in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n",$ccode, $specialties[$session['user']['specialty']],$bonus, $ccode,$specialties[$session['user']['specialty']]);
				}
			}
			$amt = (int)(get_module_pref("skill") / 3);
			if ($session['user']['specialty'] == $spec) $amt = $amt + $bonus;
			set_module_pref("uses", $amt);
		break;
		case "fightnav-specialties":
			$uses = get_module_pref("uses");
			$script = $args['script'];
			if ($uses > 0) {
				addnav(array("$ccode$name (%s points)`0", $uses),"");
				addnav(array("%s &#149; Bear Necessities`7 (%s)`0", $ccode, 1),
						$script."op=fight&skill=$spec&l=1", true);
			}
			if ($uses > 1) {
				addnav(array("%s &#149; Wolf Call`7 (%s)`0", $ccode, 2),
						$script."op=fight&skill=$spec&l=2",true);
			}
			if ($uses > 2) {
				addnav(array("%s &#149; Tree Growth`7 (%s)`0", $ccode, 3),
						$script."op=fight&skill=$spec&l=3",true);
			}
			if ($uses > 4) {
				addnav(array("%s &#149; Singing Bow`7 (%s)`0", $ccode, 5),
						$script."op=fight&skill=$spec&l=5",true);
			}
		break;
		case "apply-specialties":
			$skill = httpget('skill');
			$l = httpget('l');
			if ($skill==$spec) {
				if (get_module_pref("uses") >= $l) {
					switch($l){
						case 1:
							apply_buff('rs1', array(
								"startmsg"=>"`^A Bear appears!",
								"name"=>"`@Bear Necessities",
								"rounds"=>5,
								"wearoff"=>"Your Bear disappears into the undergrowth.",
								"minioncount"=>1,
								"effectmsg"=>"A gigantic Bear chases {badguy} for `^{damage}`) points.",
								"minbadguydamage"=>1,
								"maxbadguydamage"=>$session['user']['level']*2,
								"schema"=>"specialtyranger"
							));
						break;
						case 2:
							apply_buff('rs2', array(
								"startmsg"=>"`@You stare at `^{badguy}`@ and howl like a Wolf!",
								"name"=>"`@Wolf Call",
								"rounds"=>1,
								"wearoff"=>"You stop howling...",
								"minioncount"=>1,
								"effectmsg"=>"`^A gigantic Wolf appears, and shakes {badguy} like a doll, incurring `@{damage}`^ points.`)",
								"minbadguydamage"=>30,
								"maxbadguydamage"=>$session['user']['dragonkills']*3,
								"schema"=>"specialtyranger"
							));
						break;
						case 3:
							apply_buff('rs3', array(
								"startmsg"=>"`@You extend your thought into the land, and become one with it, and Trees begin to shield you!",
								"name"=>"`@Tree Growth",
								"rounds"=>5,
								"wearoff"=>"`^The Trees wither and die, as {badguy} strikes your senses down!`)",
								"lifetap"=>2,
								"atkmod"=>0,
								"defmod"=>2,
								"effectmsg"=>"`%The Trees seem to heal you by feeding you magical sap.`)",
								"effectnodmgmsg"=>"You feel a tingle as the Trees try to heal your already healthy body.",
								"effectfailmsg"=>"The Trees wail in anger at {badguy}.",
								"schema"=>"specialtyranger"
							));
						break;
						case 5:
							apply_buff('rs5', array(
								"startmsg"=>"`^You take out your Singing Bow, which emits a piercing shriek, breaking all glass objects within a five mile radius. You consider the wine at `@".getsetting("innname", LOCATION_INN)."`^... and hope it's in a barrel.",
								"name"=>"`@Singing Bow",
								"rounds"=>5,
								"wearoff"=>"{badguy}`^ throws a sheet of bad music lyrics, stopping your Singing Bow dead. You really should take some lessons yourself...`)",
								"damageshield"=>1.5,
								"minbadguydamage"=>round($session['user']['level']/5),
								"minbadguydamage"=>$session['user']['level'],
								"effectmsg"=>"{badguy}`% rolls about moaning on the ground, as tantalizing siren songs sound in it's head. {badguy}`% yells for `^{damage}`% damage.",
								"effectnodmg"=>"{badguy}`& is slightly amused by your Singing Bow, but otherwise unharmed. \"`^What a rubbish trinket,`&\" you hear through your senses.",
								"effectfailmsg"=>"{badguy}`& is slightly amused by your Singing Bow, but otherwise unharmed. \"`^What a rubbish trinket,`&\" you hear through your senses.",
								"schema"=>"specialtyranger"
							));
						break;
					}
					set_module_pref("uses", get_module_pref("uses") - $l);
				} else {
					apply_buff('rs0', array(
						"startmsg"=>"You send your mind into the land and collect your senses, but you just sense a void. You attempt again, but Bambi jumps from the bushes- {badguy} yells \"booooOOOOOOOOO!!!\", giggling evilly before swinging at you again.",
						"rounds"=>1,
						"schema"=>"specialtyranger"
					));
				}
			}
		break;
	}
	return $args;
}

function specialtyranger_run(){
}
?>
