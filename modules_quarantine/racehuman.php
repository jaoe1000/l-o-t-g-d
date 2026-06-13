<?php
function racehuman_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Human",
		"version"=>"1.2",
		"author"=>"`4Thanatos",
		"category"=>"Races",
		"settings"=>array(
			"Human Race Settings,title",
			"turnsup"=>"Extra Turns's,int|5",
			"pvpup"=>"Extra PVP's,int|0",
      "buffname"=>"Buff Name,text|`@Human Will",
      "hpup" =>"Hitpoint Bonous:,int|35",
      "atkmod"=>"Attack Bonous(mult) :,int|1.3",
      "defmod"=>"Defense Bonous(mult):,int|1.1",
      "badguydmgmod"=>"BadGuyDamageMod(mult):,float|1.2",
      "lifetap"=>"LifeTap:,int|0",
      "dmgshield"=>"DamageShield:,int|0", 
      "startloc"=>"Starting Village:,location|".getsetting("villagename", LOCATION_FIELDS),
      "minedeathchance"=>"Chance for Humans to die in the mine,range,0,100,1|0",
      "dk_req"=>"How many DKs do you need before the race is available?,int|2",
      "racename"=>"Race Name :,hidden|Human",
      "Level Settings,title",
      "levels"=>"Max Amount of Levels?(0 to disable),int|10",
      "levelinc"=>"each level increases buffs by X:,float|.03",
      "levelhpinc"=>"each level increases hp(cur) by X:,int|5",
      "levelltinc"=>"each level increases lifetap by X:,float|0",
      "leveldsinc"=>"each level increases damage shield by X:,float|0",
      "cre_req"=>"battle victories Required to level up the race(0 to disable),int|150",      
		),
		"prefs"=>array(
		  "level"=>"race lvl:,int|0",
		  "cre"  =>"Battle Victory points,int|0",
      "race_on"=>"Can user become race?,bool|1",
    ),
	);
	return $info;
}


function racehuman_install(){
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

function racehuman_uninstall(){
	global $session;
	// Force anyone who was a Human to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Human '";
	db_query($sql);
	if ($session['user']['race'] == 'Human')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}

function racehuman_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	// to handle this.
	// Pass it as an arg?
	global $session,$resline;
	$module="racehuman";
	$city = get_module_setting("startloc");
	$race = get_module_setting("racename");
	switch($hookname){
	case "racenames":
		$args[$race] = $race;
		break;
	case "battle-victory":
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
	case "raceminedeath":
		if ($session['user']['race']==$race){
			$args['chance'] = get_module_setting("minedeathchance");
		}
		break;
	case "charstats":
		if ($session['user']['race']==$race){
		  $lvl=get_module_pref("level");
			addcharstat("Vital Info");
			addcharstat("Race", translate_inline($race)."(Level $lvl)");
		}
		break;
	case "chooserace":
	  $dk_req=get_module_setting("dk_req")-1;
	  if(get_module_pref("race_on")&&
       $session[user][dragonkills]>$dk_req){
		output("`0<a href='newday.php?setrace=$race$resline'>From the city of %s</a>, you've led a happy and somewhat wasted life until you decide to depart on your quest. `n`n", $city, true);
		addnav("`&Human`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		}
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`&As a Human, you are a mortal and have the weakness of such but if you harness your abilities and hone your skills you will become a force to be reckoned with.");
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
			racehuman_checkcity($module);
			racehuman_applystats($module);
		}
		break;
  }
  	return $args;
}
function racehuman_applystats($module){
  global $session;
  $lvlmod=get_module_pref("level")*get_module_setting("levelinc");
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
  if($race['atk']>1){$race['atk']+=$lvlmod;}
  if($race['def']>1){$race['def']+=$lvlmod;}
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
function racehuman_checkcity($module){
  global $session;
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
