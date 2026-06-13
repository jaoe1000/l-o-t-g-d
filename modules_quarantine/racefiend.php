<?php

function racefiend_getmoduleinfo(){
    $info = array(
        "name"=>"Race - Fiend",
        "version"=>"1.0",
        "author"=>"Jonathan Newton",
        "category"=>"Races",
        "download"=>"http://dragonprime.net/users/Ender/racefiend.zip",
		"vertxtloc"=>"http://dragonprime.net/users/Ender/",
		"description"=>"Race. Buff is Attack + 3",
        "settings"=>array(
            "Fiend Race Settings,title",
            "villagename"=>"Name for the Fiend village|Pythirian",
            "minedeathchance"=>"Chance for Fiend to die in the mine,range,0,100,1|30",
			"mindk"=>"How many DKs do you need before the race is available?,int|0",
        ),
    );
    return $info;
}

function racefiend_install(){
    module_addhook("chooserace");
    module_addhook("setrace");
    module_addhook("newday");
    module_addhook("villagetext");
    module_addhook("travel");
    module_addhook("charstats");
    module_addhook("validlocation");
	module_addhook("validforestloc");
    module_addhook("moderate");
    module_addhook("changesetting");
    module_addhook("raceminedeath");
	module_addhook("racenames");
    module_addhook("pvpadjust");
    $sql = "UPDATE " . db_prefix("commentary") . " SET section='village-Fiend' WHERE section='village-Pythirian'";
    db_query($sql);
    return true;
}

function racefiend_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Fiend'";
	db_query($sql);
	if ($session['user']['race'] == 'Fiend')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racefiend_dohook($hookname,$args){
    //yeah, the $resline thing is a hack.  Sorry, not sure of a better way
    //to handle this.
    global $session,$resline;
    $city = get_module_setting("villagename");
    $race = "Fiend";
    $lzrd = max(round($session['user']['Fiendish Awareness']),3);
    switch($hookname){
    case "pvpadjust":
        if ($args['race'] == $race) {
            $args['creaturedefense']++;
        }
        break;
    case "raceminedeath":
        if ($session['user']['race'] == $race) {
            $args['chance'] = get_module_setting("minedeathchance");
        }
        break;
	case "racenames":
		$args[$race] = $race;
		break;
    case "changesetting":
        // Ignore anything other than villagename setting changes
        if ($args['setting'] == "villagename") {
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
    case "charstats":
        if ($session['user']['race']==$race){
            addcharstat("Vital Info");
            addcharstat("Race", $race);
        }
        break;
    case "chooserace":
		if ($session['user']['dragonkills'] < get_module_setting("mindk"))
			break;
        output("<a href='newday.php?setrace=Fiend$resline'>In the Depths of the Earth</a> in the city of %s, `Qsurrounded by Fire and Brimstone, you were brought up in the depths of the earth, you can withstand great heat.`n`n`0",$city,true);
        addnav("`QFiend`0","newday.php?setrace=Fiend$resline");
        addnav("","newday.php?setrace=Fiend$resline");
        break;
    case "setrace":
        if ($session['user']['race']==$race){
            output("`^As a Fiend, you feel more Aware.`nYou gain extra defense!");
            if (is_module_active("cities")) {
                if ($session['user']['dragonkills']==0 &&
                        $session['user']['age']==0){
                        
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
            racefiend_checkcity();
            apply_buff("racialbenefit",array(
                "name"=>"`@Fiendish Awareness`0",
                "defmod"=>"(<defense>?(1+((1+floor(<level>/5))/<defense>)):0)",
                "allowintrain"=>1,
                "allowinpvp"=>1,
                "rounds"=>-1,
                )
            );
        }
        break;
	case "validforestloc":
	case "validlocation":
		if (is_module_active("cities"))
			$args[$city] = "village-$race";
		break;
    case "moderate":
        if (is_module_active("cities"))
            $args["village-$race"]="City of $city";
        break;
	case "travel":
		$capital = getsetting("villagename", LOCATION_FIELDS);
		$hotkey = substr($city, 0, 1);
		tlschema("module-cities");
		if ($session['user']['location']==$capital){
			addnav("Safer Travel");
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city");
		}elseif ($session['user']['location']!=$city){
			addnav("More Dangerous Travel");
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city&d=1");
		}
		if ($session['user']['superuser'] & SU_EDIT_USERS){
			addnav("Superuser");
			addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city&su=1");
		}
		tlschema();
		break;   
    case "villagetext":
        racefiend_checkcity();
        if ($session['user']['location'] == $city){
            $args['text']="`\$`c`b$city, `)Home to the Fiends`b`c`n`Q You are standing in the middle of a massive pit of Fire and Brimstone looking up to  $city built on a pinacle of rock infront of you, you walk up the winding path feeling the opressive heat you begin to feel slightly faint.`n";
            $args['clock']="`n `QAll around you, you hear a eerie whispering stating that the time is `^%s`) `n";
            $args['title']="$city";
            $args['sayline']="growls";
            $args['talk']="`n`^Nearby some fiends growl:`n";
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
                $args['newest']="`n`4You awake in a very hot place and feel ready to attack the World.";
            } else {
                $args['newest']="`n`4Holding their head, hearing the voices of demons is `^%s`6.";
            }
			$args['gatenav']="Fire and Brimstone";
            $args['fightnav']="Fields of Death";
            $args['marketnav']="Hells Bells";
            $args['tavernnav']="The Brimstone Inn";
            $args['section']="village-$race";
        }
        break;
    }
    return $args;
}

function racefiend_checkcity(){
    global $session;
    $race="Fiend";
    $city=get_module_setting("villagename");
    
    if ($session['user']['race']==$race && is_module_active("cities")){
        //if they're this race and their home city isn't right, set it up.
        if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
            set_module_pref("homecity",$city,"cities");
        }
    }    
    return true;
}

function racefiend_run(){

}
?> 
