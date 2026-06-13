<?php

function racearchdemon_city_getmoduleinfo()
{
	$info = array
	(
		"name"		=> "Race - Archdemon",
		"version"	=> "1.04",
		"author"	=> "RPGSL",
		"category"	=> "RPGSL",
		"download"	=> "http://www.rpdragon.com/lotgd/racearchdemon_city.zip",
		"settings"	=> array
		(
			"Archdemon Race Settings,title",
			"villagename"		=> "`b1)`b Name for this race's village|Vicus Nefastus",
			"minedeathchance"	=> "`b2)`b Percent chance for this race to die in the mine,range,0,100,1|50",
			"gemchance"			=> "`b3)`b Percent chance for this race to find a gem on battle victory,range,0,100,1|0",
			"gemmessage"		=> "`b4)`b Message to display when finding a gem|`&Your eyes catch a glint of light, you notice a `%gem`&!",
			"goldloss"			=> "`b5)`b How much less gold (in percent) does this race find?,range,0,100,1|30",
			"mindk"				=> "`b6)`b How many DKs do you need before the race is available?,int|20",
			"cost"				=> "`b7)`b How many donation points do you need before the race is available?,int|0",
			"maxal" 			=> "`b8)`b What is the maximum amount of alignment allowed to obtain this race?, int | 33",
			"maxaltemphp" 		=> "`b9)`b What is the maximum amount of alignment allowed to get a new day temporary hit point bonus?, int | 33",
			"usefavor"			=> "`b10)`b Should a character's Favor affect the temporary hit point bonus?, bool | 1",
			"usefavorbonus"		=> "`b11)`b How much of a Favor bonus in percentage points should be added to temporary hit points? (based on maximum hit points and will not go higher than #8) , floatrange, 0, 100, .25 | 2.5",
			"maxtemphpbonus" 	=> "`b12)`b What is the maximum temporary hit point bonus percent gained each new day? (maximum hit points times this percentage), floatrange, 0, 100, .25 | 14.5",
			"permhpallowed"		=> "`b13)`b Does this race have a chance per new day to receive a permament raise of hit points?, bool | 1",
			"maxalpermhp" 		=> "`b14)`b What is the maximum amount of alignment needed to get a chance for a permanent hit point bonus?, int | 2",
			"permhpchance" 		=> "`b15)`b What is the chance of gaining a permanent hit point bonus if #14 is met?, range, 0, 100, 1  | 12",
			"permhpbonus" 		=> "`b16)`b How many permanent hit points are gained if #14 and #15 are met?, int | 1",
			"candemote"			=> "`b17)`b Can this race be demoted if they go above the alignment specified in #8?,bool|0",
			"charmchance"		=> "`b18)`b What is the chance this race will gain charm per new day?,range,0,100,1|33",
			"charmgain"			=> "`b19)`b If #18 is greater than zero how much charm is gained?,int|1",
			"hasminion"			=> "`b20)`b Does this race get a minion?,bool|1",
			"canheal"			=> "`b21)`b Does this race have a chance of healing on every click? (except when dead),bool|1",
        ),
		"prefs"		=> array
		(
			"Archdemon Race User Preferences,title",
			"temphptoday"		=> "How many temporary hit points did this character receive today?, hidden | 0",
			"permhptoday"		=> "Did this character receive a permanent hit point bonus today? Changing this has no effect on gameplay - it is only a reference., bool | 0",
			"totalpermhp"		=> "How many total permanent hitpoints has this character received?, hidden | 0",
		),
		"requires" 	=> array
		(
			"alignment" 		=> "1.6 | WebPixie `#Lonny Luberts `^and Chris Vorndran, http://dragonprime.net/users/Sichae/alignment.zip",
			"racevamp" 			=> "1.1 | Chris Vorndran, http://dragonprime.net/users/Sichae/racevamp.zip",
		),
	);
	return $info;
}

function racearchdemon_city_install()
{
	if (!is_module_installed("racevamp") && !is_module_installed("racedemon"))
	{
		output("You do not have the Vampire or the Demon race installed. This module requires at least one of those modules.");
		return false;
	}	
	if (is_module_active("alignment"))
	{
		if (!is_module_installed("racedemon"))
		{
			output("Installing `bRace - Archdemon`b (racearchdemon_city.php) module by Role Play Game Story Lines (www.rpgsl.com)`n");
		}
		else
		{
			output("Updating `bRace - Archdemon`b (racearchdemon_city.php) module by Role Play Game Story Lines (www.rpgsl.com)`n");
		}
	}
	else
	{
		output("You do not have the Basic Alignment module installed. This module cannot be installed until you install Basic Alignment. Visit http://dragonprime.net/users/Sichae/alignment.zip`n");
		return false;
	}
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
	module_addhook("pointsdesc");
	module_addhook("everyhit");
	return true;
}

function racearchdemon_city_uninstall()
{
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
	{
		$session['user']['location'] = $vname;
	}
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Archdemon'";
	db_query($sql);
	if ($session['user']['race'] == 'Archdemon')
	{
		$session['user']['race'] = RACE_UNKNOWN;
	}
	return true;
}

function racearchdemon_city_dohook($hookname, $args)
{
	global $session, $resline;
	$city = get_module_setting("villagename");
	$race = "Archdemon";
	$cost = get_module_setting("cost");
	$al = get_align();
	switch($hookname)
	{
	case "pointsdesc":
		if (get_module_setting("mindk") > 0 || $cost > 0)
		{
			$args['count'] ++;
			$format = $args['format'];
			$str = translate("The Archdemon Race is availiable upon reaching %s Dragon Kills and %s Donation points.");
			$str = sprintf($str, get_module_setting("mindk"), get_module_setting("cost"));
			output($format, $str, true);
		}
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race)
		{
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "Fortunately your Archdemon skill let you escape unscathed.`n";
			$args['schema' ]= "module-racearchdemon_city";
		}
		break;
	case "changesetting":
		if ($args['setting'] == "villagename" && $args['module'] == "racearchdemon_city")
		{
			if ($session['user']['location'] == $args['old'])
			{
				$session['user']['location'] = $args['new'];
			}
			$sql = "UPDATE " . db_prefix("accounts") .
				" SET location='" . $args['new'] .
				"' WHERE location='" . $args['old'] . "'";
			db_query($sql);
			if (is_module_active("cities"))
			{
				$sql = "UPDATE " . db_prefix("module_userprefs") .
					" SET value='" . $args['new'] .
					"' WHERE modulename='cities' AND setting='homecity'" .
					"AND value='" . $args['old'] . "'";
				db_query($sql);
			}
		}
		break;
	case "charstats":
		if ($session['user']['race'] == $race)
		{
			addcharstat("Vital Info");
			addcharstat("Race", translate_inline($race));
		}
		break;
	case "chooserace":
		$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
		if ($session['user']['dragonkills'] < get_module_setting("mindk") || get_module_setting("cost") > $pointsavailable)
		{
			break;
		}
		$maxal = get_module_setting("maxal");
		if ($al <= $maxal)
		{
			output("`n`nIn the unholy darkness as an <a href='newday.php?setrace=Archdemon$resline'>`$%s`7</a>, you descend into existence in %s. You have ascended into the descended into darkness of evil.`n`n	", translate_inline($race), $city, true);
			addnav("Archdemon", "newday.php?setrace=$race$resline");
			addnav("", "newday.php?setrace=$race$resline");
			break;
		}
	case "setrace":
		if ($session['user']['race'] == $race)
		{
            output("`^As an Archdemon, you feel the powers of darkness, coursing in your veins.`nYou will get your powers when you are Evil!");
			$session['user']['donationspent'] = $session['user']['donationspent'] + $cost;
			if (is_module_active("cities"))
			{
				if ($session['user']['dragonkills'] == 0 &&	$session['user']['age'] == 0)
				{
					set_module_setting("newest-$city", $session['user']['acctid'], "cities");
				}
				set_module_pref("homecity", $city, "cities");
				$session['user']['location'] = $city;
			}
		}
		break;
	case "everyhit":
		if (get_module_setting("canheal") == 1)
		{
			$healchancerand = e_rand(1,200);
			$healchancerand -= round($session['user']['level']);
			if ($healchancerand <= 1)
			{
				if ($session['user']['hitpoints'] < $session['user']['maxhitpoints'] && $session['user']['race'] == $race  && $session['user']['hitpoints'] <> 0)
				{
					$healamount = round($session['user']['level'] * 2.5);
					$healrand = e_rand(2, $healamount);
					$session['user']['hitpoints'] += $healrand;
					output("`bYour Archdemon powers heal you for %s hit points!`b`n`n", $healrand);
				}
			}
		}
		break;
	case "battle-victory":
		if ($session['user']['race'] != $race) break;
		if ($args['type'] != "forest") break;
		if ($session['user']['level'] < 15 && e_rand(1,100) <= get_module_setting("gemchance"))
		{
			output(get_module_setting("gemmessage")."`n`0");
			$session['user']['gems'] ++;
			debuglog("found a gem when slaying a monster, for being an Archdemon.");
		}
		break;
	case "creatureencounter":
		if ($session['user']['race'] == $race){
			racearchdemon_city_checkcity();
			$loss = (100 - get_module_setting("goldloss")) / 100;
			$args['creaturegold'] = round($args['creaturegold'] * $loss, 0);
		}
		break;
	case "newday":		
   	    racearchdemon_city_checkcity();
		$maxtemphpbonus 	= get_module_setting("maxtemphpbonus") / 100;
		$permhpbonus  		= get_module_setting("permhpbonus");
		$maxaltemphp 		= get_module_setting("maxaltemphp");
		$permhpchance 		= get_module_setting("permhpchance");
		$maxalpermhp 		= get_module_setting("maxalpermhp");
		$permhpallowed		= get_module_setting("permhpallowed");
		$usefavor 			= get_module_setting("usefavor");
		$usefavorbonus 		= get_module_setting("usefavorbonus") / 100;
		$temphptoday 		= get_module_pref("temphptoday");
		$permhptoday 		= get_module_pref("permhptoday");
		$totalpermhp 		= get_module_pref("totalpermhp");
		$deathpowerbonus 	= $session['user']['deathpower'] / 10000;
		set_module_pref("permhptoday", 0);
		if ($al > get_module_setting("maxal") && $session['user']['race'] == $race && get_module_setting("candemote") == 1)
		{
			if (is_module_active("racedemon"))
			{
				$session['user']['race'] = "Demon";
				output("`nYou have not been evil enough to be an Archdemon. You have been demoted to Demon!`n");
				break;
			}
			else
			{
				$session['user']['race'] = "Vampire";
				output("`nYou have not been evil enough to be an Archdemon. You have been demoted to a Vampire!`n");
				break;
			}
		}
		if ($session['user']['race'] == $race)
		{
			if (e_rand(1,100) <= get_module_setting("charmchance"))
			{
				output("`nYour unholy demeanor makes you more charismatic. You gain one charm point!`n");
				$session['user']['charm'] += get_module_setting("charmgain");
			}
			if (get_module_setting("hasminion") == 1)
			{
				apply_buff("racialbenefit",array
				(
					"name"				=> "Winged Demonspawn",
					"minioncount"		=> 1,
					"minbadguydamage"	=> 0,
					"maxbadguydamage"	=> 2 + floor($session['user']['level'] / 2),
					"effectmsg"			=> "`#Your winged demon engulfs {badguy} in a shroud of unholy darkness`# for `^{damage}`# damage.",
					"effectnodmgmsg"	=> "`#{badguy}`# evades the shroud of darkness!!",
					"allowinpvp"		=> 0,
					"allowintrain"		=> 0,
					"rounds"			=> -1,
					"schema"			=> "module-racearchdemon_city",
					)
				);
			}
		}
		if ($usefavor == 1)
		{
			if ($deathpowerbonus > $usefavorbonus)
			{
				$deathpowerbonus = $usefavorbonus;
			}			
		}
		else
		{
			$deathpowerbonus = 0;
		}
		$percentrand = e_rand(1,100) / 100 + $deathpowerbonus;
		if ($percentrand > $maxtemphpbonus)
		{
			$percentrand = $maxtemphpbonus;
		}
		$archdemon = round($session['user']['maxhitpoints'] * $percentrand);      
        if ($al <= $maxaltemphp && $session['user']['race'] == $race)
        {
       	    $session['user']['hitpoints'] += $archdemon;
        	if ($archdemon < 2)
        	{
        		$hplang = "point";
        	}
        	else
        	{
        		$hplang = "points";
        	}
        	$hplang = translate_inline($hplang);
       	    if ($usefavor == 1)
       	    {
       	    	output("`nBy being evil and having %s favor with Ramius, you gain %s hit %s today.`n", $session['user']['deathpower'], $archdemon, $hplang);
       	    }
       	    else
       	    {
       	    	output("`nYou gain %s hit %s today for being an evil Demon.`n", $archdemon, $hplang);
			}
			set_module_pref("temphptoday", $archdemon);
       		if ($permhpallowed == 1)
       		{
       			if ($al <= $maxalpermhp)
       			{
       				$permhpchancerand = e_rand(1,100);
       				if ($permhpchancerand <= $permhpchance)
       				{
       					$session['user']['maxhitpoints'] += $permhpbonus;
       					$session['user']['hitpoints'] += $permhpbonus;
        				if ($permhpbonus < 2)
        				{
        					$hplang = "point";
        				}
        				else
        				{
        					$hplang = "points";
        				}
        				$hplang = translate_inline($hplang);
    			        output("`nFor being extremely evil, you receive %s maximum hit %s!`n", $permhpbonus, $hplang);
						set_module_pref("permhptoday", 1);
						set_module_pref("totalpermhp", $totalpermhp + $permhpbonus);
        				debuglog("received $permhpbonus hit $hplang for being an Archdemon.");
	    		    }
				}
			}
        }
       	if ($session['user']['race'] == $race && $al > $maxaltemphp)
		{
	    	output("`n`bYou are not evil enough to get you Archdemonic abilities today! You better start being more evil!`b`n");
		}
    	break;
	case "validlocation":
        if (is_module_active("cities"))
        {
            $args[$city]="village-$race";
        }
        break;
    case "moderate":
        if (is_module_active("cities"))
        {
            $args["village-$race"]="City of $city";
        }
        break;
    case "travel":
        $capital = getsetting("villagename", LOCATION_FIELDS);
        if ($session['user']['location'] == $capital)
        {
            addnav("Safer Travel");
            addnav(substr($city,0,1)."?Go to $city", "runmodule.php?module=cities&op=travel&city=$city");
        }
        elseif ($session['user']['location'] != $city)
        {
            addnav("More Dangerous Travel");
            addnav(substr($city,0,1)."?Go to $city", "runmodule.php?module=cities&op=travel&city=$city&d=1");
        }
        if ($session['user']['superuser'] & SU_EDIT_USERS)
        {
            addnav("Superuser");
            addnav("Go to $city", "runmodule.php?module=cities&op=travel&city=$city&su=1");
        }
        break;       
    case "villagetext":
        racearchdemon_city_checkcity();
        if ($session['user']['location'] == $city)
        {
            $args['text'] = "`\$`c`@$city, the unholy village is not a place for the weak kneed. It's the home of the Demons and is fraught with temptations, vile creatures and unsavory characters. Few of the light come this way. The entire village wreaks of sulphur and brimstone.`0`n";
            $args['clock'] = "`n`2Ghastly screams announce that it is `^%s`2.`c`0`n";
            $args['title'] = "$city";
            $args['sayline'] 	= "wails";
            $args['talk'] 		= "`n`^Whispers, moans and gruesome grunts are heard throughout the village.`0`n";
            $new 				= get_module_setting("newest-$city", "cities");
            if (is_module_active("calendar"))
            {
				$args['calendar'] 				= "`n`2A rotting corpse gasps towards you that it is `&%s`2, `&%s %s %s`2.`n";
				$args['schemas']['calendar'] 	= "module-racearchdemon_city";
			}
            if ($new != 0)
            {
                $sql =  "SELECT name FROM " . db_prefix("accounts") .
                    " WHERE acctid='$new'";
                $result 				= db_query_cached($sql, "newest-$city");
                $row 					= db_fetch_assoc($result);
                $args['newestplayer'] 	= $row['name'];
                $args['newestid']		= $new;
            }
            else
            {
                $args['newestplayer'] 	= $new;
                $args['newestid']		= "";
            }
            if ($new == $session['user']['acctid'])
            {
                $args['newest'] 		= "`n`4This place is horrbily evil in many ways, `@$city";
            }
            else
            {
                $args['newest'] 		= " `^%s`2 has descended to the village.";
            }
			$args['schemas']['newest'] 	= "module-racearchdemon_city";
			$args['section'] 			= "village-$race";
			$args['gatenav'] 			= "Village Gates";
			$args['schemas']['gatenav'] = "module-racearchdemon_city";
        }
        break;
	}
	return $args;
}

function racearchdemon_city_checkcity()
{
	global $session;
	$race = "Archdemon";
	$city = get_module_setting("villagename");
	if ($session['user']['race'] == $race && is_module_active("cities"))
	{
        if (get_module_pref("homecity","cities") != $city)
        {
            set_module_pref("homecity",$city,"cities");
        }
    }   
	return true;
}

function racearchdemon_city_run()
{
}
?>