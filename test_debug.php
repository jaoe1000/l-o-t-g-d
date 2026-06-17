<?php
require_once("common.php");
require_once("lib/http.php");

page_header("Translator Debug");

output("`b`^Translator Debugging Script`b`n`n");

$tradeinvalue = round(($session['user']['weaponvalue']*.75),0);
$basetext=array(
	"title"			=>	"MightyE's Weapons",
	"desc"			=>	array(
		"`!MightyE `7stands behind a counter and appears to pay little attention to you as you enter, but you know from experience that he has his eye on every move you make.",
		array("He may be a humble weapons merchant, but he still carries himself with the grace of a man who has used his weapons to kill mightier %s than you.`n`n",translate_inline($session['user']['sex']?"women":"men")),
		"The massive hilt of a claymore protrudes above his shoulder; its gleam in the torch light not much brighter than the gleam off of `!MightyE's`7 bald forehead, kept shaved mostly as a strategic advantage, but in no small part because nature insisted that some level of baldness was necessary.`n`n",
		"`!MightyE`7 finally nods to you, stroking his goatee and looking like he wished he could have an opportunity to use one of these weapons.",
	),
	"tradein"		=>	array(
		"`7You stroll up the counter and try your best to look like you know what most of these contraptions do.",
		array("`!MightyE`7 looks at you and says, \"`#I'll give you `^%s`# trade-in value for your `5%s`#.",$tradeinvalue,$session['user']['weapon']),
		"Just click on the weapon you wish to buy, what ever 'click' means`7,\" and looks utterly confused.",
		"He stands there a few seconds, snapping his fingers and wondering if that is what is meant by \"click,\" before returning to his work: standing there and looking good.`n`n",
	),
	"nosuchweapon"	=>	"`!MightyE`7 looks at you, confused for a second, then realizes that you've apparently taken one too many bonks on the head, and nods and smiles.",
	"tryagain"		=>	"Try again?",
	"notenoughgold"	=>	"Waiting until `!MightyE`7 looks away, you reach carefully for the `5%s`7, which you silently remove from the rack upon which it sits...",
	"payweapon"		=>	"`!MightyE`7 takes your `5%s`7 and promptly puts a price on it...",
);

$schemas = array(
	"title"=>"weapon",
	"desc"=>"weapon",
	"tradein"=>"weapon",
	"nosuchweapon"=>"weapon",
	"tryagain"=>"weapon",
	"notenoughgold"=>"weapon",
	"payweapon"=>"weapon",
);
$basetext['schemas'] = $schemas;

output("`bRunning modulehook('weaponstext')...`b`n");
$texts = modulehook("weaponstext", $basetext);

output("`bResulting texts structure:`b`n");
rawoutput("<pre>" . htmlspecialchars(print_r($texts['tradein'], true)) . "</pre>");

output("`bTesting sprintf_translate on each tradein element:`b`n");
foreach ($texts['tradein'] as $key => $description) {
    output("Testing index %s:`n", $key);
    rawoutput("Input: <pre>" . htmlspecialchars(print_r($description, true)) . "</pre>");
    try {
        $res = sprintf_translate($description);
        output("`@Success: %s`n`n", $res);
    } catch (Throwable $e) {
        output("`\$FAIL: " . $e->getMessage() . " at line " . $e->getLine() . " in " . $e->getFile() . "`n`n");
    }
}

addnav("Return to the Village", "village.php");
page_footer();
?>
