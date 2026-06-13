<?php

function raceelemental_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Elemental",
		"version"=>"0.9.2",
		"author"=>"shadowblack",
		"category"=>"Races",
		"download"=>"http://dragonprime.net/users/shadowblack/raceelemental.zip",
		"vertxtloc"=>"http://dragonprime.net/users/shadowblack/",
    "settings"=>array(
    "Elemental Race Settings,title",
    "mindk"=>"How many DKs do you need before the race is available?,int|5",
		"villagename"=>"Name for the elemental village|Elemenia",
		"minedeathchance"=>"Chance for Elementals to die in the mine,range,0,100,1|0",
    "PvP Settings for racial buffs,title",
    "water"=>"Does `!Water Healing`0 work in PvP?,bool|0",
    "air"=>"Do `&Wind Blades`0 work in PvP?,bool|0",
    "earth"=>"Does `qStone Skin`0 work in PvP?,bool|0",
    "fire"=>"Does `QFlame Shield`0 work in PvP?,bool|0",
    ),
	);
	return $info;
}

function raceelemental_install(){
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
	module_addhook("training-victory");
	return true;
}

function raceelemental_uninstall(){
  global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Elemental'";
	db_query($sql);
	if ($session['user']['race'] == 'Elemental')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function raceelemental_dohook($hookname,$args){
	global $session,$resline;
	$city = get_module_setting("villagename");
	$race = "Elemental";
	$water = get_module_setting("water");
	$air = get_module_setting("air");
	$earth = get_module_setting("earth");
	$fire = get_module_setting("fire");

	Switch($hookname){
	Case "racenames":

  $args[$race] = $race;
	break;

	Case "raceminedeath":

  if ($session['user']['race'] == $race) {
		$args['chance'] = get_module_setting("minedeathchance");
		$args['racesave'] = "Your connection with the elements allows you to sense the collapse long before it occures, and you get out unharmed.`n";
		}
	break;

	Case "changesetting":

	if ($args['setting'] == "villagename" && $args['module']=="raceelemental"){
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

  Case "chooserace":

  if ($session['user']['dragonkills'] < get_module_setting("mindk"))
	break;
	output("`0<a href='newday.php?setrace=$race$resline'>In the city %s</a>, `Qthe city of elemental creatures, where you try to gain control over your elemental power so that you can use it to defeat the `@Green Dragon`0.`n`n",$city,true);
	addnav("`QElemental`0","newday.php?setrace=$race$resline");
	addnav("","newday.php?setrace=$race$resline");
  break;

  Case "setrace":

  if ($session['user']['race']==$race){
	output("`QAs an Elemental you have power over the elements, however you can't control it. You receive a random buff every day!");
	if (is_module_active("cities")) {
	if ($session['user']['dragonkills']==0 &&	$session['user']['age']==0){
		set_module_setting("newest-$city",
		$session['user']['acctid'],"cities");
		}
		set_module_pref("homecity",$city,"cities");
		if ($session['user']['age'] == 0)
		$session['user']['location']=$city;
		}
	}
	break;

	Case "charstats":

	if ($session['user']['race']==$race){
	addcharstat("Vital Info");
	addcharstat("Race", translate_inline($race));
	}
  break;

  Case "validforestloc":
	Case "validlocation":

	if (is_module_active("cities"))
		$args[$city]="village-$race";
	break;

  Case "moderate":

	if (is_module_active("cities")){
		tlschema("commentary");
		$args["village-$race"]=sprintf_translate("City of %s",$city);
		tlschema();
	}
	break;

  Case "newday":

	if ($session['user']['race']==$race){
	raceelemental_checkcity();
  $racebuff=e_rand(1,4);
  Switch($racebuff){
  Case 1:

  $racialbenefit=array(
  "name"=>"`QFlame Shield`0",
  "rounds"=>-1,
  "allowinpvp"=>$fire,
  "allowintrain"=>0,
  "damageshield"=>0.5,
  "effectmsg"=>"`q{badguy}`Q hits you and gets burned for `\${damage}`Q damage",
  "effectnodmgmsg"=>"`q{badguy}`Q fails to hurt you, and so avoids a nasty burn",
  "schema"=>"module-raceelemental",
  );
  output("`n`QWith the dawn of a new day you are surrounded by flames!");
  break;

  Case 2:

  $racialbenefit=array(
  "name"=>"`!Water Healing`0",
  "rounds"=>-1,
  "allowinpvp"=>$water,
  "allowintrain"=>0,
  "regen"=>"<level>",
  "effectmsg"=>"`!You are healed for `Q{damage}`! health",
  "effectnodmgmsg"=>"`!You have no wounds to heal",
  "schema"=>"module-raceelemental",
  );
  output("`n`!With the dawn of a new day the blessing of Water is bestowed upon you!");
  break;

  Case 3:

  $racialbenefit=array(
  "name"=>"`qStone Skin`0",
  "rounds"=>-1,
  "allowinpvp"=>$stone,
  "allowintrain"=>0,
  "defmod"=>1.2,
  "badguydmgmod"=>0.8,
  "roundmsg"=>"`qYour rock-hard skin protects you and reduces the effectiveness of `Q{badguy}`q's attacks.",
  "schema"=>"module-raceelemental",
  );
  output("`n`qWith the dawn of a new day your skin becomes hard as a rock!");
  break;

  Case 4:

  $racialbenefit=array(
  "name"=>"`&Wind Blades`0",
  "rounds"=>-1,
  "allowinpvp"=>$air,
  "allowintrain"=>0,
  "minioncount"=>"(1+round(<level>/5))",
  "minbadguydamage"=>0,
  "maxbadguydamage"=>round($session['user']['dragonkills']/10)+5,
  "effectmsg"=>"`&A blade made of air strikes `Q{badguy}`& and causes `\${damage}`& damage",
  "effectnodmgmsg"=>"`Q{badguy}`& manages to avoid getting hit",
  "schema"=>"module-raceelemental",
  );
  output("`n`&With the dawn of a new day Wind Blades start spinning around you, striking at any foe that gets close enough!");
  break;
  }
  apply_buff("racialbenefit",$racialbenefit);
	}
  break;

  Case "travel":

	$capital = getsetting("villagename", LOCATION_FIELDS);
	$hotkey = substr($city, 0, 1);
	tlschema("module-cities");
	if ($session['user']['location']==$capital){
		addnav("Safer Travel");
		addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city");
	}elseif($session['user']['location']!=$city){
		addnav("More Dangerous Travel");
		addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city&d=1");
	}
	if ($session['user']['superuser'] & SU_EDIT_USERS){
		addnav("Superuser");
		addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city&su=1");
	}                           
	tlschema();
	break;	

	Case "villagetext":

	raceelemental_checkcity();
	if ($session['user']['location'] == $city){
  		$args['text']=array("`#`c`b%s, Home of the Elementals`b`c`n`3You are standing in the heart of %s. Any way you look you see stone buildings that look like new even though some are centuries old. It is as if time has stopped here, preventing the stones from aging. You can see several small rivers with crystal-clear water that flow through the city. Dozens of small fountains can be seen at various locations through the city. A warm wind is blowing softly through the city streets and small balls of flame float around in the air illuminating the city day and night. It is a truly magnificent sight like nothing else you have ever seen. `n", $city, $city);
			$args['schemas']['text'] = "module-raceelemental";
			$args['clock']="`n`3The flames floating around show you the current time: `#%s`3.`n";
			$args['schemas']['clock'] = "module-raceelemental";
			if (is_module_active("calendar")){
				$args['calendar'] = "`n`3The winds softly whisper that the date is `#%s`3, `#%s %s %s`3.`n";
				$args['schemas']['calendar'] = "module-raceelemental";
			}
			$args['title']=array("%s, City of the Elementals", $city);
			$args['schemas']['title'] = "module-raceelemental";
			$args['sayline']="says";
			$args['schemas']['sayline'] = "module-raceelemental";
			$args['talk']="`n`&Nearby some villagers talk:`n";
			$args['schemas']['talk'] = "module-raceelemental";
			$new = get_module_setting("newest-$city", "cities");
			if ($new != 0){
				$sql =  "SELECT name FROM " . db_prefix("accounts") .
					" WHERE acctid='$new'";
				$result = db_query_cached($sql, "newest-$city");
				$row = db_fetch_assoc($result);
				$args['newestplayer'] = $row['name'];
				$args['newestid']=$new;
			}else{
				$args['newestplayer'] = $new;
				$args['newestid']="";
			}
	if ($new == $session['user']['acctid']){
		$args['newest']="`n`7As you wander your new home, you feel your jaw dropping at the wonders around you.";
	}else{
    $args['newest']="`n`7Wandering the village, jaw agape, is `&%s`7.";
	}
			$args['schemas']['newest'] = "module-raceelemental";
			$args['gatenav']="Elemental Gates";
			$args['schemas']['gatenav'] = "module-raceelemental";
			$args['fightnav']="Eternal Flame Grounds";
			$args['schemas']['fightnav'] = "module-raceelemental";
      $args['marketnav']="Earth Storages";
      $args['schemas']['marketnav'] = "module-raceelemental";
      $args['tavernnav']="River of Relaxation";
			$args['schemas']['tavernnav'] = "module-raceelemental";
			$args['infonav']="The Whispering Winds";
			$args['schemas']['infonav'] = "module-raceelemental";
			$args['section']="village-$race";
		}
	break;
  }
	return $args;
}

function raceelemental_checkcity(){
	global $session;
	$race="Elemental";
	$city=get_module_setting("villagename");

	if ($session['user']['race']==$race && is_module_active("cities")){
		if (get_module_pref("homecity","cities")!=$city){
			set_module_pref("homecity",$city,"cities");
		}
	}
	return true;
}

function raceelemental_run(){
}

?>
