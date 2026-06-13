<?php

//Based on Human by Eric Stevens.

function racederyni_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Deryni",
		"version"=>"1.0",
		"author"=>"Jonathan Newton",
		"category"=>"Races",
		"download"=>"http://dragonprime.net/users/Ender/racederyni.zip",
		"settings"=>array(
			"Deryni Race Settings,title",
			"villagename"=>"Name for the Deryni village|Cor Culdi",
			"minedeathchance"=>"Chance for Derynis to die in the mine,range,0,100,1|90",
			"ranchloc"=>"Where does Pandora appear,location|".getsetting("villagename", LOCATION_FIELDS)
		),
	);
	return $info;
}

function racederyni_install(){
	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("newday");
	module_addhook("villagetext");
	module_addhook("stabletext");
	module_addhook("travel");
	module_addhook("charstats");
	module_addhook("validlocation");
	module_addhook("validforestloc");
	module_addhook("moderate");
	module_addhook("changesetting");
	module_addhook("raceminedeath");
	module_addhook("stablelocs");
	module_addhook("racenames");
	module_addhook("village");
	return true;
}

function racederyni_uninstall(){
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	// Force anyone who was a Deryni to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Deryni'";
	db_query($sql);
	if ($session['user']['race'] == 'Deryni')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racederyni_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	// to handle this.
	// Pass it as an arg?
	global $session,$resline;
	$city = get_module_setting("villagename");
	$race = "Deryni";
	switch($hookname){
	case "racenames":
		$args[$race] = $race;
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race) {
			$args['chance'] = get_module_setting("minedeathchance");
			
	switch($hookname){
		case "changesetting":
			if ($args['setting'] == "villagename"){
				if ($args['old'] == get_module_setting("ranchloc")) {
					set_module_setting("ranchloc", $args['new']);
				}
				}
				}
		}
		break;
	case "changesetting":
		// Ignore anything other than villagename setting changes
		if ($args['setting'] == "villagename" && $args['module']=="racederyni") {
			if ($session['user']['location'] == $args['old'])
				$session['user']['location'] = $args['new'];
			$sql = "UPDATE " . db_prefix("accounts") .
				" SET location='" . addslashes($args['new']) .
				"' WHERE location='" . addslashes($args['old']) . "'";
			db_query($sql);
			if (is_module_active("cities")) {
				$sql = "UPDATE " . db_prefix("module_userprefs") .
					" SET value='" . addslashes($args['new']) .
					"' WHERE modulename='cities' AND setting='homecity'" .
					"AND value='" . addslashes($args['old']) . "'";
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
		output("`0<a href='newday.php?setrace=$race$resline'>Deep in the forest </a> `^in the city of %s, learning great tricks of magic and illusion.`n`n", $city, true);
		addnav("`&Deryni`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`&As a Deryni, your skill in magic and illusion makes you quicker in combat.`n`^You gain an extra forest fight each day!");
			if (is_module_active("cities")) {
				if ($session['user']['dragonkills']==0 &&
						$session['user']['age']==0){
					//new farmthing, set them to wandering around this city.
					set_module_setting("newest-$city",
							$session['user']['acctid'],"cities");
				}
				set_module_pref("homecity",$city,"cities");
				if ($session['user']['age'] == 0)
					$session['user']['location']=$city;
			}
		}
		break;
	case "newday":
		if ($session['user']['race']==$race){
			racederyni_checkcity();
			$args['turnstoday'] .= ", Race (deryni): 1";
			$session['user']['turns']++;
			output("`n`&Because you are a Deryni, you gain `^an extra`& forest fight for today!`n`0");
		}
		break;
	case "validforestloc":
	case "validlocation":
		if (is_module_active("cities"))
			$args[$city]="village-$race";
		break;
	case "moderate":
		if (is_module_active("cities")) {
			tlschema("commentary");
			$args["village-$race"]=sprintf_translate("City of %s", $city);
			tlschema();
		}
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
		racederyni_checkcity();
		if ($session['user']['location'] == $city){
			$args['text']=array("`&`c`b%s, City of Deryni`b`c`n`7You are standing in the heart of %s. `)Deep in the middle of a very dark wood stands the city of %s, a great city with natural defenses only equalled by the Dwarves underground cities. `n", $city, $city, $city);
			$args['schemas']['text'] = "module-racederyni";
			$args['clock']="`n`7The great sundial at the heart of the city reads `&%s`7.`n";
			$args['schemas']['clock'] = "module-racederyni";
			if (is_module_active("calendar")) {
				$args['calendar'] = "`n`7A smaller contraption next to it reads `&%s`7, `&%s %s %s`7.`n";
				$args['schemas']['calendar'] = "module-racederyni";
			}
			$args['title']=array("%s, City of Deryni", $city);
			$args['schemas']['title'] = "module-racederyni";
			$args['sayline']="says";
			$args['schemas']['sayline'] = "module-racederyni";
			$args['talk']="`n`&Nearby some villagers talk:`n";
			$args['schemas']['talk'] = "module-racederyni";
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
				$args['newest']="`n`7As you wander your new home, you feel your jaw dropping at the wonders around you.";
			} else {
				$args['newest']="`n`7Wandering the village, jaw agape, is `&%s`7.";
			}
			$args['schemas']['newest'] = "module-racederyni";
			$args['section']="village-$race";
			$args['stablename']="Pandora's Ranch";
			$args['schemas']['stablename'] = "module-racederyni";
			$args['gatenav']="Village Gates";
			$args['schemas']['gatenav'] = "module-racederyni";
			}
			break;
			
			case "village":
			if ($session['user']['location'] == get_module_setting("ranchloc")) {
		tlschema($args['schemas']['tavernnav']);
				addnav($args['tavernnav']);
				tlschema();
                addnav("P?Pandora's Ranch`0","ranch.php");

			
		}
		break;
	case "stabletext":
		if ($session['user']['location'] != $city) break;
		$args['title'] = "Pandora's Ranch";
		$args['schemas']['title'] = "module-racederyni";
		$args['desc'] = "`6Just outside the outskirts of the village, you see a huge Ranch with a training area and riding range.  Many people from all across the land mingle as Pandora, a lovely Lady with an eye-catching smile, extols the virtues of each of the creatures in her care.  As you approach, Pandora smiles broadly, \"`^Ahh! how can I help you today, my %s?`6\" she asks in a lovely voice.";
		$args['schemas']['desc'] = "module-racederyni";
		$args['lad']="friend";
		$args['schemas']['lad'] = "module-racederyni";
		$args['lass']="friend";
		$args['schemas']['lass'] = "module-racederyni";
		$args['nosuchbeast']="`6\"`^I'm sorry, I don't stock any such animal.`6\", Pandora says apologetically.";
		$args['schemas']['nosuchbeast'] = "module-racederyni";
		$args['finebeast']=array(
			"`6\"`^Yes, yes, that's one of my finest beasts!`6\" says Pandora.`n`n",
			"`6\"`^Not even Dustin has a finer specimen than this!`6\" Pandora boasts.`n`n",
			"`6\"`^Doesn't this one have fine musculature?`6\" she asks.`n`n",
			"`6\"`^You'll not find a better trained creature in all the land!`6\" exclaims Pandora.`n`n",
			"`6\"`^And a bargain this one'd be at twice the price!`6\" booms Pandora.`n`n",
			);
		$args['schemas']['finebeast'] = "module-racederyni";
		$args['toolittle']="`6Pandora looks over the gold and gems you offer and turns up her nose, \"`^Obviously you misheard my price.  This %s will cost you `&%s `^gold  and `%%s`^ gems and not a penny less.`6\"";
		$args['schemas']['toolittle'] = "module-racederyni";
		$args['replacemount']="`6Patting %s`6 on the rump, you hand the reins as well as the money for your new creature, and Pandora hands you the reins of a `&%s`6.";
		$args['schemas']['replacemount'] = "module-racederyni";
		$args['newmount']="`6You hand over the money for your new creature, and Pandora hands you the reins of a new `&%s`6.";
		$args['schemas']['newmount'] = "module-racederyni";
		$args['nofeed']="`6\"`^I'm terribly sorry %s, but I don't stock feed here.  I'm not a common stable after all!  Perhaps you should look elsewhere to feed your creature.`6\"";
		$args['schemas']['nofeed'] = "module-racederyni";
		$args['nothungry']="`&%s`6 picks briefly at the food and then ignores it.  Pandora, being honest, shakes her head and hands you back your gold.";
		$args['schemas']['nothungry'] = "module-racederyni";
		$args['halfhungry']="`&%s`6 dives into the provided food and gets through about half of it before stopping.  \"`^Well, %s wasn't as hungry as you thought.`6\" says Pandora as she hands you back all but %s gold.";
		$args['schemas']['halfhungry'] = "module-racederyni";
		$args['hungry']="`6%s`6 seems to inhale the food provided.  %s`6, the greedy creature that it is, then goes snuffling at Pandora's pockets for more food.`nPandora shakes her head in amusement and collects `&%s`6 gold from you.";
		$args['schemas']['hungry'] = "module-racederyni";
		$args['mountfull']="`n`6\"`^Well, %s, your %s`^ is full up now.  Come back tomorrow if it hungers again, and I'll be happy to sell you more.`6\" says Pandora with a genial smile.";
		$args['schemas']['mountfull'] = "module-racederyni";
		$args['nofeedgold']="`6\"`^I'm sorry, but that is just not enough money to pay for food here.`6\"  Pandora turns her back on you, and you lead %s away to find other places for feeding.";
		$args['schemas']['nofeedgold'] = "module-racederyni";
		$args['confirmsale']="`n`n`6Pandora eyes your mount up and down, checking it over carefully.  \"`^Are you quite sure you wish to part with this creature?`6\"";
		$args['schemas']['confirmsale'] = "module-racederyni";
		$args['mountsold']="`6With but a single tear, you hand over the reins to your %s`6 to Pandora's ranchhand.  The tear dries quickly, and the %s in hand helps you quickly overcome your sorrow.";
		$args['schemas']['mountsold'] = "module-racederyni";
		$args['offer']="`n`n`6Pandora strokes your creature's flank and offers you `&%s`6 gold and `%%s`6 gems for %s`6.";
		$args['schemas']['offer'] = "module-racederyni";
		break;
	case "stablelocs":
		tlschema("mounts");
		$args[$city]=sprintf_translate("The City of %s", $city);
		tlschema();
		break;
	}
	return $args;
}

function racederyni_checkcity(){
	global $session;
	$race="Deryni";
	$city=get_module_setting("villagename");
	
	if ($session['user']['race']==$race && is_module_active("cities")){
		//if they're this race and their home city isn't right, set it up.
		if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
			set_module_pref("homecity",$city,"cities");
		}
	}	
	return true;
}

function racederyni_run(){

}
?>
