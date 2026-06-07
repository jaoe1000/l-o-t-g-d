<?php
// City-Less Functionality adopted from racefelyne of core.

function racedrow_getmoduleinfo(){
    $info = array(
        "name"=>"Race - Drow",
        "version"=>"1.0",
        "author"=>"Chris Vorndran",
        "category"=>"Races",
        "download"=>"http://dragonprime.net/index.php?module=Downloads;sa=dlview;id=39",
		"vertxtloc"=>"http://dragonprime.net/users/Sichae/",
		"description"=>"Race. Extra Hitpoints at Newday, based on Favor.",
        "settings"=>array(
            "Drow Race Settings,title",
            "minedeathchance"=>"Chance for Drow to die in the mine,range,0,100,1|80",
			"divide"=>"Favor is divided by this value to provide extra hp perday,int|3",
			"mindk"=>"How many DKs do you need before the race is available?,int|5",
        ),
        );
    return $info;
}

function racedrow_install(){
	if (!is_module_installed("raceelf")) {
		output("The Drow only choose to live with elves.   You must install that race module.");
		return false;
	}
    module_addhook("chooserace");
    module_addhook("setrace");
    module_addhook("charstats");
	module_addhook("newday");
	module_addhook("racenames");
    module_addhook("raceminedeath");
    return true;
}

function racedrow_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Drow'";
	db_query($sql);
	if ($session['user']['race'] == 'Drow')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racedrow_dohook($hookname,$args){
    //yeah, the $resline thing is a hack.  Sorry, not sure of a better way
    //to handle this.
    // It could be passed as a hook arg?
    global $session,$resline;
	if (is_module_active("raceelf")) {
		$city = get_module_setting("villagename", "raceelf");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
    $race = "Drow";
    $divide = get_module_setting("divide");
	$drow = $session['user']['deathpower']/$divide; 
    switch($hookname){
    case "raceminedeath":
        if ($session['user']['race'] == $race) {
            $args['chance'] = get_module_setting("minedeathchance");
            $args['racesave'] = "Fortunately your Drow skill let you escape unscathed.`n";
        }
        break;
	case "racenames":
		$args[$race] = $race;
		break;
    case "charstats":
        if ($session['user']['race']==$race){
            addcharstat("Vital Info");
            addcharstat("Race", $race);
        }
        break;
    case "chooserace":
		if ($session['user']['dragonkills'] < get_module_setting("mindk"))
			break;
        output("<a href='newday.php?setrace=Drow$resline'>Living in the confines of your small village of %s</a>, `)chambered away from the world. `^Drowish `)structures protecting you, from the High Elves. You are a creature of the darkness, wishing for acceptance...`n`n`0",$city,true);
        addnav("`)D`7row`0","newday.php?setrace=Drow$resline");
        addnav("","newday.php?setrace=Drow$resline");
        break;
    case "setrace":
        if ($session['user']['race']==$race){
            output("`^As a Drow, you feel the powers of darkness, coursing in your veins.`nYou gain extra hitpoints!");
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
            racedrow_checkcity();
            $session['user']['hitpoints']+=$drow;
        }
        break;
    }
    return $args;
}

function racedrow_checkcity(){
    global $session;
    $race="Drow";
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

function racedrow_run(){
}
?>