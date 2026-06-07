<?php
//make pvp's increase on win
function racedrakling_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Drakling",
		"version"=>"1.1",
		"author"=>"`4Thanatos,`2Based on Eric Stevens racehuman",
		"category"=>"Races",
		"download"=>"http://greendragon.co.nr",
		"settings"=>array(
			"drakling Race Settings,title",
			"turnsup"=>"Extra Turns's,int|5",
			"pvpup"=>"Extra PVP's,int|0",
      "buffname"=>"Buff Name,text|`4Dragonic Powers",
      "hpup" =>"Hitpoint Bonous:,int|150",
      "atkmod"=>"Attack Bonous(mult) :,int|2",
      "defmod"=>"Defense Bonous(mult):,int|1.2",
      "badguydmgmod"=>"BadGuyDamageMod(mult):,float|1.2",
      "lifetap"=>"LifeTap:,float|.7",
      "dmgshield"=>"DamageShield:,float|.7", 
      "favor"=>"underworld Favor,int|25",
      "startloc"=>"Starting Village:,location|".getsetting("villagename", LOCATION_FIELDS),
      "minedeathchance"=>"Chance for Draklings to die in the mine,range,0,100,1|0",
      "dk_req"=>"How many DKs do you need before the race is available?,int|2",
      "racename"=>"Race Name :,hidden|drakling",
      "Level Settings,title",
      "levels"=>"Max Amount of Levels?(0 to disable),int|35",
      "levelatkinc"=>"each level increases atk buff by X:,float|.03",
      "leveldefinc"=>"each level increases def buff by X:,float|.02",
      "levelturninc"=>"each level increases turns by X:,int|1",
      "levelpvpinc"=>"each level increases PVP by X:,int|1",
      "levelhpinc"=>"each level increases hp(cur) by X:,int|10",
      "levelltinc"=>"each level increases lifetap by X:,int|0",
      "leveldsinc"=>"each level increases damage shield by X:,int|0",
      "pvp_req"=>"Pvp's Required to level up the race(0 to disable),int|100",
      "cre_req"=>"battle victories Required to level up the race(0 to disable),int|150",
		),
		"prefs"=>array(
		  "level"=>"race lvl:,int|0",
		  "pvp"  =>"Pvp's points,int|0",
		  "cre"  =>"Battle Victory points,int|0",
      "race_on"=>"Can user become race?,bool|1",
    ),
	);
	return $info;
}


function racedrakling_install(){
    module_addhook("chooserace");
    module_addhook("setrace");
    module_addhook("creatureencounter");
    module_addhook("charstats");
    module_addhook("raceminedeath");
	  module_addhook("racenames");
	  module_addhook("newday");
	  module_addhook("pvpwin");
	  module_addhook("battle-victory");
    return true;
}

function racedrakling_uninstall(){
	global $session;
	// Force anyone who was a Drakling to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Drakling '";
	db_query($sql);
	if ($session['user']['race'] == 'Drakling')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racedrakling_dohook($hookname,$args){
	global $session,$resline;
	$module="racedrakling";
	$city = get_module_setting("startloc");
	$race = get_module_setting("racename");
	switch($hookname){
	case "racenames":
		$args[$race] = $race;
		break;
	case "battle-victory":
		if ($session['user']['race']==$race&&
        get_module_setting("levels")>get_module_pref("level")){   
			   $cre=get_module_pref("cre");
			   $cre++;
			   set_module_pref("cre",$cre);
			   if($cre==get_module_setting("cre_req")){
            set_module_pref("cre",0);
            $lvl=get_module_pref("level");
            $lvl++;
            set_module_pref("level",$lvl);
            output(array("`7Your %s `7Level has increased.",$race));
         }
    }	     
  break;
	case "pvpwin":
		if ($session['user']['race']==$race&&
        get_module_setting("levels")>get_module_pref("level")){   
			   $pvp=get_module_pref("pvp");
			   $pvp++;
			   set_module_pref("pvp",$pvp);
			   if($pvp==get_module_setting("pvp_req")){
            set_module_pref("pvp",0);
            $lvl=get_module_pref("level");
            $lvl++;
            set_module_pref("level",$lvl);
            output(array("`7Your %s `7Level has increased.",$race));
         }
    }
    break;
	case "raceminedeath":
		if ($session['user']['race']==$race){
			$args['chance'] = get_module_setting("minedeathchance");
		}
		break;
	case "charstats":
		if ($session['user']['race']==$race){
			addcharstat("Vital Info");
			addcharstat("Race", translate_inline($race));
			addcharstat("Race Level",get_module_pref("level"));
		}
		break;
	case "chooserace":
	  $dk_req=get_module_setting("dk_req")-1;
	  if(get_module_pref("race_on")&&
       $session[user][dragonkills]>$dk_req){
		  output("`0<a href='newday.php?setrace=$race$resline'>In the city of %s</a>, you were infused with the blood of the dragon as such you became a drakling. `n`n", $city, true);
		  addnav("`&Drakling`0","newday.php?setrace=$race$resline");
		  addnav("","newday.php?setrace=$race$resline");
		}
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`&As a Drakling, you possess unusual strength because you are linked with the dragon race.");
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
			racedrakling_checkcity($module);
			racedrakling_applystats($module);
		}
		break;
  }
  	return $args;
}
function racedrakling_applystats($module){
  global $session;
  $lvlatkmod=get_module_pref("level")*get_module_setting("levelatkinc");
  $lvldefmod=get_module_pref("level")*get_module_setting("leveldefinc");
  $lvlturnmod=get_module_pref("level")*get_module_setting("levelturninc");
  $lvlpvpmod=get_module_pref("level")*get_module_setting("levelpvpinc");
  $lvlhpmod=get_module_pref("level")*get_module_setting("levelhpinc");
  $lvlltmod=get_module_pref("level")*get_module_setting("levelltinc");
  $lvldsmod=get_module_pref("level")*get_module_setting("leveldsinc");
  $race['name']        =get_module_setting("racename"    ,$module);
  $race['buffname']    =get_module_setting("buffname"    ,$module);
  $race['turns']       =get_module_setting("turnsup"     ,$module);
  $race['pvp']         =get_module_setting("pvpup"       ,$module);
  $race['atk']         =get_module_setting("atkmod"      ,$module);
  $race['def']         =get_module_setting("defmod"      ,$module);
  $race['hp']          =get_module_setting("hpup"        ,$module);
  $race['badguydmgmod']=get_module_setting("badguydmgmod",$module);
  $race['lifetap']     =get_module_setting("lifetap"     ,$module);
  $race['dmgshield']   =get_module_setting("dmgshield"   ,$module);
  if($race['hp']>1) {$race['hp'] +=$lvlhpmod;}
  if($race['atk']>1){$race['atk']+=$lvlatkmod;}
  if($race['def']>1){$race['def']+=$lvldefmod;}
  $race['turns']+=$lvlturnmod;
  $race['pvp']+=$lvlpvpmod;
  if($race['lifetap']>0){$race['lifetap']+=$lvlltmod;}
  if($race['dmgshield']>0){$race['dmgshield']+=$lvldsmod;}
  output(array("`n`n`7Because you are a %s you gain the following :",$race['name']));  
  if($race['hp']>0){
    $session['user']['hitpoints']+=$race['hp'];
    output(array("`nYou gain %s hitpoints!",$race['hp']));}
  if($race['turns']>0){
    $session['user']['turns']+=$race['turns'];
    output(array("`nYou gain %s turns!",$race['turns']));}
  if($race['pvp']>0){
    $session['user']['playerfights']+=$race['pvp'];
    output(array("`nYou gain %s PvP's!",$race['pvp']));}
  output(array("`n%s `7- race buff!`n",$race['buffname']));
  apply_buff("racialbenefit",array(
				"name"        =>$race['buffname'],
				"atkmod"      =>$race['atk'],
				"defmod"      =>$race['def'],
				"badguydmgmod"=>$race['badguydmgmod'],
				"dmgshield"   =>$race['dmgshield'],
				"lifetap"     =>$race['lifetap'],
				"allowinpvp"  =>1,
				"allowintrain"=>1,
				"rounds"      =>-1,
				"schema"      =>"module-".$module,)
	);
}
function racedrakling_checkcity(){
  global $session;
  $module="racedrakling";
  $race=get_module_setting("racename",$module);
  if (is_module_active($module)) {$city = get_module_setting("startloc", $module);} 
  else {$city = getsetting("villagename", LOCATION_FIELDS);}
	if ($session['user']['race']==$race && is_module_active("cities")){
		//if they're this race and their home city isn't right, set it up.
		if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
			set_module_pref("homecity",$city,"cities");
		}
	}
  return true;
}
?>
