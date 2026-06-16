<?php
// translator ready
// addnews ready

function treeoflife_getmoduleinfo(){
	$info = array(
		"name"=>"The Tree of Life",
		"version"=>"1.3",
		"author"=>"DeathDragon, Odyssey, modernized by Antigravity",
		"category"=>"Gardens",
		"download"=>"http://images.google.com/images?q=tree&ie=UTF-8&hl=en",
		"requires"=>array(),
		"settings"=>array(
			"The Tree of Life Settings,title",
			"maxpicks"=>"Max picks per day,int|3",
		),
		"prefs"=>array(
			"Tree of Life User Preferences,title",
			"treepick"=>"How many times picked today,int|0",
		)
	);
	return $info;
}

function treeoflife_install(){
	module_addhook("gardens");
	module_addhook("newday");
	return true;
}

function treeoflife_uninstall(){
	return true;
}

function treeoflife_dohook($hookname,$args){
	switch($hookname){
		case "newday":
			set_module_pref("treepick", 0);
			break;
		case "gardens":
			addnav("Gardens");
			addnav("Pick Object From Tree", "runmodule.php?module=treeoflife&op=enter");
			break;
	}
	return $args;
}

function treeoflife_run(){
	global $session;
	$op = httpget('op');
	$maxpicks = get_module_setting("maxpicks");
	$treepick = get_module_pref("treepick");

	page_header("The Tree of Life");
	output("`b`c`2The `@Tree `2of Life`c`b`n`n");

	switch($op){
		case "enter":
			output("`c`2While frolicing in the Garden, you notice a section where a ray of sun always seems to shine. Letting your curiosity get the best of you, you walk along the beaten path and find The `@Tree `2of Life. In awe of its beauty, you make a decision...`c");
			addnav("Options");
			addnav("Pick Object From Tree","runmodule.php?module=treeoflife&op=pickfruit");
			addnav("Back to the Garden","gardens.php");
			break;

		case "pickfruit":
			if ($treepick < $maxpicks){
				output("`n`n`^You decide to pick something from `2The `@Tree `2of Life, you find....`n`n");
				$rand1 = e_rand(1,20);
				switch ($rand1){
					case 1:
						output("`^that the Tree hasn't beared fruit yet!");
						break;
					case 2:
						output("`^ A small bag of gems!!");
						$session['user']['gems']+=7;
						$treepick++;
						set_module_pref("treepick", $treepick);
						break;
					case 3:
						output("`^ A Charm Fairy stuck between two branches. Always the one to help, you free the Fairy, and she is very much grateful. She waves her wand, and you notice that you look better.");
						$session['user']['charm']+=2;
						$treepick++;
						set_module_pref("treepick", $treepick);
						break;
					case 4:
						output("`^Nothing! You curse the birds, and head back to the Garden.");
						break;
					case 5:
						output("`^ A small bag of gold pieces!!");
						$session['user']['gold']+=200;
						$treepick++;
						set_module_pref("treepick", $treepick);
						break;
					case 6:
						output("`^ 2 Gems!!!");
						$session['user']['gems']+=2;
						$treepick++;
						set_module_pref("treepick", $treepick);
						break;
					case 7:
						output("`^Some rotten fruit falls from the tree. Your hunger overcomes your wisdom, and so you decide to take a bite of the tainted fruit. Just as you start to walk back, you feel a sharp pain and then you fall to the ground. The scenery in your eyes starts to turn to black, and you begin to see the souls of past warriors. You begin to realize that you are dead!!!");
						$session['user']['alive']=0;
						$session['user']['hitpoints']=0;
						addnews("%s`5 died from some tainted fruit, at `2The `@Tree `2of Life", $session['user']['name']);
						addnav("The Shades","shades.php");
						break;
					case 8:
						output("`^ Nothing! You curse the squirrels, and head back to the Garden.");
						break;
					case 9:
						output("`^ 3 Gems!!!");
						$session['user']['gems']+=3;
						$treepick++;
						set_module_pref("treepick", $treepick);
						break;
					case 10:
						output("`^ While reaching into the Tree, you feel something slither across your hand. Determined to find something, you keep on feeling around. Suddenly, you are face to face with a giant snake! That is the last thing you remember...");
						$session['user']['alive']=0;
						$session['user']['hitpoints']=0;
						addnews("%s`5 was bitten and killed by a snake, at `2The `@Tree `2of Life", $session['user']['name']);
						addnav("The Shades","shades.php");
						break;
					case 11:
						output("`^ Nothing! You curse the snakes, and head back to the Garden.");
						break;
					case 12:
						output("`^ 2 Gems!!!");
						$session['user']['gems']+=2;
						$treepick++;
						set_module_pref("treepick", $treepick);
						break;
					case 13:
						output("`^that The `@Tree `^decides to bless you while you fight! You got a buff!");
						apply_buff('treeoflife_blessing', array(
							"name"=>"`2The Blessing of the `@Tree",
							"rounds"=>10,
							"wearoff"=>"`2The `@Tree `2has helped you enough.",
							"defmod"=>1,
							"atkmod"=>3,
							"roundmsg"=>"`2The `@Tree `2gives you its blessing!",
							"activate"=>"defense"
						));
						$treepick++;
						set_module_pref("treepick", $treepick);
						break;
					case 14:
						output("`^You gain 1 attack and 10 hitpoints but lose 1 defence!");
						$session['user']['attack']++;
						$session['user']['maxhitpoints']+=10;
						$session['user']['defence']--;
						$treepick++;
						set_module_pref("treepick", $treepick);
						break;
					case 15:
						output("`^Some fruit falls from the tree. \"There is something weird about this fruit\", you inquire. You think about your hunger and decide that the consequence will be worth it!!!");
						$session['user']['drunkness']=66;
						$session['user']['turns'] = max(0, $session['user']['turns'] - 10);
						$treepick++;
						set_module_pref("treepick", $treepick);
						break;
					case 16:
						output("`^You start climbing up the `@Tree `2of Life `^and you slip breaking a branch!! The `@Tree `^starts glowing a dark red! You suddenly feel a heavy burden on your shoulders and find its harder to fight!");
						apply_buff('treeoflife_curse', array(
							"name"=>"`4Curse `7of the `@Tree",
							"rounds"=>10,
							"wearoff"=>"`^Your burden is released!",
							"defmod"=>0.7,
							"atkmod"=>0.3,
							"roundmsg"=>"`4Your Burden causes you to deal less damage!",
							"activate"=>"roundstart"
						));
						$treepick++;
						set_module_pref("treepick", $treepick);
						break;
					case 17:
						output("`^You reach up and grab a small pouch of gold!");
						$session['user']['gold']+=100;
						$treepick++;
						set_module_pref("treepick", $treepick);
						break;
					default:
						output("`^that the Tree hasn't beared fruit yet!");
						break;
				}
				if ($session['user']['alive'] == 1) {
					addnav("Back to Garden","gardens.php");
				}
			} else {
				output("`@You decide to let others have a chance..");
				addnav("Return to Garden","gardens.php");
			}
			break;
	}
	page_footer();
}
?>