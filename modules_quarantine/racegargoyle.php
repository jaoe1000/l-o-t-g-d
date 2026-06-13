<?php

function racegargoyle_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Gargoyle",
		"version"=>"1.0",
		"author"=>"Sneakabout",
		"category"=>"Races",
		"download"=>"Ask Nicely!",
		"settings"=>array(
			"Gargoyle Race Settings,title",
			"minedeathchance"=>"Percent chance for Gargoyles to die in the mine,range,0,100,1|90",
			"mindk"=>"How many DKs do you need before the race is available?,int|1",
		),
	);
	return $info;
}

function racegargoyle_install(){
	if (!is_module_installed("racedwarf")) {
		output("Gargoyles are only found in the deepest Dwarven caverns. You must install that race module.");
		return false;
	}
	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("newday");
	module_addhook("charstats");
	module_addhook("raceminedeath");
	module_addhook("pvpadjust");
	return true;
}

function racegargoyle_uninstall(){
	global $session;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Gargoyle'";
	db_query($sql);
	if ($session['user']['race'] == 'Gargoyle')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racegargoyle_dohook($hookname,$args){
	global $session,$resline;

	if (is_module_active("racedwarf")) {
		$city = get_module_setting("villagename", "racedwarf");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	$race = "Gargoyle";
	switch($hookname){
	case "pvpadjust":
		if ($args['race'] == $race) {
			$args['creaturedefense']+=(2+floor($args['creaturelevel']/5));
		}
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "The rocks bounce off your tough hide and you escape unharmed.`n";
			$args['schema']="module-racegargoyle";
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
		output("<a href='newday.php?setrace=Gargoyle$resline'>In the lowest caverns of %s</a>, there is a place where even the bravest dwarfs fear to tread, for it is home to many fearsome creatures. One of these are the Gargoyles -  slow, blood-lusting beasts, with hides strong enough to survive the hostile environment.`n`n",$city, true);
		addnav("`7Gargoyle`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`7As a Gargoyle, you have much stronger defenses and a ferocious bloodlust.`n");
			output("Your tough hide slows you down when you move!`n");
			if (is_module_active("cities")) {
				set_module_pref("homecity",$city,"cities");
				$session['user']['location']=$city;
			}
		}
		break;
	case "newday":
		if ($session['user']['race']==$race){
			racegargoyle_checkcity();
			apply_buff("racialbenefit",array(
				"name"=>"`7Gargoyle's Hide`0",
				"defmod"=>"(<defense>?(2+((1+floor(<level>/5))/<defense>)):0)",
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-racegargoyle",
				)
			);
			$turnloss=round(($session['user']['turns']*0.25),0);
			output("`n`7You feel the need to kill another! You gain a PvP fight!`nYour tough hide makes it more difficult to move! You lose %s Forest Fights!",$turnloss);
			$session['user']['turns']-=$turnloss;
			if ($session['user']['spirite']!=-6) $session['user']['playerfights']++;
		}
		break;
	}
	return $args;
}

function racegargoyle_checkcity(){
	global $session;
	$race="Gargoyle";
	if (is_module_active("racedwarf")) {
		$city = get_module_setting("villagename", "racedwarf");
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

function racegargoyle_run(){
}
?>