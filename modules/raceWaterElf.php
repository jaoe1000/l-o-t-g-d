<?php
function raceWaterElf_getmoduleinfo(){
$info = array(
"name"=>"Race - Water Elf",
"version"=>"1.0",
"author"=>"`%Admin Prince `&Setain Lornona Arthia`0 - Built with Module Builder by `3Lonny Luberts`0",
"category"=>"Races",
"download"=>"http://dragonprime.net/Setain/raceWaterElf.zip",
"settings"=>array(
"Water Elf Race Settings,title",
"minedeathchance"=>"Percent chance for Water Elfs to die in the mine,range,0,100,1|40",
"goldgemchance"=>"Percent chance for Water Elfs to find a gem on battle victory,range,0,100,1|5",
"goldgemmessage"=>"Message to display when finding a gem|`&Your Water Elf senses tingle, and you notice a `%gem`&!",
"goldloss"=>"How much less gold (in percent) do the Water Elfs find?,range,0,100,1|15",
"mindk"=>"How many DKs do you need before the race is available?,int|0",
),
);
return $info;
}

function raceWaterElf_install(){
if (!is_module_installed("raceElf")) {
output("The Water Elf choose to live with Elfs. You must install that race module.");
return false;
}

module_addhook("chooserace");
module_addhook("setrace");
module_addhook("newday");
module_addhook("charstats");
module_addhook("raceminedeath");
module_addhook("battle-victory");
module_addhook("creatureencounter");
module_addhook("pvpadjust");
module_addhook("adjuststats");
module_addhook("racenames");
return true;
}

function raceWaterElf_uninstall(){
global $session;
$sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Water Elf'";
db_query($sql);
if ($session['user']['race'] == 'Water Elf')
$session['user']['race'] = RACE_UNKNOWN;
return true;
}

function raceWaterElf_dohook($hookname,$args){
global $session,$resline;

if (is_module_active("raceElf")) {
$city = get_module_setting("villagename", "raceElf");
} else {
$city = getsetting("villagename", LOCATION_FIELDS);
}
$race = "Water Elf";
switch($hookname){
case "racenames":
$args[$race] = $race;
break;
case "pvpadjust":
if ($args['race'] == $race) {
$args['creaturedefense']+=(4+floor($args['creaturelevel']/5));
}
break;
case"adjuststats":
if ($args['race'] == $race) {
$args['defense'] += (2+floor($args['level']/5));
$args['maxhitpoints'] -= round($args['maxhitpoints']*.05, 0);
}
break;
case "raceminedeath":
if ($session['user']['race'] == $race) {
$args['chance'] = get_module_setting("minedeathchance");
$args['racesave'] = "Fortunately your Water Elf skills let you escape unscathed.`n";
$args['schema']="module-raceWaterElf";
}
break;
case "charstats":
if ($session['user']['race']==$race){
addcharstat("Vital Info");
addcharstat("Race", translate_inline($race));
}
break;
case "chooserace":
if ($session['user']['dragonkills'] < get_module_setting("mindk"))
break;
output("In the city of %s, your race of `5Water Elfs`0, ", true);
addnav("`5Water Elf`0","newday.php?setrace=$race$resline");
addnav("","newday.php?setrace=$race$resline");
break;
case "setrace":
if ($session['user']['race']==$race){
output("`&As a Water Elf, your amazing reflexes allow you to respond very quickly to any attacks.`n");
output("You gain extra defense!`n");
output("`n");
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
case "battle-victory":
if ($session['user']['race'] != $race) break;
if ($args['type'] != "forest") break;
if ($session['user']['level'] < 15 &&
e_rand(1,100) <= get_module_setting("goldgemchance")) {
output(get_module_setting("goldgemmessage")."`n`0");
$session['user']['gems']++;
//debuglog("found a gem when slaying a monster, for being a Water Elf.");
}
break;

case "creatureencounter":
if ($session['user']['race']==$race){

raceWaterElf_checkcity();
$loss = (100 - get_module_setting("goldloss"))/100;
$args['creaturegold']=round($args['creaturegold']*$loss,0);
}
break;
case "newday":
if ($session['user']['race']==$race){
raceWaterElf_checkcity();
apply_buff("racialbenefit",array(
"name"=>"`@Water Shield`0",
"defmod"=>"(<defense>?(1+((4+floor(<level>/5))/<defense>)):0)",
"badguydmgmod"=>1.05,
"allowinpvp"=>1,
"allowintrain"=>1,
"rounds"=>-1,
"schema"=>"module-raceWaterElf",
)
);
}
break;
}

return $args;
}

function raceWaterElf_checkcity(){
global $session;
$race="Water Elf";
if (is_module_active("raceElf")) {
$city = get_module_setting("villagename", "raceElf");
} else {
$city = getsetting("villagename", LOCATION_FIELDS);
}

if ($session['user']['race']==$race && is_module_active("cities")){
if (get_module_pref("homecity","cities")!=$city){
set_module_pref("homecity",$city,"cities");
}
}
return true;
}

function raceWaterElf_run(){
}
