<?php
/*
I started with one small tweak to this module, and before you know it, I basically
completely rewrote it.  The idea for the module originated from Lonny and Sixf00t4,
but most everything has been replaced.

All the buffs/effects are new.  Both the number of uses you get per day, as well as
the power of the buffs are affected by how much charm the character has.  It only
makes sense.  I felt many of the specialties were fairly static.  This rewards some
of the players who go out of the way to roleplay their character according to what
their race and specialty are.  However, I must warn you, that as I am developing new
races and specialties with dynamic effects based off charm, alignment, etc. that a
player could twink out their character.  Effectively, this is designed for that, in
so much as that it rewards players for certain actions.  To balance this, you have
to consider two things.

One, it takes effort and time to raise charm, alignment, etc. so the bonus power is
not granted for nothing.  Secondly, these specialties and races with dynamic effects
are usually a little less powerful to start off with.  Or atleast, the ones I am 
designing are that way.

For instance, until you hit around 20 charm, you get no bonus to the duration of the
buffs.  And the base is 4 rounds instead of 4.  You also won't really get any bonus
use parts early on either. So, I don't think this is unbalancing.  To start it's weaker,
and has the potential of becoming more powerful if the player plays their cards right.

-- Enderandrew
*/
function specialtyseductiveskills_getmoduleinfo(){
	$info = array(
		"name" => "Specialty - Seductive Skills",
		"author" => "`!Enderandrew<br>based upon `3Lonny Luberts `6Based on Theivery Skills<br>Info Based on Sixf00t4's 9.7 Specialties Mod",
		"version" => "1.32",
		"description"=>"This adds a Charm-based Seductive Specialty to the game.",
		"download" => "http://dragonprime.net/users/enderwiggin/specialtyseductiveskills.zip",
		"vertxtloc"=>"http://dragonprime.net/users/enderwiggin/",
		"category" => "Specialties",
		"settings"=> array(
			"Specialty - Seductive Settings,title",
			"mindk"=>"How many DKs do you need before the specialty is available?,int|0",
			"cost"=>"How many points do you need before the specialty is available?,int|0",
		),
		"prefs" => array(
			"Specialty - Seductive Skills User Prefs,title",
			"skill"=>"Skill points in Seductive Skills,int|0",
			"uses"=>"Uses of Seductive Skills allowed,int|0",
		),
	);
	return $info;
}

function specialtyseductiveskills_install(){
	module_addhook("apply-specialties");
	module_addhook("castlelibbook");
	module_addhook("castlelib");
	module_addhook("choose-specialty");
	module_addhook("dragonkill");
	module_addhook("fightnav-specialties");
	module_addhook("incrementspecialty");
	module_addhook("newday");
	module_addhook("set-specialty");
	module_addhook("specialtycolor");
	module_addhook("specialtynames");
	return true;
}

function specialtyseductiveskills_uninstall(){
	// Reset the specialty of anyone who had this specialty so they get to
	// rechoose at new day
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='SE'";
	db_query($sql);
	return true;
}

function specialtyseductiveskills_dohook($hookname,$afis){
	global $session,$resline;
	tlschema("fightnav");

	$spec = "SE";
	$name = "Seductive Skills";
	$ccode = "`%";
	$cost = get_module_setting("cost");
	$op69 = httpget('op69');

	switch ($hookname) {

	case "apply-specialties":
		$skill = httpget('skill');
		$l = httpget('l');
		if ($skill==$spec){
			if (get_module_pref("uses") >= $l){
				$rounds = (4+(int)($session['user']['charm'] / 20));
				switch($l){
				case 1:
					apply_buff('se1',array(
						"startmsg"=>"`n`^You look intent just past {badguy}, causing them to look over as well...`n`n",
						"name"=>"`%Distraction",
						"rounds"=>$rounds,
						"wearoff"=>"They catch on to your ruse",
						"effectmsg"=>"{badguy} is distracted and doesn't see your attack coming!",
						"badguydefmod"=>0.3,
						"schema"=>"specialtyseductiveskills"
					));
					break;
				case 2:
					apply_buff('se2',array(
						"startmsg"=>"`n`^Your seductive wiles convince some of the other monsters to aid you in battle!`n`n",
						"name"=>"`%Charm Enemies",
						"rounds"=>$rounds,
						"wearoff"=>"Your charmed companions catch up that it's not worth dying just because someone is attractive.",
						"minioncount"=>get_module_pref("uses"),
						"minbadguydamage"=>round($session['user']['level']/3,0),
						"maxbadguydamage"=>$session['user']['level'],
						"effectmsg"=>"`)Your followers strikes {badguy}`) for `^{damage}`) damage.",
						"effectnodmgmsg"=>"`)Your followers lash out to hit {badguy},`) but they `\$MISS`)!",
						"schema"=>"specialtyseductiveskills"
					));
					break;
				case 3:
					apply_buff('se3', array(
						"startmsg"=>"`n`^{badguy} is utterly spellbound with you`n`n",
						"name"=>"`%Entrance",
						"rounds"=>$rounds,
						"wearoff"=>"{badguy} snaps out of it.  Maybe it has something to do with you trying to kill it.",
						"badguyatkmod"=>0,
						"schema"=>"specialtyseductiveskills"
					));
					break;
				case 5:
					apply_buff('se5',array(
						"startmsg"=>"`n`^With a deadly kiss, your drain {badguy}'s very life away.`n`n",
						"name"=>"`%Fatal Attraction",
						"rounds"=>$rounds,
						"wearoff"=>"Their sense of self-preservation kicks in and they push you away.",
						"lifetap"=>1.75,
						"roundmsg"=>"They struggle, but give in to you as well as their life seeps into yours!",
						"schema"=>"specialtyseductiveskills"
					));
					break;
				}
				set_module_pref("uses", get_module_pref("uses") - $l);
			}else{
				apply_buff('se0', array(
					"startmsg"=>"You try to seduce {badguy}, but instead, you attract the notice of small woodland creatures who now hump your leg.",
					"rounds"=>1,
					"schema"=>"specialtyseductiveskills"
				));
			}
		}
		break;

	case "castlelibbook":
		output("Shake Your Moneymaker. (3 Turns)`n");
		addnav("Read a Book");
		addnav("Share Your Moneymaker","runmodule.php?module=lonnycastle&op=library&op69=seductive");
		break;

	case "castlelib":
		if ($op69 == 'seductive'){
			output("You sit down and open up the Shake Your Moneymaker book.`n");
			output("You read for a while... in the time it takes you to read you use up`n");
			output("3 Turns.`n`n");
			output("For some crazy reason, you expected a detailed treatise on the subtle art of seduction,`n");
			output("but really, this is just a specialized text on Shaking Your Moneynaker.  It explains that.`n");
			output("the right gyrations can seduce and distract all manner of creatures, both human and monstrous.`n");
			output("`!You become more skilled as the Seductive Arts.`n");
			$session['user']['turns']-=3;
			set_module_pref('skill',(get_module_pref('skill','specialtyseductiveskills') + 1),'specialtyseductiveskills');
			set_module_pref('uses', get_module_pref("uses",'specialtyseductiveskills') + 1,'specialtyseductiveskills');
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
			$t1 = translate_inline("Flirting and shaking your money maker");
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
		$script = $afis['script'];
		if ($uses > 0) {
			addnav(array("%s%s (%s points)`0", $ccode, $name, $uses), "");
			addnav(array("%s &#149; Distract`7 (%s)`0", $ccode, 1),
					$script."op=fight&skill=$spec&l=1", true);
		}
		if ($uses > 1) {
			addnav(array("%s &#149; Charm Enemies`7 (%s)`0", $ccode, 2),
					$script."op=fight&skill=$spec&l=2",true);
		}
		if ($uses > 2) {
			addnav(array("%s &#149; Entrance`7 (%s)`0", $ccode, 3),
					$script."op=fight&skill=$spec&l=3",true);
		}
		if ($uses > 4) {
			addnav(array("%s &#149; Fatal Attraction`7 (%s)`0", $ccode, 5),
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
		if($session['user']['specialty'] == $spec) {
			if($session['user']['charm'] > 0) {
				$bonus = getsetting("specialtybonus", 1);
					if ($bonus == 1) {
						output("`n`2For being interested in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name,$ccode,$name);
					} else {
						output("`n`2For being interested in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n",$ccode,$name,$bonus,$ccode,$name);
					}
				$amt = (int)(get_module_pref("skill") / 3);
				$charmbonus = (int)($session['user']['charm'] / 10);
				if($charmbonus > 0) {
					output("`n`2For being such a charmer, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name);
					$amt += $charmbonus;
				}
				if ($session['user']['specialty'] == $spec) $amt++;
				set_module_pref("uses", $amt);
			}else{
				$bonus = 0;
				output("`n`2It's pretty hard to seduce people with no charm so, you receive no `&%s%s`2 uses for today.`n",$ccode,$name);
			}
		}
		break;

	case "set-specialty":
		if($session['user']['specialty'] == $spec) {
			page_header($name);
			$session['user']['donationspent'] = $session['user']['donationspent'] + $cost;
			output("`^As a wee child, your momma quickly learned that you weren't the sharpest tool ");
			output("`^in the shed.  All was not lost.  Far from it.  You see, it's not brains, or might");
			output("`^that make the world spin round.  It's looks.  And what you lack in other areas,.");
			output("`^you make up for in other areas if you know what I mean.  Frankly, you're quite well");
			output("`^endowed, and you know how to shake your money maker.  So, that's what you do.");	}
		break;

	case "specialtycolor":
		$afis[$spec] = $ccode;
		break;

	case "specialtymodules":
		$afis[$spec] = "specialtybard";
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

function specialtyseductiveskills_run(){
}
?>