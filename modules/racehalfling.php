<?php

function racehalfling_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Halfling",
		"version"=>"1.13",
		"author"=>"Sneakabout<br>Altered by `!Enderandrew",
		"category"=>"Races",
		"description"=>"Hobbit-esque creates that live with Gnomes.",
		"download"=>"http://dragonprime.net/users/enderwiggin/racehalfling.zip",
		"requires"=>array(
			"racegnome" => "1.31|`1Enderwiggin, http://dragonprime.net/users/enderwiggin/racegnome.zip",
		),			
		"vertxtloc"=>"http://dragonprime.net/users/enderwiggin/",
		"settings"=>array(
			"Halfling Race Settings,title",
			"minedeathchance"=>"Percent chance for Halflings to die in the mine,range,0,100,1|80",
			"mindk"=>"How many DKs do you need before the race is available?,int|7",
			"cost"=>"How many Donation points do you need before the race is available?,int|0",
		),
	);
	return $info;
}

function racehalfling_install(){
	if (!is_module_installed("racegnome")) {
		output("Halflings only turn up in Gnome villages. You must install that race module.");
		return false;
	}
	module_addhook("charstats");
	module_addhook("chooserace");
	module_addhook("creatureencounter");
	module_addhook("newday");
	module_addhook("pointsdesc");
	module_addhook("pvpadjust");
	module_addhook("raceminedeath");
	module_addhook("setrace");
	module_addhook("training-victory");
	return true;
}

function racehalfling_uninstall(){
	global $session;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Halfling'";
	db_query($sql);
	if ($session['user']['race'] == 'Halfling')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racehalfling_dohook($hookname,$args){
	global $session,$badguy,$resline;

	if (is_module_active("racegnome")) {
		$city = get_module_setting("villagename", "racegnome");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	$race = "Halfling";
	$cost = get_module_setting("cost");
	$city = get_module_setting("villagename");
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
		output("<a href='newday.php?setrace=Halfling$resline'>In the rich, gem-encrusted village of %s</a>, many do not spot the sneaky Halflings who nip inbetween the busy Gnomes.  Halflings are famous for making the best thieves, so who better to live with than the rich Gnomes?  Small and sneaky, these folk take pride in being faster, less aggressive and even better at finding gold than their close relatives.`n`n",$city, true);
		addnav("`#Halfling`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;

	case "creatureencounter":
		if ($session['user']['race']==$race){
			//get those folks who haven't manually chosen a race
			racehalfling_checkcity();
			$args['creaturegold']=round($args['creaturegold']*1.3,0);
		}
		break;

	case "newday":
		if ($session['user']['race']==$race){
			racehalfling_checkcity();
			apply_buff("racialbenefit",array(
				"name"=>"`#Halfling's Size`0",
				"defmod"=>"(<defense>?(1+((1+floor(<level>/5))/<defense>)):0)",
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-racehalfling",
				)
			);
			if ($session['user']['spirits']!=-6) {
				output("You remember that you don't like attacking people.....");
				$session['user']['playerfights']--;
			}
		}
		break;

	case "pointsdesc":
		if (get_module_setting("mindk")>0 || $cost>0)
		{
			$args['count']++;
			$format = $args['format'];
			$str = translate("The Halfling race is availiable upon reaching %s Dragon Kills and %s Donation points. And maybe it takes something else to unlock as well!`n");
			$str = sprintf($str, get_module_setting("mindk"),
					get_module_setting("cost"));
			output($format, $str, true);
		}
		break;

	case "pvpadjust":
		if ($args['race'] == $race) {
			$badguy['defense']+=(1+floor($badguy['level']/5));
		}
		break;

	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "Though you are showered with dust, you nip out through your great speed.`n";
			$args['schema']="module-racehalfling";
		}
		break;

	case "setrace":
		if ($session['user']['race']==$race){
			output("`@You shoulder your pack and start out to conquer the world, secure in the knowledge that your size makes you hard to hit. However, you'd prefer to do it without killing anyone.`n");
			output("Though small and less hardy, your sharp eyes catch even more gold than a Gnome's!`n");
			output("You know secrets of sneakcraft hidden from the other races!`n");
			$session['user']['specialty']=3;
			if ($session['user']['maxhitpoints']>200) {
				$session['user']['maxhitpoints']-=20;
			} elseif ($session['user']['maxhitpoints']>150) {
				$session['user']['maxhitpoints']-=15;
			} elseif ($session['user']['maxhitpoints']>100) {
				$session['user']['maxhitpoints']-=10;
			}
			if (is_module_active("cities")) {
				set_module_pref("homecity",$city,"cities");
				$session['user']['location']=$city;
			}
		}
		break;

	case "training-victory";
		if ($session['user']['race']==$race){
			output("`7You look up more tricks in your Halfling's Handbook!");
			require_once("lib/increment_specialty.php");
			increment_specialty("`^");
		}
		break;
	}

	return $args;
}

function racehalfling_checkcity(){
	global $session;
	$race="Halfling";
	if (is_module_active("racegnome")) {
		$city = get_module_setting("villagename", "racegnome");
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

function racehalfling_run(){
}
?>
