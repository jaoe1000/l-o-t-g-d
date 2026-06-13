<?php
function specialty_CHM_getmoduleinfo(){
$info = array(
"name" => "Specialty - Chaos Master",
"author" => "`%Admin Prince `&Setain Lornona Arthia`0 - Built with Module Builder by `3Lonny Luberts`0",
"version" => "1.0",
"download" => "http://dragonprime.net/Setain/specialty_CHM.zip",
"description"=>"",
"vertxtloc"=>"",
"category" => "Specialties",
"settings"=> array(
"Specialty - Chaos Master Settings,title",
"mindk"=>"How many DKs do you need before the specialty is available?,int|10",
"cost"=>"How many points do you need before the specialty is available?,int|0",
),
"prefs" => array(
"Specialty - Chaos Master Skills User Prefs,title",
"skill"=>"Skill points in Chaos Master Skills,int|0",
"uses"=>"Uses of Chaos Master Skills allowed,int|0",
),
);
return $info;
}

function specialty_CHM_install(){
$specialty="7";
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

function specialty_CHM_uninstall(){
$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='7'";
db_query($sql);
return true;
}

function specialty_CHM_dohook($hookname,$args){
global $session,$resline;
tlschema("fightnav");

$spec = "7";
$name = "Chaos Master";
$ccode = "`7";
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
apply_buff('71', array(
"startmsg"=>"Your fits become flames",
"name"=>"Flame Fists",
"rounds"=>"15",
"wearoff"=>"Your fists return to normal",
"damageshield"=>".25",
"atkmod"=>"1.5",
"defmod"=>"1.2",
"minioncount"=>round($session['user']['level']/2)+2,
"schema"=>"specialty_CHM"
));
break;
case 2:
apply_buff('72', array(
"startmsg"=>"Tour skin dull and hardness to a rock state",
"name"=>"Rock Armor",
"rounds"=>"15",
"damageshield"=>".5",
"atkmod"=>".8",
"defmod"=>"2",
"schema"=>"specialty_CHM"
));
break;
case 3:
apply_buff('73', array(
"startmsg"=>"Your begin to pull strength from nature",
"name"=>"Absorb",
"rounds"=>"15",
"wearoff"=>"Your connection to your environment fades",
"regen"=>$session['user']['level']+10,
"minioncount"=>round($session['user']['level']/2)+2,
"schema"=>"specialty_CHM"
));
break;
case 5:
apply_buff('75', array(
"startmsg"=>"Your unleash all your souls power.",
"name"=>"Unleash",
"rounds"=>"1",
"wearoff"=>"Your soul power is exhausted",
"damageshield"=>".5",
"regen"=>$session['user']['level']+-60,
"atkmod"=>"3",
"defmod"=>".8",
"minioncount"=>round($session['user']['level']/2)+8,
"schema"=>"specialty_CHM"
));
break;
}
set_module_pref("uses", get_module_pref("uses") - $l);
}else{
apply_buff('70', array(
"startmsg"=>"Your attempt to use a skill failed!",
"rounds"=>1,
"schema"=>"specialty_CHM"
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
set_module_pref('skill',(get_module_pref('skill','specialty_CHM') + 1),'specialty_CHM');
set_module_pref('uses', get_module_pref("uses",'specialty_CHM') + 1,'specialty_CHM');
addnav("Continue","runmodule.php?module=lonnycastle&op=library");
}
break;
case "castlelibbook":
output(". (3 Turns)`n");
addnav("Read a Book");
addnav("","runmodule.php?module=lonnycastle&op=library&op69=CHM");
break;

case "choose-specialty":
if ($session['user']['dragonkills']>=get_module_setting("mindk")) {
if ($session['user']['specialty'] == "" ||
$session['user']['specialty'] == '0') {
addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline");
$t1 = translate_inline("You remeber being facinated with the elemental powers of your soul energy.");
$t2 = appoencode(translate_inline("$ccode$name`0"));
rawoutput("$t1 ($t2)
");
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
addnav(array("%s &#149; Flame Fists`7 (%s)`0", $ccode, 1),
$script."op=fight&skill=$spec&l=1", true);
}
if ($uses > 1) {
addnav(array("%s &#149; Rock Aromor`7 (%s)`0", $ccode, 2),
$script."op=fight&skill=$spec&l=2",true);
}
if ($uses > 2) {
addnav(array("%s &#149; Abosrb`7 (%s)`0", $ccode, 3),
$script."op=fight&skill=$spec&l=3",true);
}
if ($uses > 4) {
addnav(array("%s &#149; Unleash`7 (%s)`0", $ccode, 5),
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
$str = translate("The Chaos Master Specialty is availiable upon reaching %s Dragon Kills and %s points.");
$str = sprintf($str, get_module_setting("mindk"),$cost);
}
output($format, $str, true);
break;

case "set-specialty":
if($session['user']['specialty'] == $spec) {
page_header($name);
$session['user']['donationspent'] = $session['user']['donationspent'] + $cost;
output("`7 ");
}
break;

case "specialtycolor":
$args[$spec] = $ccode;
break;

case "specialtymodules":
$args[$spec] = "specialty_CHM";
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

function specialty_CHM_run(){
}

T?>