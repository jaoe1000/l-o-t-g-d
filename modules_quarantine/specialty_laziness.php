<?php
function specialty_laziness_getmoduleinfo(){
$info = array(
"name" => "Specialty - Laziness",
"author" => "Lonny Luberts - Built with Module Builder by `3Lonny Luberts`0",
"version" => "1.0",
"download" => "http://www.pqcomp.com/modules/mydownloads/visit.php?cid=3&lid=176",
"description"=>"Lazienss Specialty",
"vertxtloc"=>"http://www.pqcomp.com/",
"category" => "Specialties",
"settings"=> array(
"Specialty - Laziness Settings,title",
"mindk"=>"How many DKs do you need before the specialty is available?,int|10",
"cost"=>"How many points do you need before the specialty is available?,int|0",
),
"prefs" => array(
"Specialty - Laziness Skills User Prefs,title",
"skill"=>"Skill points in Laziness Skills,int|0",
"uses"=>"Uses of Laziness Skills allowed,int|0",
),
);
return $info;
}

function specialty_laziness_install(){
$specialty="lazy";
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

function specialty_laziness_uninstall(){
$sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='lazy'";
db_query($sql);
return true;
}

function specialty_laziness_dohook($hookname,$args){
global $session,$resline;
tlschema("fightnav");

$spec = "lazy";
$name = "Laziness";
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
apply_buff('lazy1', array(
"startmsg"=>"You wait around for something to happen",
"name"=>"Wait",
"rounds"=>"2",
"wearoff"=>"You get tired of waiting",
"defmod"=>"1.4",
"effectmsg"=>"You wonder if something will happen",
"maxbadguydamage"=>round($session['user']['level']/2,0)+10,
"schema"=>"specialty_laziness"
));
break;
case 2:
apply_buff('lazy2', array(
"startmsg"=>"You con someone else into doing your dirty work for you.",
"name"=>"Con",
"rounds"=>"4",
"wearoff"=>"This person gets tired of doing your dirty work.",
"atkmod"=>"1.4",
"effectmsg"=>"Someone else hits your opponent for you.",
"maxbadguydamage"=>round($session['user']['level']/2,0)+20,
"schema"=>"specialty_laziness"
));
break;
case 3:
apply_buff('lazy3', array(
"startmsg"=>"You slip off into your own little imaginary world",
"name"=>"Daydream",
"rounds"=>"6",
"wearoff"=>"You snap out of your trance",
"atkmod"=>"1.5",
"defmod"=>"1.5",
"effectmsg"=>"You dream of fighting your enemy",
"maxbadguydamage"=>round($session['user']['level']/2,0)+25,
"schema"=>"specialty_laziness"
));
break;
case 5:
apply_buff('lazy5', array(
"startmsg"=>"You think of good reasons not to fight your enemy.",
"name"=>"Make Excuses",
"rounds"=>"8",
"wearoff"=>"You run out of good excuses.",
"defmod"=>"2",
"effectmsg"=>"You make a lame excuse and confuse your opponent.",
"maxbadguydamage"=>round($session['user']['level']/2,0)+30,
"schema"=>"specialty_laziness"
));
break;
}
set_module_pref("uses", get_module_pref("uses") - $l);
}else{
apply_buff('lazy0', array(
"startmsg"=>"Your attempt to use a skill failed!",
"rounds"=>1,
"schema"=>"specialty_laziness"
));
}
}
break;
case "castlelib":
if ($op69 == 'tactics'){
output("You sit down and open up How to do Nothing..`n");
output("You read for a while... in the time it takes you to read you use up`n");
output("3 Turns.`n`n");
output("You are now better at doing nothing at all... It\'s amazing you even took the time to read.`n");
$session['user']['turns']-=3;
set_module_pref('skill',(get_module_pref('skill','specialty_laziness') + 1),'specialty_laziness');
set_module_pref('uses', get_module_pref("uses",'specialty_laziness') + 1,'specialty_laziness');
addnav("Continue","runmodule.php?module=lonnycastle&op=library");
}
break;
case "castlelibbook":
output("How to do Nothing.. (3 Turns)`n");
addnav("Read a Book");
addnav("How to do Nothing.","runmodule.php?module=lonnycastle&op=library&op69=laziness");
break;

case "choose-specialty":
if ($session['user']['dragonkills']>=get_module_setting("mindk")) {
if ($session['user']['specialty'] == "" ||
$session['user']['specialty'] == '0') {
addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline");
$t1 = translate_inline("As a youth you recall lying about doing asolutely nothing.");
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
addnav(array("%s &#149 Wait`7 (%s)`0", $ccode, 1),
$script."op=fight&skill=$spec&l=1", true);
}
if ($uses > 1) {
addnav(array("%s &#149 Con`7 (%s)`0", $ccode, 2),
$script."op=fight&skill=$spec&l=2",true);
}
if ($uses > 2) {
addnav(array("%s &#149 Daydream`7 (%s)`0", $ccode, 3),
$script."op=fight&skill=$spec&l=3",true);
}
if ($uses > 4) {
addnav(array("%s &#149 Make Excuses`7 (%s)`0", $ccode, 5),
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
$str = translate("The Laziness Specialty is availiable upon reaching %s Dragon Kills and %s points.");
$str = sprintf($str, get_module_setting("mindk"),$cost);
}
output($format, $str, true);
break;

case "set-specialty":
if($session['user']['specialty'] == $spec) {
page_header($name);
$session['user']['donationspent'] = $session['user']['donationspent'] + $cost;
output("`7You have chozen to live a life of laziness. ");
}
break;

case "specialtycolor":
$args[$spec] = $ccode;
break;

case "specialtymodules":
$args[$spec] = "specialty_laziness";
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

function specialty_laziness_run(){
}

?> 