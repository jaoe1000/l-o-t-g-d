<?php

//Based on Felyne and Storm Giant Race code

function racebyrd_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Byrd",
		"version"=>"1.05",
		"author"=>"Enhas, based on racecat and racestorm by Shannon Brown and Chris Vorndran",
		"category"=>"Races",
		"download"=>"http://dragonprime.net/users/Enhas/racebyrd.txt",
            "requires"=>array(
			"raceelf"=>"1.0|By Eric Stevens,part of core download",
		),
		"settings"=>array(
			"Byrd Race Settings,title",
			"minedeathchance"=>"Percent chance for Byrds to die in the mine,range,0,100,1|70",
			"mindk"=>"How many DKs do you need before the race is available?,int|3",
		),
	);
	return $info;
}

function racebyrd_install(){
	if (!is_module_installed("raceelf")) {
		output("The Byrds only choose to live with elves.   You must install that race module.");
		return false;
	}

	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("newday");
	module_addhook("charstats");
	module_addhook("raceminedeath");
	module_addhook("pvpadjust");
	module_addhook("racenames");
      module_addhook("adjuststats");
      module_addhook("battle-victory");
	module_addhook("battle-defeat");
	return true;
}

function racebyrd_uninstall(){
	global $session;
	// Force anyone who was a Byrd to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Byrd'";
	db_query($sql);
	if ($session['user']['race'] == 'Byrd')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racebyrd_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	//to handle this.
	// It could be passed as a hook arg?
	global $session,$resline;

	if (is_module_active("raceelf")) {
		$city = get_module_setting("villagename", "raceelf");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	$race = "Byrd";
	switch($hookname){
	case "racenames":
		$args[$race] = $race;
		break;
	case "pvpadjust":
            if ($args['race'] == $race) {
			apply_buff("targetracialbenefit",array(
				"name"=>"",
				"minioncount"=>1,
				"mingoodguydamage"=>0,
				"maxgoodguydamage"=>1+ceil($args['creaturelevel']/3),
				"effectmsg"=>"`Q{badguy}`Q's `4Hawk`Q swoops down and attacks you for `\${damage}`Q damage!",
				"effectnodmgmsg"=>"`Q{badguy}`Q's `4Hawk`Q tries to swoop down and attack you,`Q but it `^MISSES`Q!",
				"allowinpvp"=>1,
				"rounds"=>-1,
				"schema"=>"module-racebyrd",
				)
                        );
                        $args['creaturedefense']-=($args['creaturedefense']*.15);
                        $args['creatureattack']+=($args['creatureattack']*.2);
                        $args['creaturehealth']-= round($args['creaturehealth']*.1, 0);
		}
		break;
      case "adjuststats":
		if ($args['race'] == $race) {
			$args['defense'] -= ($args['defense']*.15);
			$args['attack'] += ($args['attack']*.2);
                  $args['maxhitpoints'] -= round($args['maxhitpoints']*.1, 0);
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
			$args['racesave'] = "Your keen Byrd senses allow you to escape unharmed!`n";
			$args['schema']="module-racebyrd";
		}
		break;
	case "charstats":
		if ($session['user']['race']==$race){
			addcharstat("Vital Info");
			addcharstat("Race", translate_inline($race));
		}
		break;
	case "chooserace":
		if ($session['user']['dragonkills'] < get_module_setting("mindk"))
			break;
		output("<a href='newday.php?setrace=Byrd$resline'>High in the trees above the city of %s</a>, your winged race of `QByrds`0 have lived in harmony with nature for generations.  The threat of `@The Green Dragon`0 looms over your people, as many have gone missing recently, never to return.`n`n",$city, true);
		addnav("`QByrd`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;
	case "setrace":
		if ($session['user']['race']==$race){ // it helps if you capitalize correctly
			output("`^As a `QByrd`^, you are more tuned in with nature than other races.`n");
			output("Your childhood pet and longtime friend, a `4Hawk`^, joins you on your journey!`n`n");
                  output("Having been raised high in the trees, you know the ways of the hunt. You gain extra attack!`n`n");
                  output("However, your body is more frail than some other races.  You lose some defense!`");
			if (is_module_active("cities")) {
				if ($session['user']['dragonkills']==0 &&
						$session['user']['age']==0){
					//new farmthing, set them to wandering around this city.
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
			racebyrd_checkcity();
			apply_buff("racialbenefit",array(
				"name"=>"`QByrd's `4Hawk`0",
				"minioncount"=>1,
				"minbadguydamage"=>0,
				"maxbadguydamage"=>"1+ceil(<level>/3)",
				"effectmsg"=>"`QYour `4Hawk`Q swoops down and attacks {badguy}`Q for `^{damage}`Q damage!",
				"effectnodmgmsg"=>"`QYour `4Hawk`Q tries to swoop down and attack {badguy}`Q, but it `\$MISSES`Q!",
                        "atkmod"=>1.2,
                        "defmod"=>0.85,
                        "badguydmgmod"=>1.1,
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-racebyrd",
				)
			);
		}
		break;
     
	}

	return $args;
}

function racebyrd_checkcity(){
	global $session;
	$race="Byrd";
	if (is_module_active("raceelf")) {
		$city = get_module_setting("villagename", "raceelf");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	
	if ($session['user']['race']==$race && is_module_active("cities")){
		//if they're this race and their home city isn't right, set it up.
		if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
			set_module_pref("homecity",$city,"cities");
		}
	}	
	return true;
}

function racebyrd_run(){
}
?>
