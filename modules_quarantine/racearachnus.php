<?php

//Code snippets from Kendaer

function racearachnus_getmoduleinfo()
{
	$info = array
	(
		"name"			=> "Race - Arachnus",
		"version"		=> "1.01",
		"author"		=> "RPGSL",
		"category"		=> "RPGSL",
		"download"		=> "http://www.rpdragon.com/lotgd/racearachnus.zip",
		"vertxtloc" 	=> "http://www.rpdragon.com/",
		"description"	=> "A powerful race that loses 1/2 charm when race is chosen and some gold in forest fights.",
		"settings"		=> array
		(
			"Arachnus Race Settings, title",
			"minedeathchance"		=> "Percent chance for Arachnai to die in the mine,range,0,100,1|40",
			"goldloss"				=> "How much less gold (in percent) do the Arachnai find?,range,0,100,1|30",
			"mindk"					=> "How many DKs do you need before the race is available?,int|25",
		),
		"requires" 		=> array
		(
			"racespecialtyreptile"	=> "1.2 | `@CortalUX, http://dragonprime.net/users/CortalUX/racespecialtyreptile.zip",
		),
	);
	return $info;
}

function racearachnus_install()
{
	if (!is_module_installed("racearachnus"))
	{
		output("Installing `bRace- Arachnus`b (racearachnus.php) module by Role Play Game Story Lines (www.rpgsl.com)`n`n");
	}
	else
	{
		output("Updating `bRace- Arachnus`b (racearachnus.php) module by Role Play Game Story Lines (www.rpgsl.com)`n`n");
	}
	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("newday");
	module_addhook("charstats");
	module_addhook("raceminedeath");
	module_addhook("creatureencounter");
	module_addhook("pvpadjust");
	module_addhook("racenames");
	return true;
}

function racearachnus_uninstall()
{
	global $session;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Arachnus'";
	db_query($sql);
	if ($session['user']['race'] == 'Arachnus')
	{
		$session['user']['race'] = RACE_UNKNOWN;
	}
	output("Uninstalling `bRace- Arachnus`b (racearachnus.php) module by Role Play Game Story Lines (www.rpgsl.com)`n");
	return true;
}

function racearachnus_dohook($hookname, $args)
{
	global $session, $resline;
	if (is_module_active("racespecialtyreptile"))
	{
		$city = get_module_setting("villagename", "racespecialtyreptile");
	}
	else
	{
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	$race = "Arachnus";
	switch($hookname)
	{
		case "racenames":
			$args[$race] = $race;
			break;
		case "pvpadjust":
			if ($args['race'] == $race)
			{
				$args['creaturedefense'] += (1 + floor($args['creaturelevel'] / 4));
				$args['creaturehealth'] -= round($args['creaturehealth'] * .05, 0);
			}
			break;
		case "raceminedeath":
			if ($session['user']['race'] == $race)
			{
				$args['chance'] 	= get_module_setting("minedeathchance");
				$args['racesave'] 	= "Fortunately your Arachnai skill once again lets you escape.`n";
				$args['schema']		= "module-racearachnus";
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
			if ($session['user']['dragonkills'] < get_module_setting("mindk"))
			{
				break;
			}
			output("Ambush, predatorial instincts and poison to match, these horrid creatures, the `%%s`7 lose 1/2 charm, but kick some serious thorax! They also lose gold when battling creatures as they don't have much interest in such things.`n", translate_inline($race));
			addnav("Arachnus`0", "newday.php?setrace=$race$resline");
			addnav("", "newday.php?setrace=$race$resline");
			break;
		case "setrace":
			if ($session['user']['race'] == $race)
			{
				output("`&As an Arachnus, you attack quickly with predatorial instincts.`n");
				output("You gain extra attack!`n");
				output("`nYou have a harder skin than most, nearly an exoskeleton under flesh.`n");
				output("You gain extra defense!");
				output("`nBeing a predator, you gain two extra PvP fights!!`n");
				output("`nYou pay the price in charm, you lose half of your charm!`n");
				output("`nYou aren't as interested in humanly concerns, thus you gain less gold than most.");
				$session['user']['charm'] *= .5;
				if (is_module_active("cities"))
				{
					if ($session['user']['dragonkills'] == 0 && $session['user']['age'] == 0)
					{
						set_module_setting("newest-$city", $session['user']['acctid'], "cities");
					}
					set_module_pref("homecity", $city, "cities");
					if ($session['user']['age'] == 0)
					$session['user']['location'] = $city;
				}
			}
			break;
		case "creatureencounter":
			if ($session['user']['race'] == $race)
			{
				racearachnus_checkcity();
				$loss = (100 - get_module_setting("goldloss")) / 100;
				$args['creaturegold'] = round($args['creaturegold'] * $loss, 0);
			}
			break;
		case "newday":
			if ($session['user']['race'] == $race)
			{
				racearachnus_checkcity();
				apply_buff("racialbenefit",array
				(
					"name"			=> "Arachnai Venom",
					"atkmod"		=> "(<attack>?(1 + ((2.75 + floor(<level> / 4)) / <attack>)):0)",
					"defmod"		=> "(<defense>?(1 + ((1.75 + floor(<level> / 4)) /<defense>)):0)",
					"badguydmgmod"	=> 1.25,
					"allowinpvp"	=> 1,
					"allowintrain"	=> 1,
					"rounds"		=> -1,
					"schema"		=> "module-racearachnus",
					)
				);
				$session['user']['playerfights'] += 2;
			}
			break;
		}
	return $args;
}

function racearachnus_checkcity()
{
	global $session;
	$race = "Arachnus";
	if (is_module_active("racespecialtyreptile"))
	{
		$city = get_module_setting("villagename", "racespecialtyreptile");
	}
	else
	{
		$city = getsetting("villagename", LOCATION_FIELDS);
	}
	
	if ($session['user']['race'] == $race && is_module_active("cities"))
	{
		if (get_module_pref("homecity", "cities") != $city)
		{
			set_module_pref("homecity", $city, "cities");
		}
	}	
	return true;
}

function racearachnus_run()
{
}

?>