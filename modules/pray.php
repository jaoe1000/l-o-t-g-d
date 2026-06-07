<?php

function pray_getmoduleinfo(){
	$info = array(
		"name"=>"Praying to Ramius",
		"author"=>"Steve McCorry<br>Cleaned by: Chris Vorndran",
		"version"=>"0.1",
		"category"=>"Graveyard Specials",
		"download"=>"http://dragonprime.net/users/Selekta/pray.zip",
		"settings"=>array(
			"Pray Settings, title",
			"favor"=>"How much Favor does a player get for praying to Ramius?,int|10",
	),
);
return $info;
}
function pray_install(){
	module_addeventhook("graveyard","return 100;");
		return true;
}
function pray_uninstall(){
	return true;
}
function pray_runevent($type){
	global $session;
	$from = "graveyard.php?";
	$session['user']['specialinc'] = "";
	$op = httpget("op");
	$favor = get_module_setting("favor");

	if ($op == "" || $op == "search"){
	$session['user']['specialinc'] = "module:pray";
		output("`)You are tormenting souls in the graveyard, when you decide to take a break. you unfold a blanket, and lay it down on the ground. You've been"); 		
		output("`)down here for a while, you think that a little prayer to `4Ramius`) might gain you some favor in his eyes.");
		addnav("Pray to Ramius", $from. "op=pray");
		addnav("Be on Your merry way", $from. "op=leave");
	}elseif($op == "pray"){
		output("`)You fold your legs Indian style on your blanket. Uou slip into a deep trance, and establish communication with `4Ramius");
		output("`)Your eye roll back into your head, as you speak in a strange, and unfamiliar tongue.");
		output("`)Suimar, oh relur fo eht Dlrowrednu. Ah yarp ot eeht. Tnarg em tahw ah evresed.`n");
		output("`4Ramius`) responds: `^As you wish, warrior.");
		switch (e_rand(1,3)){
			case 1:
			case 2:
				output("`n`)Ramius is very pleased with you. You gain `^%s `)Favor!",$favor);
				$session['user']['deathpower']+=$favor;
				break;
			case 3:
			if ($session['user']['deathpower'] > $favor){
					output("`n`)Ramius is very displeased with you. You lose `^%s `)Favor!",$favor);
					$session['user']['deathpower']-=$favor;
				}else{
					output("`n`)Ramius hurls you to the ground, and spits on you, \"`4You think that you are worthy enough, to have me take your favor?`)\"");
					output("Ramius turns back to you, \"`4I think not...tool!`)\" and proceeds to rend your favor from your soul.");
					$session['user']['deathpower']=0;
				}
				break;
		}
	}elseif($op == "leave"){
		output("`)You decide that you don't have time for a break, and that you won't be doing any praying to `4Ramius`) today.");
	}
}
?>