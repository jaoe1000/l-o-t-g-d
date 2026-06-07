<?php
// City-Less Functionality adopted from racefelyne of core.

function raceimp_getmoduleinfo(){
    $info = array(
        "name"=>"Race - Imp",
        "version"=>"1.11",
        "author"=>"Chris Vorndran",
        "category"=>"Races",
        "download"=>"http://dragonprime.net/index.php?module=Downloads;sa=dlview;id=42",
		"vertxtloc"=>"http://dragonprime.net/users/Sichae/",
		"description"=>"Race. Buff is based from Charm. Male Specific.",
        "settings"=>array(
            "Imp Race Settings,title",
            "minedeathchance"=>"Chance for Imp to die in the mine,range,0,100,1|25",
			"divide"=>"Charm is divided by this value to give buff,int|5",
			"max"=>"Cap for the charm buff calculation provided to Imps?,int|5",
			"Calculation: (1+((1+floor(\$vamp))/<attack>)).,note",
			"mindk"=>"How many DKs do you need before the race is available?,int|5",
        ),
    );
    return $info;
}

function raceimp_install(){
	if (!is_module_installed("raceelf")) {
		output("The Imp only choose to live with elves.   You must install that race module.");
		return false;
	}
    module_addhook("chooserace");
    module_addhook("setrace");
	module_addhook("newday");
	module_addhook("racenames");
    module_addhook("raceminedeath");
    module_addhook("charstats");
    return true;
}

function raceimp_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Imp'";
	db_query($sql);
	if ($session['user']['race'] == 'Imp')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function raceimp_dohook($hookname,$args){
    //yeah, the $resline thing is a hack.  Sorry, not sure of a better way
    //to handle this.
    // It could be passed as a hook arg?
    global $session,$resline;
	if (is_module_active("raceelf")) {
		$city = get_module_setting("villagename", "raceelf");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
    $race = "Imp";
	$divide = get_module_setting("divide");
    $imp = (round($session['user']['charm']/$divide));
	if ($imp > get_module_setting("max")) $imp = get_module_setting("max");
    switch($hookname){
    case "raceminedeath":
        if ($session['user']['race'] == $race) {
            $args['chance'] = get_module_setting("minedeathchance");
            $args['racesave'] = "Fortunately your Imp skill let you escape unscathed.`n";
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
		if ($session['user']['sex']==SEX_FEMALE)
		    break;
        if ($session['user']['dragonkills'] < get_module_setting("mindk"))
			break;
        output("<a href='newday.php?setrace=Imp$resline'>The land of Imps Hellshade</a> , `5hidden away from the world. `^Impish`0 `5houses, crafted out of molten rock. Hidden in the deepest of crags, protected from the world of the normal folk. You are a very small being, only able to fly. You feel the need to trick others.`n`n`0",true);
        addnav("`\$I`)mp`0","newday.php?setrace=Imp$resline");
        addnav("","newday.php?setrace=Imp$resline");
        break;
     case "setrace":
        if ($session['user']['race']==$race){
            output("`^As an Imp, you feel your matured skin protect you.`nYou gain extra attack!");
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
            raceimp_checkcity();
            apply_buff("racialbenefit",array(
                "name"=>"`@Imp Trickery`0",
                "atkmod"=>"(<attack>?(1+((1+floor($imp))/<attack>)):0)",
                "allowintrain"=>1,
                "allowinpvp"=>1,
                "rounds"=>-1,
				"schema"=>"module-raceimp",
                )
            );
        }
        break;
    }
    return $args;
}

function raceimp_checkcity(){
    global $session;
    $race="Imp";
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

function raceimp_run(){
}
?>