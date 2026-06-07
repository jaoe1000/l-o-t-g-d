<?php


function newdaylog_getmoduleinfo(){
    $info = array(
        "name"=>"Newday Log (put certain stuff into the debuglog on a player newday to track stuff down)",
        "version"=>"1.0",
        "author"=>"`2Oliver Brendel",
        "category"=>"Administrative",
        "download"=>"http://lotgd-downloads.com",
      
    );
    return $info;
}

function newdaylog_install(){
	module_addhook_priority("newday",INT_MAX);
    return true;
}

function newdaylog_uninstall(){
    return true;
}

function newdaylog_dohook($hookname,$args){
    global $session;
    switch($hookname){
        case "newday":
            require_once("lib/debuglog.php");
            $u=&$session['user'];
            restore_buff_fields();
            
            // Safely calculate donations
            $donationTotal = $u['donation'] ?? 0;
            $donationSpent = $u['donationspent'] ?? 0;
            $donationUnused = $donationTotal - $donationSpent;

            $message=sprintf(
                "%s (STR %s, DEX %s, CON %s, INT %s, WIS %s) started the newday as Level %s (%s experience) with %s DKs as Clanrank %s, with %s gold on hand and %s gold at the bank, %s gems, %s maxhp, level %s weapon, level %s armor, %s favors, mount %s, %s donationpoints unused and %s total.",
                sanitize($u['name'] ?? 'Unknown'),
                $u['strength'] ?? 0,
                $u['dexterity'] ?? 0,
                $u['constitution'] ?? 0,
                $u['intelligence'] ?? 0,
                $u['wisdom'] ?? 0,
                $u['level'] ?? 1,
                $u['experience'] ?? 0,
                $u['dragonkills'] ?? 0,
                $u['clanrank'] ?? 0,
                $u['gold'] ?? 0,
                $u['goldinbank'] ?? 0,
                $u['gems'] ?? 0,
                $u['maxhitpoints'] ?? 10,
                $u['weapondmg'] ?? 0,
                $u['armordef'] ?? 0,
                $u['deathpower'] ?? 0,
                $u['hashorse'] ?? 0,
                $donationUnused,
                $donationTotal
            );
            debuglog($message,$session['user']['acctid']);
            calculate_buff_fields();
        break;
    }
    return $args;
}
function newdaylog_run () {
}

?>
