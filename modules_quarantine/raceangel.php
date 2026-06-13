<?php
function raceangel_getmoduleinfo()
{
    $info = array
    (
        "name" 		=> "Race - Angel",
        "version" 	=> "2.02",
        "author"	=> "RPGSL",
        "category" 	=> "RPGSL",
        "download" 	=> "http://www.rpdragon.com/lotgd/raceangel.zip",
		"vertxtloc" => "http://www.rpdragon.com/",
        "settings" 	=> array
        (
            "Angel Race Settings, title",
			"mindk" 			=> "`b1)`b How many DKs do you need before this race is available?,int|5",
			"cost" 				=> "`b2)`b How many Site Points do you need before this race is available?,int|0",
            "minedeathchance" 	=> "`b3)`b What is the chance this race will die in the mine?,range,0,100,1|67",
			"minal" 			=> "`b4)`b What is the minimum amount of alignment needed to get this race?,int|67",
			"minaltemphp" 		=> "`b5)`b What is the minimum amount of alignment needed to get a new day temporary hit point bonus?,int|67",
			"usefavor"			=> "`b6)`b Should a character's Favor affect the temporary hit point bonus?,bool|1",
			"usefavorbonus"		=> "`b7)`b How much of a Favor bonus in percentage points should be added to temporary hit points? (based on maximum hit points and will not go higher than #8),floatrange,0,100,.25|2.5",
			"maxtemphpbonus" 	=> "`b8)`b What is the maximum temporary hit point bonus percent gained each new day? (maximum hit points times this percentage),floatrange,0,100,.25|12.5",
			"permhpallowed"		=> "`b9)`b Does this race have a chance per new day to receive a permament raise of hit points?,bool|1",
			"minalpermhp" 		=> "`b10)`b What is the minimum amount of alignment needed to receive a chance for a permanent hit point bonus?,int|98",
			"permhpchance" 		=> "`b11)`b What is the chance of gaining a permanent hit point bonus if #10 is met?,range,0,100,1|10",
			"permhpbonus" 		=> "`b12)`b How many permanent hit points are gained if #10 and #11 are met?,int|1",
			"candemote"			=> "`b13)`b Can this race be demoted if they drop below the alignment specified in #4?,bool|1",
        ),
		"prefs"		=> array
		(
			"Angel Race User Preferences,title",
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

function raceangel_install()
{
	if (!is_module_installed("racehuman") && !is_module_installed("racearchangel_city"))
	{
		output("You do not have the Human or the Archangel race installed. This module requires at least one of those modules.");
		return false;
	}
	if (is_module_active("alignment"))
	{
		if (!is_module_installed("raceangel"))
		{
			output("Installing `bRace - Angel`b (raceangel.php) module by Role Play Game Story Lines (www.rpgsl.com)`n`n");
		}
		else
		{
			output("Updating `bRace - Angel`b (raceangel.php) module by Role Play Game Story Lines (www.rpgsl.com)`n`n");
		}
	}
	else
	{
		output("You do not have the Basic Alignment module installed. This module cannot be installed until you install Basic Alignment. Visit http://dragonprime.net/users/Sichae/alignment.zip`n");
		return false;
	}
    module_addhook("chooserace");
    module_addhook("setrace");
    module_addhook("charstats");
	module_addhook("newday");
    module_addhook("raceminedeath");
    return true;
}

function raceangel_uninstall()
{
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location = '$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
	{
		$session['user']['location'] = $vname;
	}
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race = '" . RACE_UNKNOWN . "' WHERE race = 'Angel'";
	db_query($sql);
	if ($session['user']['race'] == 'Angel')
	{
		$session['user']['race'] = RACE_UNKNOWN;
	}
	output("Uninstalling `bRace - Angel`b (raceangel.php) module by Role Play Game Story Lines (www.rpgsl.com)`n");
	return true;
}

function raceangel_dohook($hookname, $args)
{
    global $session, $resline;
	$cost = get_module_setting("cost");
	$al = get_align();
    $race = "Angel";    
	if (is_module_active("racearchangel_city")) 
	{
		$city = get_module_setting("villagename", "racearchangel_city");
	}
	elseif (!is_module_active("racearchangel_city") && is_module_active("racehuman"))
	{
		$city = get_module_setting("villagename", "racehuman");
	}
	else 
	{
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
    switch($hookname)
    {
    case "raceminedeath":
        if ($session['user']['race'] == $race) 
        {
            $args['chance'] = get_module_setting("minedeathchance");
            $args['racesave'] = "Fortunately your Angel skill let you escape unscathed.`n";
        }
        break;
    case "charstats":
        if ($session['user']['race'] == $race)
        {
            addcharstat("Vital Info");
            addcharstat ("Race", translate_inline($race));
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
        	if (is_module_active("racearchangel_city"))
        	{
        		output("As an <a href='newday.php?setrace=Angel$resline'>`^%s`0</a>, you choose to live in %s, a holy village of heavenly spectacle.`n`n", translate_inline($race), $city, true);
        		addnav("Angel", "newday.php?setrace=Angel$resline");
        		addnav("", "newday.php?setrace=Angel$resline");
        		break;
        	}
        	else
        	{
        		output("As an <a href='newday.php?setrace=Angel$resline'>`^%s`0</a>, you choose to mingle in the human city of %s.`n`n", translate_inline($race), $city, true);
        		addnav("Angel", "newday.php?setrace=Angel$resline");
        		addnav("", "newday.php?setrace=Angel$resline");
        		break;
        	}
        }
    case "setrace":
        if ($session['user']['race'] == $race)
        {
            output("`^As an Angel, you feel the powers of light, coursing in your veins.`n`nYou will get your powers when you are Good!");
            if (is_module_active("cities")) 
            {
                if ($session['user']['dragonkills'] == 0 && $session['user']['age'] == 0)
                {
                    set_module_setting("newest-$city", $session['user']['acctid'], "cities");
                }
                set_module_pref("homecity", $city, "cities");
                $session['user']['location'] = $city;
            }
        }
		$session['user']['donationspent'] = $session['user']['donationspent'] + get_module_setting("cost");
        break;
    case "newday":      
   	    raceangel_checkcity();
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
		$angel = round($session['user']['maxhitpoints'] * $percentrand);      
        if ($al < get_module_setting("minal") && $session['user']['race'] == $race && get_module_setting("candemote") == 1)
        {
        	output("`nOh no! You haven't been good enough to remain an Angel!");
        	output("What a pity to see a creature of the light fall from grace and become a mere human.");
			$session['user']['race'] = "Human";
			break;
		}
        if ($al >= $minaltemphp && $session['user']['race'] == $race)
        {
       	    $session['user']['hitpoints'] += $angel;
        	if ($angel < 2)
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
       	    	output("`nBy being good and having %s favor with Ramius, you gain %s hit %s today.`n", $session['user']['deathpower'], $angel, $hplang);
       	    }
       	    else
       	    {
       	    	output("`nYou gain %s hit %s today for being a good Angel.`n", $angel, $hplang);
			}
			set_module_pref("temphptoday", $angel);
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
			        	$hplang=translate_inline($hplang);
    			        output("`nFor being extremely good, you receive %s maximum hit %s!`n", $permhpbonus, $hplang);
						set_module_pref("permhptoday", 1);
						set_module_pref("totalpermhp", $totalpermhp + $permhpbonus);
        				debuglog("received $permhpbonus hit $hplang for being an Angel.");
	    		    }
				}
			}
        }
       	if ($session['user']['race'] == $race && $al < $minaltemphp)
		{
	    	output("`n`bYou are not good enough to get you Angelic abilities today! You better start being more good!`b`n");
		}
    	break;
    }
	return $args;
}

function raceangel_checkcity()
{
    global $session;
    $race = "Angel";
    if (is_module_active("racearchangel_city")) 
    {
		$city = get_module_setting("villagename", "racearchangel_city");
	}
    elseif (!is_module_active("racearchangel_city") && is_module_active("racehuman")) 
    {
		$city = get_module_setting("villagename", "racehuman");
	} 
	else
	{
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	if ($session['user']['race'] == $race && is_module_active("cities"))
	{
		if (get_module_pref("homecity","cities") != $city)
		{
			set_module_pref("homecity",$city,"cities");
		}
	}
    return true;
}

function raceangel_run()
{
}
?>