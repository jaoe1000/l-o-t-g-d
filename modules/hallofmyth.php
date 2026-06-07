<?php
// addnews ready
// mail ready
// translator ready
// Hall of Myths, by Zachery Delafosse
// A place that randomly appears in the forest
// Depending on the user's actions, and random factors, the user can:
// Increase his money
// Increase his exp by 5 to 10 %
// Lose all his fights for the day
// Die and gain a lvl 16 to 20 sword, randomly named.
// Die.

require_once("lib/http.php");

function hallofmyth_getmoduleinfo(){
	$info = array(
		"name"=>"Hall of Myths",
		"author"=>"Zachery Delafosse",
		"version"=>"1.0",
		"category"=>"Forest Specials",
	);
	return $info;
}

function hallofmyth_install(){
	debug("Installing this module.`n");
	module_addeventhook("forest", "return 60;");
	return true;
}

function hallofmyth_uninstall(){
	output("Uninstalling this module.`n");
	return true;
}

function hallofmyth_runevent($type)
{
	global $session;
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:hallofmyth";
	page_header("Hall of Myths");
	$op = httpget('op');
	if($op==""){
		output("You enter the legendary Hall of Myths!`n");
		output("The marble walls house enumerable crystal statues of the ancient gods.`n");
		output("A holy aura produces melodic tones which sooth your soul.`n");
		output("At the end of the hall is a large alter...");
		addnav("Do you continue?");
		addnav("Yes", $from . "op=enter");
		addnav("No", $from . "op=leave");
	}
	if($op=="leave"){
		output("You run from the Hall of Myths and leave the gods in peace.");
	}
	if($op=="enter"){
		output("You walk up to the alter, feeling the presence of the gods guiding you in your choice...`n");
		output("You have a feeling the gods won't be happy if you give anything less than the best...`n");
		output("Of course, you don't need to offer anything.`n");
		output("Now, what will you do?");
		addnav("What will you do?");
		addnav("Pray", $from . "op=pray");
		addnav("Offer Gold", $from . "op=gold");
		addnav("Sacrafice Yourself", $from . "op=kill");
		addnav("Run Away", $from . "op=leave");
	}
	if($op=="kill"){
//		$from="graveyard.php?";
		if(rand(1,4)==1){
		$session['user']['attack']-=$session['user']['weapondmg'];
		$r2=rand(1,3);$r3=rand(1,3);
		$wname="";
		/*switch($r2){
		case 1: $wname.="`!Spirit"; break;
		case 2: $wname.="`&Angel"; break;
		case 3: $wname.="`5Demon"; break;
		}*/
		switch($r2){
		case 1: $wname.="Spirit"; break;
		case 2: $wname.="Angel"; break;
		case 3: $wname.="Demon"; break;
		}
		switch($r3){
		case 1: $wname.=" Slayer"; break;
		case 2: $wname.="s Bane"; break;
		case 3: $wname.="'s Sword"; break;
		}
		$session['user']['weapondmg'] = 16+rand(0,4);
		$session['user']['attack']+=$session['user']['weapondmg'];
		$session['user']['weaponvalue'] = 0;
		$oldw=$session['user']['weapon'];
		$session['user']['weapon'] = $wname;
		$session['user']['hitpoints']=$session['user']['maxhitpoints'];
		output("You lay yourself down on the alter and plunge your $oldw through your heart, giving yourself as a sacrafice to the gods.`n`n");
		output("The gods are pleased with your offering and restore your life, and grant you with an ancient weapon from the past wars fought by the gods.`n");
		output("You take up the sword and read the words engraved in its hilt:`n");
		output(" The $wname");
		addnews($session['user']['name']." has found the legendary $wname in the Hall of Myths!");
		}else{
		$session['user']['turns']=0;
		$session['user']['hitpoints']=1;
		$oldw=$session['user']['weapon'];
		output("You lay yourself down on the alter and plunge your $oldw through your heart, giving yourself as a sacrafice to the gods.`n`n");
		output("The gods no not accept you as an offering, as you draw yourself out of the Hall of Myths, barely alive...");
		}
	}
	if($op=="gold"){
		if($session['user']['gold']==0)
		output("You don't have any gold... you leave the Hall of Myths.");
		else{
			
			output("You place all your gold on the alter and wait...`n`n");
			$res=rand(1,3);
			if($res==1){
				$session['user']['gold']=0;
				output("The gods accept your offering... leaving you pennyless.");
			}
			if($res==2){
				$session['user']['gold']*=1.2;
				$session['user']['gold']=round($session['user']['gold'],0);
				output("The gods bless you and you feel your purse get amazingly heavy!`n");
				output("The gods have given you back your gold, and blessed you with some extra!");
				addnews($session['user']['name']." has blessed by the gods in the Hall of Myths!");
			}
			if($res==3){
				$gain=round($session['user']['gold']/(900+rand(0,200)),0);
				if($gain<2)$gain=2;
				$session['user']['gems']+=$gain;
				output("$gain gems form in the air before you... you grab them and place the holy gems into your purse.");
				addnews($session['user']['name']." has been given $gain gems from the gods in the Hall of Myths!");
			}
		}
	}
	if($op=="pray"){
		output("You kneel and pray to the gods...`n`n");
		$res=rand(1,3);
		
		if(rand(1,15)==1)//Some very unlucky person will not get a response...
		$res=0;
		
		if($res==0){//Lose all fights
		$session['user']['turns']=0;
		output("After several hours, the hollow hall becomes dark as night falls...`n");
		output("Your have lost all your forest fights waiting for a reply from the gods.");
		}
		if($res==1){//Gain many fights
		$fights=rand(5,15);
		$session['user']['turns']+=$fights;
		output("`&An aura surrounds you and you gain $fights forest fights!");
		}
		if($res==2){//Gain 5 to 10% exp
		$gain=round(($session['user']['experience']/100)*rand(5,10),0);
		$session['user']['experience']+=$gain;
		output("`&You begin to see visions of the great wars of old fought by the gods, and gain $gain experience.");
		addnews($session['user']['name']." has been trained by the gods Hall of Myths!");
		}
		if($res==3){//Refill HP to double the max
		$session['user']['hitpoints']=$session['user']['maxhitpoints']*2;
		output("`&You feel amazingly healthy, as never before!");
		}
	}
}
?>


