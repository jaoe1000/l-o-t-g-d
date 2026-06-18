<?php
/*
Vypera the Gainer
File:	vypera.php
Author:	Chris Vorndran (Sichae)
Date:	9/17/2004
Version:1.2 (9/24/2004)

Vypera was written for the Warrior on the Go.
Gives back 60% of what the user could've earned.

v1.1
Fully Transalation Compatable
*/

function vypera_getmoduleinfo(){
	$info=array(
		"name"=>"Vypera the Gainer",
		"author"=>"Chris Vorndran",
		"version"=>"1.6",
		"category"=>"Village",
		"download"=>"http://dragonprime.net/index.php?module=Downloads;sa=dlview;id=35",
		"vertxtloc"=>"http://dragonprime.net/users/Sichae/",
		"description"=>"Allows users to be able to trade in X amount of Forest Fights and have the EXP flow back to them, at a depreciated value.",
		"settings"=>array(
			"Vypera Settings,title",
				"mult"=>"Percentage of creature level experience given back to user,range,1,100,1|60",
				"charge"=>"Does Vypera charge for her services?,bool|0",
				"The amount of experience possible shall be multiplied by a random number (1-3) and that shall be the cost.,note",
				"mindk"=>"What is the minimum DK before this shop will appear to a user?,int|0",
				"shoploc"=>"Where does the shop appear,location|".getsetting("villagename", LOCATION_FIELDS),
			),
		);
	return $info;
}
function vypera_install(){
	module_addhook("village");
	module_addhook("changesetting");
	return true;
}
function vypera_uninstall(){
	return true;
}
function vypera_dohook($hookname,$args){
	global $session;
	switch ($hookname){
		case "changesetting":
     		if ($args['setting'] == "villagename") {
				if ($args['old'] == get_module_setting("shoploc")) {
					set_module_setting("shoploc", $args['new']);
				}
			}
			break;
    	case "village":
    		if ($session['user']['location'] == get_module_setting("shoploc")
				&& $session['user']['dragonkills'] >= get_module_setting("mindk")) {
				$marketnav = $args['marketnav'] ?? ($args['nav_headers']['market'] ?? 'Market');
				$schema = $args['schemas']['marketnav'] ?? 'town';
				tlschema($schema);
				addnav($marketnav);
				tlschema();
				addnav("Vypera the Gainer","runmodule.php?module=vypera&op=enter");
			}
			break;
	}
	return $args;
}
function vypera_run(){
	global $session;
	page_header("Vypera the Gainer");
	
	$op = httpget('op');
	$amount = httppost('turns');
	$mult = get_module_setting("mult");
	$m = $mult/100;
	
	switch ($op){
		case "enter":
			output("`3You walk into a small pub.");
			output(" A small girl walks over to you and tugs at the hem of your shirt.`n`n");
			output(" \"`%Can you help me mister?`3\" she asks in a cherubic voice.");
			output("`n`nYou can not detour such a cute girl.");
			output(" You take her hand and say, \"`6How may I help you?`3\"");
			output("She takes hold of your hand and jumps through the door.`n`n");
			output("You are transported to a small room, dark and dank.");
			output(" Unable to see, you search around. You find a cold doorknob and open the door.");
			output(" Inside is a room, laced in ice crystal.");
			output(" A beautiful woman looks at you.");
			output("She whispers, \"`#How may I help you today?`3\"");
			output("You stare at her blankly.");
			output("She tilts her head and laughs.`n`n");
			output("\"`#Where are my manners? My name is `!Vypera`#... and I can help you with fighting...`3\"`n`n");
			output("She hands you a pamphlet, that outlines all of her offers.");
			output("She gives out Experience in exchange for forest fights... but at a decreased amount.");
			output(" It is for the Warrior on the go.");
			addnav("Venture Further","runmodule.php?module=vypera&op=do");
			break;
		case "do":
			if ($amount == "" || $amount == 0){
				output("`!Vypera `3flashes a caustic grin, and traces a finger down her jawline.");
				if (get_module_setting("charge")) output("\"`#This service may cost approximately `^1`#-`^3 `#gold per `^EXP`# point gained.`3\"");
				rawoutput("<form action='runmodule.php?module=vypera&op=do' method='POST'>");
                output("`3How many turns are you willing to give to `!Vypera`3?`0");
				rawoutput("<select name='turns'>");
				for ($i = 0; $i < $session['user']['turns']; $i++){
					rawoutput("<option value='$i'>$i</option>");
				}
				rawoutput("</select>");
                rawoutput("<input type='submit' class='button' value='".translate_inline("Trade")."'>");
                rawoutput("</form>");
			}else{
				if ($amount > $session['user']['turns']){
					output("`3\"`#How dare you try to fool me...`3\"");
					output("She strikes forth at you, to which you dodge, and scamper from the room.");
					output("\"`#Don't ever darken my doorstep again, unless you have the proper amount of energy in you...`3\"");
				}else{
					$sql = "SELECT creatureexp FROM ".db_prefix("creatures")." WHERE creaturelevel = ".$session['user']['level']." ORDER BY RAND(".e_rand().") LIMIT 1";
					$res = db_query($sql);
					$row = db_fetch_assoc($res);
					$exp = $row['creatureexp'];
					$amnt = abs($amount);
					$expgain = round(($exp*$m)*$amnt);
					$cost = $expgain*(e_rand(1,3));
					if (get_module_setting("charge")){
						if ($session['user']['gold'] >= $cost){
							$session['user']['turns']-=$amnt;
							$session['user']['experience']+=$expgain;
							$session['user']['gold']-=$cost;
							output("`!Vypera `3smiles at you, \"`#You have gained `^%s `#Experience.",$expgain);
							output("I am quite glad, that you sought me out...`3\"");
							debuglog("lost $amnt turns and $gold gold for $expgain experience at Vyperas");
						}else{
							output("`3\"`#I am sorry... but you do not have the needed amount of gold for this transaction to take place.");
							output("Please come back when you have `^%s Gold`#.`3\"",$cost);
						}
					}else{
						$session['user']['turns']-=$amnt;
						$session['user']['experience']+=$expgain;
						output("`!Vypera `3smiles at you, \"`#You have gained `^%s `#Experience.",$expgain);
						output("I am quite glad, that you sought me out...`3\"");
						debuglog("lost $amnt turns for $expgain experience at Vyperas");				
					}
				}
			}
			addnav("","runmodule.php?module=vypera&op=do");
			break;
	}
	villagenav();
page_footer();
}
?>