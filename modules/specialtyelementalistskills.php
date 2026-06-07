<?php
//addnews ready
// mail ready
// translator ready
/*
I made one very small change to this file.  This is still Dannic's module
with one small tweak made by Enderwiggin.  It's only in my shared folder
to comply with the license since I changed the file.  In the castlelib,
if you read the book, it adds 1 to your specialty skill, not 3.
*/

/***************************************************************************/
/* Name: Specialty - Elementalist Skills                                   */
/* ver 1.61                                                                */
/* Modified => T. J. Brumfield => enderandrew@gmail.com                    */
/* Original => Billie Kennedy => dannic06@gmail.com                        */
/*                                                                         */
/* 12-18-04                                                                */
/*    Appearntly forgot the DK and cost checking.  Added code for that and */
/*    subtracting from the active balance of used donation points          */
/* 12-21-04                                                                */
/*    Added a book for the castle.                                         */
/* 12-27-04                                                                */
/*    Added hook for Skill shop - Sichae                                   */
/* 1-6-2005                                                                */
/*    Made changes to make translator ready                                */
/***************************************************************************/

function specialtyelementalistskills_getmoduleinfo(){
	$info = array(
		"name" => "Specialty - Elementalist Skills",
		"author" => "Billie Kennedy<br>tweaked by `!Enderandrew",
		"version" => "1.62",
		"description"=>"This will add an Elementalist specialty to the game.",
		"download" => "http://dragonprime.net/users/enderwiggin/specialtyelementalistskills.zip",
		"vertxtloc"=>"http://dragonprime.net/users/enderwiggin/",
		"category" => "Specialties",
		"settings"=> array(
			"Specialty - Elementalist Settings,title",
			"mindk"=>"How many DKs do you need before the specialty is available?,int|0",
			"cost"=>"How many points do you need before the specialty is available?,int|0",
		),
		"prefs" => array(
			"Specialty - Elementalist Skills User Prefs,title",
			"skill"=>"Skill points in Elementalist Skills,int|0",
			"uses"=>"Uses of Elementalist Skills allowed,int|0",
		),
	);
	return $info;
}

function specialtyelementalistskills_install(){
	module_addhook("apply-specialties");
	module_addhook("fightnav-specialties");
	module_addhook("incrementspecialty");
	module_addhook("castlelibbook");
	module_addhook("castlelib");
	module_addhook("choose-specialty");
	module_addhook("dragonkill");
	module_addhook("newday");
	module_addhook("pointsdesc");
	module_addhook("set-specialty");
	module_addhook("specialtycolor");
	module_addhook("specialtymodules");
	module_addhook("specialtynames");
	return true;
}

function specialtyelementalistskills_uninstall(){
	// Reset the specialty of anyone who had this specialty so they get to
	// rechoose at new day
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='EL'";
	db_query($sql);
	return true;
}

function specialtyelementalistskills_dohook($hookname,$args){
	global $session,$resline;


	$spec = "EL";
	$name = "Elementalist Skills";
	$ccode = "`^";
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
					apply_buff('el1',array(
						"startmsg"=>"`^You Summon an Air Elemental to create a gale force wind.",
						"name"=>"`^Wind",
						"rounds"=>5,
						"wearoff"=>"Your victim is no longer held at bay by the wind.",
						"roundmsg"=>"{badguy} is pushed back by the wind and can't fight as well.",
						"badguyatkmod"=>0.7,
						"schema"=>"specialtyelementalistskills"
					));
					break;
				case 2:
					apply_buff('el2',array(
						"startmsg"=>"`^You Summon a Water Elemental to create a large pool of water under {badguy}.",
						"name"=>"`^Pool",
						"rounds"=>5,
						"wearoff"=>"Your victim struggles above the water.",
						"atkmod"=>2,
						"roundmsg"=>"{badguy} doesn't realize they can get up out of the water.  Allowing you a better attack!", 
						"schema"=>"specialtyelementalistskills"
					));
					break;
				case 3:
					apply_buff('el3', array(
						"startmsg"=>"`^Several small stone golems rise from the ground and begin to throw stones at {badguy}.",
						"name"=>"`^Stone golems",
						"rounds"=>5,
						"wearoff"=>"The golems crumble into dust.",
						"minioncount"=>round($session['user']['level']/2.5)+1,
						"maxbadguydamage"=>round($session['user']['level']/1.8,0)+1,
						"effectmsg"=>"`)A golem throws a stone and hits {badguy}`) for `^{damage}`) damage.",
						"effectnodmgmsg"=>"`)A golem throws a stone and tries to hit {badguy}`) but `\$MISSES`)!",
						"schema"=>"specialtyelementalistskills"
					));
					break;
				case 5:
					apply_buff('el5',array(
						"startmsg"=>"`^Concentrating deeply, you summon a small flame in the palm of your hand.  It jumps from your hand and quickly increases in size. The flame leaps and envelopes {badguy}!",
                        "name"=>"`^Fire Elemental",
						"rounds"=>5,
						"wearoff"=>"Smoke rises from your victim as the elemental disapates!",
						"minioncount"=>1,
						"maxbadguydamage"=>round($session['user']['attack']*3.5,0),
						"minbadguydamage"=>round($session['user']['attack']*1.75,0),
						"roundmsg"=>"{badguy} is envolped in flames as the fire elemental surrounds them!",
						"schema"=>"specialtyelementalistskills"
					));
					break;
				}
				set_module_pref("uses", get_module_pref("uses") - $l);
			}else{
				apply_buff('el0', array(
					"startmsg"=>"You try to summon an elemental friend but end up only making a mud pie.  {badguy} has a good chuckle watching you.",
					"rounds"=>1,
					"schema"=>"specialtyelementalistskills"
				));
			}
		}
		break;
	}
	case "castlelibbook":
		output("Scattered, Covered, Smothered and Burned book. (3 Turns)`n");
		addnav("Read a Book");
		addnav("Scattered, Covered, Smothered and Burned","runmodule.php?module=lonnycastle&op=library&op69=elemental");
		break;
	case "castlelib":
		if ($op69 == 'elemental'){
			output("You sit down and open up the Scattered, Covered, Smothered and Burned book.`n");
			output("You read for a while... in the time it takes you to read you use up`n");
			output("3 Turns.`n`n");
			output("You learn to scatter your enemies with the wind, cover them with water till they drown,`n");
			output("smother them with earth and light their butts on fire.  Sounds almost appetizing.`n");
			output("You now know a little more about your Elementalist skills than you knew before.`n");
			$session['user']['turns']-=3;
			set_module_pref('skill',(get_module_pref('skill','specialtyelementalistskills') + 1),'specialtyelementalistskills');
			set_module_pref('uses', get_module_pref("uses",'specialtyelementalistskills') + 1,'specialtyelementalistskills');
			addnav("Continue","runmodule.php?module=lonnycastle&op=library");
			}
		break;
	case "choose-specialty":
		if ($session['user']['specialty'] == "" ||
				$session['user']['specialty'] == '0') {
			$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
			if ($session['user']['dragonkills'] < get_module_setting("mindk") || $cost > $pointsavailable)
				break;
			addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline");
			$t1 = translate_inline("Playing with the different elemnts of the earth");
			$t2 = appoencode(translate_inline("$ccode$name`0"));
			rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
			addnav("","newday.php?setspecialty=$spec$resline");
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
			addnav(array("$ccode$name (%s points)`0", $uses),"");
			addnav(array("$ccode &#149; Air Elemental`7 (%s)`0", 1), 
					$script."op=fight&skill=$spec&l=1", true);
		}
		if ($uses > 1) {
			addnav(array("$ccode &#149; Water Elemental`7 (%s)`0", 2),
					$script."op=fight&skill=$spec&l=2",true);
		}
		if ($uses > 2) {
			addnav(array("$ccode &#149; Earth Elemental`7 (%s)`0", 3),
					$script."op=fight&skill=$spec&l=3",true);
		}
		if ($uses > 4) {
			addnav(array("$ccode &#149; Fire Elemental`7 (%s)`0", 5),
					$script."op=fight&skill=$spec&l=5",true);
		}
		break;
	case "incrementspecialty":
		if($session['user']['specialty'] == $spec) {
			$new = get_module_pref("skill") + 1;
			set_module_pref("skill", $new);
			$c = $args['color'];
			$name = translate_inline($name);
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
			$name = translate_inline($name);
			if ($bonus == 1) {
				output("`n`2For being interested in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name,$ccode,$name);
			} else {
				output("`n`2For being interested in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n",$ccode,$name,$bonus,$ccode,$name);
			}
		}
		$amt = (int)(get_module_pref("skill") / 3);
		if ($session['user']['specialty'] == $spec) $amt = $amt + $bonus;
		set_module_pref("uses", $amt);
		break;
	case "pointsdesc":
		$args['count']++;
		$format = $args['format'];
		$str = translate("The Elementalist Specialty is availiable upon reaching %s Dragon Kills and %s points.");
		$str = sprintf($str, get_module_setting("mindk"),
		get_module_setting("cost"));
		output($format, $str, true);
		break;
	case "set-specialty":
		if($session['user']['specialty'] == $spec) {
			page_header($name);
			$session['user']['donationspent'] = $session['user']['donationspent'] - $cost;
			output("`3Growing up, you remember diging holes in the ground, swimming, starting fires and trying to fly like a bird.");
			output("You learned the basics of the elements of the earth.  This stuff was really facinating.  Realizing that if you could just harness these elements you could use them as weapons.  Time to train.");
			output("Over time, you began to control the ground, you could shape water, create powerful gusts of wind and create fire where others couldn't.");
			output("To your delight, it could also be used as a weapon against your foes.");		}
		break;
	case "specialtycolor":
		$args[$spec] = $ccode;
		break;
	case "specialtymodules":
		$args[$spec] = "specialtyelementalistskills";
	    break;
	case "specialtynames":
		$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
		if ($session['user']['superuser'] & SU_EDIT_USERS || $session['user']['dragonkills'] >= get_module_setting("mindk") || get_module_setting("cost") <= $pointsavailable){
			$args[$spec] = translate_inline($name);
		}
		break; 
	return $args;
}



function specialtyelementalistskills_run(){
}
?>