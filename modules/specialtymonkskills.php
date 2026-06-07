<?php
/* dannic06@gmail.com */ 
/* */ 
/* 12-29-2004 */ 
/* Fixed major flaw where it would take away everyones weapons and armor */ 
/* updated pointdesc so that it doesn't show up in the lodge unless the */ 
/* lodge cost is set */ 
/* 1-6-2005 */ 
/* Fixed Translation readiness and typo */ 
/* Changed the weapon and armor dropping code in the fightnav */ 
/* 3-15-2005 */ 
/* Fixed gold reduction for all players. Should only reduce gold for */ 
/* monks now. */ 
/* Added option to disable navs for the weapon shop. */ 
/* Added CheckModVer support. */ 
/* 4-2-2005 */ 
/* Fixed missing break; in a case. */ 
/* Fixed bracket in the wrong place. */ 
/* 4-26-2005 */ 
/* Fixed double message issue. */ 
/* 11-8-05 */ 
/* Fixed missing dragon points when calculating attack/defense */ 
/***************************************************************************/ 

function specialtymonkskills_getmoduleinfo(){ 
    $info = array( 
        "name" => "Specialty - Monk Skills", 
        "author" => "Billie Kennedy", 
        "version" => "1.8", 
        "download" => "http://nuketemplate.com/modules.php?name=Downloads&d_op=viewdownload&cid=34", 
        "vertxtloc"=>"http://dragonprime.net/users/Dannic/", 
        "category" => "Specialties", 
        "settings"=> array( 
            "Specialty - Monk Settings,title", 
            "mindk"=>"How many DKs do you need before the specialty is available?,int|0", 
            "cost"=>"How many points do you need before the specialty is available?,int|0", 
            "goldloss"=>"How much less gold (in percent) do the Monks find?,range,0,100,1|15", 
            "disableshop"=>"Disable the Weapons and Armor shops?,bool|0", 
            "(Not disabling the shops can be a much more fun experience but may cause complaints from players about loss of gold.),note", 
        ), 
        "prefs" => array( 
            "Specialty - Monk Skills User Prefs,title", 
            "skill"=>"Skill points in Monk Skills,int|0", 
            "uses"=>"Uses of Monk Skills allowed,int|0", 
        ), 
    ); 
    return $info; 
} 

function specialtymonkskills_install(){ 
    module_addhook("choose-specialty"); 
    module_addhook("set-specialty"); 
    module_addhook("fightnav-specialties"); 
    module_addhook("apply-specialties"); 
    module_addhook("newday"); 
    module_addhook("incrementspecialty"); 
    module_addhook("specialtycolor"); 
    module_addhook("dragonkill"); 
    module_addhook("castlelibbook"); 
    module_addhook("castlelib"); 
    module_addhook("pointsdesc"); 
    module_addhook("creatureencounter"); 
    module_addhook("village-desc"); 
    return true; 
} 

function specialtymonkskills_uninstall(){ 
    // Reset the specialty of anyone who had this specialty so they get to 
    // rechoose at new day 
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='MN'"; 
    db_query($sql); 
    return true; 
} 

function specialtymonkskills_dohook($hookname,$args){ 
    global $session,$resline; 
    $spec = "MN"; 
    $name = "Monk Skills"; 
    $ccode = "`Q"; 
    $cost = get_module_setting("cost"); 
    $op69 = httpget('op69'); 
    
    switch ($hookname) { 
        case "castlelibbook": 
            if ($session['user']['specialty']=='MN'){ 
                output("The five point exploding heart palm technique. (2 Turns)`n"); 
                addnav("Read a Book"); 
                addnav("The five point exploding heart palm technique","runmodule.php?module=lonnycastle&op=library&op69=monk"); 
            } 
            break; 
        case "village-desc": 
            if ($session['user']['specialty']=='MN' && get_module_setting("disableshop")){ 
                output("`n`n$ccodeAs a Monk, you find no use for the Weapon and Armor shops... so you just ignore them...`n`n`0"); 
                blocknav("weapons.php"); 
                blocknav("armor.php"); 
            } 
            break; 
        case "castlelib": 
            if ($op69 == 'monk'){ 
                output("You sit down and open up \"The five point exploding heart palm technique\" book.`n"); 
                output("You read for a while... in the time it takes you to read you use up`n"); 
                output("2 Turns.`n`n"); 
                output("Several pictures show various points of power on a body. You learn about using those points to your advantage.`n"); 
                output("You now know a little more about your Monk skills than you knew before.`n"); 
                $session['user']['turns']-=2; 
                set_module_pref('skill',(get_module_pref('skill','specialtymonkskills') + 3),'specialtymonkskills'); 
                set_module_pref('uses', get_module_pref("uses",'specialtymonkskills') + 1,'specialtymonkskills'); 
                addnav("Continue","runmodule.php?module=lonnycastle&op=library"); 
            } 
            break; 
        case "pointsdesc": 
            if ($cost > 0){ 
                $args['count']++; 
                $format = $args['format']; 
                $str = translate("The Monk Specialty is availiable upon reaching %s Dragon Kills and %s points."); 
                $str = sprintf($str, get_module_setting("mindk"),$cost); 
                output($format, $str, true); 
            } 
            break; 
        case "creatureencounter": 
            if($session['user']['specialty'] == $spec) { 
                $loss = (100 - get_module_setting("goldloss"))/100; 
                $args['creaturegold']=round($args['creaturegold']*$loss,0); 
            } 
            break; 
        case "dragonkill": 
            set_module_pref("uses", 0); 
            set_module_pref("skill", 0); 
            break; 
        case "choose-specialty": 
            if ($session['user']['specialty'] == "" || $session['user']['specialty'] == '0') { 
                if ($session['user']['dragonkills'] < get_module_setting("mindk") || $cost > $session['user']['donation']) 
                    break; 
                addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline"); 
                $t1 = translate_inline("Holistic defense and fighting without weapons. Monks are not as interested in Gold as other specialties."); 
                $t2 = appoencode(translate_inline("$ccode$name`0")); 
                rawoutput("$t1 ($t2)<br>"); 
                addnav("","newday.php?setspecialty=$spec$resline"); 
            } 
            break; 
        case "set-specialty": 
            if($session['user']['specialty'] == $spec) { 
                page_header($name); 
                $session['user']['defense']= $session['user']['defense']+1; 
                $session['user']['attack']=$session['user']['attack']+1; 
                output("`3Showing up at the great doors of the Monestary, the monks told you to go away."); 
                output("Having no where else to go since your parents died and you really want to learn the martial arts of the Monks, you stay seated at the doors for days."); 
                output("The Monks notice you don't move from the doors of the monestary and bring you food periodically.`n`n After what seems like weeks, the Monks beckon you inside."); 
                output("You choose not to pass up the opprotunity and quickly become one of the best in the Monestary."); 
            } 
            break; 
        case "specialtycolor": 
            $args[$spec] = $ccode; 
            break; 
        case "incrementspecialty": 
            if($session['user']['specialty'] == $spec) { 
                $new = get_module_pref("skill") + 1; 
                set_module_pref("skill", $new); 
                $c = $args['color']; 
                $name = translate_inline($name); 
                output("`n%sYou gain a level in `&%s%s to `#%s%s!", $c, $name, $c, $new, $c); 
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
                $name = translate_inline($name); 
                if ($bonus == 1) { 
                    output("`n`2For being interested in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n",$ccode,$name,$ccode,$name); 
                } else { 
                    output("`n`2For being interested in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n",$ccode,$name,$bonus,$ccode,$name); 
                } 
            } 
            $amt = (int)(get_module_pref("skill") / 3); 
            if ($session['user']['specialty'] == $spec) $amt = $amt + $bonus; 
            set_module_pref("uses", $amt); 
            break; 
        case "fightnav-specialties": 
            $uses = get_module_pref("uses"); 
            $script = $args['script']; 
            if($session['user']['specialty'] == $spec){ 
                if ($session['user']['armor'] != "T-Shirt"){ 
                    output("You feel rather silly in your %s and remove your armor. A T-Shirt is much less restricting.`n",$session['user']['armor']); 
                } 
                $session['user']['armor'] = "T-Shirt"; 
                $session['user']['weapondmg']=0; 
                $session['user']['weaponvalue']=0; 
                $session['user']['defense']=$session['user']['level']*2; 
                $xpoints=0; 
                reset($session['user']['dragonpoints']); 
                while(list($key,$val)=each($session['user']['dragonpoints'])){ 
                    if ($val=="at" || $val == "de"){ 
                        if($val == "de"){ 
                            $xpoints++; 
                        } 
                    } 
                } 
                $session['user']['defense']= $session['user']['defense'] + $xpoints; 
                
                if ($session['user']['weapon']!="Fists"){ 
                    output("It just doesn't feel right to use %s. You lay the weapon down and go back to just your bare hands.`n",$session['user']['weapon']); 
                } 
                $session['user']['weapon'] = "Fists"; 
                $session['user']['armordef']=0; 
                $session['user']['armorvalue']=0; 
                $session['user']['attack']=$session['user']['level']*2; 
                $xpoints=0; 
                reset($session['user']['dragonpoints']); 
                while(list($key,$val)=each($session['user']['dragonpoints'])){ 
                    if ($val=="at" || $val == "de"){ 
                        if($val == "at"){ 
                            $xpoints++; 
                        } 
                    } 
                } 
                $session['user']['attack']= $session['user']['attack'] + $xpoints; 
            } 
            
            if ($uses > 0) { 
                addnav(array("$ccode$name (%s points)`0", $uses),""); 
                addnav(array("$ccode • Block`7 (%s)`0", 1), $script."op=fight&skill=$spec&l=1", true); 
            } 
            if ($uses > 1) { 
                addnav(array("$ccode • Fall`7 (%s)`0", 2), $script."op=fight&skill=$spec&l=2",true); 
            } 
            if ($uses > 2) { 
                addnav(array("$ccode • Punch`7 (%s)`0", 3), $script."op=fight&skill=$spec&l=3",true); 
            } 
            if ($uses > 4) { 
                addnav(array("$ccode • Kick`7 (%s)`0", 5), $script."op=fight&skill=$spec&l=5",true); 
            } 
            break; 
        case "apply-specialties": 
            $skill = httpget('skill'); 
            $l = httpget('l'); 
            if ($skill==$spec){ 
                if (get_module_pref("uses") >= $l){ 
                    switch($l){ 
                        case 1: 
                            apply_buff('MN1',array( 
                                "startmsg"=>"`^You take a deep breath while studying {badguy}'s attack.", 
                                "name"=>"`^Block", 
                                "rounds"=>2, 
                                "wearoff"=>"Your concentration is broken and you can't seem to get back into rhythm.", 
                                "roundmsg"=>"`^Concentrating on the battle at hand you step aside and block {badguy}'s attack.", 
                                "badguyatkmod"=>0, 
                                "badguydefmod"=>0, 
                                "schema"=>"specialtymonkskills" 
                            )); 
                            break; 
                        case 2: 
                            apply_buff('MN2',array( 
                                "startmsg"=>"`^You begin concentrating and waiting for a blow from {badguy}.", 
                                "name"=>"`^Fall", 
                                "rounds"=>5, 
                                "wearoff"=>"Your concentration is broken and you can't seem to get back into rhythm.", 
                                "badguyatkmod"=>0.5, 
                                "roundmsg"=>"You minimize the damage delt by {badguy} by taking the blow and falling to the ground!", 
                                "schema"=>"specialtymonkskills" 
                            )); 
                            break; 
                        case 3: 
                            apply_buff('MN3',array( 
                                "startmsg"=>"`^You concentrate on {badguy}, waiting for openings.", 
                                "name"=>"`^Punch", 
                                "rounds"=>5, 
                                "wearoff"=>"Your concentration is broken and you can't seem to get back into rythm.", 
                                "maxbadguydamage"=>round($session['user']['level']/1.3,0)+1, 
                                "roundmsg"=>"{badguy} gives you an opprotunity to place a punch to a very sensitive spot!", 
                                "badguyatkmod"=>0.9, 
                                "badguydefmod"=>0.9, 
                                "badguydmgmod"=>0.9, 
                                "schema"=>"specialtymonkskills" 
                            )); 
                            break; 
                        case 5: 
                            apply_buff('MN5',array( 
                                "startmsg"=>"`^You concentrate on {badguy}, waiting for openings.", 
                                "name"=>"`^Kick", 
                                "rounds"=>5, 
                                "wearoff"=>"Your concentration is broken and you can't seem to get back into rythm.", 
                                "maxbadguydamage"=>round($session['user']['level']/1.8,0)+1, 
                                "roundmsg"=>"Noticing an opening you kick {badguy} right where it counts most. Guess {badguy} won't be trying to have kids any time soon!", 
                                "badguyatkmod"=>0.5, 
                                "badguydefmod"=>0.5, 
                                "badguydmgmod"=>0.5, 
                                "schema"=>"specialtymonkskills" 
                            )); 
                            break; 
                    } 
                    set_module_pref("uses", get_module_pref("uses") - $l); 
                }else{ 
                    apply_buff('MN0', array( 
                        "startmsg"=>"{badguy} chuckles as you try to get into the crane stance only to fall over.", 
                        "rounds"=>1, 
                        "schema"=>"specialtymonkskills" 
                    )); 
                } 
            } 
            break; 
    } 
    return $args; 
} 

function specialtymonkskills_run(){ 
} 
?>
