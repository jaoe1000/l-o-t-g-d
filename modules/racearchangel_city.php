<?php

function racearchangel_city_getmoduleinfo()
{
	$info = array
	(
		"name"		=> "Race - Archangel",
		"version"	=> "1.02",
		"author"	=> "RPGSL",
		"category"	=> "RPGSL",
		"download"	=> "http://www.rpdragon.com/lotgd/racearchangel_city.zip",
		"settings"	=> array
		(
			"Archangel Race Settings,title",
			"villagename"		=> "`b1)`b Name for this race's village|Vicus Sanctum",
			"minedeathchance"	=> "`b2)`b Percent chance for this race to die in the mine,range,0,100,1|50",
			"gemchance"			=> "`b3)`b Percent chance for this race to find a gem on battle victory,range,0,100,1|0",
			"gemmessage"		=> "`b4)`b Message to display when finding a gem|`&Your eyes catch a glint of light, you notice a `%gem`&!",
			"goldloss"			=> "`b5)`b How much less gold (in percent) does this race find?,range,0,100,1|30",
			"mindk"				=> "`b6)`b How many DKs do you need before this race is available?,int|20",
			"cost"				=> "`b7)`b How many donation points do you need before the race is available?,int|0",
			"minal" 			=> "`b8)`b What is the minimum amount of alignment needed to get this race?,int|66",
			"minaltemphp" 		=> "`b9)`b What is the minimum amount of alignment needed to get a new day temporary hit point bonus?,int|66",
			"usefavor"			=> "`b10)`b Should a character's Favor affect the temporary hit point bonus?,bool|1",
			"usefavorbonus"		=> "`b11)`b How much of a Favor bonus in percentage points should be added to temporary hit points? (based on maximum hit points and will not go higher than #8),floatrange,0,100,.25|2.5",
			"maxtemphpbonus" 	=> "`b12)`b What is the maximum temporary hit point bonus percent gained each new day? (maximum hit points times this percentage),floatrange,0,100,.25|14.5",
			"permhpallowed"		=> "`b13)`b Does this race have a chance per new day to receive a permament raise of hit points?,bool|1",
			"minalpermhp" 		=> "`b14)`b What is the minimum amount of alignment needed to receive a chance for a permanent hit point bonus?,int|98",
			"permhpchance" 		=> "`b15)`b What is the chance of gaining a permanent hit point bonus if #14 is met?,range,0,100,1|12",
			"permhpbonus" 		=> "`b16)`b How many permanent hit points are gained if #14 and #15 are met?,int|1",
			"candemote"			=> "`b17)`b Can this race be demoted if they drop below the alignment specified in #8?,bool|0",
			"charmchance"		=> "`b18)`b What is the chance this race will gain charm per new day?,range,0,100,1|33",
			"charmgain"			=> "`b19)`b If #18 is greater than zero how much charm is gained?,int|1",
			"hasminion"			=> "`b20)`b Does this race get a minion?,bool|1",
			"canheal"			=> "`b21)`b Does this race have a chance of healing on every click?,bool|1",
        ),
		"prefs"		=> array
		(
			"Archangel Race User Preferences,title",
			"temphptoday"		=> "How many temporary hit points did this character receive today?, hidden | 0",
			"permhptoday"		=> "Did this character receive a permanent hit point bonus today? Changing this has no effect on gameplay - it is only a reference., bool | 0",
			"totalpermhp"		=> "How many total permanent hitpoints has this character received?, hidden | 0",
		),
		"requires" 	=> array
		(
			"alignment" 		=> "1.6 | WebPixie `#Lonny Luberts `^and Chris Vorndran, http://dragonprime.net/users/Sichae/alignment.zip",
			"racehuman"			=> "Core Module",
		),
	);
	return $info;
}

function racearchangel_city_install()
{
	if (!is_module_installed("racehuman") && !is_module_installed("raceangel"))
	{
		output("You do not have the Human or the Angel race installed. This module requires at least one of those modules.");
		return false;
	}	
	if (is_module_active("alignment"))
	{
		if (!is_module_installed("raceangel"))
		{
			output("Installing `bRace - Archangel`b (racearchangel_city.php) module by Role Play Game Story Lines (www.rpgsl.com)`n");
		}
		else
		{
			output("Updating `bRace - Archangel`b (racearchangel_city.php) module by Role Play Game Story Lines (www.rpgsl.com)`n");
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

function racearchangel_city_uninstall()
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
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Archangel'";
	db_query($sql);
	if ($session['user']['race'] == 'Archangel')
	{
		$session['user']['race'] = RACE_UNKNOWN;
	}
	return true;
}

function racearchangel_city_dohook($hookname, $args)
{
	global $session, $resline;
	$city = get_module_setting("villagename");
	$race = "Archangel";
	$cost = get_module_setting("cost");
	$al = get_align();
	switch($hookname)
	{
	case "pointsdesc":
		if (get_module_setting("mindk") > 0 || $cost > 0)
		{
			$args['count'] ++;
			$format = $args['format'];
			$str = translate("The Archangel Race is availiable upon reaching %s Dragon Kills and %s Donation points.");
			$str = sprintf($str, get_module_setting("mindk"), get_module_setting("cost"));
			output($format, $str, true);
		}
		break;
	case "raceminedeath":
		if ($session['user']['race'] == $race)
		{
			$args['chance'] = get_module_setting("minedeathchance");
			$args['racesave'] = "Fortunately your Archangel skill let you escape unscathed.`n";
			$args['schema' ]= "module-racearchangel_city";
		}
		break;
	case "changesetting":
		if ($args['setting'] == "villagename" && $args['module'] == "racearchangel_city")
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
		$minal = get_module_setting("minal");
		if ($al >= $minal)
		{
			output("In the glorious light as an <a href='newday.php?setrace=Archangel$resline'>`^%s`0</a>, you arise into existence in %s. You have ascended into the benevolent light of goodness.`n`n", translate_inline($race), $city, true);
			addnav("Archangel", "newday.php?setrace=$race$resline");
			addnav("", "newday.php?setrace=$race$resline");
			break;
		}
	case "setrace":
		if ($session['user']['race'] == $race)
		{
            output("`^As an Archangel, you feel the powers of light, coursing in your veins.`nYou will get your powers when you are Good!");
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
				if ($session['user']['hitpoints'] < $session['user']['maxhitpoints'] && $session['user']['race'] == $race && $session['user']['hitpoints'] <> 0)
				{
					$healamount = round($session['user']['level'] * 2.5);
					$healrand = e_rand(2, $healamount);
					$session['user']['hitpoints'] += $healrand;
					output("`bYour Archangel powers heal you for %s hit points!`b`n`n", $healrand);
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
			debuglog("found a gem when slaying a monster, for being an Archangel.");
		}
		break;
	case "creatureencounter":
		if ($session['user']['race'] == $race){
			racearchangel_city_checkcity();
			$loss = (100 - get_module_setting("goldloss")) / 100;
			$args['creaturegold'] = round($args['creaturegold'] * $loss, 0);
		}
		break;
	case "newday":		
   	    racearchangel_city_checkcity();
		$maxtemphpbonus 	= get_module_setting("maxtemphpbonus") / 100;
		$permhpbonus  		= get_module_setting("permhpbonus");
		$minaltemphp 		= get_module_setting("minaltemphp");
		$permhpchance 		= get_module_setting("permhpchance");
		$minalpermhp 		= get_module_setting("minalpermhp");
		$permhpallowed		= get_module_setting("permhpallowed");
		$usefavor 			= get_module_setting("usefavor");
		$usefavorbonus 		= get_module_setting("usefavorbonus") / 100;
		$temphptoday 		= get_module_pref("temphptoday");
		$permhptoday 		= get_module_pref("permhptoday");
		$totalpermhp 		= get_module_pref("totalpermhp");
		$deathpowerbonus 	= $session['user']['deathpower'] / 10000;
		set_module_pref("permhptoday", 0);
		if ($al < get_module_setting("minal") && $session['user']['race'] == $race && get_module_setting("candemote") == 1)
		{
			if (is_module_active("raceangel"))
			{
				$session['user']['race'] = "Angel";
				output("`nYou have not been good enough to be an Archangel. You have been demoted to an Angel!`n");
				break;
			}
			else
			{
				$session['user']['race'] = "Human";
				output("`nYou have not been good enough to be an Archangel. You have been demoted to a Human!`n");
				break;
			}
		}
		if ($session['user']['race'] == $race)
		{
			if (e_rand(1,100) <= get_module_setting("charmchance"))
			{
				output("`nYour holy demeanor makes you more charismatic. You gain %s charm point!`n", get_module_setting("charmgain"));
				$session['user']['charm'] += get_module_setting("charmgain");
			}
			if (get_module_setting("hasminion") == 1)
			{
				apply_buff("racialbenefit",array
				(
					"name"				=> "Cherub of Light",
					"minioncount"		=> 1,
					"minbadguydamage"	=> 0,
					"maxbadguydamage"	=> 2 + floor($session['user']['level'] / 2),
					"effectmsg"			=> "`#Your gaurdian cherub engulfs {badguy} in a shroud of holy light`# for `^{damage}`# damage.",
					"effectnodmgmsg"	=> "`#{badguy}`# evades the shroud of light!!",
					"allowinpvp"		=> 0,
					"allowintrain"		=> 0,
					"rounds"			=> -1,
					"schema"			=> "module-racearchangel_city",
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
		$archangel = round($session['user']['maxhitpoints'] * $percentrand);      
        if ($al >= $minaltemphp && $session['user']['race'] == $race)
        {
       	    $session['user']['hitpoints'] += $archangel;
        	if ($archangel < 2)
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
       	    	output("`nBy being good and having %s favor with Ramius, you gain %s hit %s today.`n", $session['user']['deathpower'], $archangel, $hplang);
       	    }
       	    else
       	    {
       	    	output("`nYou gain %s hit %s today for being a good Angel.`n", $archangel, $hplang);
			}
			set_module_pref("temphptoday", $archangel);
       		if ($permhpallowed == 1)
       		{
       			if ($al >= $minalpermhp)
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
    			        output("`nFor being extremely good, you receive %s maximum hit %s!`n", $permhpbonus, $hplang);
						set_module_pref("permhptoday", 1);
						set_module_pref("totalpermhp", $totalpermhp + $permhpbonus);
        				debuglog("received $permhpbonus hit $hplang for being an Archangel.");
	    		    }
				}
			}
        }
       	if ($session['user']['race'] == $race && $al < $minaltemphp)
		{
	    	output("`n`bYou are not good enough to get you Archangelic abilities today! You better start being more good!`b`n");
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
        racearchangel_city_checkcity();
        if ($session['user']['location'] == $city)
        {
            $args['text'] 		= "`\$`c`@$city, the holy village is a spectacle to behold. It's the home of the Angels and is teeming with goodness. Everything is a splendid mix of marble white, silver and gold. An occasional gust of wind blows several errant feathers across the road.`0`n";
            $args['clock'] 		= "`n`2A spectacular clock tower tolls the time of `^%s`2.`c`0`n";
            $args['title']		= "$city";
            $args['sayline']	= "chants";
            $args['talk']		= "`n`^The town inhabitants speak within the square.`0`n";
            $new 				= get_module_setting("newest-$city", "cities");
            if (is_module_active("calendar"))
            {
				$args['calendar'] 				= "`n`2You hear a small cherub whisper in your ear that it is `&%s`2, `&%s %s %s`2.`n";
				$args['schemas']['calendar'] 	= "module-racearchangel_city";
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
                $args['newestid'] 		= "";
            }
            if ($new == $session['user']['acctid'])
            {
                $args['newest']			= "`n`4This place is glorious in many ways, `@$city";
            }
            else
            {
                $args['newest'] 		= " `^%s`2 has ascended to the village.";
            }
			$args['schemas']['newest'] 	= "module-racearchangel_city";
			$args['section']			= "village-$race";
			$args['gatenav']			= "Village Gates";
			$args['schemas']['gatenav'] = "module-racearchangel_city";
        }
        break;
	}
	return $args;
}

function racearchangel_city_checkcity()
{
	global $session;
	$race = "Archangel";
	$city = get_module_setting("villagename");
	if ($session['user']['race'] == $race && is_module_active("cities"))
	{
    	if (get_module_pref("homecity","cities")!=$city)
    	{
    		set_module_pref("homecity",$city,"cities");
        }
    }   
	return true;
}

function racearchangel_city_run()
{
}
?>