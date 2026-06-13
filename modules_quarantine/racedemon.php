<?php

function racedemon_getmoduleinfo()
{
    $info = array
    (
        "name" 		=> "Race - Demon",
        "version" 	=> "2.03",
        "author"	=> "RPGSL",
        "category" 	=> "RPGSL",
        "download" 	=> "http://www.rpdragon.com/lotgd/racedemon.zip",
		"vertxtloc" => "http://www.rpdragon.com/",
        "settings" 	=> array
        (
            "Demon Race Settings, title",
			"mindk" 			=> "`b1)`b How many DKs do you need before this race is available?,int|5",
			"cost" 				=> "`b2)`b How many Site Points do you need before this race is available?,int|0",
            "minedeathchance" 	=> "`b3)`b What is the chance this race will die in the mine?,range,0,100,1|67",
			"maxal" 			=> "`b4)`b What is the maximum amount of alignment allowed to obtain this race?,int|33",
			"maxaltemphp" 		=> "`b5)`b What is the maximum amount of alignment allowed to get a new day temporary hit point bonus?,int|33",
			"usefavor"			=> "`b6)`b Should a character's Favor affect the temporary hit point bonus?,bool|1",
			"usefavorbonus"		=> "`b7)`b How much of a Favor bonus in percentage points should be added to temporary hit points? (based on maximum hit points and will not go higher than #8),floatrange,0,100,.25|2.5",
			"maxtemphpbonus" 	=> "`b8)`b What is the maximum temporary hit point bonus percent gained each new day? (maximum hit points times this percentage),floatrange,0,100,.25|12.5",
			"permhpallowed"		=> "`b9)`b Does this race have a chance per new day to receive a permament raise of hit points?,bool|1",
			"maxalpermhp" 		=> "`b10)`b What is the maximum amount of alignment allowed to receive a chance for a permanent hit point bonus?,int|2",
			"permhpchance" 		=> "`b11)`b What is the chance of gaining a permanent hit point bonus if #10 is met?,range,0,100,1|10",
			"permhpbonus" 		=> "`b12)`b How many permanent hit points are gained if #10 and #11 are met?,int|1",
			"candemote"			=> "`b13)`b Can this race be demoted if they go above the alignment specified in #4?,bool|1",
        ),
		"prefs"		=> array
		(
			"Demon Race User Preferences, title",
			"temphptoday"		=> "How many temporary hit points did this character receive today?, hidden | 0",
			"permhptoday"		=> "Did this character receive a permanent hit point bonus today? Changing this has no effect on gameplay - it is only a reference., bool | 0",
			"totalpermhp"		=> "How many total permanent hitpoints has this character received?, hidden | 0",
		),
		"requires" 	=> array
		(
			"alignment" 		=> "1.6 | WebPixie `#Lonny Luberts `^and Chris Vorndran, http://dragonprime.net/users/Sichae/alignment.zip",
			"racevamp"			=> "1.1 | Chris Vorndran, http://dragonprime.net/users/Sichae/racevamp.zip",
		),
	);
    return $info;
}

function racedemon_install()
{
	if (!is_module_installed("racevamp") && !is_module_installed("racearchdemon_city"))
	{
		output("You do not have the Vampire or the Archdemon race installed. This module requires at least one of those modules.");
		return false;
	}
	if (is_module_active("alignment"))
	{
		if (!is_module_installed("racedemon"))
		{
			output("Installing `bRace - Demon`b (racedemon.php) module by Role Play Game Story Lines (www.rpgsl.com)`n`n");
		}
		else
		{
			output("Updating `bRace - Demon`b (racedemon.php) module by Role Play Game Story Lines (www.rpgsl.com)`n`n");
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

function racedemon_uninstall()
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
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race = '" . RACE_UNKNOWN . "' WHERE race = 'Demon'";
	db_query($sql);
	if ($session['user']['race'] == 'Demon')
	{
		$session['user']['race'] = RACE_UNKNOWN;
	}
	output("Uninstalling `bRace - Demon`b (racedemon.php) module by Role Play Game Story Lines (www.rpgsl.com)`n");
	return true;
}

function racedemon_dohook($hookname, $args)
{
    global $session, $resline;
	$al = get_align();
	$race = "Demon";
	if (is_module_active("racearchdemon_city")) 
	{
		$city = get_module_setting("villagename", "racearchdemon_city");
	}
	elseif (!is_module_active("racearchdemon_city") && is_module_active("racevamp"))
	{
		$city = get_module_setting("villagename", "racevamp");
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
            $args['racesave'] = "Fortunately your Demon skill let you escape unscathed.`n";
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
		$maxal = get_module_setting("maxal");
		if ($al <= $maxal)
		{
			if (is_module_active("racearchdemon_city"))
			{
        		output("`0As a <a href='newday.php?setrace=Demon$resline'>`$%s`0</a> you choose to live in %s, a harsh, sulphuric village of despair.`n`n", translate_inline($race), $city, true);
        		addnav("Demon", "newday.php?setrace=Demon$resline");
        		addnav("", "newday.php?setrace=Demon$resline");
        		break;
        	}
        	else
        	{
        		output("As an <a href='newday.php?setrace=Demon$resline'>`^%s`0</a>, you choose to mingle in the vampire city of %s.`n`n", translate_inline($race), $city, true);
        		addnav("Demon", "newday.php?setrace=Demon$resline");
        		addnav("", "newday.php?setrace=Demon$resline");
        		break;
        	}
        }
    case "setrace":
        if ($session['user']['race'] == $race)
        {
            output("`^As an Demon, you feel the powers of darkness, coursing in your veins.`n`nYou will get your powers when you are Evil!");
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
   	    racedemon_checkcity();
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
		$maxal 				= get_module_setting("maxal");
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
		$demon = round($session['user']['maxhitpoints'] * $percentrand);      
        if ($al > get_module_setting("maxal") && $session['user']['race'] == $race && get_module_setting("candemote") == 1)
        {
        	output("`nOh no! You haven't been evil enough to remain a demon!");
        	output("What a pity to see a creature of the darkness lose their demonic powers. You have been demoted to a vampire.");
			$session['user']['race'] = "Vampire";
			break;
		}        
        if ($al <= $maxaltemphp && $session['user']['race'] == $race)
        {
       	    $session['user']['hitpoints'] += $demon;
        	if ($demon < 2)
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
       	    	output("`nBy being evil and having %s favor with Ramius, you gain %s hit %s today.`n", $session['user']['deathpower'], $demon, $hplang);
       	    }
       	    else
       	    {
       	    	output("`nYou gain %s hit %s today for being an evil Demon.`n", $demon, $hplang);
			}
			set_module_pref("temphptoday", $demon);
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
			        	$hplang=translate_inline($hplang);
    			        output("`nFor being extremely evil, you receive %s maximum hit %s!`n", $permhpbonus, $hplang);
						set_module_pref("permhptoday", 1);
						set_module_pref("totalpermhp", $totalpermhp + $permhpbonus);
        				debuglog("received $permhpbonus hit $hplang for being a Demon.");
	    		    }
				}
			}
        }
       	if ($session['user']['race'] == $race && $al > $maxaltemphp)
		{
	    	output("`n`bYou are not evil enough to get you Demonic abilities today! You better start being more evil!`b`n");
		}
    	break;
    }
	return $args;
}

function racedemon_checkcity()
{
    global $session;
    $race = "Demon";
	$cost = get_module_setting("cost");
    if (is_module_active("racearchdemon_city")) 
    {
		$city = get_module_setting("villagename", "racearchdemon_city");
	}
    elseif (!is_module_active("racearchdemon_city") && is_module_active("racevamp")) 
    {
		$city = get_module_setting("villagename", "racevamp");
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

function racedemon_run()
{
}

?>