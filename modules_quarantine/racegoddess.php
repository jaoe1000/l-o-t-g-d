<?php

function racegoddess_getmoduleinfo(){
    $info = array(
        "name"=>"Race - Goddess",
        "version"=>"1.1",
        "author"=>"Kyle Spence based on Chris Vorndran(Sichae) imp",
        "category"=>"Races",
		"description"=>"Race is female only.",
        "settings"=>array(
            "Goddess Race Settings,title",
            "minedeathchance"=>"Chance for Goddess to die in the mine,range,0,100,1|25",
			"divide"=>"Attack is divided by this value to give buff,int|2",
			"mindk"=>"How many DKs do you need before the race is available?,int|100",
        ),
        );
    return $info;
}

function racegoddess_install(){
	if (!is_module_installed("raceelf")) {
		output("The Imp only choose to live with elves.   You must install that race module.");
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

function racegoddess_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Goddess'";
	db_query($sql);
	if ($session['user']['race'] == 'Goddess')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racegoddess_dohook($hookname,$args){
    //yeah, the $resline thing is a hack.  Sorry, not sure of a better way
    //to handle this.
    // It could be passed as a hook arg?
    global $session,$resline;
	if (is_module_active("raceelf")) {
		$city = get_module_setting("villagename", "raceelf");
	} else {
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
    $race = "Goddess";
	$divide = get_module_setting("divide");
    $Goddess = 1.3;
    switch($hookname){
    case "raceminedeath":
        if ($session['user']['race'] == $race) {
            $args['chance'] = get_module_setting("minedeathchance");
            $args['racesave'] = "You disappered and they were shocked you got away unhurt also.`n";
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
		if ($session['user']['sex']==SEX_MALE)
		    break;
        if ($session['user']['dragonkills'] < get_module_setting("mindk"))
			break;
         output("<a href='newday.php?setrace=God$resline'>The land of the Gods</a> DragonsFire, `5Illuminating the world. `^Gods`0 `5 houses, crafted out of pure light. Making a world a better or worse place, protected from the world of the normal folk. You are a very awe inspiring, only able to as you wish. You feel the need to do as you wish.`n`n",true);
        addnav("`\$G`)od`0","newday.php?setrace=God$resline");
        addnav("","newday.php?setrace=God$resline");
        break;
     case "setrace":
        if ($session['user']['race']==$race){
            output("`^As a God, you feel your godliness skin protect you.`nYou gain extra attack!");
            if (is_module_active("cities")) {
                if ($session['user']['dragonkills']==0 &&
                        $session['user']['age']==0){
                    //new God, set them to wandering around this city.
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
            racegoddess_checkcity();
            apply_buff("racialbenefit",array(
                "name"=>"`@Goddess wishes`0",
                "atkmod"=>"1.3",
                "allowintrain"=>1,
                "allowinpvp"=>1,
                "rounds"=>-1,
                )
            );
        }
        break;
    }
    return $args;
}

function racegoddess_checkcity(){
    global $session;
    $race="Goddess";
    if (is_module_active("Arcadia")) {
		$city = get_module_setting("villagename");
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

function racegoddess_run(){
}
?>
