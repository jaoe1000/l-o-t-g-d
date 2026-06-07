<?php
// translator ready
// addnews ready

/* Draconus Race */
/* ver 1.1 */
/* Based on code for Succubus by Mammon
/* Edited by Lightbringer for http://lotgd.xen23.net */
/* concept by Lightbringer */

function racedraconus_getmoduleinfo(){
        $info = array(
                "name"=>"Race - Draconus",
                "version"=>"1.1",
                "author"=>"Lightbringer",
                "category"=>"Races",
                "download"=>"http://dragonprime.net/users/Lightbringer/racedraconus.zip",
                "requires"=>array(
      "racehuman"=>"Race - Human 1.0|By Eric Stevens",
   ),
                "settings"=>array(
                        "Draconus Race Settings,title",
                        "minedeathchance"=>"Percent chance for Draconus to die in the mine,range,0,100,1|40",
                        "gemchance"=>"Percent chance for Draconus to find a gem on battle victory,range,0,100,1|5",
                        "gemmessage"=>"Message to display when finding a gem|`&Your Draconus senses tingle, and you notice a `%gem`&!",
                        "goldloss"=>"How much less gold (in percent) do the Draconus find?,range,0,100,1|15",
                        "mindk"=>"How many DKs do you need before the race is available?,int|50",
                ),
        );
        return $info;
}

function racedraconus_install(){
        // The Draconi share the city with humans, so..
        if (!is_module_installed("racehuman")) {
                output("The Draconus only choose to live with humans.   You must install that race module.");
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
        return true;
}

function racedraconus_uninstall(){
        global $session;
        // Force anyone who was a Draconus to rechoose race
        $sql = "UPDATE  " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Draconus'";
        db_query($sql);
        if ($session['user']['race'] == 'Draconus')
                $session['user']['race'] = RACE_UNKNOWN;
        return true;
}

function racedraconus_dohook($hookname,$args){
        //yeah, the $resline thing is a hack.  Sorry, not sure of a better way
        //to handle this.
        // It could be passed as a hook arg?
        /*That is the original comment by the original author..If it is possible otherwise,
        let me know and I will adjust accordingly */
        global $session,$resline;
        $sex=$session['user']['sex'];
        if (is_module_active("racehuman")) {
                $city = get_module_setting("villagename", "racehuman");
        } else {
                $city = getsetting("villagename", LOCATION_FIELDS);
        }
        $race = "Draconus";
        switch($hookname){
        case "pvpadjust":
                if ($args['race'] == $race) {
                        $args['creaturedefense']+=(4+floor($args['creaturelevel']/4));
                        $args['creaturehealth']-= round($args['creaturehealth']*.30, 0);
                }
                break;
        case "raceminedeath":
                if ($session['user']['race'] == $race) {
                        $args['chance'] = get_module_setting("minedeathchance");
                        $args['racesave'] = "Lucky for you, your preternatural reflexes and strong wings carry you away from a horrible demise.`n";
                        $args['schema']="module-racedraconus";
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
                if ($sex) {
                output("<a href='newday.php?setrace=Draconus$resline'>On the plains surrounding the city of %s</a>, the city of men, your race of `4Succubi`0,  traveled behind the great herds for generations, seducing them and stealing their souls.`n`n",$city, true);
                addnav("`4Draconus`0","newday.php?setrace=$race$resline");
                addnav("","newday.php?setrace=$race$resline");
                break;
                } else break;
        case "setrace":
                if ($session['user']['race']==$race){ // it helps if you capitalize correctly
                        output("`&As a Draconus, your mere presence terrifies your foe and lower their defenses.`n");
                        output("You gain extra defense!`n");
                        output("You have an instinctive greed for glittering gems and gold.`n");
                        output("You gain extra golds and gems from forest fights!");
                        if (is_module_active("cities")) {
                                if ($session['user']['dragonkills']==0 &&
                                                $session['user']['age']==0){
                                        //new serf, set them to wandering around this city.
                                        set_module_setting("newest-$city",
                                                        $session['user']['acctid'],"cities");
                                }
                                set_module_pref("homecity",$city,"cities");
                                $session['user']['location']=$city;
                        }
                }
                break;
        case "battle-victory":
                if ($session['user']['race'] != $race) break;
                if ($args['type'] != "forest") break;
                if ($session['user']['level'] < 15 &&
                                e_rand(1,100) <= get_module_setting("gemchance"))
                                {
                        output(get_module_setting("gemmessage")."`n`0");
                        $session['user']['gems']++;
                        debuglog("found a gem when slaying a monster, for being a greedy Draconus.");
                }
                break;

        case "creatureencounter":
                if ($session['user']['race']==$race){
                        //get those folks who haven't manually chosen a race
                        racedraconus_checkcity();
                        $loss = (100 + get_module_setting("goldloss"))*2;
                        $args['creaturegold']=round($args['creaturegold']*$loss,0);
                }
                break;
        case "newday":
                if ($session['user']['race']==$race){
                        racedraconus_checkcity();
                        apply_buff("racialbenefit",array(
                                "name"=>"`4Frightful Presence`0",
                                "defmod"=>"(<defense>?(1+((4+floor(<level>/4))/<defense>)):0)",
                                "badguydmgmod"=>1.15,
                                "allowinpvp"=>1,
                                "allowintrain"=>1,
                                "rounds"=>-1,
                                "schema"=>"module-racedraconuus",
                                )
                        );
                }
                break;
        }

        return $args;
}

function racedraconus_checkcity(){
        global $session;
        $race="Draconus";
        if (is_module_active("racehuman")) {
                $city = get_module_setting("villagename", "racehuman");
        } else {
                $city = getsetting("villagename", LOCATION_FIELDS);
        }

        if ($session['user']['race']==$race && is_module_active("cities")){
                //if they're this race and their home city isn't right, set it up.
                if (get_module_pref("homecity","cities")!=$city){ //home city is wrong
                        set_module_pref("homecity",$city,"cities");
                }
        }
        return true;
}

function racedraconus_run(){
}
?>