<?php
function racetroll_getmoduleinfo(){
	$info = array(
		"name"=>"Race - Troll",
		"version"=>"1.2",
		"author"=>"`4Thanatos,`2Based on Eric Stevens's racetroll",
		"category"=>"Races",
		"download"=>"http://greendragon.co.nr",
		"settings"=>array(
			"Troll Race Settings,title",
			"turnsup"=>"Extra Turns's,int|0",
			"pvpup"=>"Extra PVP's,int|0",
      "buffname"=>"Buff Name,text|`@Trollish Strength`0",
      "hpup" =>"Hitpoint Bonous:,int|10",
      "atkmod"=>"Attack Bonous(mult) :,int|2.4",
      "defmod"=>"Defense Bonous(mult):,int|.7",
      "badguydmgmod"=>"BadGuyDamageMod(mult):,float|1.2",
      "goldmod"=>"GoldMod(mult):,float|.5",
      "lifetap"=>"LifeTap:,int|2",
      "dmgshield"=>"DamageShield:,int|0", 
      "startloc"=>"Starting Village:,location|".getsetting("villagename", LOCATION_FIELDS),
      "minedeathchance"=>"Chance for Trolls to die in the mine,range,0,100,1|0",
      "dk_req"=>"How many DKs do you need before the race is available?,int|0",
      "racename"=>"Race Name :,hidden|Troll",
      "Level Settings,title",
      "levels"=>"Max Amount of Levels?(0 to disable),int|40",
      "levelatkinc"=>"each level increases atk buff by X:,float|.03",
      "leveldefinc"=>"each level increases def buff by X:,float|0",
      "levelturninc"=>"each level increases turns by X:,int|1",
      "levelpvpinc"=>"each level increases PVP by X:,int|0",
      "levelhpinc"=>"each level increases hp(cur) by X:,int|50",
      "levelltinc"=>"each level increases lifetap by X:,float|0",
      "leveldsinc"=>"each level increases damage shield by X:,float|0",
      "goldmodlvl"=>"Gold Mod increase by X:,float|0",
      "cre_req"=>"battle victories Required to level up the race(0 to disable),int|140",
		),
		"prefs"=>array(
		  "level"=>"race lvl:,int|0",
		  "cre"  =>"Battle Victory points,int|0",
      "race_on"=>"Can user become race?,bool|1",
    ),
	);
	return $info;
}
function racetroll_install(){
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
function racetroll_uninstall(){
	global $session;
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Troll '";
	db_query($sql);
	if ($session['user']['race'] == 'Troll')
		$session['user']['race'] = RACE_UNKNOWN;
	return true;
}
function racetroll_dohook($hookname,$args){
	global $session,$resline;
	$module="racetroll";
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
	case "creatureencounter":
		if ($session['user']['race']==$race){
			//get those folks who haven't manually chosen a race
			racetroll_checkcity();
			$goldmodlvl=get_module_pref("level")*get_module_setting("goldmodlvl");
			$goldmod=get_module_setting("goldmod")+$goldmodlvl;
			$args['creaturegold']=round($args['creaturegold']*$goldmod,0);
		}
		break;
	case "chooserace":
	  $dk_req=get_module_setting("dk_req")-1;
	  if(get_module_pref("race_on")&&
       $session[user][dragonkills]>$dk_req){
		output("<a href='newday.php?setrace=$race$resline'>In the swamps of %s</a>`2 as a `@Troll`2, fending for yourself from the very moment you crept out of your leathery egg, slaying your yet unhatched siblings, and feasting on their bones.`n`n",$city, true);
		addnav("`@Troll`0","newday.php?setrace=$race$resline");
		addnav("","newday.php?setrace=$race$resline");
		}
		break;
	case "setrace":
		if ($session['user']['race']==$race){
			output("`@As a troll, and having always fended for yourself, the ways of battle are not foreign to you.`n");
			output("`^You gain extra attack!");
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
			racetroll_checkcity($module);
			racetroll_applystats($module);
		}
		break;
  }
  	return $args;
}
function racetroll_applystats($module){
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
function racetroll_checkcity(){
  global $session;
  $module="racetroll";
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
