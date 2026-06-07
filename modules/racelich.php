<?php

// Based on Felyne - thanks Saucy!

function racelich_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Lich",
		"version"=>"1.2",
		"author"=>"Sneakabout<br>Altered by `!Enderandrew",
		"category"=>"Races",
		"description"=>"A evil race that likes to live with Vampires.  They are weak but possess great knowledge!",
		"download"=>"http://dragonprime.net/users/enderwiggin/racelich.zip",
		"requires"=>array(
			"racevampire" => "1.0|Chris Vorndran, http://dragonprime.net/users/Sichae/racevamp.zip",
			"alignment" => "1.71|`1Enderandrew, http://dragonprime.net/users/enderwiggin/alignment98.zip",
		),	
		"vertxtloc"=>"http://dragonprime.net/users/enderwiggin/",
		"settings"=>array(
			"Lich Race Settings,title",
			"minedeathchance"=>"Percent chance for Lichs to die in the mine,range,0,100,1|95",
			"mindk"=>"How many DKs do you need before the race is available?,int|10",
			"cost"=>"How many Donation points do you need before the race is available?,int|0",
		),
	);
	return $info;
}

function racelich_install(){
	if (!is_module_installed("racevampire")) {
		output("Lichs are only tolerated in Vampire cities. You must install that race module.");
		return false;
	}
	module_addhook("charstats");
	module_addhook("chooserace");
	module_addhook("newday");
	module_addhook("pointsdesc");
	module_addhook("pvpadjust");
	module_addhook("raceminedeath");
	module_addhook("setrace");
	module_addhook("training-victory");
	return true;
}

function racelich_uninstall(){
	global $session, $badguy;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Lich'";
	db_query($sql);
	if ($session['user']['race'] == 'Lich')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racelich_dohook($hookname,$args){
	global $session,$badguy,$resline;

	if (is_module_active("racevampire")) {
		$city = get_module_setting("villagename", "racevampire");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	$race = "Lich";
	switch($hookname){

	case "charstats":
		if ($session['user']['race']==$race){
			addcharstat("Vital Info");
			addcharstat("Race", translate_inline($race));
		}
		break;

	case "chooserace":
		$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
		if ($session['user']['dragonkills'] < get_module_setting("mindk") || get_module_setting("cost") > $pointsavailable)
			break;
		output("<a href='newday.php?setrace=Lich$resline'>In the city of %s</a>, where the darkest of arcane arts are studied, a rare few have the potential to transcend life itself and \"Ascend\" to the ranks of the undead. These rare beings have incredible knowledge of the arcane arts, but are weak from the dark rituals needed to sustain their \"life\".`n`n",$city, true);
		addnav("`\$Lich`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;

	case "newday":
		if ($session['user']['race']==$race){
			racelich_checkcity();
			apply_buff("racialbenefit",array(
				"name"=>"`\$Lich's Weakness`0",
				"defmod"=>0.85,
				"badguydmgmod"=>1.05,
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-racelich",
				)
			);
		}
		break;

	case "pointsdesc":
		if (get_module_setting("mindk")>0 || $cost>0)
		{
			$args['count']++;
			$format = $args['format'];
			$str = translate("The Lich race is availiable upon reaching %s Dragon Kills and %s Donation points.  There may some other secret to unlocking them.`n");
			$str = sprintf($str, get_module_setting("mindk"),
					get_module_setting("cost"));
			output($format, $str, true);
		}
		break;

	case "pvpadjust":
		if ($args['race'] == $race) {
			$badguy['defense']-=(2+floor($badguy['level']/5));
			$badguy['health']-= round($badguy['health']*.1, 0);
		}
		break;

	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "Despite your weak frame, you escape unscathed.`n";
			$args['schema']="module-racelich";
		}
		break;

	case "setrace":
		if ($session['user']['race']==$race){
			output("`&With the dark ritual complete, you rise again to life!.`n");
			output("You are weak and lose defense!`n");
			output("You know of dark secrets beyond the knowledge of mortals!`n");
			if (is_module_active('alignment')) {
				align("-5");
			}			
			if (is_module_active("cities")) {
				set_module_pref("homecity",$city,"cities");
				$session['user']['location']=$city;
			}
		}
		break;

	case "training-victory";
		if ($session['user']['race']==$race){
			output("`7You remember more forbidden lore from your days of life!");
			require_once("lib/increment_specialty.php");
			increment_specialty("`7");
		}
		break;
	}

	return $args;
}

function racelich_checkcity(){
	global $session;
	$race="Lich";
	if (is_module_active("racevampire")) {
		$city = get_module_setting("villagename", "racevampire");
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

function racelich_run(){
}
?>
