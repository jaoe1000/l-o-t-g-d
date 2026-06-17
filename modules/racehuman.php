<?php
/**
 * Advanced Race Module - Human
 * Built from the Advanced Race Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function racehuman_getmoduleinfo() {
    return [
        "name" => "Race - Human",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Race",
        "download" => "", 
        "settings" => [
            "Human - Race Settings,title",
            "villagename" => "Name for the Human city,|Oakhaven", 
            "stableowner" => "Name of the city stable-owner,|Master Horatio", 
            "minedeathchance" => "Chance for this race to die in the mine,range,0,100,1|20", 
            "dklimit" => "Limit the Race to a certain amount of dragon kills?,int|0",
            "ff" => "Extra forest fights per day,int|1",
        ],
        "prefs" => [
            "Human - User Prefs,title", 
            "investment" => "Commerce Guild investment,int|0",
            "academy_purchased" => "Academy masterclass purchased this DK,bool|0",
        ]
    ];
}

function racehuman_install() {
    module_addhook("chooserace");
    module_addhook("setrace");
    module_addhook("villagetext");
    module_addhook("stabletext");
    module_addhook("travel");
    module_addhook("charstats");
    module_addhook("validlocation");
    module_addhook("moderate");
    module_addhook("changesetting");
    module_addhook("raceminedeath");
    module_addhook("stablelocs");
    module_addhook("newday");
    module_addhook("village");
    module_addhook("weaponstext");
    module_addhook("armortext");
    module_addhook("trainingtitle");
    module_addhook("modify-master");
    debug("Installed 'Human' advanced race module.");
    return true;
}

function racehuman_uninstall() {
    global $session;
    $vname = getsetting("villagename", LOCATION_FIELDS);
    $gname = get_module_setting("villagename");
    
    // Evict players from the custom city back to the capital
    $sql = "UPDATE " . db_prefix("accounts") . " SET location='$vname' WHERE location = '$gname'";
    db_query($sql);
    if (($session['user']['location'] ?? '') === $gname) {
        $session['user']['location'] = $vname;
    }
    
    // Force anyone who was this race to rechoose race
    $sql = "UPDATE " . db_prefix("accounts") . " SET race='" . RACE_UNKNOWN . "' WHERE race='Human'";
    db_query($sql);
    if (($session['user']['race'] ?? '') === 'Human') {
        $session['user']['race'] = RACE_UNKNOWN;
    }
    
    debug("Uninstalled 'Human' race module.");
    return true;
}

function racehuman_dohook($hookname, $args) {
    global $session, $resline;
    $city = get_module_setting("villagename");
    $race = "Human"; 
    
    $userRace = $session['user']['race'] ?? '';
    
    switch($hookname) {
        case "raceminedeath":
            if ($userRace === $race) {
                $args['chance'] = get_module_setting("minedeathchance");
            }
            break;

        case "changesetting":
            if (($args['setting'] ?? '') === "villagename" && ($args['module'] ?? '') === "racehuman") {
                if (($session['user']['location'] ?? '') === $args['old'])
                    $session['user']['location'] = $args['new'];
                $sql = "UPDATE " . db_prefix("accounts") . " SET location='" . $args['new'] . "' WHERE location='" . $args['old'] . "'";
                db_query($sql);
                $travel_mod = racehuman_get_travel_mod();
                if ($travel_mod !== false) {
                    $sql = "UPDATE " . db_prefix("module_userprefs") . " SET value='" . $args['new'] . "' WHERE modulename='$travel_mod' AND setting='homecity' AND value='" . $args['old'] . "'";
                    db_query($sql);
                }
            }
            break;

        case "charstats":
            if ($userRace === $race) {
                addcharstat("Vital Info");
                addcharstat("Race", translate_inline("`^$race"));
            }
            break;

        case "chooserace":
            if (($session['user']['dragonkills'] ?? 0) >= (int)get_module_setting("dklimit")) {
                $atrans = translate_inline("In the bustling trade squares of %s"); 
                $rtrans = translate_inline(", surrounded by ambitious merchants and unyielding stone walls.`n`n");
                addnav(["%s (%s)", $city, $race], "newday.php?setrace=$race$resline");
                output_notl("`0<a href='newday.php?setrace=$race$resline'>$atrans</a>$rtrans", $city, true);
                addnav("", "newday.php?setrace=$race$resline");
            }
            break;

        case "setrace":
            if ($userRace === $race) {
                output("`&You embrace your human heritage, granting you unparalleled adaptability and an extra forest fight per day!`n"); 
                $travel_mod = racehuman_get_travel_mod();
                if ($travel_mod !== false) {
                    if (($session['user']['dragonkills'] ?? 0) == 0 && ($session['user']['age'] ?? 0) == 0) {
                        set_module_setting("newest-$city", $session['user']['acctid'], $travel_mod);
                    }
                    set_module_pref("homecity", $city, $travel_mod);
                    if (($session['user']['age'] ?? 0) == 0)
                        $session['user']['location'] = $city;
                }
            }
            break;

        case "newday":
            if ($userRace === $race) {
                racehuman_checkcity();
                
                $ff = (int)get_module_setting("ff");
                
                if ($ff > 0) {
                    $args['turnstoday'] = ($args['turnstoday'] ?? '') . ", Race ($race): $ff";
                    $session['user']['turns'] += $ff;
                    output("`n`^Driven by human ambition, you wake up feeling energetic and gain `%$ff`^ extra turn(s) today!`n`0");
                }
                
                $investment = (int)get_module_pref("investment");
                if ($investment > 0) {
                    $chance = e_rand(1, 100);
                    if ($chance <= 5) {
                        $lost = round($investment * 0.5);
                        $investment -= $lost;
                        set_module_pref("investment", $investment);
                        output("`n`\$Bad news! One of your Oakhaven trade caravans was raided by bandits! You lost `^%s` gold from your investments.`n", $lost);
                    } else {
                        $dividend = round($investment * 0.05);
                        $session['user']['gold'] += $dividend;
                        output("`n`@Good news! Your trade investments in the Oakhaven Commerce Guild returned a dividend of `^%s` gold!`n", $dividend);
                    }
                }
            }
            break;

        case "validlocation":
            $travel_mod = racehuman_get_travel_mod();
            if ($travel_mod !== false)
                $args[$city] = "village-$race";
            break;

        case "moderate":
            $travel_mod = racehuman_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("commentary");
                $args["village-$race"] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "travel":
            $travel_mod = racehuman_get_travel_mod();
            if ($travel_mod !== false) {
                $capital = getsetting("villagename", LOCATION_FIELDS);
                $hotkey = substr($city, 0, 1);
                tlschema("module-" . $travel_mod);
                $param = ($travel_mod === "villages") ? "village" : "city";
                if (($session['user']['location'] ?? '') === $capital) {
                    addnav("Safer Travel");
                    addnav(["%s?Go to %s", $hotkey, $city], "runmodule.php?module=$travel_mod&op=travel&$param=$city");
                } elseif (($session['user']['location'] ?? '') !== $city) {
                    addnav("More Dangerous Travel");
                    addnav(["%s?Go to %s", $hotkey, $city], "runmodule.php?module=$travel_mod&op=travel&$param=$city&d=1");
                }
                if (($session['user']['superuser'] ?? 0) & SU_EDIT_USERS) {
                    addnav("Superuser");
                    addnav(["%s?Go to %s", $hotkey, $city], "runmodule.php?module=$travel_mod&op=travel&$param=$city&su=1");
                }
                tlschema();
            }
            break;  

        case "villagetext":
            racehuman_checkcity();
            if (($session['user']['location'] ?? '') === $city) {
                $args['text'] = ["`\$`c`b%s`b`c`n`^The cobblestone streets of %s bustle with merchants, guards, and adventurers alike. It is a beacon of human civilization.`n", $city, $city];
                $args['schemas']['text'] = "module-racehuman";
                $args['description'] = ["`\$`c`b%s`b`c`n`^The cobblestone streets of %s bustle with merchants, guards, and adventurers alike. It is a beacon of human civilization.`n", $city, $city];
                $args['schemas']['description'] = "module-racehuman";
                $args['clock'] = "`n`7The town square clock reads `@%s`7.`n";
                $args['schemas']['clock'] = "module-racehuman";
                
                if (is_module_active("calendar")) {
                    $args['calendar'] = "`n`\$A posted parchment calendar reads `&%s`\$, `&%s %s %s`\$.`n";
                    $args['schemas']['calendar'] = "module-racehuman";
                }
                $args['title'] = ["%s", $city];
                $args['schemas']['title'] = "module-racehuman";
                $args['sayline'] = "says";
                $args['schemas']['sayline'] = "module-racehuman";
                $args['talk'] = "`n`&Nearby, some town guards stand talking:`n";
                $args['schemas']['talk'] = "module-racehuman";
                
                $travel_mod = racehuman_get_travel_mod();
                $new = $travel_mod !== false ? get_module_setting("newest-$city", $travel_mod) : 0;
                if ($new != 0) {
                    $sql =  "SELECT name FROM " . db_prefix("accounts") . " WHERE acctid='$new'";
                    $result = db_query_cached($sql, "newest-$city");
                    $row = db_fetch_assoc($result);
                    $args['newestplayer'] = $row['name'] ?? '';
                    $args['newestid'] = $new;
                } else {
                    $args['newestplayer'] = $new;
                    $args['newestid'] = "";
                }
                
                if ($new == ($session['user']['acctid'] ?? 0)) {
                    $args['newest'] = "`n`7You are the newest resident of this bustling city.";
                } else {
                    $args['newest'] = "`n`7The newest resident of this bustling city is `&%s`7.";
                }
                $args['schemas']['newest'] = "module-racehuman";
                $args['section'] = "village-$race";
                
                $args['stablename'] = "Oakhaven Stables";
                $args['schemas']['stablename'] = "module-racehuman";
                $args['gatenav'] = "City Gates";
                $args['schemas']['gatenav'] = "module-racehuman";
                $args['fightnav'] = "Training Grounds";
                $args['schemas']['fightnav'] = "module-racehuman";
                $args['marketnav'] = "Merchant Square";
                $args['schemas']['marketnav'] = "module-racehuman";
                $args['tavernnav'] = "The Boar's Head Tavern";
                $args['schemas']['tavernnav'] = "module-racehuman";
                
                // Override standard location nav labels
                $args['navs']['weapons'] = "Oakhaven Steel & Iron";
                $args['navs']['armor'] = "Oakhaven Heavy Plates";
                $args['navs']['train'] = "u?Oakhaven Training Academy";
                $args['schemas']['navs']['weapons'] = "module-racehuman";
                $args['schemas']['navs']['armor'] = "module-racehuman";
                $args['schemas']['navs']['train'] = "module-racehuman";

                unblocknav("stables.php");
            }
            break;

        case "stabletext":
            if (($session['user']['location'] ?? '') !== $city) break;
            $owner = get_module_setting("stableowner");
            
            $args['title'] = "Oakhaven Stables";
            $args['schemas']['title'] = "module-racehuman";
            $args['desc'] = "`\$The smell of hay and horses fills the air. $owner is busy brushing down a sturdy stallion.";
            $args['schemas']['desc'] = "module-racehuman";
            $args['lad'] = "Sir";
            $args['schemas']['lad'] = "module-racehuman";
            $args['lass'] = "Miss";
            $args['schemas']['lass'] = "module-racehuman";
            $args['nosuchbeast'] = "`3\"`7I don't stock that creature.`3\", says $owner.`3";
            $args['schemas']['nosuchbeast'] = "module-racehuman";
            $args['finebeast'] = [
                "`3\"`7A fine steed for a brave adventurer!`3\"`n`n",
                "`3\"`7You won't find a better mount in all the realm.`3\"`n`n",
            ];
            $args['schemas']['finebeast'] = "module-racehuman";
            $args['toolittle'] = "`%\"`7You seem to be a bit short on gold for that one,`%\" $owner says politely.";
            $args['schemas']['toolittle'] = "module-racehuman";
            $args['replacemount'] = "`%You trade in your `^%s for a strong `&%s`%.";
            $args['schemas']['replacemount'] = "module-racehuman";
            $args['newmount'] = "`@You hand over the gold and receive a sturdy `&%s`@.";
            $args['schemas']['newmount'] = "module-racehuman";
            $args['nofeed'] = "`3\"`7I'm currently out of feed, my apologies.`3\"";
            $args['schemas']['nofeed'] = "module-racehuman";
            $args['nothungry'] = "`&%s`6 doesn't look hungry at all.";
            $args['schemas']['nothungry'] = "module-racehuman";
            $args['halfhungry'] = "`&%s`6 eats the feed happily.";
            $args['schemas']['halfhungry'] = "module-racehuman";
            $args['hungry'] = "`6%s`6 devours the feed instantly!";
            $args['schemas']['hungry'] = "module-racehuman";
            $args['mountfull'] = "`n`6\"`^Your %s`^ is completely full now.`6\"";
            $args['schemas']['mountfull'] = "module-racehuman";
            $args['nofeedgold'] = "`6\"`^You don't have enough money to feed your mount.`6\"";
            $args['schemas']['nofeedgold'] = "module-racehuman";
            $args['confirmsale'] = "`n`n`6Are you sure you wish to sell your mount?";
            $args['schemas']['confirmsale'] = "module-racehuman";
            $args['mountsold'] = "`6You hand over the reins and receive your gold.";
            $args['schemas']['mountsold'] = "module-racehuman";
            $args['offer'] = "`n`n`6\"`^I'll buy that beast off your hands if you're looking to sell,`6\" $owner offers.";
            $args['schemas']['offer'] = "module-racehuman";
            break;

        case "stablelocs":
            $travel_mod = racehuman_get_travel_mod();
            if ($travel_mod !== false) {
                tlschema("mounts");
                $args[$city] = sprintf_translate("City of %s", $city); 
                tlschema();
            }
            break;

        case "village":
            if (($session['user']['location'] ?? '') === $city && ($session['user']['race'] ?? '') === $race) {
                // Add Commerce Guild to Merchant Square
                addnav($args['marketnav']);
                addnav("Commerce Guild", "runmodule.php?module=racehuman&op=guild");
                // Add Academy Masterclass to Training Grounds
                addnav($args['fightnav']);
                addnav("Academy Masterclass", "runmodule.php?module=racehuman&op=academy");
            }
            break;

        case "weaponstext":
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['weaponvalue'] * 0.75), 0);
                $args['title'] = "Oakhaven Steel & Iron";
                $args['desc'] = [
                    "`&You enter Oakhaven's grand weaponry forge. Master Horatio's metalworkers are busy hammering out stout steel blades.`n`n",
                    "Racks of sturdy broadswords, iron spears, and solid round shields line the cobblestone walls.`n`n"
                ];
                $args['tradein'] = [
                    "`7You step up to the heavy counter and display your weapon.`n",
                    [
                        "`&The chief blacksmith wipes sweat from his brow, looks at your gear and says, \"`#I'll give you `^%s`# trade-in value for your `5%s`#.\"`n`n",
                        $tradeinvalue,
                        $session['user']['weapon']
                    ]
                ];
            }
            break;

        case "armortext":
            if (($session['user']['location'] ?? '') === $city) {
                $tradeinvalue = round(($session['user']['armorvalue'] * 0.75), 0);
                $args['title'] = "Oakhaven Heavy Plates";
                $args['desc'] = [
                    "`&Suits of polished plate mail, heavy iron breastplates, and stout shields stand in orderly rows inside the armoury.`n`n",
                    "This armor is forged to withstand the heaviest blows from dragons and wild beasts.`n`n"
                ];
                $args['tradein'] = [
                    "`7You show your current protection to the armorer.`n",
                    [
                        "`&The armorer taps on your chestplate and nods. \"`#I can offer you `^%s`# trade-in value for your `5%s`#.\"`n`n",
                        $tradeinvalue,
                        $session['user']['armor']
                    ]
                ];
            }
            break;

        case "trainingtitle":
            if (($session['user']['location'] ?? '') === $city) {
                $args['title'] = "Oakhaven Training Academy";
            }
            break;

        case "modify-master":
            if (($session['user']['location'] ?? '') === $city) {
                $args['creaturename'] = "Commander Landon";
                $args['creatureweapon'] = "Steel Broadsword";
                $args['creaturewin'] = "You fight well for a human, but you must strive harder! Go back and train!";
                $args['creaturelose'] = "Your adaptation is remarkable. You have surpassed my teachings.";
            }
            break;
    }
    return $args;
}

function racehuman_run() {
    global $session;
    $op = httpget('op');
    $city = get_module_setting("villagename");
    
    // Check if the user is a Human
    if (($session['user']['race'] ?? '') !== 'Human') {
        page_header("Access Denied");
        output("`\$Only Humans may access this location in Oakhaven.`n`n");
        addnav("Return to Oakhaven", "village.php");
        page_footer();
        return;
    }
    
    if ($op == "guild") {
        page_header("Oakhaven Commerce Guild");
        output("`c`b`^Oakhaven Commerce Guild`b`c`n`n");
        output("`7Welcome to the Commerce Guild, where human capital drives mercantile prosperity. Here, you can invest gold into long-range trade caravans.`n`n");
        
        $investment = (int)get_module_pref("investment");
        output("`7Your current active investment: `^%s`7 gold.`n`n", $investment);
        
        $subop = httpget('subop');
        if ($subop == "invest") {
            $amt = (int)httppost('amount');
            if ($amt <= 0) {
                $amt = (int)httpget('amount');
            }
            if ($amt > 0) {
                if ($session['user']['gold'] >= $amt) {
                    $session['user']['gold'] -= $amt;
                    $investment += $amt;
                    set_module_pref("investment", $investment);
                    output("`@You have invested `^%s`@ gold into the guild caravans!`n`n", $amt);
                } else {
                    output("`\$You do not have enough gold on hand to make that investment.`n`n");
                }
            }
        } elseif ($subop == "withdraw") {
            if ($investment > 0) {
                $tax = round($investment * 0.10);
                $net = $investment - $tax;
                $session['user']['gold'] += $net;
                set_module_pref("investment", 0);
                output("`@You have withdrawn your investments. After a 10%% guild fee (`^%s`@ gold), you receive `^%s`@ gold.`n`n", $tax, $net);
                $investment = 0;
            } else {
                output("`7You do not have any active investments to withdraw.`n`n");
            }
        }
        
        output("`5Caravans yield a `^5%% dividend`5 every new day. However, they run a `\$5%% risk`5 of being raided by bandits (losing 50%% of invested capital).`n`n");
        
        // Investment form
        output("`7How much would you like to invest?`n");
        rawoutput("<form action='runmodule.php?module=racehuman&op=guild&subop=invest' method='POST'>");
        rawoutput("<input type='number' name='amount' min='1' max='".(int)$session['user']['gold']."' placeholder='Gold amount' style='padding: 5px; border-radius: 4px; border: 1px solid #ccc;'>");
        rawoutput(" <input type='submit' class='button' value='Invest Gold'>");
        rawoutput("</form>");
        
        addnav("", "runmodule.php?module=racehuman&op=guild&subop=invest");
        
        if ($investment > 0) {
            addnav("Withdraw Investments", "runmodule.php?module=racehuman&op=guild&subop=withdraw");
        }
        addnav("Return to Oakhaven", "village.php");
        page_footer();
    } elseif ($op == "academy") {
        page_header("Oakhaven Combat Academy");
        output("`c`b`^Oakhaven Combat Academy`b`c`n`n");
        output("`7The trainers of the Academy offer advanced masterclasses in weapon handling and physical resilience, helping you unlock your full potential.`n`n");
        
        $purchased = get_module_pref("academy_purchased");
        $cost_gold = 500;
        $cost_gems = 1;
        
        $subop = httpget('subop');
        if ($subop == "train") {
            if ($purchased) {
                output("`\$You have already completed a masterclass during this lifetime. You must wait until your next dragon kill to learn more.`n`n");
            } elseif ($session['user']['gold'] < $cost_gold || $session['user']['gems'] < $cost_gems) {
                output("`\$You do not possess the required `^%s gold`0 or `@%s gem(s)`0 to pay the trainers.`n`n", $cost_gold, $cost_gems);
            } else {
                $stat = httpget('stat');
                if ($stat == "attack") {
                    $session['user']['gold'] -= $cost_gold;
                    $session['user']['gems'] -= $cost_gems;
                    $session['user']['attack'] += 1;
                    set_module_pref("academy_purchased", 1);
                    output("`@You attend the weapon masterclass and permanently gain `^+1 Attack`@!`n`n");
                    $purchased = true;
                } elseif ($stat == "defense") {
                    $session['user']['gold'] -= $cost_gold;
                    $session['user']['gems'] -= $cost_gems;
                    $session['user']['defense'] += 1;
                    set_module_pref("academy_purchased", 1);
                    output("`@You attend the defensive masterclass and permanently gain `^+1 Defense`@!`n`n");
                    $purchased = true;
                }
            }
        }
        
        output("`7Attendance cost: `^%s gold`7 and `@%s gem(s)`7.`n`n", $cost_gold, $cost_gems);
        
        if (!$purchased) {
            addnav("Attend Masterclass");
            addnav("Train Attack (+1 Attack)", "runmodule.php?module=racehuman&op=academy&subop=train&stat=attack");
            addnav("Train Defense (+1 Defense)", "runmodule.php?module=racehuman&op=academy&subop=train&stat=defense");
        } else {
            output("`&You have already attended the masterclass for this dragon kill.`n`n");
        }
        
        addnav("Return to Oakhaven", "village.php");
        page_footer();
    }
}



function racehuman_checkcity() {
    global $session;
    $race = "Human";
    $city = get_module_setting("villagename");
    
    $travel_mod = racehuman_get_travel_mod();
    if ($travel_mod !== false) {
        if (($session['user']['race'] ?? '') === $race) {
            if (get_module_pref("homecity", $travel_mod) !== $city) {
                set_module_pref("homecity", $city, $travel_mod);
            }
        }
    }
    return true;
}

function racehuman_get_travel_mod() {
    if (is_module_active("villages")) {
        return "villages";
    } elseif (is_module_active("cities")) {
        return "cities";
    } elseif (is_module_active("multi-towns")) {
        return "multi-towns";
    }
    return false;
}
?>
