<?php

function vampirelord_getmoduleinfo(){

return (array(
	"name"=>"Vampire Lord",
	"author"=>"genmac",
	"version"=>"1.0",
	"category"=>"Forest Specials",
	"download"=>"http://dualtranslations.org/files/vampirelord.zip"
	));

}

function vampirelord_install(){

	module_addeventhook("forest","return 100;");
	return true;
}

function vampirelord_uninstall(){
	return true;
}

function vampirelord_runevent($type){

	global $session;
	$from = "forest.php?";
	$session['user']['specialinc'] = "module:vampirelord";

	$op = httpget('op');

	switch($op){
	case "beg":
		$what = httpget('what');
		switch($what){
		case "vit":
			$what_str="vitality";
			break;
		case "str":
			$what_str="strength";
			break;
		case "cash":
			$what_str="cash";
			break;
		}
		output("`7You decide to chance the vampire's generosity, knowing full well that nothing is free.  You bow until your head scrapes the floor, and beg him for just a little ".$what_str.".`n`n");
	
	output("The vampire grins and casually applies his teeth to your neck.`n`n");

	output("You feel drained and more powerful at the same time - but mostly drained.`n`n");

	switch($what){
	case "str":
		$reward = $session['user']['level']/2 + 1;
		$session['user']['attack'] += $reward;
		break;
	case "vit":
		$reward = $session['user']['level']/2 + 1;
		$session['user']['defence'] += $reward;
		break;
	case "cash":
		$reward = $session['user']['level']*(100*$session['user']['level']);
		$session['user']['gold'] += $reward;		
		break;
	}

	$loss = $session['user']['level']*1.5;

	$session['user']['maxhitpoints'] -= $loss;
	set_module_pref("visited","yes");

	output("You gain ".$reward." ".$what_str."!`n");
	output("You lose ".$loss." hitpoints!`n");

	addnav("Run Screaming",$from."op=run");
	break;
	case "enter":
	if(get_module_pref("visited") == "yes"){
	output("`7This path seems sickeningly familiar.  You decide it would be best to leave.");
	addnav("Turn Away",$from."op=leave");
	}else{
		output("`7After a dreary while truding the foreboding path, you finally approach a castle, which appears to be constructed of the darkest material imaginable.`n`nYou enter the castle.`n`n");
		output("You realize that you are in the presence of a vampire lord, sometime around when he puts his clawed hands on your shoulders.");

		addnav("Beg for Strength",$from."op=beg&what=str");
		addnav("Beg for Vitality",$from."op=beg&what=vit");
		addnav("Beg for Wealth",$from."op=beg&what=cash");
		addnav("Run Screaming",$from."op=run");
	}
	break;
	case "run":
	$session['user']['specialinc']="";
	output("`7You run from the vampire, screaming like a little girl.");
	break;
	case "leave":
	$session['user']['specialinc'] = "";
	output("`7You turn away, your heart lightening as you return to frolic with the merry woodland creatures.");
	break;
	default:
		output("`7Whilst merrily wandering the forest, you stumble upon a grim path, leading into a darkness that instills foreboding in your very soul.  The inner explorer urges you forth, but your common sense says to turn away.");

	addnav("F?ollow the Path",$from."op=enter");
	addnav("T?urn Away",$from."op=leave");
	}

}

?>
