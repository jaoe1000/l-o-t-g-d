<?php
function sanctuary_getmoduleinfo() {
    $info = array(
        "name"=>"Sanctuary", 
        "author"=>"Kevin Kilgore (Nivek) & Jake Taft (Zanzaras)", 
        "version"=>"1.14", 
        "category"=>"Village", 
        "description"=>"A small temple in town that can heal the players and/or bless them for a price.", 
        "download"=>"http://www.dragonprime.net/users/Zanzaras/Sanctuary%20Module.zip", 
        "vertxtloc"=>"http://www.dragonprime.net/users/Zanzaras/", 
        "settings"=>array(
            "Sanctuary - Settings,title", 
            "healingcost"=>"Amount of gold a player must pay to be healed. Non-zero values will be multiplied by user's level. (0 = Healer hut rates),int|0", 
            "blessingcost"=>"Number of gems required to receive a blessing,int|1", 
            "blessingsallowed"=>"Number of times per day players can receive a blessing,int|1", 
            "extraturnblessing"=>"Number of extra turns a player will receive from a blessing,int|1", 
            "maxhitblessing"=>"Number of extra hitpoints a player will receive from a blessing,int|1", 
            "charmsblessing"=>"Number of charm points a player will receive from a blessing,int|1", 
            "goldblessing"=>"Amount of gold a player will receive from a blessing (value will be multiplied by the user's level),int|30", 
            "sanctuaryloc"=>"Where does the Sanctuary appear,location|".getsetting("villagename", LOCATION_FIELDS)
        ), 
        "prefs"=>array(
            "Sanctuary - User Preferences,title",
            "blessingstoday"=>"Number of blessings the player has received today,int|0"
        )
    ); 
    return $info; 
} 

function sanctuary_install() {
    module_addhook("village"); 
    module_addhook("newday"); 
    return true; 
} 

function sanctuary_uninstall(){
    return true;
} 

function sanctuary_dohook($hookname,$args) {
    global $session; 
    switch($hookname) {
        case "newday": 
            set_module_pref("blessingstoday",0); 
            break; 
        case "changesetting": 
            if ($args['setting'] == "villagename") {
                if ($args['old'] == get_module_setting("sanctuaryloc")) {
                    set_module_setting("sanctuaryloc", $args['new']);
                } 
            } 
            break; 
        case "village": 
            if ($session['user']['location'] == get_module_setting("sanctuaryloc")) {
                $tavernnav = $args['tavernnav'] ?? ($args['nav_headers']['tavern'] ?? 'Tavern');
                $schema = $args['schemas']['tavernnav'] ?? 'town';
                tlschema($schema); 
                addnav($tavernnav); 
                tlschema(); 
                addnav("The Sanctuary","runmodule.php?module=sanctuary"); 
            } 
            break; 
    } 
    return $args; 
} 

function sanctuary_run() {
    require_once("lib/increment_specialty.php"); 
    global $session; 
    page_header("The Sanctuary"); 
    $op = httpget("op"); 
    
    //Preferences and Settings 
    $blessingstoday=get_module_pref("blessingstoday"); 
    $blessingcost=get_module_setting("blessingcost"); 
    $blessingsallowed=get_module_setting("blessingsallowed"); 
    $charmblessing=get_module_setting("charmblessing"); 
    $maxhitblessing=get_module_setting("maxhitblessing"); 
    $extraturnblessing=get_module_setting("extraturnblessing"); 
    $goldblessing=get_module_setting("goldblessing"); 
    $goldblessing=$goldblessing*$session['user']['level']; 
    $healingcost=get_module_setting("healingcost"); 
    
    if ($healingcost>0) {
        $healingcost=$healingcost*$session['user']['level'];
    } 
    if ($healingcost<=0) {
        $loglev = log($session['user']['level']); 
        $cost = ($loglev * ($session['user']['maxhitpoints']-$session['user']['hitpoints'])) + ($loglev*10); 
        $healingcost = round($cost,0); 
        if ($session['user']['level']==1){$healingcost=2;} 
    } 
    
    output("`#`b`cThe Sanctuary`c`b`n"); 
    
    if ($op=="") {
        output("`3As you enter the Sanctuary, the candles light up and a presence of strength fills the room.`n`n"); 
        if ($session['user']['hitpoints'] < $session['user']['maxhitpoints']) {
            output("A voice from out of nowhere says,''`^An offering of `%%s`^ gold pieces is required before I heal your wounds.",$healingcost); 
            if ($blessingcost>1) output("If you wish to be blessed, an offering of `%%s`^ gems is required`3.''`n`n",$blessingcost); 
            else output("If you wish to be blessed, an offering of `%%s`^ gem is required`3.''`n`n",$blessingcost); 
            addnav("Sanctuary"); 
            addnav("`0Heal wounds","runmodule.php?module=sanctuary&op=heal"); 
            addnav("`0Ask for a blessing","runmodule.php?module=sanctuary&op=bless"); 
            addnav("`bReturn`b"); 
            villagenav(); 
        } else if($session['user']['hitpoints'] >= $session['user']['maxhitpoints']) {
            output("A voice from out of nowhere says,''`^A strong, healthy warrior, such as yourself, has no need of healing."); 
            if ($blessingcost>1) output("If you wish to be blessed, an offering of `%%s`^ gems is required.''`n`n",$blessingcost); 
            else output("If you wish to be blessed, an offering of `%%s`^ gem is required`3.''`n`n",$blessingcost); 
            addnav("Sanctuary"); 
            addnav("`0Ask for a blessing","runmodule.php?module=sanctuary&op=bless"); 
            addnav("Return"); 
            villagenav(); 
        } 
    } 
    if ($op=="enter") {
        if ($session['user']['hitpoints'] < $session['user']['maxhitpoints']) {
            output("As you stand back up in front of the altar, the mysterious voice says,''`^An offering of `%%s`^ gold pieces is required before I heal your wounds.",$healingcost); 
            addnav("Sanctuary"); 
            addnav("`0Heal wounds","runmodule.php?module=sanctuary&op=heal"); 
            addnav("`bReturn`b"); 
            villagenav(); 
        } else if($session['user']['hitpoints'] >= $session['user']['maxhitpoints']) {
            output("As you stand back up in front of the altar, the mysterious voice says, ''`^If you wish to be blessed, an offering of `%%s`^ gem is required`3.''`n`n",$blessingcost); 
            addnav("Sanctuary"); 
            addnav("`0Ask for a blessing","runmodule.php?module=sanctuary&op=bless"); 
            addnav("Return"); 
            villagenav(); 
        } 
    } 
    if ($op=="heal") {
        if ($session['user']['gold']>=$healingcost) {
            $session['user']['gold']-=$healingcost; 
            debuglog("spent $healingcost gold on healing"); 
            $diff = round($session['user']['maxhitpoints']-$session['user']['hitpoints']); 
            $session['user']['hitpoints'] += $diff; 
            output("You bow and place the requested offering on the altar. As you do so, the candles in the room flare brightly. You suddenly feel refreshed."); 
            output("`n`n`#You have been healed for `^%s`# points!`0",$diff); 
            addnav("Sanctuary"); 
            addnav("`0Stand before the altar","runmodule.php?module=sanctuary&op=enter"); 
            addnav("`bReturn`b"); 
            villagenav(); 
        } else {
            output("`3The candles grow dim and you feel nothing.`n`nYou recall that the offering was for `$%s`3 gold.",$healingcost); 
            addnav("`bReturn`b"); 
            villagenav(); 
        } 
    } 
    if ($op=="bless") {
        if ($blessingstoday>=$blessingsallowed) {
            output("As you stand before the altar about to ask for another blessing, you are suddenly overcome with guilt for being so greedy."); 
            output("You decide it would be best if you came back tomorrow."); 
            addnav("`bReturn`b"); 
            villagenav(); 
        } else if ($session['user']['gems']>=$blessingcost) {
            $session['user']['gems']=$session['user']['gems']-$blessingcost; 
            $blessingstoday++; 
            set_module_pref("blessingstoday",$blessingstoday); 
            addnav("Sanctuary"); 
            addnav("`0Stand before the altar","runmodule.php?module=sanctuary&op=enter"); 
            addnav("`bReturn`b"); 
            villagenav(); 
            output("A sharp crack of thunder splits the air!`n`n"); 
            $blessing = e_rand(1,22); 
            switch ($blessing) {
                case 1:case 2:case 3:case 4: 
                    output("You suddenly feel much more skillfull!`n`0"); 
                    increment_specialty("`3"); 
                    break; 
                case 5:case 6:case 7:case 8: 
                    if ($charmblessing>1) output("You suddenly feel much more charming!`n`n `^You gain %s charm points!`n`0",$charmblessing); 
                    else output("You suddenly feel much more charming!`n`n `^You gain %s charm point!`n`0",$charmblessing); 
                    $session['user']['charm']+=$charmblessing; 
                    break; 
                case 9:case 10:case 11:case 12: 
                    if ($maxhitblessing>1) output("You suddenly feel much healthier!`n`n `^You gain %s hitpoints!`n`0",$maxhitblessing); 
                    else output("You suddenly feel much healthier!`n`n `^You gain %s hitpoint!`n`0",$maxhitblessing); 
                    $session['user']['maxhitpoints']+=$maxhitblessing; 
                    $session['user']['hitpoints']+=$maxhitblessing; 
                    break; 
                case 13:case 14:case 15:case 16: 
                    if ($extraturnblessing>1) output("You suddenly feel energized!`n`n `^You gain %s extra turns for today!`n`0",$extraturnblessing); 
                    else output("You suddenly feel energized!`n`n `^You gain %s extra turn for today!`n`0",$extraturnblessing); 
                    $session['user']['turns']+=$extraturnblessing; 
                    break; 
                case 17:case 18: case 19:case 20: 
                    if ($goldblessing>1) output("You suddenly realize your coin purse feels much heavier!`n`n `^You gain `%%s`^ extra gold coins!`n`0",$goldblessing); 
                    else output("You suddenly realize your coin purse feels a little heavier!`n`n `^You gain `%%s`^ extra gold coin!`n`0",$goldblessing); 
                    $session['user']['gold']+=$goldblessing; 
                    debuglog("was blessed with $goldblessing gold for offering $blessingcost gem(s) in the Sanctuary."); 
                    break; 
                case 21:case 22: 
                    output("You stand there for a few moments but nothing happens."); 
                    if ($blessingcost>1) {
                        output("You see your gems still on the altar. As you go to retrieve them,"); 
                        output("you see several flaws in them that you hadn't noticed before. You're offering was rejected!"); 
                        output("As you reach out to take the offending gems, they crumble to dust!`n`n"); 
                        output("You make a mental note to carefully examine the next offering you bring.`n`n"); 
                    } else {
                        output("You see your gem still on the altar. As you go to retrieve it,"); 
                        output("you see a flaw in the gem that you hadn't noticed before. You're offering was rejected!"); 
                        output("As you reach out to take the offending gem, it crumbles to dust!`n`n"); 
                        output("You make a mental note to carefully examine the next offering you bring.`n`n"); 
                    } 
                    break; 
            } 
        } else {
            if ($blessingcost>1) output("`3There is nothing but silence. You recall that the offering was for `%%s`3 gems.",$blessingcost); 
            else output("`3There is nothing but silence. You recall that the offering was for `%%s`3 gem.",$blessingcost); 
            addnav("`bReturn`b"); 
            villagenav(); 
        } 
    }
    page_footer(); 
} 

function sanctuary_runevent(){} 
?>
