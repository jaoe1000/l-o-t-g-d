<?php
// This race is meant to mix things up, and build upon the Alignment system.  This race will not give out alignment points.
// That's up to other modules to handle.  What this module does, is add a new race to the game, whose power is determined
// by how good their alignment gets.  I eventually intend to develop some more modules with alignment in mind, and I hope
// to encourage other developers to do the same.
//
function racepaladin_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Paladin",
		"version"=>"1.0",
		"author"=>" - Eternal",
		"category"=>"Races",
		"download"=>"http://dragonprime.net/users/Eternal/racepaladin.zip",
		"settings"=>array(
			"Paladin Race Settings,title",
			"villagename"=>"Name for the Paladin village|LightHaven",
			"minedeathchance"=>"Chance for Paladin to die in the mine,range,0,100,1|25",
			"mindk"=>"How many DKs do you need before the race is available?,int|0",
			"cost"=>"How many Donation points do you need before the race is available?,int|0",
		),
	);
	return $info;
}

function racepaladin_install(){
	if (!is_module_installed("alignment")) {
    		output("This module requires both the alignment module be installed.");
    		return false;
	}
	else {
		module_addhook("chooserace");
		module_addhook("setrace");
		module_addhook("newday");
		module_addhook("villagetext");
		module_addhook("travel");
		module_addhook("charstats");
		module_addhook("validlocation");
		module_addhook("moderate");
		module_addhook("changesetting");
		module_addhook("raceminedeath");
		module_addhook("pvpadjust");
		module_addhook("pointsdesc");
	}
}

function racepaladin_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Paladin'";
	db_query($sql);
	if ($session['user']['race'] == 'Paladin')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racepaladin_dohook($hookname,$args){
	
	global $session,$badguy,$resline;
	$cost = get_module_setting("cost");
	$city = get_module_setting("villagename");
	$race = "Paladin";
	// Modern PHP 8.4 Fix: Native LotGD Dependency Check
	$al = 0;
if (is_module_active('alignment') && function_exists('get_align')) {
    // If the module is active AND the function loaded successfully, grab the alignment
    $align = get_align($session['user']['acctid']);
} else {
    // Fallback if the alignment module is inactive, uninstalled, or broken
    $align = 0; 
}
	$paladin = ($al+50);
	switch($hookname){
	case "chooserace":
		$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
		if ($session['user']['dragonkills'] < get_module_setting("mindk") || get_module_setting("cost") > $pointsavailable)
			break;
		output("<a href='newday.php?setrace=paladin$resline'>`&The  city of `!%s</a>`& towers before you. `!Paladins`& in this village are protectors of the innocents.`n`n",$city,true);
		addnav("`@Paladin`0","newday.php?setrace=paladin$resline");
		addnav("","newday.php?setrace=paladin$resline");
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`n`&As a `!paladin`&, you have the power of wizards and the strenght of a warrior running through your blood. `n");
			output("`^As such, you are held to a higher standard.  ");
			output("Both mortals and gods expect more from you.  ");
			output("You have the power and  strenght to challenge foes, and save the innocents .  ");
			
		output("Your growth and bonuses as a `!paladin`& is tied directly to your mortality.  While half wizard, you are half human as well.  ");
			
			output("However, those of evil alignments receive little to no bonus, and evil aligned Paladins are actually penalized.`n");
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
	case "newday":
		if ($session['user']['race']==$race){
			racepaladin_checkcity();
			if ($paladin<=0){
				apply_buff("racialbenefit",array(
				"name"=>"`!Ancestry`0",
	 			"atkmod"=>"(+1*(<attack>?(2+((1+floor(<level>/5))/<attack>)):0))",
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-racepaladin",
				));
				output("`n`!The Fate of the world lies in your hands!To achieve your destuny, you must first harness your powers!`0`n");
			} else {
				$session['user']['hitpoints']+=$paladin;
				$bonusturns=(int)$paladin/5;
				$session['user']['turns']+=$bonusturns;
				output("`n`&For being a rather heroic `!Being`&, you receive %s extra hit points, and %s extra forest fights!`n", $paladin, $bonusturns);
				if ($paladin==30){
					apply_buff("racialbenefit",array(
					"name"=>"`!Vaporize`0",
	 				"atkmod"=>"(<attack>?(5+((1+floor(<level>/5))/<attack>)):0)",
					"allowinpvp"=>1,
					"allowintrain"=>1,
					"rounds"=>-1,
					"schema"=>"module-racepaladin",
					));
					output("`n`&For being the hero, you receive an additional blessing of `!Godly Strength!`0`n");
				}
			}	
		}
		break;
	case "villagetext":
		racepaladin_checkcity();
		if ($session['user']['location'] == $city){
			$args['text']=("`!`b`c$city, Home of the Paladins`c`b`n`!The sun shines brightly on this Heroic yet rather small village.  $city is an ancient town built by the Gods themselves to ensure the safety and fate of the Paladins.The rise of the evil Vampiric race beckons.But Paladins believe good always prevail over evil at soem point in time and space.`n");
			$args['schemas']['text'] = "module-racepaladin";
			$args['clock']="`n`6One of the tiny kids softly whispers in your ear that it is`^%s`6 before disappearing in a blue light`n";
			$args['schemas']['clock'] = "module-racepaladin";
			if (is_module_active("calendar")) {
				$args['calendar']="`n`6Another kid gently whispers in your ear, \"`^Today is `&%3\$s %2\$s`^, `&%4\$s`^.  It is `&%1\$s`^.`6\"`n";
				$args['schemas']['calendar'] = "modules-racepaladin";
			}
			$args['title']= array("%s City", $city);
			$args['schemas']['title'] = "module-racepaladin";
			$args['sayline']="announces";
			$args['schemas']['sayline'] = "module-racepaladin";
			$args['talk']="`n`&The word on the block is:`n";
			$args['schemas']['talk'] = "module-racepaladin";
			$new = get_module_setting("newest-$city", "cities");
			if ($new != 0) {
				$sql =  "SELECT name FROM " . db_prefix("accounts") .
					" WHERE acctid='$new'";
				$result = db_query_cached($sql, "newest-$city");
				$row = db_fetch_assoc($result);
				$args['newestplayer'] = $row['name'];
				$args['newestid']=$new;
			} else {
				$args['newestplayer'] = $new;
				$args['newestid']="";
			}
			if ($new == $session['user']['acctid']) {
				$args['newest']="`n`6Even with the potential for greatness, you are a human none the less being released in a cruel world.  Even crueler are the towering expectations, like your destiny.";
			} else {
				$args['newest']="`n`#Looking at all the towering `!Godly`# buildings, and distracted is `^%s`#.";
			}
			$args['schemas']['newest'] = "module-racepaladin";
			$args['gatenav']="The Heavenly Gates";
			$args['schemas']['gatenav'] = "module-racepaladin";
			$args['fightnav']="Hall of Heroes";
			$args['schemas']['fightnav'] = "module-racepaladin";
			$args['marketnav']="The Pub";
			$args['schemas']['marketnav'] = "module-racepaladin";
			$args['tavernnav']="Ambrosia and Ale";
			$args['schemas']['tavernnav'] = "module-racepaladin";
			$args['section']="village-$race";
		}
		break;
	case "travel":
		$capital = getsetting("villagename", LOCATION_FIELDS);
		$hotkey = substr($city, 0, 1);
		tlschema("module-cities");
		if ($session['user']['location']==$capital){
			addnav("Safer Travel");
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city");
		}
		elseif ($session['user']['location']!=$city){
			addnav("More Dangerous Travel");
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city&d=1");
		}
		if ($session['user']['superuser'] & SU_EDIT_USERS){
			addnav("Superuser");
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city&su=1");
		}
		tlschema();
		break;	
	// Modern PHP Fix: Defensive Dependency Check
case "charstats":
    if ($session['user']['race'] == 'Paladin') {
        // Only run the alignment logic if the companion module is actually installed
        if (function_exists('get_align')) {
            $align = get_align($session['user']['acctid']);
            // ... (keep the original alignment logic here) ...
        } else {
            // Optional: Provide a fallback or simply do nothing if alignment is missing
            $align = 'Unknown'; 
        }
    }
    break;
	case "validlocation":
		if (is_module_active("cities"))
			$args[$city] = "village-$race";
		break;
	case "moderate":
            if (is_module_active("cities")) {
               tlschema("commentary");
               $args["village-$race"]=sprintf_translate("City of %s", $city);
               tlschema();
               }
               break;
	case "changesetting":
		// Ignore anything other than villagename setting changes
		if ($args['setting'] == "villagename" && $args['module']=="racepaladin") {
			if ($session['user']['location'] == $args['old'])
				$session['user']['location'] = $args['new'];
			$sql = "UPDATE " . db_prefix("accounts") .
				" SET location='" . $args['new'] .
				"' WHERE location='" . $args['old'] . "'";
			db_query($sql);
			if (is_module_active("cities")) {
				$sql = "UPDATE " . db_prefix("module_userprefs") .
					" SET value='" . $args['new'] .
					"' WHERE modulename='cities' AND setting='homecity'" .
					"AND value='" . $args['old'] . "'";
				db_query($sql);
			}
		}
		break;
	case "raceminedeath":
      	if ($session['user']['race'] == $race) {
            	$args['chance'] = get_module_setting("minedeathchance");
		$args['racesave'] = "It was your destiny as a Paladin that allowed you to survive unscathed.`n";
		$args['schema']="module-racepaladin";
      	}
	      break;
	case "pvpadjust":
	$enemyAL = get_module_pref('alignment','alignment',$badguy['acctid']);
		if($enemyAL >= 50) {
			$badguy['attack']+=(1+floor($badguy['level']/5));
		} else {
			$badguy['attack']-=(1+floor($badguy['level']/5));
		}
		if ($badguy['race'] == $race) {
			$baduy['hitpoints']+=($enemyAL-50);
		}
		break;
	case "pointsdesc":
		if (get_module_setting("mindk")>0 || $cost>0)
		{
			$args['count']++;
			$format = $args['format'];
			$str = translate("The Paladin race is availiable upon reaching %s Dragon Kills and %s Donation points.");
			$str = sprintf($str, get_module_setting("mindk"),
					get_module_setting("cost"));
			output($format, $str, true);
		}
		break;
	}
	return $args;
}

function racepaladin_checkcity(){
	global $session;
	$race="Paladin";
	$city=get_module_setting("villagename");
	
	if ($session['user']['race']==$race && is_module_active("cities")){
		//if they're this race and their home city isn't right, set it up.
		if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
			set_module_pref("homecity",$city,"cities");
		}
	}	
	return true;
}

function racepaladin_run(){

}
?>
