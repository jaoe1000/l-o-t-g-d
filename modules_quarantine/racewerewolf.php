<?php

// Some code taken and modified from Caravan module, thanks to Shannon Brown for making the original
// Some code also taken and modified from Dwarven Fight module, thanks to Chris Vorndran for making the original
// Character MUST be Human in order to be able to transform into a Werewolf
// Human players have a 33% chance of transforming into a Werewolf on two or more full moons
// They will stay as a Werewolf either for the number of days set (then they go back to being Human), or if they DK

function racewerewolf_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Werewolf",
		"version"=>"1.04",
		"author"=>"Enhas, based on caravan and dwarvenfight by Shannon Brown and Chris Vorndran",
		"category"=>"Races",
		"download"=>"http://dragonprime.net/users/Enhas/racewerewolf.txt",
            "requires"=>array(
			"racehuman"=>"1.0|By Eric Stevens,part of core download",
                  "moons"=>"1.0|By JT Traub, part of the core download",
                  "rabidwerewolf"=>"1.01|By Enhas, http://dragonprime.net/users/Enhas/rabidwerewolf.txt",
		),
		"settings"=>array(
			"Werewolf Race Settings,title",
			"minedeathchance"=>"Percent chance for Werewolves to die in the mine,range,0,100,1|45",
                  "werewolfdays"=>"Number of game days the player will stay a Werewolf,range,1,10,1|5",
                  "canbounty"=>"Will Werewolves have a bounty placed on their head upon transforming,bool|1",
                  "bountyamount"=>"Amount of bounty to be placed,range,500,2500,500|1500",
		),
            "prefs"=>array(
			"Werewolf Race Preferences,title",
                  "playerdays"=>"Number of days player has been a Werewolf,int|0",
                  "cantransform"=>"Can this player transform into a Werewolf (if Human),bool|0",
		)
	);
	return $info;
}

function racewerewolf_install(){
	if (!is_module_installed("racehuman")) {
		output("Werewolves only transform from Humans during the full moon.  You must install that race module.");
		return false;
	}

	module_addhook("newday");
	module_addhook("charstats");
	module_addhook("raceminedeath");
	module_addhook("pvpadjust");
	module_addhook("racenames");
      module_addhook("adjuststats");
      module_addhook("header-dragon");
      module_addhook("battle-victory");
	module_addhook("battle-defeat");
	return true;
}

function racewerewolf_uninstall(){
	global $session;
	// Force anyone who was a Werewolf to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Werewolf'";
	db_query($sql);
	if ($session['user']['race'] == 'Werewolf')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racewerewolf_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	//to handle this.
	// It could be passed as a hook arg?
	global $session,$resline;

	if (is_module_active("racehuman")) {
		$city = get_module_setting("villagename", "racehuman");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	$race = "Werewolf";
	switch($hookname){
	case "racenames":
		$args[$race] = $race;
		break;
	case "pvpadjust":
            if ($args['race'] == $race) {
                  apply_buff("targetracialbenefit",array(
				"name"=>"",
				"badguydmgmod"=>1.15,
				"allowinpvp"=>1,
				"rounds"=>-1,
				"schema"=>"module-racewerewolf",
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
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "Your Werewolf strength lets you easily escape with no injury!`n";
			$args['schema']="module-racewerewolf";
		}
		break;
      case "battle-victory":
	case "battle-defeat":
		if ($args['type'] == 'pvp') {
			strip_buff("targetracialbenefit");
		}
		break;
      case "header-dragon":
		set_module_pref("playerdays",0);
		break;
	case "charstats":
		if ($session['user']['race']==$race){
			addcharstat("Vital Info");
			addcharstat("Race", translate_inline($race));
		}
		break;     
	case "newday":
            $canwerewolf = 0;
            $werewolfdays = 0;
            $playerdays = 0;
            $transformchance = 0;
            $cantransform = 0;
		$moon1place=get_module_setting("moon1place","moons");
		$moon2place=get_module_setting("moon2place","moons");
		$moon3place=get_module_setting("moon3place","moons");
		$moon1cycle=get_module_setting("moon1cycle","moons");
		$moon2cycle=get_module_setting("moon2cycle","moons");
		$moon3cycle=get_module_setting("moon3cycle","moons");
		$moon1=get_module_setting("moon1","moons");
		$moon2=get_module_setting("moon2","moons");
		$moon3=get_module_setting("moon3","moons");
		if ($moon1 && ($moon1place < $moon1cycle*0.62)) $moon1isfull=1;
		if ($moon2 && ($moon2place < $moon2cycle*0.62)) $moon2isfull=1;
		if ($moon3 && ($moon3place < $moon3cycle*0.62)) $moon3isfull=1;
		// only one moon and it is full
		if (($moon1+$moon2+$moon3 == 1) &&
				($moon1isfull+$moon2isfull+$moon3isfull == 1))  {
			// set $canwerewolf to yes
			$canwerewolf = 1;
		}elseif ($moon1isfull+$moon2isfull+$moon3isfull > 1) {
			// We have at least two moons full
			// set $canwerewolf to yes
			$canwerewolf = 1;
		}
            $werewolfdays = get_module_setting("werewolfdays");
            $playerdays = get_module_pref("playerdays");
            $cantransform = get_module_pref("cantransform");
            $canbounty = get_module_setting("canbounty");
            $bountyamount = get_module_setting("bountyamount");
            if ($session['user']['race'] != "Werewolf") {
            set_module_pref("playerdays",0);
            }
            $transformchance = e_rand(1,3);
            if ($canwerewolf==1 && $transformchance==1 && $cantransform==1 && ($session['user']['race'] == "Human")) {
            $session['user']['race'] = 'Werewolf';
            set_module_pref("cantransform",0);
            output("`n`&You go about your business for a while, until you suddenly fall to the ground, twitching and clutching your left arm!  Excruciating pain covers your entire body as hair sprouts out of every pore, and large fangs grow from your mouth!  You have transformed into a `\$Werewolf`&!  You can feel the `\$bloodlust`& calling for you..`0`n");
            addnews("`\$%s`7 transformed into a `\$Werewolf`7 at the light of the full moon!`0",$session['user']['name']);
            }
            if ($playerdays >= $werewolfdays){
            $session['user']['race'] = 'Human';
            set_module_pref("playerdays",0);
            output("`n`&Waking up today, you notice that the hair covering your body has vanished, as well as your `\$bloodlust`&!  The `\$Werewolf`& curse has ended!`0`n");
            $session['user']['turns']++;
	      output("`n`&Because you are human, you gain `^an extra`& forest fight for today!`n`0");
            }
		if ($session['user']['race']==$race){
			racewerewolf_checkcity();
            if ((is_module_active("dag")) && $canbounty==1 && $playerdays==0) {
			$id = $session['user']['acctid'];
			$sql = "SELECT bountyid,amount,target,setter,status FROM ".db_prefix("bounty")." WHERE target=".$id."";
			$result = db_query($sql);
			$row = db_fetch_assoc($result);
		if ($row['amount'] <= 0) {
			$sqlset = "INSERT INTO ".db_prefix("bounty")." (amount,target,setter,setdate) VALUES (".$bountyamount.",".$session['user']['acctid'].",0,now())";
			db_query($sqlset);
			output("`n`&You have an odd feeling that `\$bounty hunters`& will now be after your fanged head!`0`n");
		}else{
			$sqlset2 = "INSERT INTO ".db_prefix("bounty")." (amount,target,setter,setdate) VALUES (".$bountyamount.",".$session['user']['acctid'].",0,now())";                
			db_query($sqlset2);
                  output("`n`&You have an odd feeling that `\$bounty hunters`& will be after your fanged head more than ever!`0`n");
            }
            }elseif ($playerdays > 0) {
                  output("`n`&The `\$bloodlust`& continues within you!`0`n");
            }
                  $playerdays++;
                  set_module_pref("playerdays",$playerdays);
			apply_buff("racialbenefit",array(
				"name"=>"`\$Werewolf Bloodlust`0",
                        "atkmod"=>"(<attack>?(1+((2+floor(<level>/3))/<attack>)):0)",
                        "dmgmod"=>1.15,
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-racewerewolf",
				)
			);
		}
		break;
     
	}

	return $args;
}

function racewerewolf_checkcity(){
	global $session;
	$race="Werewolf";
	if (is_module_active("racehuman")) {
		$city = get_module_setting("villagename", "racehuman");
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

function racewerewolf_run(){
}
?>
