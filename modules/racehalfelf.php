<?php
// translator ready
// addnews ready

function racehalfelf_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Half-Elf",
		"version"=>"1.11",
		"author"=>"Billie Kennedy",
		"category"=>"Races",
		"download"=>"http://nuketemplate.com/modules.php?name=Downloads&d_op=viewdownload&cid=32",
		"vertxtloc"=>"http://dragonprime.net/users/Dannic/",
		"settings"=>array(
			"Half-Elf Race Settings,title",
			"minedeathchance"=>"Percent chance for Half-Elves to die in the mine,range,0,100,1|90",
			"mindk"=>"How many DKs do you need before the race is available?,int|0",
			"cost"=>"How many Donation points do you need before the race is available?,int|0",
			"goldloss"=>"How much less gold (in percent) do the Half-Elves find?,range,0,100,1|15",
		),
	);
	return $info;
}

function racehalfelf_install(){
	// The Half-Elves share the city with Elves, so..
	if (!is_module_installed("raceelf")) {
		output("The Half-Elves only choose to live with Elves.   You must install that race module.");
		return false;
	}

	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("newday");
	module_addhook("charstats");
	module_addhook("raceminedeath");
	module_addhook("battle-victory");
	module_addhook("creatureencounter");
	module_addhook("pvpadjust");
	return true;
}

function racehalfelf_uninstall(){
	global $session;
	// Force anyone who was a Half-Elves to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Half-Elf'";
	db_query($sql);
	if ($session['user']['race'] == 'Half-Elf')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racehalfelf_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	//to handle this.
	// It could be passed as a hook arg?
	global $session,$resline;

	if (is_module_active("raceelf")) {
		$city = get_module_setting("villagename", "raceelf");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	$race = "Half-Elf";
	switch($hookname){
	case "pvpadjust":
		if ($args['race'] == $race) {
			$args['creaturedefense']+=(1+floor($args['creaturelevel']/5));
		}
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "Good thing you had a little human in you.  You noticed a fatal flaw in the mine just in time to escape.`n";
			$args['schema']="module-racehalfelf";
		}
		break;
	case "charstats":
		if ($session['user']['race']==$race){
			addcharstat("Vital Info");
			addcharstat("Race", translate_inline($race));
		}
		break;
	case "chooserace":
		$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
		if ($session['user']['dragonkills'] < get_module_setting("mindk") || $cost > $pointsavailable)
			break;
		output("<a href='newday.php?setrace=Half-Elf$resline'>Below the trees of the Elven the city of %s</a>, your race of `5Half-Elves`0 have been the servants of the Eleves.  Your dual heritage of Elven and Human gives you some advantages of each race.`n`n",$city, true);
		addnav("`5Half-Elf`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;
	case "setrace":
		if ($session['user']['race']==$race){ // it helps if you capitalize correctly
			output("`^As an Half-Elf, you are keenly aware of your surroundings at all times, very little ever catches you by surprise.`n");
			output("You gain extra defense!");
			output("`&Your size and strength permit you the ability to effortlessly wield weapons, tiring much less quickly than other races.`n`^You gain an extra forest fight each day!");
			output("`&Because of your servant upbringing, You tend to find less gold than other races.");
			if (is_module_active("cities")) {
				if ($session['user']['dragonkills']==0 &&
						$session['user']['age']==0){
					//new farmthing, set them to wandering around this city.
					set_module_setting("newest-$city",
							$session['user']['acctid'],"cities");
				}
				set_module_pref("homecity",$city,"cities");
				$session['user']['location']=$city;
			}
		}
		break;
	case "creatureencounter":
		if ($session['user']['race']==$race){
			//get those folks who haven't manually chosen a race
			racehalfelf_checkcity();
			$loss = (100 - get_module_setting("goldloss"))/100;
			$args['creaturegold']=round($args['creaturegold']*$loss,0);
		}
		break;
	case "newday":
		if ($session['user']['race']==$race){
			racehalfelf_checkcity();
			$args['turnstoday'] .= ", Race (halfelf): 1";
			$session['user']['turns']++;
			output("`n`&Because you are Half-Elven, you gain `^an extra`& forest fight for today!`n`0");
			apply_buff("racialbenefit",array(
				"name"=>"`@Half-Elvish Awareness`0",
				"defmod"=>"(<defense>?(1.0+((0.8+floor(<level>/5))/<defense>)):0)",
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-racehalfelf",
				)
			);
		}
		break;
	}

	return $args;
}

function racehalfelf_checkcity(){
	global $session;
	$race="Half-Elf";
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

function racehalfelf_run(){
}
?>