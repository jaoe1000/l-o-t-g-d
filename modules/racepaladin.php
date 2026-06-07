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

	// Modern PHP 8.4 Fix: Native LotGD Dependency Check & Force Load
	$al = 0;
	if (is_module_active('alignment')) {
		// Force-load the module to beat the load order trap
		@require_once('modules/alignment/alignment.php'); 
		
		if (function_exists('get_align')) {
			// Grab the alignment using the correct variable name
			$al = get_align(); 
		}
	}
	
	// Paladin math now safely relies on the $al variable
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
