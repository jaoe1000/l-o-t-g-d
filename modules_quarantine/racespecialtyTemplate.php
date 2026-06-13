<?php
/*
Details:
 * Translator Ready
 * Addnews Ready
 * Mail Ready
History Log:
 * Version 1.0:
   o Master Template Created
Easy way to customize:
 * Run a replace all in your PHP editor, replacing 'racespecialtytemplate' with your module name (e.g., 'racespecialtypaladin')
 * Search for '[PLACEHOLDER]' to find all flavor text and names that need to be customized.
 * Search for '***CHANGE' to see structural variables that must be defined.
*/

function racespecialtytemplate_getmoduleinfo(){
	$info = array(
		"name"=>"Race/Specialty - [PLACEHOLDER RACE NAME]", // ***CHANGE
		"version"=>"1.0",
		"vertxtloc"=>"",
		"author"=>"[PLACEHOLDER AUTHOR NAME]", // ***CHANGE
		"category"=>"Race Specialities",
		"download"=>"", 
		"settings"=>array(
			"[PLACEHOLDER RACE NAME] - Race Settings,title", // ***CHANGE
			"villagename"=>"Name for the [PLACEHOLDER RACE NAME] city,|[PLACEHOLDER CITY NAME]", // ***CHANGE
			"stableowner"=>"Name of the city stable-owner,|[PLACEHOLDER STABLE OWNER]", // ***CHANGE
			"minedeathchance"=>"Chance for this race to die in the mine,range,0,100,1|20", // ***CHANGE
			"dklimit"=>"Limit the Race to a certain amount of dragon kills?,int|3",
			"(some of the specialities are quite strong- the DK limit is recommended),note",
			"wepchange"=>"Will weapons & armour change names?,bool|1", // ***CHANGE
			"IE- an 'Adze' would become a '[PLACEHOLDER SUBCLASS] Adze',note", 
		),
		// UNCOMMENT THIS BLOCK TO ENFORCE MODULE DEPENDENCIES
        /*
        "requires" => array(
            "alignment" => "Alignment Module, 1.0|",
            // "cities" => "Multiple Cities Module, 1.0|",
        ),
        */
		"prefs" => array(
			"[PLACEHOLDER RACE NAME] - Specialty User Prefs,title", // ***CHANGE
			"skill"=>"Skill points in Specialty Powers,int|0", 
			"uses"=>"Uses of Specialty Powers allowed,int|0", 
			"class"=>"Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|", 
			"subclass"=>"Sublass,enum,Unset,0,[PLACEHOLDER SUBCLASS 1],1,[PLACEHOLDER SUBCLASS 2],2,[PLACEHOLDER SUBCLASS 3],3|0", // ***CHANGE
			"newdayset"=>"Newday intercept at footer...,hidden|0", 
			"pagereturn"=>"Return to what page after forced specialty change?,hidden|",
			"scoundrel"=>"How many times has this user attempted to have the specialty without the race?,viewonly|0", 
		),
	);
	return $info;
}

function racespecialtytemplate_install(){
	module_addhook("chooserace");
	module_addhook("setrace");
	module_addhook("villagetext");
	module_addhook("stabletext");
	module_addhook("travel");
	module_addhook("charstats");
	module_addhook("validlocation");
	module_addhook("moderate");
	module_addhook("changesetting");
	module_addhook("raceminedeath");
	module_addhook("stablelocs");
	module_addhook("choose-specialty");
	module_addhook("set-specialty");
	module_addhook("fightnav-specialties");
	module_addhook("apply-specialties");
	module_addhook("newday");
	module_addhook("header-newday");
	module_addhook("incrementspecialty");
	module_addhook("specialtynames");
	module_addhook("specialtymodules");
	module_addhook("specialtycolor");
	module_addhook("dragonkill");
	module_addhook("newday-intercept");
	debug("Installed '[PLACEHOLDER RACE NAME]' specialty and race module."); // ***CHANGE
	return true;
}

function racespecialtytemplate_uninstall(){
	$spec = "XX"; // ***CHANGE: MUST BE A UNIQUE 2-LETTER CODE
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	
	// Force anyone who was this race to rechoose race
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='[PLACEHOLDER RACE NAME]'"; // ***CHANGE
	db_query($sql);
	if ($session['user']['race'] == '[PLACEHOLDER RACE NAME]')  // ***CHANGE
		$session['user']['race'] = RACE_UNKNOWN;
	
	// Reset the specialty
	$sql = "SELECT acctid FROM ".db_prefix("accounts")." WHERE race='[PLACEHOLDER RACE NAME]' ORDER BY acctid"; // ***CHANGE
	$result = db_query($sql);
	require_once("lib/systemmail.php");
	for ($i=0;$i<db_num_rows($result);$i++){
		$row = db_fetch_assoc($result);
		$mailmessage="[PLACEHOLDER SYSTEM MAIL FOR BANISHED RACE]"; // ***CHANGE
		systemmail($row['acctid'],"To the [PLACEHOLDER RACE NAME]s...",$mailmessage); // ***CHANGE
	}
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
	db_query($sql);
	debug("Uninstalled '[PLACEHOLDER RACE NAME]' specialty and race module."); // ***CHANGE
	return true;
}

function racespecialtytemplate_dohook($hookname,$args){
	global $session,$resline;
	$city = get_module_setting("villagename");
	$race = "[PLACEHOLDER RACE NAME]"; // ***CHANGE
	$spec = "XX"; // ***CHANGE: SAME 2-LETTER CODE AS UNINSTALL
	$name = "[PLACEHOLDER SPECIALTY NAME]"; // ***CHANGE
	$sname = racespecialtytemplate_namer();
	$ccode = "`@"; // ***CHANGE: COLOR CODE
	
	switch($hookname){
		case "header-newday":
			if (get_module_pref("newdayset")==1) {
				set_module_pref("newdayset",2);
				output("`c`b`#You had a $name specialty, but not the race!`nWe'll soon fix that.`n`\$-=-=-=-`b`c`n");
			}
		break;
		case "newday-intercept":
			racespecialtytemplate_change();
			if (get_module_pref("newdayset")==2) {
				set_module_pref("scoundrel",get_module_pref("scoundrel")+1);
				set_module_pref("newdayset",0);
				$p = get_module_pref("pagereturn");
				if ($p=="") {
					$p = "village.php";
				}
				$session['user']['restorepage']=$p;
				set_module_pref("pagereturn","");
				redirect($p,"Forced specialty change."); 
			}
		break;
		case "raceminedeath":
			if ($session['user']['race'] == $race) {
				$args['chance'] = get_module_setting("minedeathchance");
			}
		break;
		case "changesetting":
			if ($args['setting'] == "villagename" && $args['module']=="racespecialtytemplate") {
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
		case "charstats":
			if ($session['user']['race']==$race){
				addcharstat("Vital Info");
				addcharstat("Race", translate_inline($race));
			}
		break;
		case "chooserace":
			if ($session['user']['dragonkills']>=get_module_setting("dklimit")) {
				$can_select = true;

				// UNCOMMENT TO RESTRICT RACE BY ALIGNMENT
                /*
                if (is_module_active("alignment")) {
                    $player_align = get_module_pref("alignment", "alignment");
                    // Example: Restrict this race to Good players only
                    if ($player_align != "good") { 
                        $can_select = false;
                        output("`n`7Your soul lacks the purity required to walk the path of the %s.`n", $race);
                    }
                }
                */

				if ($can_select) {
					$atrans = translate_inline("[PLACEHOLDER CHOOSE RACE LINK TEXT]"); // ***CHANGE
					$rtrans = translate_inline("[PLACEHOLDER CHOOSE RACE FLAVOR TEXT]`n`n"); // ***CHANGE
					addnav("`&$race`0","newday.php?setrace=$race$resline");
					output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans"."</a>$rtrans", $city, true);
					addnav("","newday.php?setrace=$race$resline");
				}
			}
		break;
		case "setrace":
			if ($session['user']['race']==$race){
				output("`&[PLACEHOLDER RACE SELECTED FLAVOR TEXT AND BUFF EXPLANATION]`n"); // ***CHANGE
				if (is_module_active("cities")) {
					if ($session['user']['dragonkills']==0 && $session['user']['age']==0){
						set_module_setting("newest-$city", $session['user']['acctid'],"cities");
					}
					set_module_pref("homecity",$city,"cities");
					if ($session['user']['age'] == 0)
						$session['user']['location']=$city;
				}
			}
		break;
		case "newday":
			if ($session['user']['race']==$race){
				racespecialtytemplate_checkcity();
				$args['turnstoday'] .= ", Race ($race): 1"; // ***CHANGE
				$session['user']['turns']++;
				output("`n`&[PLACEHOLDER NEWDAY RACE BUFF TEXT]`n`0"); // ***CHANGE
				$bonus = getsetting("specialtybonus", 1);
				
				if($session['user']['specialty'] == $spec) {
					if ($bonus == 1) {
						output("`n`2For your specialty in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name,$ccode,$name);
					} else {
						output("`n`2For your specialty in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n",$ccode,$name,$bonus,$ccode,$name);
					}
					
					// UNCOMMENT TO ENABLE ALIGNMENT-BASED NEWDAY BONUSES
                /*
                if (is_module_active("alignment")) {
                    $player_align = get_module_pref("alignment", "alignment");
                    
                    if ($player_align == "good") {
                        output("`n`^Your pure heart grants you an extra use of your power!`n");
                        set_module_pref("uses", get_module_pref("uses") + 1);
                    } elseif ($player_align == "evil") {
                        output("`n`$Your dark nature demands blood. You lose a forest fight.`n");
                        $session['user']['turns']--;
                    }
                }
                */
				}
				$amt = (int)(get_module_pref("skill") / 3);
				if ($session['user']['specialty'] == $spec) $amt = $amt + $bonus;
				set_module_pref("uses", $amt);
			}
		break;
		case "validlocation":
			if (is_module_active("cities"))
				$args[$city]="village-$race";
		break;
		case "moderate":
			if (is_module_active("cities")) {
				tlschema("commentary");
				$args["village-$race"]=sprintf_translate("City of %s", $city); 
				tlschema();
			}
		break;
		case "travel":
			$capital = getsetting("villagename", LOCATION_FIELDS);
			$hotkey = substr($city, 0, 1);
			tlschema("module-cities");
			if ($session['user']['location']==$capital){
				addnav("Safer Travel");
				addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city");
			}elseif ($session['user']['location']!=$city){
				addnav("More Dangerous Travel");
				addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city&d=1");
			}
			if ($session['user']['superuser'] & SU_EDIT_USERS){
				addnav("Superuser");
				addnav(array("%s?Go to %s", $hotkey, $city),"runmodule.php?module=cities&op=travel&city=$city&su=1");
			}
			tlschema();
		break;	
		case "villagetext":
			racespecialtytemplate_checkcity();
			if ($session['user']['location'] == $city){
				// ***LCHANGE (VILLAGE DESCRIPTIONS)
				$args['text']=array("`\$`c`b%s`b`c`n`^[PLACEHOLDER VILLAGE MAIN DESCRIPTION TEXT]`n", $city);
				$args['schemas']['text'] = "module-racespecialtytemplate";
				$args['clock']="`n`7A clock reads `@%s`7.`n";
				$args['schemas']['clock'] = "module-racespecialtytemplate";
				if (is_module_active("calendar")) {
					$args['calendar'] = "`n`\$A calendar reads `&%s`\$, `&%s %s %s`\$.`n";
					$args['schemas']['calendar'] = "module-racespecialtytemplate";
				}
				$args['title']=array("%s", $city);
				$args['schemas']['title'] = "module-racespecialtytemplate";
				$args['sayline']="says";
				$args['schemas']['sayline'] = "module-racespecialtytemplate";
				$args['talk']="`n`&Nearby some residents stand talking:`n";
				$args['schemas']['talk'] = "module-racespecialtytemplate";
				$new = get_module_setting("newest-$city", "cities");
				if ($new != 0) {
					$sql =  "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$new'";
					$result = db_query_cached($sql, "newest-$city");
					$row = db_fetch_assoc($result);
					$args['newestplayer'] = $row['name'];
					$args['newestid']=$new;
				} else {
					$args['newestplayer'] = $new;
					$args['newestid']="";
				}
				if ($new == $session['user']['acctid']) {
					$args['newest']="`n`7[PLACEHOLDER NEWEST PLAYER TEXT (SELF)].";
				} else {
					$args['newest']="`n`7[PLACEHOLDER NEWEST PLAYER TEXT (OTHER)] `&%s`7.";
				}
				$args['schemas']['newest'] = "module-racespecialtytemplate";
				$args['section']="village-$race";
				$args['stablename']="[PLACEHOLDER STABLE NAME]";
				$args['schemas']['stablename'] = "module-racespecialtytemplate";
				$args['gatenav']="[PLACEHOLDER GATE NAV TEXT]";
				$args['schemas']['gatenav'] = "module-racespecialtytemplate";
				$args['fightnav']="[PLACEHOLDER FIGHT NAV TEXT]";
				$args['schemas']['fightnav'] = "module-racespecialtytemplate";
				$args['marketnav']="[PLACEHOLDER MARKET NAV TEXT]";
				$args['schemas']['marketnav'] = "module-racespecialtytemplate";
				$args['tavernnav']="[PLACEHOLDER TAVERN NAV TEXT]";
				$args['schemas']['tavernnav'] = "module-racespecialtytemplate";
				// ***ENDLCHANGE
				unblocknav("stables.php");
			}
		break;
		case "stabletext":
			if ($session['user']['location'] != $city) break;
			// ***LCHANGE (STABLE DESCRIPTIONS)
			$args['title'] = "[PLACEHOLDER STABLE NAME]";
			$args['schemas']['title'] = "module-racespecialtytemplate";
			$args['desc'] = "`\$[PLACEHOLDER STABLE DESCRIPTION FEATURING OWNER: ".get_module_setting("stableowner")."]";
			$args['schemas']['desc'] = "module-racespecialtytemplate";
			$args['lad']="Sir";
			$args['schemas']['lad'] = "module-racespecialtytemplate";
			$args['lass']="Miss";
			$args['schemas']['lass'] = "module-racespecialtytemplate";
			$args['nosuchbeast']="`3\"`7I don't stock that creature.`3\", says ".get_module_setting("stableowner")."`3.";
			$args['schemas']['nosuchbeast'] = "module-racespecialtytemplate";
			$args['finebeast']=array(
				"`3\"`7[PLACEHOLDER MOUNT PURCHASE QUOTE 1]`3\"`n`n",
				"`3\"`7[PLACEHOLDER MOUNT PURCHASE QUOTE 2]`3\"`n`n",
				);
			$args['schemas']['finebeast'] = "module-racespecialtytemplate";
			$args['toolittle']="`%[PLACEHOLDER NOT ENOUGH GOLD QUOTE]";
			$args['schemas']['toolittle'] = "module-racespecialtytemplate";
			$args['replacemount']="`%[PLACEHOLDER REPLACE MOUNT TEXT: YOU TRADE YOUR `^%s FOR A `&%s`%]";
			$args['schemas']['replacemount'] = "module-racespecialtytemplate";
			$args['newmount']="`@[PLACEHOLDER NEW MOUNT TEXT: YOU RECEIVE A `&%s`@]";
			$args['schemas']['newmount'] = "module-racespecialtytemplate";
			$args['nofeed']="`3\"`7I don't stock feed here.`3\"";
			$args['schemas']['nofeed'] = "module-racespecialtytemplate";
			$args['nothungry']="`&%s`6 [PLACEHOLDER MOUNT NOT HUNGRY TEXT]";
			$args['schemas']['nothungry'] = "module-racespecialtytemplate";
			$args['halfhungry']="`&%s`6 [PLACEHOLDER MOUNT HALF HUNGRY TEXT]";
			$args['schemas']['halfhungry'] = "module-racespecialtytemplate";
			$args['hungry']="`6%s`6 [PLACEHOLDER MOUNT FULLY HUNGRY TEXT]";
			$args['schemas']['hungry'] = "module-racespecialtytemplate";
			$args['mountfull']="`n`6\"`^Your %s`^ is full now.`6\"";
			$args['schemas']['mountfull'] = "module-racespecialtytemplate";
			$args['nofeedgold']="`6\"`^Not enough money for food.`6\"";
			$args['schemas']['nofeedgold'] = "module-racespecialtytemplate";
			$args['confirmsale']="`n`n`6[PLACEHOLDER CONFIRM MOUNT SALE TEXT]";
			$args['schemas']['confirmsale'] = "module-racespecialtytemplate";
			$args['mountsold']="`6[PLACEHOLDER MOUNT SOLD TEXT]";
			$args['schemas']['mountsold'] = "module-racespecialtytemplate";
			$args['offer']="`n`n`6[PLACEHOLDER MOUNT SALE OFFER TEXT]";
			$args['schemas']['offer'] = "module-racespecialtytemplate";
			// ***ENDLCHANGE
		break;
		case "stablelocs":
			tlschema("mounts");
			$args[$city]=sprintf_translate("City of %s", $city); 
			tlschema();
		break;
		case "dragonkill":
			set_module_pref("uses", 0);
			set_module_pref("skill", 0);
		break;
		case "choose-specialty":
			if ($session['user']['specialty'] == "" || $session['user']['specialty'] == '0') {
				if ($session['user']['race']==$race) { 
					$can_select = true;

					// UNCOMMENT TO RESTRICT SPECIALTY BY ALIGNMENT
					/*
					if (is_module_active("alignment")) {
						$player_align = get_module_pref("alignment", "alignment");
						// Example: Restrict this specialty to Evil players only
						if ($player_align != "evil") {
							$can_select = false;
							output("`n`7You do not meet the alignment requirements to learn %s.`n", $name);
						}
					}
					*/

					if ($can_select) {
						addnav("$ccode$name`0","runmodule.php?module=racespecialtytemplate&spec=".$spec.$resline);
						$t1 = translate_inline("[PLACEHOLDER CHOOSE SPECIALTY LINK TEXT]"); // ***CHANGE
						$t2 = appoencode(translate_inline("$ccode$name`0"));
						rawoutput("<a href='runmodule.php?module=racespecialtytemplate&spec=".$spec.$resline."'>$t1 ($t2)</a><br>");
						addnav("","runmodule.php?module=racespecialtytemplate&spec=".$spec.$resline);
					} else {
						set_module_pref("class","");
						set_module_pref("subclass","");
					}
				} else {
					set_module_pref("class","");
					set_module_pref("subclass","");
				}
			}
		break;
		case "set-specialty":
			if ($session['user']['specialty'] == $spec) {
				page_header($name);
				$resline = httpget("resline");
				$spec = httpget("spec");
				$mc = httpget("mc");	
				$sc = httpget("sc");
				set_module_pref("class