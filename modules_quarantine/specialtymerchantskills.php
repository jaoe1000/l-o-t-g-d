<?php
// addnews ready
// mail ready
// translator ready

function specialtymerchantskills_getmoduleinfo(){
	$spec_version = "1.41";
	$info = array(
		"name" => "Specialty - Merchant Skills",
		"author" => "Tizen",
		"version" => $spec_version,
		"download" => "http://dragonprime.net/users/Tizen/specialtymerchantskills.zip",
		"category" => "Specialties",
		"settings"=>array(
			"Specialty - Merchant Settings,title",
			"Version ".$spec_version.",note",
			"mindk"=>"Minimum DK for specialty,int|1",
		),
		"prefs" => array(
			"Specialty - Merchant Skills User Prefs,title",
			"skill"=>"Skill points in Merchant Skills,int|0",
			"uses"=>"Uses of Merchant Skills allowed,int|0",
		),
	);
	return $info;
}

function specialtymerchantskills_install(){
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
	return true;
}

function specialtymerchantskills_uninstall(){
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='ME'";
	db_query($sql);
	return true;
}

function specialtymerchantskills_dohook($hookname,$args){
	global $session,$resline;

	$spec = "ME";
	$name = "Merchant Skills";
	$ccode = "`^";

	switch ($hookname) {
	case "dragonkill":
		set_module_pref("uses", 0);
		set_module_pref("skill", 0);
		break;
	case "choose-specialty":
		if ($session['user']['specialty'] == "" || $session['user']['specialty'] == '0') {
			addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline");
			$t1 = translate_inline("Capitolizing on every situation.");
			$t2 = appoencode(translate_inline("$ccode$name`0"));
			rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
			addnav("","newday.php?setspecialty=$spec$resline");
		}
		break;
	case "set-specialty":
		if($session['user']['specialty'] == $spec) {
			page_header($name);
			output("`6As a boy, your lemonade stand always did better than anyone elses. The extra income provided you with financial advantages others lacked.");
			output("You always had the smarts required to make the best out of any situation, and to always exceed at what you do, to come out on top.");
		}
		break;
	case "specialtycolor":
		$args[$spec] = $ccode;
		break;
	case "specialtynames":
		$args[$spec] = translate_inline($name);
		break;
	case "specialtymodules":
		$args[$spec] = "specialtymerchantskills";
		break;
	case "incrementspecialty":
		if($session['user']['specialty'] == $spec) {
			$new = get_module_pref("skill") + 1;
			set_module_pref("skill", $new);
			$name = translate_inline($name);
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
	case "fightnav-specialties":
		$uses = get_module_pref("uses");
		$script = $args['script'];
		if ($uses > 0) {
			addnav(array("$ccode$name (%s points)`0", $uses), "");
			addnav(array("$ccode &#149; Show of Wealth`7 (%s)`0", 1), 
					$script."op=fight&skill=$spec&l=1", true);
		}
		if ($uses > 1) {
			addnav(array("$ccode &#149; House Call`7 (%s)`0", 2),
					$script."op=fight&skill=$spec&l=2",true);
		}
		if ($uses > 2) {
			addnav(array("$ccode &#149; Bribe`7 (%s)`0", 3),
					$script."op=fight&skill=$spec&l=3",true);
		}
		if ($uses > 4) {
			addnav(array("$ccode &#149; Hired Goon`7 (%s)`0", 5),
					$script."op=fight&skill=$spec&l=5",true);
		}
		break;
	case "apply-specialties":
		$skill = httpget('skill');
		$l = httpget('l');
		if ($skill==$spec){
			if (get_module_pref("uses") >= $l){
				switch($l){
				case 1:
					apply_buff('me1',array(
						"startmsg"=>"`\$You flash {badguy} your money clip, causing it's jaw to drop in awe.",
						"name"=>"`\$Show of Wealth",
						"rounds"=>2,
						"wearoff"=>"Your victim stops gawking at your money clip.",
						"badguyatkmod"=>0,
						"badguydefmod"=>0,
						"roundmsg"=>"{badguy} continues to stare at your money clip.",
						"schema"=>"module-specialtymerchantskills"
					));
					break;
				case 2:
					apply_buff('me2', array(
						"startmsg"=>"`^You use your financial status to call a doctor!",
						"name"=>"`%House Call",
						"rounds"=>8,
						"wearoff"=>"The doctor has gone home.",
						"regen"=>$session['user']['level'],
						"effectmsg"=>"The doctor heals you for {damage} health.",
						"effectnodmgmsg"=>"You have no wounds to heal.",
						"schema"=>"module-specialtymerchantskills"
					));
					break;
				case 3:
					apply_buff('me3', array(
						"startmsg"=>"`^You bribe {badguy} with your wealth, it agrees take it easy on you.",
						"name"=>"`^Bribe",
						"rounds"=>8,
						"wearoff"=>"Your victim decides the bribe is not worth it.",
						"roundmsg"=>"{badguy} attacks with less force than normal.",
						"badguyatkmod"=>0.5,
						"schema"=>"module-specialtymerchantskills"
					));
					break;
				case 5:
					apply_buff('me5',array(
						"startmsg"=>"`^Using your wealth as a merchant, you hire a nearby mercenary to help you!",
						"name"=>"`^Hired Goon",
						"rounds"=>10,
						"wearoff"=>"The mercenary decides to call it a day, and leaves.",
						"atkmod"=>1.5,
						"defmod"=>1.5,
						"roundmsg"=>"The mercenary joins you in battle, increasing your overall attack and defense.",
						"schema"=>"module-speciatlymerchantskills"
					));
					break;
				}
				set_module_pref("uses", get_module_pref("uses") - $l);
			}else{
				apply_buff('me0', array(
					"startmsg"=>"You try to attack {badguy} by putting your best merchantry skills into practice, but instead, you trip over your feet.",
					"rounds"=>1,
					"schema"=>"module-specialtymerchantskills"
				));
			}
		}
		break;
	}
	return $args;
}

function specialtymerchantskills_run(){
}
?>
