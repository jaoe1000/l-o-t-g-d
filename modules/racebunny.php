<?php
// translator ready
// addnews ready

/* Toki Race */
/* ver 1.3 */
/* Billie Kennedy => dannic06@gmail.com */
/* concept by JC Petersen */
/*  Fixes
 * fixed the download attribute to reflect that it is actually a download. *
 * 12/2/04
 * Added settings to load into the Lodge if you want to have it a points award only race
*/

function racebunny_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Toki",
		"version"=>"1.3",
		"author"=>"Billie Kennedy",
		"category"=>"Races",
		"download"=>"http://dragonprime.net/users/Dannic/racebunny.zip",
		"settings"=>array(
			"Toki Race Settings,title",
			"villagename"=>"Name for the Toki village|Under Hill",
			"minedeathchance"=>"Percent chance for Toki to die in the mine,range,0,100,1|20",
			"gemchance"=>"Percent chance for Toki to find a gem on battle victory,range,0,100,1|5",
			"gemmessage"=>"Message to display when finding a gem|`&Your Toki nose wiggles and tickles, you notice a `%gem`&!",
			"goldloss"=>"How much less gold (in percent) do the Toki find?,range,0,100,1|10",
			"mindk"=>"How many DKs do you need before the race is available?,int|0",
			"cost"=>"How many Donation points do you need before the race is available?,int|0",
		),
	);
	return $info;
}

function racebunny_install(){
	
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
	return true;
}

function racebunny_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	// Force anyone who was a Toki to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Toki'";
	db_query($sql);
	if ($session['user']['race'] == 'Toki')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racebunny_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	//to handle this.
	// It could be passed as a hook arg?
	global $session,$resline;
	$city = get_module_setting("villagename");
	$race = "Toki";
	$cost = get_module_setting("cost");
	switch($hookname){
	case "pointsdesc":
		if (get_module_setting("mindk")>0 || $cost>0)
		{
			$args['count']++;
			$format = $args['format'];
			$str = translate("The Toki Race is availiable upon reaching %s Dragon Kills and %s Donation points.");
			$str = sprintf($str, get_module_setting("mindk"),
					get_module_setting("cost"));
			output($format, $str, true);
		}
		break;
	case "pvpadjust":
		if ($args['race'] == $race) {
			$args['creaturedefense']+=(2+floor($args['creaturelevel']/5));
			$args['creaturehealth']-= round($args['creaturehealth']*.05, 0);
		}
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "Your knowledge of undground burrows allows you to notice the weakness in the cave before it collapsed.  You escape unscathed.`n";
			$args['schema']="module-racebunny";
		}
		break;
	case "changesetting":
		// Ignore anything other than villagename setting changes
		if ($args['setting'] == "villagename" && $args['module']=="racebunny") {
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
			addcharstat("Race", translate_inline($race));
		}
		break;
	case "chooserace":
		$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
		if ($session['user']['dragonkills'] < get_module_setting("mindk") || get_module_setting("cost") > $pointsavailable)
			break;
		output("<a href='newday.php?setrace=Toki$resline'>On the rolling hills surrounding the city of %s</a>, your race of `5Toki`0 farmed the hills for cloves.  Your long jumps allow you to visit places and jump to heights that larger races can only dream of.`n`n",$city, true);
		addnav("`5Toki`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;
	case "setrace":
		if ($session['user']['race']==$race){ // it helps if you capitalize correctly
			output("`&As a Toki, your rabbit-like reflexes allow you to respond very quickly to any attacks.`n");
			output("You gain extra defense!`n");
			output("You have the nose for glittering gems typical of your race, but your childhood as a farmer has left you less knowledgable about currency.`n");
			output("You gain extra gems from forest fights, but you also do not gain as much gold!");
			$session['user']['donationspent'] = $session['user']['donationspent'] + $cost;
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
	case "battle-victory":
		if ($session['user']['race'] != $race) break;
		if ($args['type'] != "forest") break;
		if ($session['user']['level'] < 15 &&
				e_rand(1,100) <= get_module_setting("gemchance")) {
			output(get_module_setting("gemmessage")."`n`0");
			$session['user']['gems']++;
			debuglog("found a gem when slaying a monster, for being a Toki.");
		}
		break;
	// Lets actually lower their gold a bit.. really
	case "creatureencounter":
		if ($session['user']['race']==$race){
			//get those folks who haven't manually chosen a race
			racebunny_checkcity();
			$loss = (100 - get_module_setting("goldloss"))/100;
			$args['creaturegold']=round($args['creaturegold']*$loss,0);
		}
		break;
	case "newday":
		if ($session['user']['race']==$race){
			racebunny_checkcity();
			apply_buff("racialbenefit",array(
				"name"=>"`@Rabbit-like Reflexes`0",
				"defmod"=>"(<defense>?(1+((2+floor(<level>/5))/<defense>)):0)",
				"allowinpvp"=>1,
				"allowintrain"=>1,
				"rounds"=>-1,
				"schema"=>"module-racebunny",
				)
			);
		}
		break;
	case "validlocation":
        if (is_module_active("cities"))
            $args[$city]="village-$race";
        break;
    case "moderate":
        if (is_module_active("cities"))
            $args["village-$race"]="City of $city";
        break;
    case "travel":
        $capital = getsetting("villagename", LOCATION_FIELDS);
        if ($session['user']['location']==$capital){
            addnav("Safer Travel");
            addnav(substr($city,0,1)."?Go to $city","runmodule.php?module=cities&op=travel&city=$city");
        }elseif ($session['user']['location']!=$city){
            addnav("More Dangerous Travel");
            addnav(substr($city,0,1)."?Go to $city","runmodule.php?module=cities&op=travel&city=$city&d=1");
        }
        if ($session['user']['superuser'] & SU_EDIT_USERS){
            addnav("Superuser");
            addnav("Go to $city","runmodule.php?module=cities&op=travel&city=$city&su=1");
        }
        break;       
    case "villagetext":
        racebunny_checkcity();
        //remind me to edit this later ^.^
        if ($session['user']['location'] == $city){
            $args['text']="`\$`c`@`b$city, `2Sleepy Hollows of the Toki`b`@`c`n`n`2You stand in a valley between rolling hills.  The residents of $city busy themselves among the clove farms on the hilltops.  Doors and windows sprout at the base of each hill suggesting residences and businesses of the city.  Small animals run around and insects buzz from flower to flower in the clove fields.`n";
            $args['clock']="`n`2One of the smaller butterflies lands on your ear and whispers the time as `^%s`2 before disappearing among the cloves.`n";
            $args['title']="$city";
            $args['sayline']="gossips";
            $args['talk']="`n`^Nearby some villagers gossip:`n";
            $new = get_module_setting("newest-$city", "cities");
            if (is_module_active("calendar")) {
				$args['calendar'] = "`n`2A small rock in the center of the city reads `&%s`2, `&%s %s %s`2.`n";
				$args['schemas']['calendar'] = "module-racebunny";
			}
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
                $args['newest']="`n`4The cloves crush softly beneath your feet as you wander through the hilltop fields of `@`b$city";
            } else {
                $args['newest']=" `^%s`2 is wandering around nibbling on a handful of cloves.";
            }
			$args['schemas']['newest'] = "module-racebunny";
			$args['section']="village-$race";
			$args['gatenav']="Village Gates";
			$args['schemas']['gatenav'] = "module-racebunny";
        }
        break;
	}

	return $args;
}

function racebunny_checkcity(){
	global $session;
	
	$race="Toki";
	$city=get_module_setting("villagename");
	
	if ($session['user']['race']==$race && is_module_active("cities")){
        //if they're this race and their home city isn't right, set it up.
        if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
            set_module_pref("homecity",$city,"cities");
        }
    }   
	return true;
}

function racebunny_run(){
}
?>
