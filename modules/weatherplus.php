<?php
//addnews ready
// mail ready
// translator ready

/*This is my first major forage into the forest special department..The idea for cases and such
  was inspired by my all-time favourite module - Stonehenge...

  Module Name   : Weather Plus 
  Category      : Forest Special
  Genre         : Weather Related
  Author        : Lightbringer
  Date Created: : Thursday, February 24, 2005
  Version       : 1.0
  Credits       : Colin Harvie and JT Traub for the legendary Stonehenge
                  Akuma for his help in debugging

  Future Edits  : *Adding hooks into the Weather module
                  *Race specific edits
                  *Ties to the graveyard

  UPDATE - Ver 1.1
           * fixed a few errors
           * added the if race = vampire edit
*/


function weatherplus_getmoduleinfo(){
        $info = array(
                "name"=>"Weather Plus",
                "version"=>"1.1",
                "author"=>"Lightbringer",
                "category"=>"Forest Specials",
        "credits"=>"Based on Stonehenge by Colin Harvie<br>Updates by JT Traub",
        "download"=>"http://dragonprime.net/users/Lightbringer.zip",
        );
        return $info;
}

function weatherplus_install(){
        module_addeventhook("forest", "return 100;");
        return true;
}

function weatherplus_uninstall(){
        return true;
}

function weatherplus_dohook($hookname,$args){
        return $args;
}

function weatherplus_runevent($type)
{
        global $session;
        // We assume this event only shows up in the forest currently.
        $from = "forest.php?";
        $session['user']['specialinc'] = "module:weatherplus";

        $op = httpget('op');
        if ($op=="" || $op=="search"){
        output("`4As you delve deeper into the forest, a queer calmness pervades the air around here. The weather was slightly muggy until now... In a dazzling display of nature - the weather changes dramatically and goes from calm sunshine to rain, hail to painfully hot sun..`n`n");
        output("`!'What is going on here?'`4, you wonder to yourself as the weather shows no signs of behaving...`n`n");
        output("You see a strange looking staff leaning against a tree - with a button of some kind...`n`n`n");
        output("Upon picking it up, the wind begins to howl like a daemon...You wonder if you should press the button.....`n`n");
        output("You think to yourself...It is just a button...");
        output("What will you do?");
        addnav("Press the button","forest.php?op=weatherplus");
        addnav("Leave it alone","forest.php?op=nopress");
        }elseif ($op=="weatherplus2"){
                $session['user']['specialinc']="";
                $rand = e_rand(1,22);
                output("`#Knowing that the staff can't `ireally`i hurt you, you decide to take your chances.");
                output("You walk up to the staff and tentatively press the button.");
                output("As the button clicks in, the sky turns to a featureless void..You start to panic.");
                output("You start to feel a tingling which envelops your whole body.");
                output("Suddenly a bright, intense light envelops the staff, and you with it.");
                switch ($rand){
                case 1:
                case 2:
            output("`^The staff glows slightly and vanishes before your very eyes. You stand there dumbfounded and wonder what the heck just happened...`n`n");
            output("`^You stand there long enough to realise you have lost `!1 turn `^worrying about the glorified stick!`0");
            $session['user']['turns']-=1;
            break;
                 case 3:
            output("`^The button clicks and the forest is instantly suffused by a scorching heat as the sun intensifies exponentially1`n`0");
            output("`^The intense heat saps your strength and makes blows less effective...`0");
            apply_buff('searingheat', array(
            "name"=>"`^Searing Heat",
            "rounds"=>3,
            "wearoff"=>"The weather returns to normal.",
            "dmgmod"=>0.5,
            "badguydmgmod"=>0.5,
            "effectmsg"=>"The blistering heat lowers the damage caused.",
            )
            );
            break;
                case 4:
                case 5:
                case 6:
            output("`^You press the button and suddenly - the forest is consumed by an invigorating mist`n`n`0!");
            output("`^There is a sudden rush of power as the mist envelops you...`0");
            apply_buff('mist', array(
            "startmsg"=>"An invigorating mist suffuses you with an amazing feeling of power and stamina!",
            "name"=>"Invigorating Mist",
            "rounds"=>4,
            "wearoff"=>"The feeling fades.",
            "atkmod"=>2,
            "defmod"=>1.5,
            "effectmsg"=>"You are stronger and tougher.",
            )
            );
            break;
                case 7:
                case 8:
                case 9:
                case 10:
            if ($session['user']['race'] == 'Vampire') {
            output("`4Crimson clouds gather as you click the button...Without warning, a torrent of blood rains down..You revel in the blood and feel it add to your potence!`n`0");
            output("`^Your attack and defense have soared!.");
            apply_buff('bloodrain', array(
            "startmsg"=>"The blood rain adds to your strength, speed and stamina!",
            "name"=>"`%Blood Rain",
            "rounds"=>4,
            "wearoff"=>"You will need to feed to keep this up.",
            "atkmod"=>4,
            "defmod"=>4,
            )
            );

          } else {
            output("`4Crimson clouds gather as you click the button...Without warning, a torrent of blood rains down on you. The sheer horror of this happenstance sickens you to the core...you have no thoughts of attack and defense...`0");
            apply_buff('bloodrain', array(
            "startmsg"=>"The blood rain reviles you...you are too sick to attack and defence is not an option. You just want to be out of this weather!",
            "name"=>"`%Blood Rain",
            "rounds"=>4,
            "wearoff"=>"The sickness passes.",
            "atkmod"=>0,
            "defmod"=>0,
            )
            );
                        }


            break;
                case 11:
                case 12:
                case 13:
            output("`#Upon pressing the button, a wall of ice forms around you and your foe - rendering you both immobile...`0");
            apply_buff('icewall', array(
            "startmsg"=>"You and your foe are surrounded by a wall of ice!",
            "name"=>"Ice Wall",
            "rounds"=>2,
            "wearoff"=>"The ice melts as the weather returns to normal.",
            "atkmod"=>0,
            "defmod"=>0,
            "badguyatkmod"=>0,
            "badguydefmod"=>0,
            )
            );
            break;
                case 14:
                case 15:
                case 16:
                case 17:
                case 18:
            output("`^Flaming meteors shower down on you!`n");
            output("`4You were unable to sustain such a barrage!`n");
            output("You lose 10% of your experience.`n");
            output("You may continue playing again tomorrow.");
            $session['user']['alive']=false;
            $session['user']['hitpoints']=0;
            $session['user']['experience']*=0.9;
            addnav("Daily News","news.php");
            addnews("The body of %s was found lying in an empty clearing.",$session['user']['name']);
            break;
                case 19:
                case 20:
            output("An angelic light permeates the drak forest, suffusing it with glorious radiance!`n");
            output("As this light envelops you, you feel more energetic!`n`n");
            $reward = e_rand(1, 5);
            output("`^Your hit points have been `bpermanently`b increased by `7%s`^!", $reward);
            $session['user']['maxhitpoints'] += $reward;
            $session['user']['hitpoints'] += $reward;
            break;
                case 21:
                case 22:
            $prev = $session['user']['turns'];
            if ($prev >= 5) $session['user']['turns']-=5;
            else if ($prev < 5) $session['user']['turns']=0;
            $current = $session['user']['turns'];
            $lost = $prev - $current;

            output("The sky reforms and you realise the day has passed you by.");
            output("It seems that the staff had trapped you in some kind of stasis of time.`n`n");
            output("`^As a result, you lose `\$%s`^ forest fights!", $lost);
            break;
                }
                output("`0");
        }else{
                $session['user']['specialinc']="";
                output("All returns to normal and as it was before all of this curious weather business took place.`n`0");
        }
}
function weatherplus_run(){
}
?>