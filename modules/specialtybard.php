<?php
/**
 * Advanced Specialty Module - Bard
 * Built from the Advanced Specialty Blueprint
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtybard_getmoduleinfo() {
    return [
        "name" => "Specialty - Bard Songs",
        "version" => "1.0",
        "author" => "Dragon.NDGaming", 
        "category" => "Specialties",
        "download" => "", 
        "settings" => [
            "Bard Songs - Settings,title",
            "dklimit" => "Limit the Specialty to a certain amount of dragon kills?,int|0",
            "wepchange" => "Will weapons & armour change names?,bool|1", 
            "IE- an 'Adze' would become a 'Cantor Adze',note", 
        ],
        "prefs" => [
            "Bard Songs - User Prefs,title",
            "skill" => "Skill points in Specialty Powers,int|0", 
            "uses" => "Uses of Specialty Powers allowed,int|0", 
            "class" => "Main Class,enum,Unset,,Magic,magic,Melee,melee,Ranging,ranging|", 
            "subclass" => "Subclass,enum,Unset,0,Cantor,1,Blade,2,Troubadour,3|0", 
        ]
    ];
}

function specialtybard_install() {
    module_addhook("choose-specialty");
    module_addhook("set-specialty");
    module_addhook("fightnav-specialties");
    module_addhook("apply-specialties");
    module_addhook("newday");
    module_addhook("incrementspecialty");
    module_addhook("specialtynames");
    module_addhook("specialtymodules");
    module_addhook("specialtycolor");
    module_addhook("dragonkill");
    module_addhook("newday-intercept");
    module_addhook("boughtweapon");
    module_addhook("boughtarmor");
    debug("Installed 'Bard Songs' specialty module.");
    return true;
}

function specialtybard_uninstall() {
    $spec = "BA"; 
    global $session;
    
    // Reset the specialty for any user currently possessing it
    $sql = "UPDATE " . db_prefix("accounts") . " SET specialty='' WHERE specialty='".$spec."'";
    db_query($sql);
    
    if (($session['user']['specialty'] ?? '') === $spec) {
        $session['user']['specialty'] = "";
    }
    
    debug("Uninstalled 'Bard Songs' specialty module.");
    return true;
}

function specialtybard_dohook($hookname, $args) {
    global $session, $resline;
    $spec = "BA"; 
    $name = "Bard Songs"; 
    $ccode = "`5"; 
    
    $userSpec = $session['user']['specialty'] ?? '';
    
    switch($hookname) {
        case "specialty-codex":
            $args[$spec] = [
                'name' => $name,
                'color' => $ccode,
                'desc' => 'Melodies that alter the battlefield based on your class path.',
                'subclasses' => [
                    'magic' => [
                        'name' => 'Cantor (Magic Path)',
                        'techniques' => [
                            1 => ['name' => 'Fascinating Melody', 'cost' => 1, 'desc' => 'Strum a soft tune. Reduces enemy attack by 40% (lasts 4-9 rounds based on charm).'],
                            3 => ['name' => 'Mass Suggestion', 'cost' => 3, 'desc' => 'Sing an uplifting aria. Summons a sylvan beast helper minion (lasts 4-9 rounds based on charm).'],
                        ]
                    ],
                    'melee' => [
                        'name' => 'Blade (Melee Path)',
                        'techniques' => [
                            1 => ['name' => 'Defensive Flourish', 'cost' => 1, 'desc' => 'Flashy defensive flourish. Increases defense by 40% (lasts 4-9 rounds based on charm).'],
                            3 => ['name' => 'Bardic Inspiration', 'cost' => 3, 'desc' => 'Combat coordination song. Increases attack by 40% and defense by 20% (lasts 4-9 rounds based on charm).'],
                        ]
                    ],
                    'ranging' => [
                        'name' => 'Troubadour (Ranging Path)',
                        'techniques' => [
                            1 => ['name' => 'Discordant Chord', 'cost' => 1, 'desc' => ' discordant note. Reduces enemy defense by 30% (lasts 4-9 rounds based on charm).'],
                            3 => ['name' => 'Vampiric Melody', 'cost' => 3, 'desc' => 'Haunting tune. Drains life force from the enemy, healing player for 100% of damage dealt (lasts 4-9 rounds based on charm).'],
                        ]
                    ]
                ]
            ];
            break;

        case "newday-intercept":
            specialtybard_change();
            break;

        case "newday":
            if ($userSpec === $spec) {
                $bonus = (int)getsetting("specialtybonus", 1);
                
                if ($bonus == 1) {
                    output("`n`2For your specialty in %s%s`2, you receive `^1`2 extra `&%s%s`2 use for today.`n", $ccode, $name, $ccode, $name);
                } else {
                    output("`n`2For your specialty in %s%s`2, you receive `^%s`2 extra `&%s%s`2 uses for today.`n", $ccode, $name, $bonus, $ccode, $name);
                }
                
                $amt = (int)(get_module_pref("skill") / 3) + $bonus;
                
                // Charm scaling bonus
                $charm = (int)($session['user']['charm'] ?? 0);
                $charmbonus = (int)($charm / 10);
                if ($charmbonus > 0) {
                    output("`n`2For being such a charmer, your silver tongue grants you `^%s`2 extra use point(s) today!`n", $charmbonus);
                    $amt += $charmbonus;
                }
                
                set_module_pref("uses", $amt);
            }
            break;

        case "dragonkill":
            set_module_pref("uses", 0);
            set_module_pref("skill", 0);
            break;

        case "choose-specialty":
            if ($userSpec === "" || $userSpec === '0') {
                $can_select = true;

                if (($session['user']['dragonkills'] ?? 0) < (int)get_module_setting("dklimit")) {
                    $can_select = false;
                }

                if ($can_select) {
                    addnav("$ccode$name`0", "runmodule.php?module=specialtybard&spec=".$spec.$resline);
                    $t1 = translate_inline("Telling the world of your artistic greatness"); 
                    $t2 = appoencode(translate_inline("$ccode$name`0"));
                    rawoutput("<a href='runmodule.php?module=specialtybard&spec=".$spec.$resline."'>$t1 ($t2)</a><br>");
                    addnav("", "runmodule.php?module=specialtybard&spec=".$spec.$resline);
                } else {
                    set_module_pref("class", "");
                    set_module_pref("subclass", "");
                }
            }
            break;

        case "set-specialty":
            if ($userSpec === $spec) {
                page_header($name);
                $resline = httpget("resline");
                $spec = httpget("spec");
                $mc = httpget("mc");    
                $sc = httpget("sc");
                set_module_pref("class", $mc);
                set_module_pref("subclass", $sc);
                
                output("`#`c`bYou are a Bard!`b`c`n");
                output("`@Your parent called you a jobless bum, but you knew hedonism and song go a long way. Your melodies will alter the battlefield!`n");
            }
            break;

        case "fightnav-specialties":
            if ($userSpec === $spec) {
                $stuff = specialtybard_texts(get_module_pref("class"), get_module_pref("subclass"));
                $uses = (int)get_module_pref("uses");
                $script = $args['script'] ?? '';
                
                if ($uses > 0) {
                    addnav(["$ccode ".specialtybard_namer()." (%s points)`0", $uses], "");
                }
                
                foreach ($stuff as $points => $rubbish) {
                    foreach ($rubbish as $buffName => $morerubbish) {
                        if ($buffName === "name") {
                            if ($uses >= $points) {
                                $morei = str_replace("`%", "`%%", $morerubbish);
                                addnav(["`@ &#149; $morei`@ (%s)`0", $points], $script."op=fight&skill=$spec&l=$points", true);
                            }
                        }
                    }
                }
            }
            break;

        case "apply-specialties":
            $skill = httpget('skill');
            $l = (int)httpget('l');
            $stuff = specialtybard_texts(get_module_pref("class"), get_module_pref("subclass"));
            
            if ($skill === $spec) {
                if ((int)get_module_pref("uses") >= $l) {
                    if (isset($stuff[$l])) {
                        $buff_to_apply = $stuff[$l];
                        apply_buff($spec.$l, $buff_to_apply);
                    } else {
                        apply_buff($spec."0", [
                            "startmsg" => "You try to sing, but you crack your voice. How embarrassing.", 
                            "rounds" => 1,
                            "schema" => "specialtybard"
                        ]);
                    }
                    set_module_pref("uses", (int)get_module_pref("uses") - $l);
                }
            }
            break;

        case "incrementspecialty":
            if($userSpec === $spec) {
                $new = (int)get_module_pref("skill") + 1;
                set_module_pref("skill", $new);
                $c = $args['color'] ?? '';
                
                output("`n%sYou gain a level in `&%s%s to `#%s%s!", $c, $name, $c, $new, $c);
                $x = $new % 3;
                
                if ($x == 0) {
                    output("`n`^You gain an extra use point!`n");
                    set_module_pref("uses", (int)get_module_pref("uses") + 1);
                } else {
                    if (3-$x == 1) {
                        output("`n`^Only 1 more skill level until you gain an extra use point!`n");
                    } else {
                        output("`n`^Only %s more skill levels until you gain an extra use point!`n", (3-$x));
                    }
                }
                output_notl("`0");
            }
            break;

        case "specialtynames":
            if (($session['user']['dragonkills'] ?? 0) >= (int)get_module_setting("dklimit")) {
                $args[$spec] = translate_inline($name);
            }
            break;

        case "specialtymodules":
            $args[$spec] = "specialtybard";
            break;

        case "specialtycolor":
            $args[$spec] = $ccode;
            break;

        case "boughtweapon":
        case "boughtarmor":
            if (get_module_setting("wepchange") == 1 && $userSpec === $spec) {
                $nstr = "`%" . specialtybard_namer("Y");
                if (!preg_match('/' . preg_quote($nstr, '/') . '/', $args['name'] ?? '')) {
                    $args['name'] = $nstr . " " . $args['name'];
                }
            }
            break;
    }
    return $args;
}

function specialtybard_run() {
    global $session;
    $op = httpget('op');
    $spec = httpget('spec');
    $resline = httpget('resline');
    
    if ($op == "") {
        page_header("Choose Bard Specialty Path"); 
        output("`c`b`#Choose your Bardic Specialty Path`b`c`n"); 
        output("`@A Bard can study different paths depending on their desired combat tree. Choose your subclass path below:`n`n"); 
        
        addnav("Choose Path"); 
        addnav("Cantor (Magic Path)", "newday.php?setspecialty=$spec&mc=magic&sc=1$resline"); 
        addnav("Blade (Melee Path)", "newday.php?setspecialty=$spec&mc=melee&sc=2$resline"); 
        addnav("Troubadour (Ranging Path)", "newday.php?setspecialty=$spec&mc=ranging&sc=3$resline"); 
    }
    page_footer();
}

function specialtybard_change() {
    global $session;
    $spec = "BA"; 
    
    if (get_module_setting("wepchange") == 1 && ($session['user']['specialty'] ?? '') === $spec) {
        $nstr = "`%".specialtybard_namer("Y");
        
        if (!preg_match('/' . preg_quote($nstr, '/') . '/', $session['user']['weapon'] ?? '')) {
            $n = $nstr." ".($session['user']['weapon'] ?? '');
            if (strlen($n) < 50) $session['user']['weapon'] = $n;
        }
        
        if (!preg_match('/' . preg_quote($nstr, '/') . '/', $session['user']['armor'] ?? '')) {
            $n = $nstr." ".($session['user']['armor'] ?? '');
            if (strlen($n) < 50) $session['user']['armor'] = $n;
        }
    }
}

function specialtybard_namer($type='X') {
    $class = [];
    
    $class['magic']['1'] = "Cantor Songs"; 
    $class['melee']['2'] = "Blade Flourishes"; 
    $class['ranging']['3'] = "Troubadour Chords"; 
    
    $class['1'] = "Cantor"; 
    $class['2'] = "Blade"; 
    $class['3'] = "Troubadour"; 
    
    if ($type === "X") {
        return $class[get_module_pref("class")][get_module_pref("subclass")] ?? '';
    } else {
        return $class[get_module_pref("subclass")] ?? '';
    }
}

function specialtybard_texts($class, $subclass) {
    global $session;
    $array = [];
    $spec = "BA"; 
    $rounds = 4 + (int)(($session['user']['charm'] ?? 0) / 20);
    if ($rounds > 9) $rounds = 9;
    
    // Magic Cantor path
    $array['magic']['1']['1'] = [ 
        "startmsg" => "`5You strum a soft tune. {badguy} is fascinated and loses focus!`",
        "name" => "`%Fascinating Melody",
        "rounds" => $rounds,
        "wearoff" => "Your song ends.",
        "badguyatkmod" => 0.60,
    ];
    $array['magic']['1']['3'] = [ 
        "startmsg" => "`5You sing an uplifting aria. A sylvan beast comes to your aid!`",
        "name" => "`%Mass Suggestion",
        "rounds" => $rounds,
        "minioncount" => 1,
        "maxbadguydamage" => (int)($session['user']['level'] ?? 1) * 2,
        "minbadguydamage" => 5,
        "effectmsg" => "`%The charmed beast claws {badguy} for {damage} damage!`",
        "effectnodmgmsg" => "`%The charmed beast misses.`",
        "wearoff" => "The sylvan beast runs back to the woods.",
    ];
	
    // Melee Blade path
    $array['melee']['2']['1'] = [ 
        "startmsg" => "`5You perform a flashy defensive flourish with your blade!`",
        "name" => "`%Defensive Flourish",
        "rounds" => $rounds,
        "wearoff" => "You lose your stance.",
        "defmod" => 1.40,
    ];
    $array['melee']['2']['3'] = [ 
        "startmsg" => "`5Your song inspires great combat coordination!`",
        "name" => "`%Bardic Inspiration",
        "rounds" => $rounds,
        "wearoff" => "The inspiration fades.",
        "atkmod" => 1.40,
        "defmod" => 1.20,
    ];
    
    // Ranging Troubadour path
    $array['ranging']['3']['1'] = [ 
        "startmsg" => "`5You pluck a sharp, discordant note on your lute!`",
        "name" => "`%Discordant Chord",
        "rounds" => $rounds,
        "wearoff" => "The echo fades.",
        "badguydefmod" => 0.70,
    ];
    $array['ranging']['3']['3'] = [ 
        "startmsg" => "`5You play a haunting tune that drains {badguy}'s life force!`",
        "name" => "`%Vampiric Melody",
        "rounds" => $rounds,
        "lifetap" => 1.0, 
        "wearoff" => "The haunting tune fades.",
    ];
    
    if (($session['user']['specialty'] ?? '') === $spec) { 
        if (isset($array[$class][$subclass])) {
            foreach ($array[$class][$subclass] as $t => $d) {
                $array[$class][$subclass][$t]['schema'] = "specialtybard";
            }
            return $array[$class][$subclass];
        }
    }
    
    return [];
}
?>
