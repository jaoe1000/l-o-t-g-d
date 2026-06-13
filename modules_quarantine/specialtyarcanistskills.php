<?php
//addnews ready
// mail ready
// translator ready
/******************************************
 Name: Specialty - Stratomancer
 Ver: 1.0   Date: 02-23-05
 By: Lightbringer
 
 Modified by: Enderandrew

******************************************/

function specialtyarcanistskills_getmoduleinfo(){
        $info = array(
                "name" => "Specialty - Arcanist Skills",
                "author" => "Lightbringer<br>Modified by Enderandrew",
                "version" => "1.1",
                "download" => "http://dragonprime.net/users/Enderwiggin/specialtyarcanist.zip",
                "vertxtloc"=>"http://dragonprime.net/users/enderwiggin/",
                "category" => "Specialties",
                "settings"=> array(
                        "Specialty - Arcanist Settings,title",
                        "mindk"=>"How many DKs do you need before the specialty is available?,int|0",
                        "cost"=>"How many points do you need before the specialty is available?,int|0",
                ),
                "prefs" => array(
                        "Specialty - Arcanist Skills User Prefs,title",
                        "skill"=>"Skill points in Arcanist Skills,int|0",
                        "uses"=>"Uses of Arcanist Skills allowed,int|0",
                        "change"=>"Amount of times allowed to change weather,int|0",
                ),
        );
        return $info;
}

function specialtyarcanistskills_install(){
                module_addhook("apply-specialties");
                module_addhook("fightnav-specialties");
                module_addhook("incrementspecialty");
                module_addhook("castlelibbook");
                module_addhook("castlelib");
                module_addhook("choose-specialty");
                module_addhook("dragonkill");
                module_addhook("newday");
                module_addhook("pointsdesc");
                module_addhook("set-specialty");
                module_addhook("specialtycolor");
                module_addhook("specialtymodules");
                module_addhook("specialtynames");
        return true;
}

function specialtyarcanistskills_uninstall(){
        // Reset the specialty of anyone who had this specialty so they get to
        // rechoose at new day
        $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='PS'";
        db_query($sql);
        return true;
}

function specialtyarcanistskills_dohook($hookname,$args){
        global $session,$resline;
        $cost = get_module_setting("cost");
        $op69 = httpget('op69');
        tlschema("fightnav");
        $spec = "AS";
        $name = "Arcanist Skills";
        $ccode = "`@";
        $cost = get_module_setting("cost");

        switch ($hookname) {
        case "apply-specialties":
                $skill = httpget('skill');
                $l = httpget('l');
                if ($skill==$spec){
                        if (get_module_pref("uses") >= $l){
                                switch($l){
                                case 1:
                                        apply_buff('ps1',array(
                                                "startmsg"=>"`^You unleash your powers upon your foe - sending it reeling!",
                                                "name"=>"`^Magical Jolt`^",
                                                "rounds"=>5,
                                                "wearoff"=>"`^...`^",
                                                "minioncount"=>1,
                                                "maxbadguydamage"=>round($session['user']['level']/2)+2,
                                                "effectmsg"=>"`^You jolt strikes {badguy} for {damage} damage.`^",
                                                "effectnodmgmsg"=>"`)The jolt careens towards {badguy}`) but `\$MISSES`)!",
                                                "schema"=>"specialtyarcanistskills"
                                        ));
                                        break;
                                case 2:
                                        apply_buff('ps2',array(
                                                "startmsg"=>"`^You focus your will into a potent blast of energy.`^",
                                                "name"=>"`^Concentrated Blast`^",
                                                "rounds"=>5,
                                                "wearoff"=>"`^Your concentration dissipates, as does the blast...`^",
                                                "maxbadguydamage"=>round($session['user']['level']/2)+6,
                                                "effectmsg"=>"`^You blast pounds {badguy} for {damage} damage.`^",
                                                "effectnodmgmsg"=>"`)The jolt careens towards {badguy}`) but `\$MISSES`)!",
                                                "schema"=>"specialtypsionicskills"
                                        ));
                                        break;
                                case 3:
                                        apply_buff('ps3', array(
                                                "startmsg"=>"`^You polymorph into an impressive dragon!`^",
                                                "name"=>"`^Dragon Form`^",
                                                "rounds"=>8,
                                                "wearoff"=>"`^You can no longer maintain the concentration required...`^",
                                                "minioncount"=>1,
                                                "maxbadguydamage"=>round($session['user']['level']/1,0)+4,
                                                "effectmsg"=>"`^You rend your foe for {damage} damage.`^",
                                                "effectnodmgmsg"=>"`)Your attack on {badguy} fails!",
                                                "schema"=>"specialtyarcanistskills"
                                        ));
                                        break;
                                case 5:
                                        apply_buff('ps5',array(
                                                "startmsg"=>"`^Concentrating deeply you polymorph into a majestic Soveriegn Dragon and dive down on {badguy}!`^",
                                                "name"=>"`^Greater Dragon Form`^",
                                                "rounds"=>4,
                                                "wearoff"=>"`^{badguy} watches as you revert to your true form...but is it your true form...?`^",
                                                "minioncount"=>1,
                                                "minbadguydamage"=>round($session['user']['attack']*2,0),
                                                "maxbadguydamage"=>round($session['user']['attack']*4,0),
                                                "effectmsg"=>"`^Your sheer power strikes {badguy} for {damage} damage.`^",
                                                "schema"=>"specialtyarcanistskills"
                                        ));
                                        break;
                                }
                                set_module_pref("uses", get_module_pref("uses") - $l);
                        }else{
                                apply_buff('ps0', array(
                                        "startmsg"=>"`^You try to harness your magical energies, but they are depleted.  `%{badguy} `^shuffles his feet and advances slowly.`^",
                                        "rounds"=>1,
                                        "schema"=>"specialtyarcanistskills"
                                ));
                        }
                }
                break;
        case "castlelibbook":
                output("Book on Arcane Lore. (3 Turns)`n");
                addnav("Read a Book");
                addnav("Book on Arcane Lord","runmodule.php?module=lonnycastle&op=library&op69=arcanist");
                break;
        case "castlelib":
                if ($op69 == 'arcanist'){
                            output("You sit down and open up the Book on Arcane Lore.`n");
                            output("You read for a while... in the time it takes you to read you use up`n");
                            output("3 Turns.`n`n");
                            output("Unlike modern tomes on swords and sorcery, this book focuses on older techniques `n");
                            output("long since abandoned.  People may scoff, but you find it encouraging in your pursuits `n");
                            output("of Arcanist lore.  Too bad it doesn't help with that nagging acne problem!`n");
                            $session['user']['turns']-=3;
                            set_module_pref('skill',(get_module_pref('skill','specialtyarcanistskills') + 1),'specialtyarcanistskills');
                            set_module_pref('uses', get_module_pref("uses",'specialtyarcanistskills') + 1,'specialtyarcanistskills');
                            addnav("Continue","runmodule.php?module=lonnycastle&op=library");
                }
                break;
        case "choose-specialty":
                if ($session['user']['specialty'] == "" ||
                                $session['user']['specialty'] == '0') {
                        $pointsavailable = $session['user']['donation'] - $session['user']['donationspent'];
                        if ($session['user']['dragonkills'] < get_module_setting("mindk") || get_module_setting("cost") > $pointsavailable)
                                break;
                        addnav("$ccode$name`0","newday.php?setspecialty=".$spec."$resline");
                        $t1 = translate_inline("Memorising your spells");
                        $t2 = appoencode(translate_inline("$ccode$name`0"));
                        rawoutput("<a href='newday.php?setspecialty=$spec$resline'>$t1 ($t2)</a><br>");
                        addnav("","newday.php?setspecialty=$spec$resline");
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
                        addnav(array("%s%s (%s points)`0", $ccode, $name, $uses), "");
                        addnav(array("%s &#149; Magical Jolt`7 (%s)`0", $ccode, 1),
                                        $script."op=fight&skill=$spec&l=1", true);
                }
                if ($uses > 1) {
                        addnav(array("%s &#149; Concentrated Blast`7 (%s)`0", $ccode, 2),
                                        $script."op=fight&skill=$spec&l=2",true);
                }
                if ($uses > 2) {
                        addnav(array("%s &#149; Dragon Form`7 (%s)`0", $ccode, 3),
                                        $script."op=fight&skill=$spec&l=3",true);
                }
                if ($uses > 4) {
                        addnav(array("%s &#149; Greater Dragon Form`7 (%s)`0", $ccode, 5),
                                        $script."op=fight&skill=$spec&l=5",true);
                }
                break;
        case "incrementspecialty":
                if($session['user']['specialty'] == $spec) {
                        $new = get_module_pref("skill") + 1;
                        set_module_pref("skill", $new);
                        $c = $args['color'];
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
                if ($cost>0)
                {
                    $args['count']++;
                    $format = $args['format'];
                    $str = translate("The Arcanist Specialty is availiable upon reaching %s Dragon Kills and %s points.");
                    $str = sprintf($str, get_module_setting("mindk"),
                            $cost);
                    output($format, $str, true);
                }
                break;
        case "set-specialty":
                if($session['user']['specialty'] == $spec) {
                        page_header($name);
                        $session['user']['donationspent'] = $session['user']['donationspent'] + $cost;
                        output("`3Growing up, you remember reading and endless hours of study.");
                        output("Over time you began to feel a strange energy begin to surface, ancient tomes mysteriously revealing their true nature to you!");
                        output("You found this could be further developed and used as a weapon against your foes.");              }
                break;
        case "specialtycolor":
                $args[$spec] = $ccode;
                break;
        case "specialtymodule":
                $args[$spec] = "specialtyarcanistskills";
                break;
        case "specialtynames":
                $args[$spec] = translate_inline($name);
                break;
        } // This was the missing closing brace!
        return $args;
}

function specialtyarcanistskills_run(){
}
?>
