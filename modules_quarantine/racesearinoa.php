<?php
// City-Less Functionality adopted from racefelyne of core.

function racesearinoa_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Searinoa",
		"version"=>"1.02",
		"author"=>"Chris Vorndran",
		"category"=>"Races",
		"download"=>"http://dragonprime.net/index.php?module=Downloads;sa=dlview;id=11",
		"vertxtloc"=>"http://dragonprime.net/users/Sichae/",
		"description"=>"Race. Minion and Attack Buff. Bought with Donation Points.",
		"requires"=>array(
			"racetroll"=>"1.0|By Eric Stevens,part of core download",
		),
		"settings"=>array(
			"Searinoa Race Settings,title",
			"minedeathchance"=>"Percent chance for a Searinoa to die in the mine,range,0,100,1|60",
			"cost"=>"Cost of Race in Lodge Points,int|500",
		),
		"prefs"=>array(
			"Searinoa Preferences,title",
			"bought"=>"Has Searinoa race been bought,bool|0",
		)
	);
	return $info;
}
function racesearinoa_install(){
	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("newday");
	module_addhook("raceminedeath");
	module_addhook("pvpadjust");
	module_addhook("adjuststats");
	module_addhook("pointsdesc");
	module_addhook("lodge");
	module_addhook("racenames");
	module_addhook("battle-victory");
	module_addhook("battle-defeat");
	return true;
}
function racesearinoa_uninstall(){
	global $session;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Searinoa'";
	db_query($sql);
	if ($session['user']['race'] == 'Searinoa')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}
function racesearinoa_dohook($hookname,$args){
	global $session,$resline;

	if (is_module_active("racetroll")) {
		$city = get_module_setting("villagename", "racetroll");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	$race = "Searinoa";
	$cost = get_module_setting("cost");
	$bought = get_module_pref("bought");
	switch($hookname){
	case "racenames":
		$args[$race] = $race;
		break;
	case "pointsdesc":
		$args['count']++;
		$format = $args['format'];
		$str = translate("`^The Race: Searinoa; Denizens of the Swamps.");
		$str = sprintf($str, $cost);
		output($format, $str, true);
		break;
	case "pvpadjust":
		if ($args['race'] == $race) {
			apply_buff("targetracialbenefit",array(
				"name"=>"",
				"minioncount"=>1,
				"mingoodguydamage"=>0,
				"maxgoodguydamage"=>3+ceil($args['level']/2),
				"effectmsg"=>"`#{badguy} `#fires a jet of water, dealing `\${damage}`# damage.",
				"effectnodmgmsg"=>"`#{badguy} fires at you,`# but `\$MISSES`)!",
				"allowinpvp"=>1,
				"rounds"=>-1,
				"schema"=>"module-racesearinoa",
				)
			);
			$args['creatureattack']+=(2+floor($args['creaturelevel']/3));
		}
		break;
	case "adjuststats":
		if ($args['race'] == $race) {
			$args['attack']+=(2+floor($args['level']/3));
		}
		break;
	case "battle-victory":
	case "battle-defeat":
		if ($args['type'] == 'pvp') {
			strip_buff("targetracialbenefit");
		}
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "Blasting out violent water spurts, you are able to push boulders from collapsing upon you.`n";
			$args['schema']="module-racesearinoa";
		}
		break;
	case "chooserace":
		if ($bought == 1){
		output("<a href='newday.php?setrace=$race$resline'>`2Deep in the Swamps of %s</a>, `@the city of trolls, the race of the `#Searinoa `@dwell in the murky depths.",$city, true);
		output("`@The protectors of the marshlands. Fierce and furious, yet proud and diligent.`n`n`0");
		addnav("`!Searinoa`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		}
		break;
	case "lodge":
		$pointsavailable = $session['user']['donation'] -
			$session['user']['donationspent'];
		if ($pointsavailable >= $cost && $bought == 0){
		addnav(array("Acquire Searinoan Blood (%s Points)",$cost),
				"runmodule.php?module=racesearinoa&op=lodge&op=start");
		}
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`#Searinoans `@are well renowned for their skill display of water pulses and water manipulation.`n");
			output("`@Piercing `!jets of water `@explode from your hands, as you rush into battle!`n");
			if (is_module_active("cities")) {
				if ($session['user']['dragonkills']==0 &&
						$session['user']['age']==0){
					set_module_setting("newest-$city",
							$session['user']['acctid'],"cities");
				}
				set_module_pref("homecity",$city,"cities");
				if ($session['user']['age'] == 0)
					$session['user']['location']=$city;
			}
		}
		break;
	case "newday":
		if ($session['user']['race']==$race){
			racesearinoa_checkcity();
			apply_buff("racialbenefit",array(
				"name"=>"`#Searinoan Water`0",
				"minioncount"=>1,
				"minbadguydamage"=>0,
				"maxbadguydamage"=>"3+ceil(<level>/2)",
				"effectmsg"=>"`3A piercing water jet escapes your palm, hitting `\${badguy}`3 for `^{damage}`3 damage.",
				"effectnodmgmsg"=>"`\${badguy} `3skillfully dodges the water jet!",
				"atkmod"=>"(<attack>?(1+((2+floor(<level>/3))/<attack>)):0)",
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-racesearinoa",
				)
			);
		}
		break;
	}
	return $args;
}
function racesearinoa_checkcity(){
	global $session;
	$race = "Searinoa";
	if (is_module_active("racetroll")) {
		$city = get_module_setting("villagename", "racetroll");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	if ($session['user']['race']==$race && is_module_active("cities")){
		if (get_module_pref("homecity","cities")!=$city){
			set_module_pref("homecity",$city,"cities");
		}
	}	
	return true;
}
function racesearinoa_run(){
	global $session;
	page_header("Hunter's Lodge");
	$race = 'Searinoa';
	$cost = get_module_setting("cost");
	$bought = get_module_pref("bought");
	if (is_module_active("bloodbank")){
		$str = translate_inline("the `\$Blood Bank");
	}else{
		$str = translate_inline("`%Cedrik");
	}
	$op = httpget('op');

	switch ($op){
		case "start":
			output("`)JCP looks upon you, a smile toying with his lips.");
			output("\"`\$Yet another one, that wishes to purchase the fine `!Searinoan Blood`\$.");
			output("Is that correct?`)\"");
			addnav("Choices");
			addnav("Yes","runmodule.php?module=racesearinoa&op=yes");
			addnav("No","runmodule.php?module=racesearinoa&op=no");
			break;
		case "yes":
			output("`)JCP hands you a tiny vial, with a bright `!sapphire `)liquid in it.`n`n");
			output("\"`\$That is pure `^Searinoan Blood`\$.");
			output(" Now, drink it all up!`)\"`n`n");
			output(" You double over, spasming on the ground.");
			output(" JCP grins, \"`\$Your body shall finish it's change upon newday... I suggest ye rest.`3\"");
			$session['user']['race'] = $race;
			$session['user']['donationspent'] += $cost;
			set_module_pref("bought",1);
			break;
		case "no":
			output("`)JCP looks at you and shakes his head.");
			output("\"`\$I swear to ye, this stuff is top notch.");
			output(" This isn't like the crud that %s `\$is selling.`)\"",$str);
			break;
	}
	addnav("Return");
	addnav("Return to the Lodge","lodge.php");
	page_footer();
}	
?>