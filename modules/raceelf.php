<?php
function raceelf_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Elf",
		"version"=>"1.2",
		"author"=>"`4Thanatos,`2Based on Eric Stevens raceelf",
		"category"=>"Races",
		"download"=>"http://greendragon.co.nr",
		"settings"=>array(
			"Elf Race Settings,title",
			"turnsup"=>"Extra Turns's,int|10",
			"pvpup"=>"Extra PVP's,int|0",
      "buffname"=>"Buff Name,text|`@Elvish Awareness`0",
      "hpup" =>"Hitpoint Bonous:,int|40",
      "atkmod"=>"Attack Bonous(mult) :,int|1",
      "defmod"=>"Defense Bonous(mult):,int|1.8",
      "badguydmgmod"=>"BadGuyDamageMod(mult):,float|.7",
      "lifetap"=>"LifeTap:,int|5",
      "dmgshield"=>"DamageShield:,int|5", 
      "startloc"=>"Starting Village:,location|".getsetting("villagename", LOCATION_FIELDS),
      "minedeathchance"=>"Chance for Elfs to die in the mine,range,0,100,1|0",
      "dk_req"=>"How many DKs do you need before the race is available?,int|0",
      "racename"=>"Race Name :,hidden|Elf",
      "Level Settings,title",
      "levels"=>"Max Amount of Levels?(0 to disable),int|70",
      "levelatkinc"=>"each level increases atk buff by X:,float|.01",
      "leveldefinc"=>"each level increases def buff by X:,float|.03",
      "levelturninc"=>"each level increases turns by X:,int|1",
      "levelpvpinc"=>"each level increases PVP by X:,int|0",
      "levelhpinc"=>"each level increases hp(cur) by X:,int|15",
      "levelltinc"=>"each level increases lifetap by X:,float|1",
      "leveldsinc"=>"each level increases damage shield by X:,float|1",
      "cre_req"=>"battle victories Required to level up the race(0 to disable),int|220",
		),
		"prefs"=>array(
		  "level"=>"race lvl:,int|0",
		  "cre"  =>"Battle Victory points,int|0",
      "race_on"=>"Can user become race?,bool|1",
    ),
	);
	return $info;
}
function raceelf_install(){
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
function raceelf_uninstall(){
	global $session;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Elf '";
	db_query($sql);
	if ($session['user']['race'] == 'Elf')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}
function raceelf_dohook($hookname,$args){
	global $session,$resline;
	$module="raceelf";
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
		output("<a href='newday.php?setrace=$race$resline'>High among the trees</a> of the %s forest, in frail looking elaborate `^Elvish`0 structures that look as though they might collapse under the slightest strain, yet have existed for centuries.`n`n", $city, true);
		addnav("`^Elf`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		}
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`^As an elf, you are keenly aware of your surroundings at all times; very little ever catches you by surprise.`n");
			output("You gain extra defense!");
			if (is_module_active("cities")) {
				if ($session['user']['dragonkills']==0 &&
						$session['user']['age']==0){
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
			raceelf_checkcity($module);
			raceelf_applystats($module);
		}
		break;
  }
  	return $args;
}
function raceelf_applystats($module){
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
function raceelf_checkcity(){
  global $session;
  $module="raceelf";
  $race=get_module_setting("racename",$module);
  if (is_module_active($module)) {$city = get_module_setting("startloc", $module);} 
  else {$city = getsetting("villagename", LOCATION_FIELDS);}
	if ($session['user']['race']==$race && is_module_active("cities")){
		if (get_module_pref("homecity","cities")!=$city){
			set_module_pref("homecity",$city,"cities");
		}
	}
  return true;
}
?>
