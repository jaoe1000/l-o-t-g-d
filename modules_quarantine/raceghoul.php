<?php
// City-Less Functionality adopted from racefelyne of core.

function raceghoul_getmoduleinfo(){
    $info = array(
        "name"=>"Race - Ghoul",
        "version"=>"1.0",
        "author"=>"Chris Vorndran",
        "category"=>"Races",
        "download"=>"http://dragonprime.net/users/Sichae/raceghoul.zip",
		"vertxtloc"=>"http://dragonprime.net/users/Sichae/",
		"description"=>"Race. Extra Hitpoints at Newday, based on Favor.",
        "settings"=>array(
            "Ghoul Race Settings,title",
            "minedeathchance"=>"Chance for Ghoul to die in the mine,range,0,100,1|25",
			"divide"=>"Favor is divided by this value to provide extra hp perday,int|3",
			"mindk"=>"How many DKs do you need before the race is available?,int|5",
        ),
        );
    return $info;
}

function raceghoul_install(){
	if (!is_module_installed("racevamp")) {
		output("The Ghoul only choose to live with vampires.   You must install that race module.");
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

function raceghoul_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Ghoul'";
	db_query($sql);
	if ($session['user']['race'] == 'Ghoul')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function raceghoul_dohook($hookname,$args){
    //yeah, the $resline thing is a hack.  Sorry, not sure of a better way
    //to handle this.
    // It could be passed as a hook arg?
    global $session,$resline;
	if (is_module_active("racevamp")) {
		$city = get_module_setting("villagename", "racevamp");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
    $race = "Ghoul";
	$divide = get_module_setting("divide");
	$ghoul = $session['user']['deathpower']/$divide; 
    switch($hookname){
    case "raceminedeath":
        if ($session['user']['race'] == $race) {
            $args['chance'] = get_module_setting("minedeathchance");
            $args['racesave'] = "Fortunately your Ghoul skill let you escape unscathed.`n";
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
        output("<a href='newday.php?setrace=Ghoul$resline'>Born out of death</a>. Crowmina `)hidden away from the world. `^Ghoulish`0 `)crystals protecting you, from those that do not realize your kind. You are a creature of the dead, wishing for a purpose...`n`n`0",true);
        addnav("`7G`)houl`0","newday.php?setrace=Ghoul$resline");
        addnav("","newday.php?setrace=Ghoul$resline");
        break;
    case "setrace":
        if ($session['user']['race']==$race){
            output("`^As an Ghoul, you feel your ancestors building in your bones.`nYou gain extra defense!");
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
            raceghoul_checkcity();
            $session['user']['hitpoints']+=$ghoul;
        }
        break;
	}
    return $args;
}

function raceghoul_checkcity(){
    global $session;
    $race="Ghoul";
    if (is_module_active("racevamp")) {
		$city = get_module_setting("villagename", "racevamp");
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

function raceghoul_run(){
}
?>