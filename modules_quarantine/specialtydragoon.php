<?php
/******************************************
 Name: Specialty - Dragoon
 Ver: 1.0   Date: 05-28-06
 By: TheGreatOrator
******************************************/

function specialtydragoon_getmoduleinfo(){
		$info = array(
				"name" => "Specialty - Dragoon Specialty",
				"author" => "`%The Great `&Orator`0",
				"version" => "1.0",
				"download" => "specialtydragoon.zip",
				"category" => "Specialties",
				"settings"=> array(
						"Specialty - Dragoon Specialty Settings,title",
						"mindk"=>"How many DKs do you need before the specialty is available?,int|0",
						"cost"=>"How many points do you need before the specialty is available?,int|0",
				),
						"prefs" => array(
								"Specialty - Dragoon Specialty Skills User Prefs,title",
								"skill"=>"Skill points in Dragoon Specialty Skills,int|0",
								"uses"=>"Uses of Dragoon Specialty Skills allowed,int|0",
				),
		);
		return $info;
}

function specialtydragoon_install(){
$specialty="dgr";
module_addhook("apply-specialties");
module_addhook("castlelib");
module_addhook("castlelibbook");
module_addhook("choose-specialty");
module_addhook("dragonkill");
module_addhook("fightnav-specialties");
module_addhook("incrementspecialty");
module_addhook("newday");
module_addhook("pointsdesc");
module_addhook("set-specialty");
module_addhook("specialtycolor");
module_addhook("specialtymodules");
module_addhook("specialtynames");
return true;
}

function specialtydragoon_uninstall(){
$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='dgr'";
db_query($sql);
return true;
}

function specialtydragoon_dohook($hookname,$args){
global $session,$resline;
tlschema("fightnav");

$spec = "dgr";
$name = "Dragoon Specialty";
$ccode = "`q";
$cost = get_module_setting("cost");
$op69 = httpget('op69');

switch ($hookname) {

case "apply-specialties":
$skill = httpget('skill');
$l = httpget('l');
if ($skill==$spec){
if (get_module_pref("uses") >= $l){
switch($l){
case 1:
apply_buff('dgr1', array(
"startmsg"=>"You call upon the warrior spirits",
"name"=>"Dragoon Heal",
"rounds"=>"5",
"wearoff"=>"The spirits have gone back to rest.",
"regen"=>$session['user']['level']+5,
"roundmsg"=>"The spirits grant you healing!",
"effectmsg"=>"The spirits grant you healing!",
"effectnodmg"=>"The Spirits have ignored your plea.",
"effectfailmsg"=>"The spirits tried to heal you, but they had no effect.",
"schema"=>"specialtydragoon"
));
break;
case 2:
apply_buff('dgr2', array(
"startmsg"=>"You Concentrate on dodging {badguy}.",
"name"=>"Dragoon Dodge",
"rounds"=>"5",
"wearoff"=>"Your concentration has been broken.",
"defmod"=>"1.1",
"roundmsg"=>"Your perceptions allow you to see the attack coming.",
"effectmsg"=>"Your perceptions allow you to see the attack coming.",
"effectnodmg"=>"You just did not concentrate enough.",
"effectfailmsg"=>"You could not focus.",
"schema"=>"specialtydragoon"
));
break;
case 3:
apply_buff('dgr3', array(
"startmsg"=>"You let out a viscous war cry!",
"name"=>"Dragoon Attack",
"rounds"=>"5",
"wearoff"=>"The adrenaline rush is over.",
"atkmod"=>"1.1",
"roundmsg"=>"Your adrenaline rushes to your muscles.",
"effectmsg"=>"Your adrenaline rushes to your muscles.",
"effectnodmg"=>"Despite the strength boost, you still could not get a solid hit.",
"effectfailmsg"=>"You just did not have a strong grip on your {weapon} at all.",
"schema"=>"specialtydragoon"
));
break;
case 5:
apply_buff('dgr5', array(
"startmsg"=>"You unleash the fury you plan to use on the `@Green Dragon`0.",
"name"=>"Dragoon Fury",
"rounds"=>"10",
"wearoff"=>"You are too tired to stay mad.",
"damageshield"=>"1.1",
"regen"=>$session['user']['level']+5,
"atkmod"=>"2",
"roundmsg"=>"With a full fury you begin to attak {badguy} like it was the `@Green Dragon`0.",
"effectmsg"=>"With a full fury you begin to attak {badguy} like it was the `@Green Dragon`0.",
"effectnodmg"=>"Your fury blinded you completely.",
"effectfailmsg"=>"The rage is over...you realize you are only fighting {badguy}.",
"schema"=>"specialtydragoon"
));
break;
}
set_module_pref("uses", get_module_pref("uses") - $l);
}else{
apply_buff('dgr0', array(
"startmsg"=>"Your attempt to use a skill failed!",
"rounds"=>1,
"schema"=>"specialtydragoon"
));
}
}
break;
case "castlelib":
if ($op69 == 'tactics'){
output("You sit down and open up .`n");
output("You read for a while... in the time it takes you to read you use up`n");
output("3 Turns.`n`n");
output("`n");
$session['user']['turns']-=3;
set_module_pref('skill',(get_module_pref('skill','specialtydragoon') + 1),'specialtydragoon');
set_module_pref('uses', get_module_pref("uses",'specialtydragoon') + 1,'specialtydragoon');
addnav("Continue","runmodule.php?module=lonnycastle&op=library");
}
break;
case "castlelibbook":
output(". (3 Turns)`n");
addnav("Read a Book");
addnav("","runmodule.php?module=lonnycastle&op=library&op69=Dragoon Specialty");
break;

case "choose-specialty":
if ($session['user']['dragonkills']>=get_module_setting("mindk")) {
if ($session['user']['specialty'] == "" ||
$session['user']['specialty'] == '0') {
addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline");
				$t1 = translate_inline("You longed for the day to be able to fight the legendary Green Dragon.");
				$t2 = appoencode(translate_inline("$ccode$name`0"));
				rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
				addnav("","newday.php?setspecialty=$spec$resline");
}
}
break;
case "dragonkill":
set_module_pref("uses", 0);
set_module_pref("skill", 0);
break;
case "fightnav-specialties":
$uses = get_module_pref("uses");
$script = $args['script'];
if ($uses > 0) {
addnav(array("%s%s (%s points)`0", $ccode, translate_inline($name), $uses), "");
addnav(array("%s &#149; Dragoon Heal`7 (%s)`0", $ccode, 1),
$script."op=fight&skill=$spec&l=1", true);
}
if ($uses > 1) {
addnav(array("%s &#149; Dragoon Dodge`7 (%s)`0", $ccode, 2),
$script."op=fight&skill=$spec&l=2",true);
}
if ($uses > 2) {
addnav(array("%s &#149; Dragoon Attack`7 (%s)`0", $ccode, 3),
$script."op=fight&skill=$spec&l=3",true);
}
if ($uses > 4) {
addnav(array("%s &#149; Dragoon Fury`7 (%s)`0", $ccode, 5),
$script."op=fight&skill=$spec&l=5",true);
}
break;

case "incrementspecialty":
if($session['user']['specialty'] == $spec) {
$new = get_module_pref("skill") + 1;
set_module_pref("skill", $new);
$c = $args['color'];
$name=translate_inline($name);
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

case "newday":
$bonus = getsetting("specialtybonus", 1);
if($session['user']['specialty'] == $spec) {
$name=translate_inline($name);
if ($bonus == 1) {
output("`n`2For being interested in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name,$ccode,$name);
} else {
output("`n`2For being interested in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n",$ccode,$name,$bonus,$ccode,$name);
}
}
$amt = (int)(get_module_pref("skill") / 3);
if ($session['user']['specialty'] == $spec) $amt++;
set_module_pref("uses", $amt);
break;

case "pointsdesc":
$cost = get_module_setting("cost");
if ($cost > 0){
$args['count']++;
$format = $args['format'];
$str = translate("The Dragoon Specialty Specialty is availiable upon reaching %s Dragon Kills and %s points.");
$str = sprintf($str, get_module_setting("mindk"),$cost);
}
output($format, $str, true);
break;

case "set-specialty":
if($session['user']['specialty'] == $spec) {
page_header($name);
$session['user']['donationspent'] = $session['user']['donationspent'] + $cost;
output("`7Finally your chance has come to defeat the all-powerful Green Dragon! ");
}
break;

case "specialtycolor":
$args[$spec] = $ccode;
break;

case "specialtymodules":
$args[$spec] = "specialtydragoon";
break;

case "specialtynames":
$pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
if ($session['user']['superuser'] & SU_EDIT_USERS || $session['user']['dragonkills'] >= get_module_setting("mindk") || get_module_setting("cost") <= $pointsavailable){
$args[$spec] = translate_inline($name);
}
break;

}
return $args;
}

function specialtydragoon_run(){
}

?>