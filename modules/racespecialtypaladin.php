<?php
/*
Details:
 * Translator Ready
 * Addnews Ready
 * Mail Ready
History Log:
 * Version 1.0:
   o Master Template Merged Build: Paladin
*/

function racespecialtypaladin_getmoduleinfo(){
	$info = array(
		"name"=>"Race/Specialty - Paladin",
		"version"=>"1.0",
		"vertxtloc"=>"",
		"author"=>"Eternal & Enderandrew (Merged by Admin)", 
		"category"=>"Race Specialities",
		"download"=>"", 
		"settings"=>array(
			"Paladin - Settings,title", 
			"villagename"=>"Name for the Paladin city,|LightHaven", 
			"stableowner"=>"Name of the city stable-owner,|The Holy Hostler", 
			"minedeathchance"=>"Chance for this race to die in the mine,range,0,100,1|25", 
			"mindk"=>"How many DKs do you need before the module is available?,int|5",
			"cost"=>"How many donation points do you need before the specialty is available?,int|5",
			"gain"=>"How much Alignment is gained when specialty is chosen,int|20",
			"wepchange"=>"Will weapons & armour change names?,bool|1", 
			"IE- an 'Adze' would become a 'Divine Adze',note", 
		),
        // ENFORCED MODULE DEPENDENCIES
        "requires" => array(
            "alignment" => "Alignment Module, 1.0|",
        ),
		"prefs" => array(
			"Paladin - Specialty User Prefs,title", 
			"skill"=>"Skill points in Specialty Powers,int|0", 
			"uses"=>"Uses of Specialty Powers allowed,int|0", 
			"class"=>"Main Class,enum,Unset,,Divine,magic|", 
			"subclass"=>"Sublass,enum,Unset,0,Paladin,1|0", 
			"newdayset"=>"Newday intercept at footer...,hidden|0", 
			"pagereturn"=>"Return to what page after forced specialty change?,hidden|",
			"scoundrel"=>"How many times has this user attempted to have the specialty without the race?,viewonly|0", 
		),
	);
	return $info;
}

function racespecialtypaladin_install(){
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
	module_addhook("pointsdesc");
	
	// Unique Paladin Hooks
	module_addhook("castlelib");
	module_addhook("castlelibbook");
	module_addhook("pvpadjust");
	
	debug("Installed 'Paladin' specialty and race module."); 
	return true;
}

function racespecialtypaladin_uninstall(){
	$spec = "PL"; 
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Paladin'"; 
	db_query($sql);
	if ($session['user']['race'] == 'Paladin')  
		$session['user']['race'] = RACE_UNKNOWN;
	
	$sql = "SELECT acctid FROM ".db_prefix("accounts")." WHERE race='Paladin' ORDER BY acctid"; 
	$result = db_query($sql);
	require_once("lib/systemmail.php");
	for ($i=0;$i<db_num_rows($result);$i++){
		$row = db_fetch_assoc($result);
		$mailmessage="The Gods have recalled the Paladins to the heavens. Your divine gifts have faded."; 
		systemmail($row['acctid'],"To the Paladins...",$mailmessage); 
	}
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
	db_query($sql);
	debug("Uninstalled 'Paladin' specialty and race module."); 
	return true;
}

function racespecialtypaladin_dohook($hookname,$args){
	global $session,$resline,$badguy;
	$city = get_module_setting("villagename");
	$race = "Paladin"; 
	$spec = "PL"; 
	$name = "Paladin Gifts"; 
	$sname = racespecialtypaladin_namer();
	$ccode = "`#"; 
	
	switch($hookname){
		case "header-newday":
			if (get_module_pref("newdayset")==1) {
				set_module_pref("newdayset",2);
				output("`c`b`#You had the $name specialty, but lacked the holy bloodline!`nWe'll soon fix that.`n`\$-=-=-=-`b`c`n");
			}
		break;
		case "newday-intercept":
			racespecialtypaladin_change();
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
				$args['racesave'] = "It was your destiny as a Paladin that allowed you to survive unscathed.`n";
				$args['schema'] = "module-racespecialtypaladin";
			}
		break;
		case "changesetting":
			if ($args['setting'] == "villagename" && $args['module']=="racespecialtypaladin") {
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
			$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
			if ($session['user']['dragonkills']>=get_module_setting("mindk") && get_module_setting("cost") <= $pointsavailable) {
				$can_select = true;

                if (is_module_active("alignment")) {
					@require_once('modules/alignment.php');
                    $al = function_exists('get_align') ? get_align() : 0;
                    if ($al < 0) { 
                        $can_select = false;
                        output("`n`7Your soul lacks the purity required to walk the path of the %s.`n", $race);
                    }
                }

				if ($can_select) {
					addnav("`@Paladin`0","newday.php?setrace=$race$resline");
					output_notl("`0<a href='newday.php?setrace=$race$resline'>`&The city of `!%s</a>`& towers before you. `!Paladins`& in this village are protectors of the innocents.`n`n", $city, true);
					addnav("","newday.php?setrace=$race$resline");
				}
			}
		break;
		case "setrace":
			if ($session['user']['race']==$race){
				output("`n`&As a `!paladin`&, you have the power of wizards and the strenght of a warrior running through your blood. `n");
				output("`^As such, you are held to a higher standard. Both mortals and gods expect more from you. ");
				output("Your growth and bonuses as a `!paladin`& is tied directly to your morality.`n");
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
				racespecialtypaladin_checkcity();
				$args['turnstoday'] .= ", Race ($race): 1"; 
				$session['user']['turns']++;
				
				// Centralized Alignment Math
				$al = 0;
				if (is_module_active('alignment')) {
					@require_once('modules/alignment.php'); 
					$al = function_exists('get_align') ? get_align() : 0; 
				}
				
				$paladin_race_val = ($al+50);
				$paladin_spec_val = ($al-49);

				// Race Bonuses
				if ($paladin_race_val <= 0){
					apply_buff("racialbenefit",array(
					"name"=>"`!Ancestry`0",
					"atkmod"=>"(+1*(<attack>?(2+((1+floor(<level>/5))/<attack>)):0))",
					"allowinpvp"=>1,
					"allowintrain"=>1,
					"rounds"=>-1,
					"schema"=>"module-racespecialtypaladin",
					));
					output("`n`!The Fate of the world lies in your hands! To achieve your destiny, you must first harness your powers!`0`n");
				} else {
					$session['user']['hitpoints']+=$paladin_race_val;
					$bonusturns=(int)$paladin_race_val/5;
					$session['user']['turns']+=$bonusturns;
					output("`n`&For being a rather heroic `!Being`&, you receive %s extra hit points, and %s extra forest fights!`n", $paladin_race_val, $bonusturns);
					if ($paladin_race_val==30){
						apply_buff("racialbenefit",array(
						"name"=>"`!Vaporize`0",
						"atkmod"=>"(<attack>?(5+((1+floor(<level>/5))/<attack>)):0)",
						"allowinpvp"=>1,
						"allowintrain"=>1,
						"rounds"=>-1,
						"schema"=>"module-racespecialtypaladin",
						));
						output("`n`&For being the hero, you receive an additional blessing of `!Godly Strength!`0`n");
					}
				}
				
				// Specialty Bonuses
				if($session['user']['specialty'] == $spec) {
					if($paladin_spec_val > 0) {
						$bonus = getsetting("specialtybonus", 1);
						if ($bonus == 1) {
							output("`n`2For being interested in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name,$ccode,$name);
						} else {
							output("`n`2For being interested in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n",$ccode,$name,$bonus,$ccode,$name);
						}
						$amt = (int)(get_module_pref("skill") / 3);
						$paladinbonus = (int)($paladin_spec_val / 10);
						if($paladinbonus > 0) {
							output("`n`2For being such a noble creature, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name);
							$amt += $paladinbonus;
						}
						$amt++;
						set_module_pref("uses", $amt);
					} else {
						output("`n`2Your power stems from righteousness which you lack so, you receive no `&%s%s`2 uses for today.`n",$ccode,$name);
						set_module_pref("uses", 0);
					}
				}
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
			racespecialtypaladin_checkcity();
			if ($session['user']['location'] == $city){
				$args['text']=array("`!`b`c%s, Home of the Paladins`c`b`n`!The sun shines brightly on this Heroic yet rather small village. %s is an ancient town built by the Gods themselves to ensure the safety and fate of the Paladins.`n", $city, $city);
				$args['schemas']['text'] = "module-racespecialtypaladin";
				$args['clock']="`n`6One of the tiny kids softly whispers in your ear that it is`^%s`6 before disappearing in a blue light`n";
				$args['schemas']['clock'] = "module-racespecialtypaladin";
				if (is_module_active("calendar")) {
					$args['calendar']="`n`6Another kid gently whispers in your ear, \"`^Today is `&%3\$s %2\$s`^, `&%4\$s`^.  It is `&%1\$s`^.`6\"`n";
					$args['schemas']['calendar'] = "module-racespecialtypaladin";
				}
				$args['title']=array("%s City", $city);
				$args['schemas']['title'] = "module-racespecialtypaladin";
				$args['sayline']="announces";
				$args['schemas']['sayline'] = "module-racespecialtypaladin";
				$args['talk']="`n`&The word on the block is:`n";
				$args['schemas']['talk'] = "module-racespecialtypaladin";
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
					$args['newest']="`n`6Even with the potential for greatness, you are a human none the less being released in a cruel world.";
				} else {
					$args['newest']="`n`#Looking at all the towering `!Godly`# buildings, and distracted is `^%s`#.";
				}
				$args['schemas']['newest'] = "module-racespecialtypaladin";
				$args['section']="village-$race";
				$args['stablename']="The Holy Hostler";
				$args['schemas']['stablename'] = "module-racespecialtypaladin";
				$args['gatenav']="The Heavenly Gates";
				$args['schemas']['gatenav'] = "module-racespecialtypaladin";
				$args['fightnav']="Hall of Heroes";
				$args['schemas']['fightnav'] = "module-racespecialtypaladin";
				$args['marketnav']="The Pub";
				$args['schemas']['marketnav'] = "module-racespecialtypaladin";
				$args['tavernnav']="Ambrosia and Ale";
				$args['schemas']['tavernnav'] = "module-racespecialtypaladin";
				unblocknav("stables.php");
			}
		break;
		case "stabletext":
			if ($session['user']['location'] != $city) break;
			$args['title'] = "The Holy Hostler";
			$args['schemas']['title'] = "module-racespecialtypaladin";
			$args['desc'] = "`\$You approach a magnificent stable of white marble. ".get_module_setting("stableowner")."`\$ welcomes you warmly.";
			$args['schemas']['desc'] = "module-racespecialtypaladin";
			$args['lad']="Sir";
			$args['schemas']['lad'] = "module-racespecialtypaladin";
			$args['lass']="Miss";
			$args['schemas']['lass'] = "module-racespecialtypaladin";
			$args['nosuchbeast']="`3\"`7I don't stock that creature.`3\", says ".get_module_setting("stableowner")."`3.";
			$args['schemas']['nosuchbeast'] = "module-racespecialtypaladin";
			$args['finebeast']=array(
				"`3\"`7A fine steed for a holy warrior.`3\"`n`n",
				"`3\"`7May this creature carry you to victory.`3\"`n`n",
				);
			$args['schemas']['finebeast'] = "module-racespecialtypaladin";
			$args['toolittle']="`%I am sorry, but the church requires a larger donation for that.";
			$args['schemas']['toolittle'] = "module-racespecialtypaladin";
			$args['replacemount']="`%You leave your `^%s behind, mounting a glorious `&%s`%.";
			$args['schemas']['replacemount'] = "module-racespecialtypaladin";
			$args['newmount']="`@You accept the reins to your new `&%s`@.";
			$args['schemas']['newmount'] = "module-racespecialtypaladin";
			$args['nofeed']="`3\"`7We do not provide feed here.`3\"";
			$args['schemas']['nofeed'] = "module-racespecialtypaladin";
			$args['nothungry']="`&%s`6 refuses the food.";
			$args['schemas']['nothungry'] = "module-racespecialtypaladin";
			$args['halfhungry']="`&%s`6 eats gracefully, leaving half.";
			$args['schemas']['halfhungry'] = "module-racespecialtypaladin";
			$args['hungry']="`6%s`6 eats ravenously.";
			$args['schemas']['hungry'] = "module-racespecialtypaladin";
			$args['mountfull']="`n`6\"`^Your %s`^ is full now.`6\"";
			$args['schemas']['mountfull'] = "module-racespecialtypaladin";
			$args['nofeedgold']="`6\"`^You lack the funds for feed.`6\"";
			$args['schemas']['nofeedgold'] = "module-racespecialtypaladin";
			$args['confirmsale']="`n`n`6Are you sure you wish to part with your steed?";
			$args['schemas']['confirmsale'] = "module-racespecialtypaladin";
			$args['mountsold']="`6You hand the reins over.";
			$args['schemas']['mountsold'] = "module-racespecialtypaladin";
			$args['offer']="`n`n`6I can offer you `&%s`6 gold and `%%s`6 gems for %s`6.";
			$args['schemas']['offer'] = "module-racespecialtypaladin";
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

					if (is_module_active("alignment")) {
						@require_once('modules/alignment.php');
						$al = function_exists('get_align') ? get_align() : 0;
						if ($al < 0) {
							$can_select = false;
							output("`n`7You do not have the purity required to learn %s.`n", $name);
						}
					}

					if ($can_select) {
						addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline");
						$t1 = translate_inline("being pompous and pious"); 
						$t2 = appoencode(translate_inline("$ccode$name`0"));
						rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
						addnav("","newday.php?setspecialty=$spec$resline");
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
				$cost = get_module_setting("cost");
				$session['user']['donationspent'] = $session['user']['donationspent'] - $cost;
				
				set_module_pref("class", "magic");
				set_module_pref("subclass", "1");
				
				output("`#You knew as a young child, that yours was a higher calling. You were touched, a Messenger. ");
				output("`#Your devout faith and gifts were a blessing from God, and it was your destiny in life to ");
				output("`#carry God's message to the unwashed masses.`n");
				output("`^Well, that's part of the story anyway.`n`n");
				output("`^It should be noted that your power stems from righteous behavior. So long as your morals are just, your.");
				output("`^power shall be mighty. If you slip in your faith and judgement, your divine gifts will be yanked away...");
				
				if (is_module_active('alignment')) {
					$gain = get_module_setting("gain");
					set_module_pref('alignment',get_module_pref('alignment','alignment') - $gain,'alignment');
				}
			}
		break;
		case "specialtycolor":
			$args[$spec] = $ccode;
		break;
		case "specialtynames":
			$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
			if ($session['user']['superuser'] & SU_EDIT_USERS || $session['user']['dragonkills'] >= get_module_setting("mindk") || get_module_setting("cost") <= $pointsavailable){
				$args[$spec] = translate_inline($name);
			}
		break;
		case "specialtymodules":
			$args[$spec] = "racespecialtypaladin"; 
		break;
		case "incrementspecialty":
			if($session['user']['specialty'] == $spec) {
				$new = get_module_pref("skill") + 1;
				set_module_pref("skill", $new);
				$c = $args['color'];
				output("`n%sYou gain a level in `&%s%s to `#%s%s!",
						$c, $name, $c, $new, $c);
				$x = $new % 3;
				if ($x == 0){
					output("`n`^You gain an extra use point!`n");
					set_module_pref("uses", get_module_pref("uses") + 1);
				}else{
					if (3-$x == 1) {
						output("`n`^Only 1 more skill level until you gain an extra use point!`n");
					} else {
						output("`n`^Only %s more skill levels until you gain an extra use point!`n", (3-$x));
					}
				}
				output_notl("`0");
			}
		break;
		case "fightnav-specialties":
			if ($session['user']['race']==$race && $session['user']['specialty'] == $spec) {
				$stuff = racespecialtypaladin_texts(get_module_pref("class"),get_module_pref("subclass"));
				$uses = get_module_pref("uses");
				$script = $args['script'];
				if ($uses > 0) {
					addnav(array("$ccode ".racespecialtypaladin_namer()." (%s points)`0", $uses), "");
				}
				foreach ($stuff as $points => $rubbish) {
					foreach ($rubbish as $name => $morerubbish) {
				 		if ($name == "name") {
				 			if ($uses >= $points) {
					 			$morei = str_replace("`%","`%%",$morerubbish);
								addnav(array("`@ &#149; $morei`@ (%s)`0", $points), 
								$script."op=fight&skill=$spec&l=$points", true);
				 			}
			 			}
			 		}
				}
			}
		break;
		case "apply-specialties":
			$skill = httpget('skill');
			$l = httpget('l');
			$stuff = racespecialtypaladin_texts(get_module_pref("class"),get_module_pref("subclass"));
			if ($skill==$spec){
				if (get_module_pref("uses") >= $l){
					if (isset($stuff[$l])) {
						$buff_to_apply = $stuff[$l]; 
						apply_buff($spec.$l, $buff_to_apply); 
					} else {
						apply_buff($spec."0", array(
							"startmsg"=>"You call on your deity above, but they do not need your call. They were busy heeding nature's call.", 
							"rounds"=>1,
							"schema"=>"racespecialtypaladin" 
						));
					}
					set_module_pref("uses", get_module_pref("uses") - $l);
				}
			}
		break;
		case "pointsdesc":
			$cost = get_module_setting("cost");
			if (get_module_setting("mindk")>0 || $cost>0){
				$args['count']++;
				$format = $args['format'];
				$str = translate("The Paladin race/specialty is availiable upon reaching %s Dragon Kills and %s points.");
				$str = sprintf($str, get_module_setting("mindk"),$cost);
				output($format, $str, true);
			}
		break;
		case "castlelib":
			$op69 = httpget('op69');
			if ($op69 == 'paladin'){
				output("You sit down and open up the Ye Olde Bible.`n");
				output("You read for a while... in the time it takes you to read you use up`n");
				output("3 Turns.`n`n");
				output("You spend some quality time with the good book of your choosen deity, and they are,`n");
				output("quite pleased. You learn more about your deity, and how to appease them. You grow`n");
				output("in power as a Paladin, and your deity gives you a merit badge to boot!`n");
				$session['user']['turns']-=3;
				set_module_pref('skill',(get_module_pref('skill','racespecialtypaladin') + 1),'racespecialtypaladin');
				set_module_pref('uses', get_module_pref("uses",'racespecialtypaladin') + 1,'racespecialtypaladin');
				addnav("Continue","runmodule.php?module=lonnycastle&op=library");
			}
		break;
		case "castlelibbook":
			output("Ye Olde Bible. (3 Turns)`n");
			addnav("Read a Book");
			addnav("Ye Olde Bible","runmodule.php?module=lonnycastle&op=library&op69=paladin");
		break;
		case "pvpadjust":
			$enemyAL = get_module_pref('alignment','alignment',$badguy['acctid']);
			if($enemyAL >= 50) {
				$badguy['attack']+=(1+floor($badguy['level']/5));
			} else {
				$badguy['attack']-=(1+floor($badguy['level']/5));
			}
			if ($badguy['race'] == $race) {
				$badguy['hitpoints']+=($enemyAL-50);
			}
		break;
	}
	return $args;
}

function racespecialtypaladin_change() {
    global $session;
    $race="Paladin"; 
    $spec="PL"; 
    if (get_module_setting("wepchange")==1 && ($session['user']['race'] ?? '') == $race && ($session['user']['specialty'] ?? '') == $spec) {
        $nstr = "`%".racespecialtypaladin_namer("Y");
        
        if (!preg_match('/' . preg_quote($nstr, '/') . '/', $session['user']['weapon'])) {
            $n = $nstr." ".$session['user']['weapon'];
            if (strlen($n) < 50) $session['user']['weapon'] = $n;
        }
        
        if (!preg_match('/' . preg_quote($nstr, '/') . '/', $session['user']['armor'])) {
            $n = $nstr." ".$session['user']['armor'];
            if (strlen($n) < 50) $session['user']['armor'] = $n;
        }
    }
}

function racespecialtypaladin_checkcity(){
	global $session,$SCRIPT_NAME;
	$race="Paladin"; 
	$spec="PL"; 
	$city=get_module_setting("villagename");
	if (get_module_pref("subclass")!=0&&$session['user']['specialty']!=$spec) {
		set_module_pref("subclass",0);
	}
	if ($session['user']['race']==$race && is_module_active("cities")){
		if (get_module_pref("homecity","cities")!=$city){ 
			set_module_pref("homecity",$city,"cities");
		}
	}
	if ($session['user']['race']!=$race && $session['user']['specialty']==$spec) {
		$session['user']['specialty']="";
		set_module_pref("newdayset",1);
		set_module_pref("pagereturn",$SCRIPT_NAME);
		$session['user']['restorepage']="newday.php?continue=1";
		require_once("lib/forcednavigation.php");
		do_forced_nav(false,true);
		redirect("newday.php?continue=1","Specialty set, but not the race. Forced change."); 
	}
	return true;
}

function racespecialtypaladin_run(){
}

function racespecialtypaladin_texts($class,$subclass) {
	global $session;
	$array = array();
	
	$array['magic']['1']['1'] = array(
		"startmsg"=>"`!There is no peace without justice, and as God is your witness, you will dish it out!",
		"name"=>"`#Smite Evil",
		"rounds"=>5,
		"wearoff"=>"Divinity's blessings are powerful, yet brief.",
		"effectmsg"=>"`&With righteous fury, you smite `^{badguy}`& for `^{damage}`& points.",
		"atkmod"=>1.5,
	);
	$array['magic']['1']['2'] = array(
		"startmsg"=>"`!You can feel divine blessings protecting, and shielding you.!",
		"name"=>"`#Divine Grace",
		"rounds"=>10,
		"wearoff"=>"Your divine protection fades.",
		"effectmsg"=>"`!Your angelic protection makes you harder to hit.",
		"defmod"=>1.5,
	);
	$array['magic']['1']['3'] = array(
		"startmsg"=>"`!You often shed blood, but peace is your goal and your hands can heal as well.",
		"name"=>"`#Lay on Hands",
		"rounds"=>5,
		"wearoff"=>"You have exhausted this power.",
		"regen"=>($session['user']['level']*1.5),
		"effectmsg"=>"`!Your hands provide miraculous healing power, healing `#{damage}`! health.",
		"effectnodmgmsg"=>"`2You have no wounds to regenerate.",
	);
	$array['magic']['1']['5'] = array(
		"startmsg"=>"`!All great heroes have an equally great steed. This is yours.",
		"rounds"=>5,
		"name"=>"`#Paladin Mount",
		"minioncount"=>1,
		"damageshield"=>0.5,
		"maxbadguydamage"=>round($session['user']['attack']*2.5,0),
		"minbadguydamage"=>round($session['user']['attack']*1.5,0),
		"effectmsg"=>"`#{badguy}`! strikes at you, but your mount soaks part of the blow and also deals out `#{damage}`! damage back.",
	);
	
	if ($session['user']['race']=='Paladin') { 
		if (isset($array[$class][$subclass])) {
			foreach ($array[$class][$subclass] as $t => $d) {
				$array[$class][$subclass][$t]['schema']="racespecialtypaladin"; 
			}
			return $array[$class][$subclass];
		} else {
			return array();
		}
	} else {
		return array();
	}
}

function racespecialtypaladin_namer($type='X') {
	$class = array();
	$class['magic']['1']="Paladin Gifts";
	$class['1'] = "Divine"; 
	$class['0'] = "Holy";
	
	if ($type=="X") {
		return $class[get_module_pref("class")][get_module_pref("subclass")] ?? '';
	} else {
		return $class[get_module_pref("subclass")] ?? '';
	}
}
?>