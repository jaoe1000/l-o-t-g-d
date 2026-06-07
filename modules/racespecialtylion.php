<?php
/*
Details:
 * Translator Ready
 * Addnews Ready
 * Mail Ready
History Log:
 * Version 1.0:
   o Now being tested
 * Version 1.1:
   o Slight bug fixed
 * Version 1.2:
   o Another bug fixed
Easy way to customize:
 * Run a replace all in your PHP editor, or in wordpad, replacing 'racespecialtylion' with your module name
 * Search for '***CHANGE' and customize the stuff on that line
 * Search for '***LCHANGE' and customize all the stuff between that and the next '***ENDLCHANGE'
*/
function racespecialtylion_getmoduleinfo(){
	$info = array(
		"name"=>"Race/Specialty - Lion", // ***CHANGE
		"version"=>"1.2",
		"vertxtloc"=>"",
		"author"=>"Peter Corcoran", // ***CHANGE
		"category"=>"Race Specialities",
		"download"=>"", // ***CHANGE
		"settings"=>array(
			"Lion - Race Settings,title", // ***CHANGE
			"villagename"=>"Name for the Lion city,|Raor", // ***CHANGE
			"stableowner"=>"Name of the Lion city stable-owner,|Noil Repeek", // ***CHANGE
			"minedeathchance"=>"Chance for lions to die in the mine,range,0,100,1|20", // ***CHANGE
			"dklimit"=>"Limit the Race to a certain amount of dragon kills?,int|3",
			"(some of the specialities are quite strong- the DK limit is recommended),note",
			"wepchange"=>"Will Lion weapons & armour change?,bool|1", // ***CHANGE
			"IE- an 'Adze' would become a 'Snake Adze' if a user had a snake specialty- and an 'Adze',note", // ***CHANGE
			"If they had the race but not a specialty- the weapon would become a 'lion Adze',note", // ***CHANGE
		),
		"prefs" => array(
			"Lion - Specialty User Prefs,title", // ***CHANGE
			"skill"=>"Skill points in Lion Powers,int|0", // ***CHANGE
			"uses"=>"Uses of Lion Powers allowed,int|0", // ***CHANGE
			"class"=>"Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|", // ***CHANGE
			"subclass"=>"Sublass,enum,Unset,0,Cub,1,Lion,2,Alpha Lion,3|0", // ***CHANGE
			"newdayset"=>"Newday intercept at footer...,hidden|0", // Basically, when redirects the user away from the newday page, so that if a user has the wrong specialty set, it forces them to choose a new one.
			"pagereturn"=>"Return to what page after forced specialty change?,hidden|",
			"scoundrel"=>"How many times has this user attempted to have Lion specialties- but not the lion race?,viewonly|0", // ***CHANGE
		),
	);
	return $info;
}

function racespecialtylion_install(){
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
	debug("Installed 'Lion' specialty and race module."); // ***CHANGE
	return true;
}

function racespecialtylion_uninstall(){
	$spec = "PR"; // ***CHANGE
	global $session;
	$vname = getsetting("villagename", LOCATION_FIELDS);
	$gname = get_module_setting("villagename");
	$sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
	db_query($sql);
	if ($session['user']['location'] == $gname)
		$session['user']['location'] = $vname;
	// Force anyone who was a lion to rechoose race - ***CHANGE
	$sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='lion'"; // ***CHANGE
	db_query($sql);
	if ($session['user']['race'] == 'lion')  // ***CHANGE
		$session['user']['race'] = RACE_UNKNOWN;
	// Reset the specialty of anyone who had this specialty so they get to
	// rechoose at new day
	$sql = "SELECT acctid FROM ".db_prefix("accounts")." WHERE race='lion' ORDER BY acctid"; // ***CHANGE
	$result = db_query($sql);
	require_once("lib/systemmail.php");
	for ($i=0;$i<db_num_rows($result);$i++){
		$row = db_fetch_assoc($result);
		$mailmessage="The lions have been banished from the land.. their powers are gone... what will you do now?"; // ***CHANGE
		systemmail($row['acctid'],"To the lions...",$mailmessage); // ***CHANGE
	}
	$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
	db_query($sql);
	debug("Uninstalled 'lion' specialty and race module. Users of it were mailed."); // ***CHANGE
	return true;
}

function racespecialtylion_dohook($hookname,$args){
	//yeah, the $resline thing is a hack.  Sorry, not sure of a better way
	// to handle this.
	// Pass it as an arg?
	global $session,$resline;
	$city = get_module_setting("villagename");
	$race = "Lion"; // ***CHANGE
	$spec = "LN"; // ***CHANGE
	$name = "Lion Powers"; // ***CHANGE
	$sname = racespecialtylion_namer();
	$ccode = "`@";
	switch($hookname){
		case "header-newday":
			if (get_module_pref("newdayset")==1) {
				set_module_pref("newdayset",2);
				$gender = ($session['user']['sex']?"Girl":"Boy");
				output("`c`b`#Ooh! You've been a norty ".$gender."!! You had a lion specialty, but not the race!`nWe'll soon fix that.`n`\$-=-=-=-`b`c`n");
			}
		break;
		case "newday-intercept":
			racespecialtylion_change();
			if (get_module_pref("newdayset")==2) {
				set_module_pref("scoundrel",get_module_pref("scoundrel")+1);
				set_module_pref("newdayset",0);
				$p = get_module_pref("pagereturn");
				if ($p=="") {
					$p = "village.php";
				}
				$session['user']['restorepage']=$p;
				set_module_pref("pagereturn","");
				redirect($p,"Forced specialty change, as the user had a lion specialty set, but not the race. Fixed."); // ***CHANGE
			}
		break;
		case "raceminedeath":
			if ($session['user']['race'] == $race) {
				$args['chance'] = get_module_setting("minedeathchance");
			}
		break;
		case "changesetting":
			// Ignore anything other than villagename setting changes
			if ($args['setting'] == "villagename" && $args['module']=="racespecialtylion") {
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
				// ***LCHANGE
				$atrans = translate_inline("Raised in the forest,");
				$rtrans = translate_inline("you grew to your power, eventually entering the city of %s. You learnt more, yet it satisfied you less, till you went to seek out the `\$higher arts`&.`n`n");
				addnav("`&lion`0","newday.php?setrace=$race$resline");
				// ***ENDLCHANGE
				output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans"."</a>$rtrans", $city, true);
				addnav("","newday.php?setrace=$race$resline");
			}
		break;
		case "setrace":
			if ($session['user']['race']==$race){
				output("`&As a lion, your agile body allows you to move faster, with much more agility than other races.`n`^You gain an extra forest fight each day!"); // ***CHANGE
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
				racespecialtylion_checkcity();
				$args['turnstoday'] .= ", Race (lion): 1"; // ***CHANGE
				$session['user']['turns']++;
				output("`n`&Because you are a lion, you gain `^an extra`& forest fight for today!`n`0"); // ***CHANGE
				$bonus = getsetting("specialtybonus", 1);
				if($session['user']['specialty'] == $spec) {
					if ($bonus == 1) {
						output("`n`2For being interested in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name,$ccode,$name);
					} else {
						output("`n`2For being interested in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n",$ccode,$name,$bonus,$ccode,$name);
					}
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
				$args["village-$race"]=sprintf_translate("City of %s", $city); // ***CHANGE
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
			racespecialtylion_checkcity();
			if ($session['user']['location'] == $city){
				// ***LCHANGE
				$args['text']=array("`\$`c`b%s, City of lions`b`c`n`^You are standing below a hidden mountain, within a blood-soaked hall. The reptillian city of %s, built by the ancient dragon lords, the same beings that unleashed all of the green dragons. With horrific carvings and a shadowy, dark, underground, unnatural forest. A dark, dank shaft, leads to 'The Snake Pit', a horiffic stable. Footpads stalk the walkways, things get drunk in pits, and reptillian monstrosities learn their powers. You think you could really learn to like it here, and you talk to the residents.`n", $city, $city);
				$args['schemas']['text'] = "module-racespecialtylion";
				$args['clock']="`n`7A glowing clock, a sickly `@green`7 colour, reads `@%s`7.`n";
				$args['schemas']['clock'] = "module-racespecialtylion";
				if (is_module_active("calendar")) {
					$args['calendar'] = "`n`\$A blood soaked stone sitting at the base of the clock, reads `&%s`\$, `&%s %s %s`\$.`n";
					$args['schemas']['calendar'] = "module-racespecialtylion";
				}
				$args['title']=array("%s, City of lions", $city);
				$args['schemas']['title'] = "module-racespecialtylion";
				$args['sayline']="hisses";
				$args['schemas']['sayline'] = "module-racespecialtylion";
				$args['talk']="`n`&Nearby some creatures stand, hissing:`n";
				$args['schemas']['talk'] = "module-racespecialtylion";
				$new = get_module_setting("newest-$city", "cities");
				if ($new != 0) {
					$sql =  "SELECT name FROM " . db_prefix("accounts") .
						" WHERE acctid='$new'";
					$result = db_query_cached($sql, "newest-$city");
					$row = db_fetch_assoc($result);
					$args['newestplayer'] = $row['name'];
					$args['newestid']=$new;
				} else {
					$args['newestplayer'] = $new;
					$args['newestid']="";
				}
				if ($new == $session['user']['acctid']) {
					$args['newest']="`n`7As you creep around the city, some part of you enjoys the horrors around you, but mostly you shudder.";
				} else {
					$args['newest']="`n`7Creeping around the city, concealed by stealthy footpads, is `&%s`7.";
				}
				$args['schemas']['newest'] = "module-racespecialtylion";
				$args['section']="village-$race";
				$args['stablename']="The Snake Pit";
				$args['schemas']['stablename'] = "module-racespecialtylion";
				$args['gatenav']="The Path Up and Outwards";
				$args['schemas']['gatenav'] = "module-racespecialtylion";
				$args['fightnav']="The Modern Horrors";
				$args['schemas']['fightnav'] = "module-racespecialtylion";
				$args['marketnav']="The Ancient Horrors";
				$args['schemas']['marketnav'] = "module-racespecialtylion";
				$args['tavernnav']="Ale Soaked Pit";
				$args['schemas']['tavernnav'] = "module-racespecialtylion";
				// ***ENDLCHANGE
				unblocknav("stables.php");
			}
		break;
		case "stabletext":
			if ($session['user']['location'] != $city) break;
			// ***LCHANGE
			$args['title'] = "The Snake Pit";
			$args['schemas']['title'] = "module-racespecialtylion";
			$args['desc'] = "`\$A cold dank shaft, leading away from the main city, takes you to a large blood-stained paddock. A creature rushes up to you, and introduces itself as ".get_module_setting("stableowner")."`\$. `3\"`7Ahh! %s, what are you doing here?`3\", it asssks in a hissing, sinister voice. \"`7Thiss is where I, ".get_module_setting("stableowner")."`7, keep my beassstsss. Would you wish to... buy one?`3\" it says. `\$Grimacing slightly, you indicate assent, and walk with ".get_module_setting("stableowner")."`\$.";
			$args['schemas']['desc'] = "module-racespecialtylion";
			$args['lad']="SsSsir";
			$args['schemas']['lad'] = "module-racespecialtylion";
			$args['lass']="MisssSSes";
			$args['schemas']['lass'] = "module-racespecialtylion";
			$args['nosuchbeast']="`3\"`7I'm sorry, I don't stock any such creature.`3\", ".get_module_setting("stableowner")."`3 says, its teeth widening in a toothy, malignant grin.";
			$args['schemas']['nosuchbeast'] = "module-racespecialtylion";
			$args['finebeast']=array(
				"`3\"`7One of my best items, I'll have to be paid in advance!`3\", says ".get_module_setting("stableowner")."`3.`n`n",
				"`3\"`7I was going to have this fine creature for my lunch, you'll have to pay me much, for this excellent beast, and provide a sandwich.`3\", ".get_module_setting("stableowner")."`3 speaks, while grinning disconcertingly.`n`n",
				"`3\"`7Doesn't this one catch your fancy?`3\". it asks.`n`n",
				"`3\"`7You'll not find a tastier creature!`3\". exclaims ".get_module_setting("stableowner")."`3. `&Attempting to lower the price, you say `3\"`^I want to ride it, not eat it!`3\" `3\"`7What a waste of a fine beast, you'll have to pay me extra, for my shock.`3\" ".get_module_setting("stableowner")."`3 replies.`n`n",
				"`3\"`7Sorry, this is nowhere near good enough for you. It is so bad, I package free mustard with it.`3\", grimaces ".get_module_setting("stableowner").".`n`n",
				);
			$args['schemas']['finebeast'] = "module-racespecialtylion";
			$args['toolittle']="`%".get_module_setting("stableowner")." looks over the gold and gems you offer and turns up his nose, `3\"`7Obviously you don't have enough, I'll accept an arm in part-payment, or you can have half now, and half later.  This %s will cost you `&%s `7gold  and `%%s`7 gems, if you want it now, with an option on your unborn child. Alternatively, I have a nice mule.`3\"";
			$args['schemas']['toolittle'] = "module-racespecialtylion";
			$args['replacemount']="`%After rubbing ketchup into, `^%s`% to raise the price, you hand the reins as well as the money for your new creature, and ".get_module_setting("stableowner")."`% shows you the reins to a `&%s`%.";
			$args['schemas']['replacemount'] = "module-racespecialtylion";
			$args['newmount']="`@You hand over the money for your new creature, and ".get_module_setting("stableowner")." shows you the reins of to a new `&%s`@.";
			$args['schemas']['newmount'] = "module-racespecialtylion";
			$args['nofeed']="`3\"`7%s, I don't stock feed here. I'm not a common stable. Perhaps you should look elsewhere to feed your creature. I don't see why people are so fussy about them needing to be alive.`3\" ".get_module_setting("stableowner")."`3 comments.";
			$args['schemas']['nofeed'] = "module-racespecialtylion";
			$args['nothungry']="`&%s`6 picks briefly at the food and then ignores it.  ".get_module_setting("stableowner").", pockets the gold, and shakes his head. As you walk past, you manage to pickpocket it for your trouble.";
			$args['schemas']['nothungry'] = "module-racespecialtylion";
			$args['halfhungry']="`&%s`6dives into the provided food and gets through about half of it before stopping.  \"`^Well, it wasn't as hungry as you thought.`6\" says ".get_module_setting("stableowner")."`6 as it keeps %s gold.";
			$args['schemas']['halfhungry'] = "module-racespecialtylion";
			$args['hungry']="`6%s`6 seems to inhale the food provided.  %s`6, the greedy creature that it is, then starts to go snuffling at ".get_module_setting("stableowner")."`6's pockets for more food, before ".get_module_setting("stableowner")."`6 tries to see what its ear tastes like. It takes `&%s`6 gold from you.";
			$args['schemas']['hungry'] = "module-racespecialtylion";
			$args['mountfull']="`n`6\"`^Well, %s, your %s`^ was truly hungry today, but I've filled it up now. I'll offer you half price on a knife and fork, if you want, and I'll throw in a free stove, with a recipe for tasty stew. Are you really sure you don't want to eat it? If it hungers again I'll sell you more.`6\", says ".get_module_setting("stableowner")." with a face like thunder.";
			$args['schemas']['mountfull'] = "module-racespecialtylion";
			$args['nofeedgold']="`6\"`^I'm sorry, but that is just not enough money to pay for food here.`6\"  ".get_module_setting("stableowner")." turns his back on you, and you lead %s away to find other places for feeding.";
			$args['schemas']['nofeedgold'] = "module-racespecialtylion";
			$args['confirmsale']="`n`n`6".get_module_setting("stableowner")." eyes your mount up and down, checking it over carefully.  \"`^Are you sure you don't want to eat, erm, keep this creature?`6\"";
			$args['schemas']['confirmsale'] = "module-racespecialtylion";
			$args['mountsold']="`6With but a single tear, you hand over the reins to your %s`6 to ".get_module_setting("stableowner")."`6. It gives you %s in hand, and you look away, because ".get_module_setting("stableowner")."`6 starts to open a book called 'Carnivourous cooking for All Occasions', and places another book called 'Cooking Delia, a 100 easy recipes' next to it.";
			$args['schemas']['mountsold'] = "module-racespecialtylion";
			$args['offer']="`n`n`6".get_module_setting("stableowner")." puts mustard on your creatures flank and offers you `&%s`6 gold and `%%s`6 gems for %s`6.";
			$args['schemas']['offer'] = "module-racespecialtylion";
			// ***ENDLCHANGE
		break;
		case "stablelocs":
			tlschema("mounts");
			$args[$city]=sprintf_translate("The City of %s", $city); // ***CHANGE
			tlschema();
		break;
		case "dragonkill":
			set_module_pref("uses", 0);
			set_module_pref("skill", 0);
		break;
		case "choose-specialty":
			if ($session['user']['specialty'] == "" || $session['user']['specialty'] == '0') {
				if ($session['user']['race']=="lion") { // ***CHANGE
					addnav("$ccode$name`0","runmodule.php?module=racespecialtylion&spec=".$spec.$resline);
					$t1 = translate_inline("Learning your skills"); // ***CHANGE
					$t2 = appoencode(translate_inline("$ccode$name`0"));
					rawoutput("<a href='runmodule.php?module=racespecialtylion&spec=".$spec.$resline."'>$t1 ($t2)</a><br>");
					addnav("","runmodule.php?module=racespecialtylion&spec=".$spec.$resline);
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
				set_module_pref("class",$mc);
				set_module_pref("subclass",$sc);
				// ***LCHANGE
				output("`^You learn your way, after many years of studying.");
				output("`n`@You now go to seek your way in the world....");
				output("`n`&North, east, south, and west.. the world will change. It is your destiny.");
				output("`n`%Purrrrrr....");
				// ***ENDLCHANGE
			}
		break;
		case "specialtycolor":
			$args[$spec] = $ccode;
		break;
		case "specialtynames":
			$args[$spec] = translate_inline($name);
		break;
		case "specialtymodules":
			$args[$spec] = "racespecialtylion"; // ***CHANGE
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
			if ($session['user']['race']==$spec&&$session['user']['specialty'] == $spec) {
				$stuff = racespecialtylion_texts(get_module_pref("class"),get_module_pref("subclass"));
				$uses = get_module_pref("uses");
				$script = $args['script'];
				if ($uses > 0) {
					addnav(array("$ccode ".racespecialtylion_namer()." (%s points)`0", $uses), "");
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
			$stuff = racespecialtylion_texts(get_module_pref("class"),get_module_pref("subclass"));
			if ($skill==$spec){
				if (get_module_pref("uses") >= $l){
					if (isset($stuff[$l])) {
						apply_buff($spec.$l, $stuff[$l]);
					} else {
						apply_buff($spec."0", array(
							"startmsg"=>"Your powers are gone! What will you do?", // ***CHANGE
							"rounds"=>1,
							"schema"=>"racespecialtylion" // ***CHANGE
						));
					}
				}
			}
		break;
	}
	return $args;
}

function racespecialtylion_change() {
	global $session;
	$race="Lion"; // ***CHANGE
	$spec="LN"; // ***CHANGE
	if (get_module_setting("wepchange")==1&&$session['user']['race']==$race&&$session['user']['specialty']==$spec) {
		$nstr = "`%".racespecialtylion_namer("Y");
		if (!ereg($nstr,$session['user']['weapon'],$whatevr)) {
			$n = $nstr." ".$session['user']['weapon'];
			if (strlen($n)<50) $session['user']['weapon']=$n;
		}
		if (!ereg($nstr,$session['user']['armor'],$whatevr)) {
			$n = $nstr." ".$session['user']['armor'];
			if (strlen($n)<50) $session['user']['armor']=$n;
		}
	}
}

function racespecialtylion_checkcity(){
	global $session,$SCRIPT_NAME;
	$race="Lion"; // ***CHANGE
	$spec="LN"; // ***CHANGE
	$city=get_module_setting("villagename");
	if (get_module_pref("subclass")!=0&&$session['user']['specialty']!="PR") {
		set_module_pref("subclass",0);
	}
	if ($session['user']['race']==$race && is_module_active("cities")){
		//if they're this race and their home city isn't right, set it up.
		if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
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
		redirect("newday.php?continue=1","Lion specialty set, but not the race. Specialty *must* be changed. This will be forced."); // ***CHANGE
	}
	return true;
}

function racespecialtylion_run(){
	global $session;
	$resline = httpget("resline");
	$spec = httpget('spec');
	// ***LCHANGE
	page_header("Lion Power");
	addnav("Lion Magic");
	addnav("Lion Lore","newday.php?setspecialty=".$spec."&mc=magic&sc=1&spec=".$spec."&resline=".$resline."");
	addnav("Lion Spells","newday.php?setspecialty=".$spec."&mc=magic&sc=2&spec=".$spec."&resline=".$resline."");
	addnav("Lion Enchantments","newday.php?setspecialty=".$spec."&mc=magic&sc=3&spec=".$spec."&resline=".$resline."");
	addnav("Melee lion");
	addnav("Lion Claws","newday.php?setspecialty=".$spec."&mc=melee&sc=1&spec=".$spec."&resline=".$resline."");
	addnav("Lion Daggers","newday.php?setspecialty=".$spec."&mc=melee&sc=2&spec=".$spec."&resline=".$resline."");
	addnav("Lion Axes","newday.php?setspecialty=".$spec."&mc=melee&sc=3&spec=".$spec."&resline=".$resline."");
	addnav("Ranging lion");
	addnav("Lion Bolts","newday.php?setspecialty=".$spec."&mc=ranging&sc=1&spec=".$spec."&resline=".$resline."");
	//addnav("Lion Darts","newday.php?setspecialty=".$spec."&mc=ranging&sc=2&spec=".$spec."&resline=".$resline."");
	//addnav("Lion Arrows","newday.php?setspecialty=".$spec."&mc=ranging&sc=3&spec=".$spec."&resline=".$resline."");
	output("`n`@Your kind has always been very varied, and so, you have many ways to choose from...");
	output("`^`c`bMagic?`n");
	output("Close Fighting?`n");
	output("Long-range Fighting??`b`c");
	output("`n`n`&What will you decide..");
	output("`n`%`c`bA voice speaks to you... you are not advanced enough to learn Snake Darts or Amphibious Arrows.`b`c");
	// ***ENDLCHANGE
	page_footer();
}

function racespecialtylion_texts($class,$subclass) {
	global $session;
	$array = array();
	// ***LCHANGE
	/* Setting up main classes.
 	1 is Lizard, 2 is Snake, and 3 is Amphibious*/
 	
	/* lion Magic */
	##-- Lion Lore --##
	$array['magic']['1']['1'] = array(
		"startmsg"=>"`^Your `@Fur`^ shivers, as a supernatural `\$fire`^, cold, and soothing, washes over you...",
		"name"=>"`\$Fire`% of `7Ice",
		"rounds"=>5,
		"wearoff"=>"`@Your fur has stopped growing.. you hunger for the fire.",
		"regen"=>$session['user']['level'],
		"effectmsg"=>"`^Your fur regrows `&{damage}`^ health... the fire soothes.",
		"effectnodmgmsg"=>"`^You have no fur to regrow... the fire washes over you.",
	);
	$array['magic']['1']['2'] = array(
		"startmsg"=>"`@Curling tendrils of Emerald `7mist`@ unfurl from the end of your `%razor sharp `^claws`@, binding your opponent with a deceptive softness...",
		"name"=>"`@Hissing Mist",
		"rounds"=>5,
		"wearoff"=>"`^The mist dissipates into the ground...",
		"badguyatkmod"=>0,
		"badguydefmod"=>0,
		"roundmsg"=>"{badguy} `^stares blankly into the distance, its eyeballs opaque, and damaged beyond repair... it cannot even writhe.",
	);
	$array['magic']['1']['4'] = array(
		"startmsg"=>"`^In addition to your {weapon}`^, you uncover a `%stained and bloody bag`^, pulling at it, you dislodge mysterious discs... which shift colour and form.",
		"name"=>"`^Chameleon Fur",
		"rounds"=>5,
		"wearoff"=>"`^The Fur disappears, and you are left with just your {weapon}`^.",
		"atkmod"=>2,
		"roundmsg"=>"`@The fur flows onto the surface of your weapon, increasing the damage!",
		"minioncount"=>1,
		"effectmsg"=>"`@The Fur attacks {badguy}`@ with their nocked edges, for `^{damage}`@ points.",
		"minbadguydamage"=>1,
		"maxbadguydamage"=>$session['user']['level'],
	);
	$array['magic']['1']['5'] = array(
		"startmsg"=>"`\$You pluck out one of your eyeballs, and throw it towards the ground... upon touching the earth, it burrows into it, and a `@plant`\$ made from `^eyeballs`\$ springs from the ground, and surrounds you with a living shield... the eyeballs stare at {badguy}`\$ balefully, yet also malignantly.",
		"name"=>"`\$Eyeball `!Tendrils",
		"rounds"=>10,
		"wearoff"=>"`@The plant withers, and as it does so, you snatch a dessicated eye, from upon it, and return it to your head...",
		"damageshield"=>2,
		"effectmsg"=>"{badguy}`@ recoils as an evil aura singes it, emanating from your malignant eye plant... subduing it for `^{damage}`@ damage.",
		"effectnodmg"=>"{badguy}`% is slightly stunned by the evil aura around the `!tendrils`% of your `@eyeball`% plant, but otherwise unharmed.",
		"effectfailmsg"=>"{badguy}`% just stares right back at your `%eyeball `!plant`%.",
	);
	##-- Lion Spells --##
	$array['magic']['2']['1'] = array(
		"startmsg"=>"`^Moving faster than your opponent believed you possibly could, you throw a deadly paw around his torso. Enveloping your opponent in your pulsating muscular hug, you inexorably tighten your grip, slowly choking the life and soul of {badguy}`^.",
		"name"=>"`!Writhing `2Destiny",
		"rounds"=>7,
		"wearoff"=>"`%As {badguy}`% breathes one of its last breaths, the small black spiderlike creature enters {badguy}`%'s body, signalling the end of your spell.",
		"lifetap"=>1, //ratio of damage healed to damage dealt
		"effectmsg"=>"`%You steal the soul of {badguy}`%, which looks small and spiderlike. Healing you for `^{damage}`% health.",
		"effectnodmgmsg"=>"`%You leave the dessicated, and cruelly twisted soul of {badguy}`% alone...",
		"effectfailmsg"=>"`%You wail, as your spell does not manage to draw the twisted soul of {badguy}`% from its fleshy vessel.",
	);
	$array['magic']['2']['3'] = array(
		"startmsg"=>"`^Your head feels strange. Your fur bristles, brightly patterned with strangely hypnotic shapes. Your `@eyes`^ start to whirl, in shades of `Qorange`^ and `7black`^, as you weave your head from side to side. As your opponent attempts to resist your hypnotic power, you strike.",
		"name"=>"`^Lion Veils",
		"rounds"=>10,
		"wearoff"=>"`@Your hood curls up...",
		"minioncount"=>1,
		"effectmsg"=>"`\$HISSSSSSSSSSSSSSSSSSSSSSSSSS!`n{badguy}`^'s body does `@{damage}`^ to itself...",
		"minbadguydamage"=>1,
		"maxbadguydamage"=>$session['user']['level']*4,
	);
	$array['magic']['2']['4'] = array(
		"startmsg"=>"`@A small whirling `&breeze`@ on the ground gathers strength and you find yourself in the centre of a whirlwind. `%Debris`@ in the centre of the wind richochets off the length of your body, tearing away your fur. As each length of skin is removed, it reveals new skin in many `^vibrant `\$c`%o`^l`@o`!u`&r`@s.",
		"name"=>"`&Winds of Change",
		"rounds"=>5,
		"wearoff"=>"`&The `@whirlwind`& abates.",
		"regen"=>$session['user']['level']*3,
		"effectmsg"=>"`@Your body starts to shimmer with new health and vitality. You feel `&{damage}`@ points healthier.",
		"effectnodmgmsg"=>"`@You have no wounds.",
	);
	$array['magic']['2']['5'] = array(
		"startmsg"=>"`\$The pupils of your eyes change shape and colour. As your periphal vision blurs in a mosaic of colours, you realize that your sight can pierce the veils of reality.",
		"name"=>"`\$Lion Scrying",
		"rounds"=>10,
		"wearoff"=>"`^In a flash of bright light, your eyes return to normal...",
		"badguyatkmod"=>0,
		"badguydefmod"=>0,
		"roundmsg"=>"{badguy}`@ recoils, as your eyes manage to see any attack it attempts, and repel it....",
	);
	##-- Lion Enchantments --##
	$array['magic']['3']['1'] = array(
		"startmsg"=>"`@Curling tendrils erupt from your sides, `7shimmering, `@ they cover your opponent in thick `%fur, `^quietly`@, binding your opponent with a crushing grip...",
		"name"=>"`^Suspended Statue",
		"rounds"=>5,
		"wearoff"=>"`^Your burning fur dissipate into the ground...",
		"badguyatkmod"=>0,
		"badguydefmod"=>0,
		"roundmsg"=>"{badguy} `^stares blankly into the distance, looking as though carved from stone...",
	);
	$array['magic']['3']['2'] = array(
		"startmsg"=>"`^With your magic sight, your weave a bubble of condensation around {badguy}`^, and yourself. Magewater fills the bubble up, not quite to popping pressure.",
		"name"=>"`!Atmospheric Change",
		"rounds"=>5,
		"wearoff"=>"`@The bubble pops!",
		"regen"=>$session['user']['level'],
		"effectmsg"=>"`^The water soothes you for `&{damage}`^ health... the water soothes.",
		"effectnodmgmsg"=>"`^You have no damage to soothe.. the water washes over you.",
	);
	$array['magic']['3']['4'] = array(
		"startmsg"=>"`!You bash your {weapon}`! to the ground, creating an enchanted hole... the hole seems full of sand, yet the sand is runny as water, and as heavy as granite... the siren song draws you in.... you can resist!!!",
		"name"=>"`!Watery Grave",
		"rounds"=>5,
		"wearoff"=>"`!The water dries up... you are left with just your {weapon}`^.",
		"atkmod"=>2,
		"roundmsg"=>"`!Resisting the water stretches the faculties of {badguy}`!.. it's all helping you!!!",
		"minioncount"=>1,
		"effectmsg"=>"`!The water draws {badguy}`! in, for `^{damage}`@ damage.",
		"minbadguydamage"=>1,
		"maxbadguydamage"=>$session['user']['level'],
	);
	$array['magic']['3']['5'] = array(
		"startmsg"=>"`^You elongate your tongue with a powerful enchantment, thrashing and lashing, use your tongue as a whip...",
		"name"=>"`%Tongue Lashing",
		"rounds"=>10,
		"wearoff"=>"`@Your tongue shrinks to normal...",
		"minioncount"=>1,
		"effectmsg"=>"`\$THWAP! THWAP!`n{badguy}`% gets hurt for `@{damage}`% damage by your tongue!",
		"minbadguydamage"=>1,
		"maxbadguydamage"=>$session['user']['level']*4,
	);
	/* Melee lion */
	##-- Lion Blades --##
	$array['melee']['1']['2'] = array(
		"startmsg"=>"`@Your wrinkled, lion paws, pull a Katana from your hidden body sheath... attacking {badguy}`@ with an extra weapon...",
		"name"=>"`^Hidden Katana",
		"rounds"=>5,
		"wearoff"=>"`@You drop your Katana...",
		"minioncount"=>round($session['user']['level']/3)+1,
		"maxbadguydamage"=>round($session['user']['level']/2,0)+1,
		"effectmsg"=>"`^You attack {badguy}`^ for `@{damage}`^ with your Katana!",
		"effectnodmgmsg"=>"`^You attempt to hit {badguy}`^ with your Katana, but you miss!",
	);
	$array['melee']['1']['3'] = array(
		"startmsg"=>"`@You pace around {badguy}`@ searching for a weakness... finding the head to be the weakest, you start to jump over {badguy}`@, and attack!",
		"name"=>"`@Lion Leap",
		"rounds"=>7,
		"wearoff"=>"`^You are too tired to leap any more..",
		"minioncount"=>round($session['user']['level']/3)+1,
		"maxbadguydamage"=>round($session['user']['level']/2,0)+1,
		"effectmsg"=>"`^You jump over {badguy}`^, and hit it with your {weapon}`^ for `@{damage}`^ damage!",
		"effectnodmgmsg"=>"`^You attempt to hit {badguy}`^ as you jump over it, but you miss!",
	);
	$array['melee']['1']['4'] = array(
		"startmsg"=>"`@You grab your {weapon}`@ and sheathe it into {badguy}`@ with an almighty whap!",
		"name"=>"`^Sheathing of the Flesh",
		"rounds"=>8,
		"wearoff"=>"`^You no longer have the strength to continue your sheathing...",
		"badguyatkmod"=>0,
		"badguydefmod"=>0,
		"roundmsg"=>"{badguy}`^ is frozen by your sheathing for one round, allowing you to attack!",
	);
	##-- Snake Daggers --##
	$array['melee']['2']['1'] = array(
		"startmsg"=>"`@You reach into your mouth, tearing out a tooth with a sickly bonecrunching slurping noise.. covered with secreted poison, you attach it to your {weapon}`@.",
		"name"=>"`%Poison Fang",
		"rounds"=>5,
		"wearoff"=>"`@Your fang falls off of your {weapon}`@, and in the heat of battle, you cannot search for it.",
		"atkmod"=>2,
		"roundmsg"=>"`@Your Poison Fang increases your damage!", 
	);
	$array['melee']['2']['3'] = array(
		"startmsg"=>"`@Your frozen lips uncurl from your face, revealing a long sinuous tongue, which flicks, and your roar numbs {badguy}`@!",
		"name"=>"`@Roar of Pain",
		"rounds"=>7,
		"wearoff"=>"`^Your voice falters....",
		"minioncount"=>round($session['user']['level']/3)+1,
		"maxbadguydamage"=>round($session['user']['level']/2,0)+1,
		"effectmsg"=>"`^Your roar burns {badguy}`^ for `@{damage}`^ damage!",
		"effectnodmgmsg"=>"{badguy}`^ manages to plug its ears against your roar!",
	);
	$array['melee']['2']['4'] = array(
		"startmsg"=>"`^You purr in a super sexy purrrrr voice... '`@Trust in Me...`^', and your opponent temporarily swoons..",
		"name"=>"`\$Purring",
		"rounds"=>5,
		"wearoff"=>"`@Your voice grows hoarse, you have to stop singing.",
		"regen"=>$session['user']['level']*2,
		"effectmsg"=>"{damage}`^ thimbles worth of blood runs from `@{badguy}`^'s mouth... you cup `@{badguy}`^'s face, and drink its blood, regenerating {damage}`^ health...",
		"effectnodmgmsg"=>"`^Your opponent ignores your singing...",
	);
	$array['melee']['2']['6'] = array(
		"startmsg"=>"`!You slice yourself with a dagger as dark as night.. a drop of blood burns the ground, and from it springs another.. it attacks as you defend.",
		"name"=>"`!Shadow Lion",
		"rounds"=>7,
		"wearoff"=>"`^Your shodow hides....",
		"minioncount"=>round($session['user']['level']/3)+2,
		"maxbadguydamage"=>round($session['user']['level']/2,0)+2,
		"effectmsg"=>"`!Your shadow hits {badguy}`! for `@{damage}`! damage!",
		"effectnodmgmsg"=>"{badguy}`! evades your shadow!",
		"badguyatkmod"=>0,
		"badguydefmod"=>0,
		"roundmsg"=>"{badguy}`! is frozen by your shadow!",
	);
	##-- Lion Axes --##
	$array['melee']['3']['1'] = array(
		"startmsg"=>"`QYour `^{weapon}`Q glows with an unearthly light, turning into an axe of fire...",
		"name"=>"`QFire Axe",
		"rounds"=>7,
		"wearoff"=>"`QYour {weapon}`Q loses it's unholy lustre.",
		"minioncount"=>round($session['user']['level']/3)+1,
		"maxbadguydamage"=>round($session['user']['level']/2,0)+1,
		"effectmsg"=>"`!Blue`@ Green`Q flames erupt from your Fire Axe, enveloping {badguy}`Q and burning for `@{damage}`Q damage!",
		"effectnodmgmsg"=>"{badguy}`Q dodges the flames!",
	);
	$array['melee']['3']['3'] = array(
		"startmsg"=>"`!Your `^{weapon}`! glows with an unearthly light, and turns into an axe.. strangely wet, when you lift it, it covers you with a sheen of warm water, and continues to do so, for each hit you inflict...",
		"name"=>"`!Water Axe",
		"rounds"=>7,
		"wearoff"=>"`!Your `^{weapon}`! loses it's unholy lustre.",
		"lifetap"=>2, //ratio of damage healed to damage dealt
		"effectfailmsg"=>"`!Your Water Axe misses {badguy}`!....",
		"effectmsg"=>"`!You bathe in your Water Axe's healing power, and heal twice `@{damage}`!, the amount you attacked {badguy}`! with...",
		"effectnodmgmsg"=>"`!Your Water Axe is dry.. weird, no?",
	);
	$array['melee']['3']['4'] = array(
		"startmsg"=>"`7Your `^{weapon}`7 glows with an unearthly light, turning into an axe of air... seemingly unconsequential on the surface, it has no obvious advantages, except a strange symbol on the head...",
		"name"=>"`7Air Axe",
		"rounds"=>7,
		"wearoff"=>"`7Your `^{weapon}`7 loses it's unholy lustre.",
		"badguyatkmod"=>0,
		"badguydefmod"=>0,
		"roundmsg"=>"`7When you strike {badguy}`7 a membrane flows from your axe, choking your opponent, and it can no longer breathe...",
	);
	$array['melee']['3']['5'] = array(
		"startmsg"=>"`@Your `^{weapon}`@ glows with an unearthly light, turning into an axe of earth...",
		"name"=>"`@Earth Axe",
		"rounds"=>7,
		"wearoff"=>"`@Your {weapon}`@ loses it's unholy lustre.",
		"minioncount"=>round($session['user']['level']/3)+1,
		"maxbadguydamage"=>round($session['user']['level']/2,0)+1,
		"effectmsg"=>"`@When you try to strike your opponent, your Axe writhes and ends up striking the ground... a great chasm erupts, which {badguy}`@ falls into, causing `^{damage}`@ damage.. a while later, your opponent emerges.",
		"effectnodmgmsg"=>"`@Your axe does nothing..",
	);
	/* Ranging lion */
	##-- Lion Bolts --##
	$array['ranging']['1']['1'] = array(
		"startmsg"=>"`7Your `^{weapon}`7 emits a shrieking whistle, turning into a magnificent crossbow...",
		"name"=>"`7Steel Bolt",
		"rounds"=>7,
		"wearoff"=>"`7Your {weapon}`7 stops shrieking...",
		"minioncount"=>round($session['user']['level']/3)+1,
		"maxbadguydamage"=>round($session['user']['level']/2,0)+1,
		"effectmsg"=>"`7Your crossbow fires a Steel Bolt for `^{damage}`7 damage!.",
		"effectnodmgmsg"=>"`7Your crossbow does nothing..",
	);
	$array['ranging']['1']['2'] = array(
		"startmsg"=>"`\$Your `^{weapon}`\$ emits a shrieking whistle, turning into a magnificent crossbow... your crossbow fires a Sting Bolt, covered with venom, which paralyzes your opponent.",
		"name"=>"`\$Sting Bolt",
		"rounds"=>7,
		"wearoff"=>"`\$Your {weapon}`\$ stops shrieking...",
		"badguyatkmod"=>0,
		"badguydefmod"=>0,
		"roundmsg"=>"`\$Your opponent is paralyzed..",
	);
	$array['ranging']['1']['4'] = array(
		"startmsg"=>"`%Your `^{weapon}`% emits a shrieking whistle, turning into a magnificent crossbow... your crossbow fires a Nocked Bolt, which twists and turns in the air, finally, striking your opponent, it continues to twist and turn.",
		"name"=>"`%Nocked Bolt",
		"rounds"=>5,
		"wearoff"=>"`%Your {weapon}`% stops shrieking...",
		"atkmod"=>2,
		"roundmsg"=>"`%Your nocked bolt wears down your opponent, increasing your attack!", 
	);
	$array['ranging']['1']['5'] = array(
		"startmsg"=>"`&Your `^{weapon}`& emits a shrieking whistle, turning into a magnificent crossbow... your crossbow fires a Nocked Bolt, which strikes your opponent, and leeches out it's lifeforce....",
		"name"=>"`&Shadow Bolt",
		"rounds"=>7,
		"wearoff"=>"`&Your {weapon}`& stops shrieking...",
		"lifetap"=>2,
		"effectfailmsg"=>"`&Your Shadow Bolt fires off {badguy}`&'s lifeforce, right past you...!",
		"effectmsg"=>"`&You gain twice the amount of health that you damaged {badguy}`&... (`^{damage} x 2`&)",
		"effectnodmgmsg"=>"`&Your Shadow Bolt does nothing...",
	);
	##-- Lion Darts --##
	if ($session['user']['race']=='lion') {
		// ***ENDLCHANGE
		foreach ($array[$class][$subclass] as $t => $d) {
			// $d holds the arrays with the stuff in, but we currently don't use that
			// We just need $t, which holds the number of the specialty, and how many points to use it.
			// We then set the schema to avoid needlessly wasting code
			$array[$class][$subclass][$t]['schema']="racespecialtylion"; // ***CHANGE
			// Whoa! Long array string.
		}
		return $array[$class][$subclass];
	} else {
		$x = array();
		return $x;
	}
}

function racespecialtylion_namer($type='X') {
	$class = array();
	// ***LCHANGE
	$class['magic']['1']="Lizard Lore";
	$class['magic']['2']="Snake Spells";
	$class['magic']['3']="Amphibious Enchantments";
	$class['melee']['1']="Lizard Blades";
	$class['melee']['2']="Snake Daggers";
	$class['melee']['3']="Amphibious Axes";
	$class['ranging']['1']="Lizard Bolts";
	$class['ranging']['2']="Snake Darts";
	$class['ranging']['3']="Amphibious Arrows";
	$class['1'] = "Lizard";			// These are used to name the weapons.
	$class['2'] = "Snake";
	$class['3'] = "Amphibious";
	$class['0'] = "Reptillian";
	// ***ENDLCHANGE
	if ($type=="X") {
		return $class[get_module_pref("class")][get_module_pref("subclass")];
	} else {
		return $class[get_module_pref("subclass")];
	}
}
?>