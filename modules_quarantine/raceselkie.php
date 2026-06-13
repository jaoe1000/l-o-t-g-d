<?php

function raceselkie_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Selkie",
		"version"=>"1.0",
		"author"=>"Sneakabout",
		"category"=>"Races",
		"download"=>"Ask Nicely!",
		"settings"=>array(
			"Selkie Race Settings,title",
			"minedeathchance"=>"Percent chance for Selkies to die in the mine,range,0,100,1|15",
			"mindk"=>"How many DKs do you need before the race is available?,int|10",
		),
		"prefs"=>array(
			"Selkie Race Preferences,title",
			"naturepotential"=>"Is this person good enough to become a Selkie?,bool|0",
		),
	);
	return $info;
}

function raceselkie_install(){
	if (!is_module_installed("raceelf")) {
		output("Selkies only prosper in the woodland lakes of Elves. You must install that race module.");
		return false;
	}
	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("newday");
	module_addhook("charstats");
	module_addhook("raceminedeath");
	module_addhook("header-dragon");
	module_addhook("pvpadjust");
	module_addhook("training-victory");
	return true;
}

function raceselkie_uninstall(){
	global $session;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Selkie'";
	db_query($sql);
	if ($session['user']['race'] == 'Selkie')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function raceselkie_dohook($hookname,$args){
	global $session,$resline;

	if (is_module_active("raceelf")) {
		$city = get_module_setting("villagename", "raceelf");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	$race = "Selkie";
	switch($hookname){
	case "pvpadjust":
		if ($args['race'] == $race) {
			$args['creatureattack']-=(2+floor($args['creaturelevel']/5));
		}
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "Though you are showered with dust, you escape unharmed.`n";
			$args['schema']="module-raceselkie";
		}
		break;
	case "charstats":
		if ($session['user']['race']==$race){
			addcharstat("Vital Info");
			addcharstat("Race", translate_inline($race));
		}
		break;
	case "chooserace":
		if ($session['user']['dragonkills'] < get_module_setting("mindk")) break;
		if (!get_module_pref("naturepotential")) break;
		output("<a href='newday.php?setrace=Selkie$resline'>In the vast woodland lakes of %s</a>, there dwells beneath the waves the race of Selkies, known only to the elves and even to them mysterious and fey. It is only known that they are physically weak, but that they have mastery of natural magic and supernatural beauty.`n`n",$city, true);
		addnav("`#Selkie`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`@You make your way to the elven village, disguised as a beautiful being.`n");
			output("You are weak and cannot attack as well!`n");
			output("You know secrets of the woods beyond the knowledge of mortals!`n");
			$session['user']['specialty']="MA";
			$session['user']['charm']+=2;
			if (is_module_active("cities")) {
				set_module_pref("homecity",$city,"cities");
				if ($session['user']['age'] == 0)
					$session['user']['location']=$city;
			}
		}
		break;
	case "header-dragon":
		if ($session['user']['race'] == $race) {
			set_module_pref("naturepotential",1);
			break;
		}
		if (get_module_pref("skill","specialtymysticpower")>=16 && ($session['user']['race']=="Elf" || $session['user']['race']=="Human")) {
			set_module_pref("naturepotential",1);
		} else {
			set_module_pref("naturepotential",0);
		}
		break;
	case "newday":
		if ($session['user']['race']==$race){
			raceselkie_checkcity();
			apply_buff("racialbenefit",array(
				"name"=>"`#Selkie's Weakness`0",
				"atkmod"=>0.9,
				"badguydmgmod"=>1.05,
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-raceselkie",
				)
			);
			if ($session['user']['marriedto']=1) {
				output("You remember your heritage and take care of yourself a little more.");
				$session['user']['charm']++;
			}
		}
		break;
	case "training-victory";
		if ($session['user']['race']==$race){
			output("`7You remember more secrets from your days underwater!");
			$oldskill=$session['user']['specialty'];
			$session['user']['specialty']="MA";
			require_once("lib/increment_specialty.php");
			increment_specialty("`@");
			$session['user']['specialty']=$oldskill;
		}
		break;
	}

	return $args;
}

function raceselkie_checkcity(){
	global $session;
	$race="Selkie";
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

function raceselkie_run(){
}
?>
