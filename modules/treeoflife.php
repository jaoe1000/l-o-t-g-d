<?
require_once "common.php";
page_header("The Tree of Life");
output("`c`2The `@Tree `2of Life`c`n`n");
//--------------------------------------------------------------------------------------------------------
//| Written by:  DeathDragon 
//| Version:    1.2 - 06/11/2004
//| Thanks Talisman for help with stupid mistakes :D
//| Revisions by: Odyssey
//| About:  A tree that randomly gives a user something
//| make sure you include the image that comes with the file which can be found at..
//| http://images.google.com/images?q=tree&ie=UTF-8&hl=en
//|
//| SQL: ALTER TABLE `accounts` ADD `treepick` INT( 11 ) UNSIGNED DEFAULT '0' NOT NULL ;
//| in newday add $session['user']['treepick']=0;
//--------------------------------------------------------------------------------------------------------
switch($HTTP_GET_VARS[op])
    {

case "":
 output("`c`2While frolicing in the Garden, you notice a section where the a ray of sun always seems to shine.  Letting your curiosity get the best of you, you walk along the beaten path and find The `@Tree `2of Life.  In the awe of it's beauty, you make a decision...`c");
 addnav("Options");
 addnav("Pick Object From Tree","treeoflife.php?op=pickfruit");
 addnav("Back to the Garden","stonegarden.php");
break;

case "pickfruit":
	if ($session[user][treepick] <3){
    output("`n`n`^You decide to pick something from `2The `@Tree `2of Life, you find....`n`n");
        $rand1 = e_rand(1,20);
        switch ($rand1){
        case 1:
            output("`^that the Tree hasn't beared fruit yet!");
            break;
        case 2:
            output("`^ A small bag of gems!!");
            $session[user][gems]+=7;
			$session[user][treepick]++;
            break;
        case 3:
            output("`^ A Charm Fairy stuck between two branches.  Always the one to help, you free the Fairy, and she is very much grateful. She waves her wand, and you notice that you look better.");
            $session[user][charm]+=2;
			$session[user][treepick]++;
			break;
        case 4:
            output("`^Nothing!  You curse the birds, and head back to the Garden.");
            break;
        case 5:
            output("`^ A small bag of gold pieces!!");
            $session[user][gold]+=200;
			$session[user][treepick]++;
            break;
        case 6:
            output("`^ 2 Gems!!!");
                        $session[user][gems]+=2;
						$session[user][treepick]++;
            break;
            case 7:
            output("`^Some rotten fruit falls from the tree.  Your hunger overcomes your wisdom, and so you decide to take a bite of the tainted fruit.  Just as you start to walk back, you feel a sharp pain and then you fall to the ground.  The scenery in your eyes start to turn to black, and you begin to see the souls of the past warriors.  You begin to realize that you are dead!!!");
       $session[user][alive]=0;
       $session[user][hitpoints]=0;
      addnews($session[user][name]."`5 died from some tainted fruit, at `2The `@Tree `2of Life ");
       addnav("The Shades","shades.php");

            break;
            case 8:
            output("`^ Nothing!  You curse the squirrels, and head back to the Garden.");
            break;
            case 9:
            output("`^ 3 Gems!!!");
                        $session[user][gems]+=3;
						$session[user][treepick]++;
            break;
            case 10:
            output("`^ While reaching into the Tree, you feel something slither across your hand.  Determined to find something, you keep on feeling around.  Suddenly, you are face to face with a giant snake! That is the last thing you remember...");
       $session[user][alive]=0;
       $session[user][hitpoints]=0;
      addnews($session[user][name]."`5 was bitten and killed by a snake, at `2The `@Tree `2of Life ");
       addnav("The Shades","shades.php");
            break;
            case 11:
            output("`^ Nothing!  You curse the snakes, and head back to the Garden");
            break;
            case 12:
            output("`^ 2 Gems!!!");
                        $session[user][gems]+=2;
		$session[user][treepick]++;
            break;
	    case 13:
	    output("`^that The `@Tree `^decides to bless you while you fight! You got a buff!");
	    $session[bufflist][865] = array(
          "name"=>"`2The Bleesing of the `@Tree",
          "rounds"=>10,
          "wearoff"=>"`2The `@Tree `2has helped you enough.",
          "defmod"=>1,
          "atkmod"=>3,
          "roundmsg"=>"`2The `@Tree `2gives you it's blessing!",
          "activate"=>"defense"); 
		 $session[user][treepick]++;
	  break;
	  case 13:
	    output("`^You gain 1 attack and 10 hitpoints but lose 1 defence!");
	   $session[user][attack]++;
	   $session[user][maxhitpoints]+=10;
	   $session[user][defence]--;
	   $session[user][treepick]++;
	  break;
	  case 14:
		  output("`^Some fruit falls from the tree.  \"There is something weird about this fruit\" ,you inquire. You think about your hunger and decide that the consequence will be worth it!!!");
		  $session[user][drunkness]=66;
		  $session[user][turns]-10;
		   $session[user][treepick]++;
		  break;
	  case 15:
		  output("`^You start climbing up the `@Tree `2of Life `^and you slip breaking a branch!! The `@Tree `^starts glowing a dark red! You suddenly feel a heavy burden on your shoulders and find its harder to fight!");
		  $session[bufflist][999] = array(
          "name"=>"`4Curse `7of the `@Tree",
          "rounds"=>10,
          "wearoff"=>"`^Your burden is released!",
          "defmod"=>0.7,
          "atkmod"=>0.3,
          "roundmsg"=>"`4Your Burden causes you to deal less damage!",
          "activate"=>"roundstart"); 
		   $session[user][treepick]++;
	  break;
	  case 16:
           output("`^You reach up and grab a small pouch of gold!");
	  $session[user][gold]+=100;
	   $session[user][treepick]++;
	  break;
        }
		if ($session[user][alive]==1)        
    addnav("Back to Garden","stonegarden.php"); 
	}else{
		output("`@You decide to let others have a chance..");
		addnav("Return to Garden","stonegarden.php");
	}
      
break;

}
page_footer();
?>