<?php

function racevamp_getmoduleinfo(){
    $info = array(
        "name"=>"Race - Vampire",
        "version"=>"1.2",
        "author"=>"Chris Vorndran",
        "category"=>"Races",
        "download"=>"http://dragonprime.net/index.php?module=Downloads;sa=dlview;id=43",
		"vertxtloc"=>"http://dragonprime.net/users/Sichae/",
		"description"=>"Race. Buff is determined by Favor",
        "settings"=>array(
            "Vampiric Race Settings,title",
            "villagename"=>"Name for the vampire village|Deus Nocturnem",
			"favorratio"=>"Divide Favor by this to define the attack buff,int|50",
			"max"=>"Cap for the favor buff calculation provided to Vampries?,int|5",
			"Calculation: (1+((1+floor(\$vamp))/<attack>)).,note",
            "minedeathchance"=>"Chance for Vampires to die in the mine,range,0,100,1|90",
			"mindk"=>"How many DKs do you need before the race is available?,int|5",
        ),
    );
    return $info;
}

function racevamp_install(){
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
    // Update from commentary sections using village-$city to village-$race;
    // This is pretty much a one-time thing
    $sql = "UPDATE " . db_prefix("commentary") . " SET section='village-Vampire' WHERE section='village-Deus Nocturnem'";
    db_query($sql);
    return true;
}

function racevamp_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Vampire'";
	db_query($sql);
	if ($session['user']['race'] == 'Vampire')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racevamp_dohook($hookname,$args){
    //yeah, the $resline thing is a hack.  Sorry, not sure of a better way
    //to handle this.
    // Pass it in via args?
    global $session,$resline;
    $city = get_module_setting("villagename");
    $race = "Vampire";
	$favorratio = get_module_setting("favorratio");
    $vamp = round($session['user']['deathpower']/$favorratio);
	if ($vamp > get_module_setting("max")) $vamp = get_module_setting("max");
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
        output("<a href='newday.php?setrace=Vampire$resline'>Growing up, shrouded in darkness of `%%s</a> `&chambered away from the world. `\$Vampiric `&structures protecting you, from those that wish to destroy your kind. You are a creature of the night, wishing to feed...`n`n`0",$city,true);
        addnav("`\$V`4ampire`0","newday.php?setrace=Vampire$resline");
        addnav("","newday.php?setrace=Vampire$resline");
        break;
    case "setrace":
        if ($session['user']['race']==$race){
            output("`^As a Vampire, you feel the powers of old, coursing in your veins.`nYou gain extra attack!");
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
            racevamp_checkcity();
            apply_buff("racialbenefit",array(
                "name"=>"`@Vampiric Elders`0",
                "atkmod"=>"(<attack>?(1+((1+floor($vamp))/<attack>)):0)",
                "allowintrain"=>1,
                "allowinpvp"=>1,
                "rounds"=>-1,
				"schema"=>"module-racevamp",
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
        racevamp_checkcity();
        //remind me to edit this later ^.^
        if ($session['user']['location'] == $city){
            $args['text']="`\$`c`b$city, `)Home to Vampires`b`c`n`)You stand on the cold stone floor.  $city rises about you, one cold tomb...for all to rest within.  Strong crimson pillars hold up the ceiling. The magnificent tapestries around you, paint the history of the Vampires. In the distance, you hear the shrill cry of bats. One swoops by, trying to snatch at your gold pouch. You swat it down with your weapon, and then strike off into the square.`n";
            $args['clock']="`n`)One of the bat's voice is quite distinct.`nThe bat speaks the time as `^%s`) before disappearing into the night.`n";
            $args['title']="$city";
            $args['sayline']="chants";
            $args['talk']="`n`^Nearby some villagers chant:`n";
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
                $args['newest']="`n`4You feel your new fangs finally peek out. The pain is agonizing, and your brain is telling you to hunt.";
            } else {
                $args['newest']="`n`4Holding their head, hearing the voices of old is `^%s`6.";
            }
			$args['gatenav']="Nether Regions";
            $args['fightnav']="Hunting Grounds";
            $args['marketnav']="Storage Houses";
            $args['tavernnav']="Crimson Avenue";
            $args['section']="village-$race";
        }
        break;
    }
    return $args;
}

function racevamp_checkcity(){
    global $session;
    $race="Vampire";
    $city=get_module_setting("villagename");
    
    if ($session['user']['race']==$race && is_module_active("cities")){
        //if they're this race and their home city isn't right, set it up.
        if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
            set_module_pref("homecity",$city,"cities");
        }
    }    
    return true;
}

function racevamp_run(){

}
?> 