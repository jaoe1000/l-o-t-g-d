<?php
/**
 * Specialty Codex Module (Self-Contained Edition)
 * Integrates into the FAQ to show players a comprehensive directory of specialty powers.
 * Designed for PHP 8.4 Strict Compliance
 */

function specialtycodex_getmoduleinfo() {
    return [
        "name" => "Specialty Codex",
        "version" => "1.0",
        "author" => "Dragon.NDGaming",
        "category" => "Specialties",
        "download" => "",
        "settings" => [],
        "prefs" => []
    ];
}

function specialtycodex_install() {
    module_addhook("faq-toc");
    debug("Installed 'Specialty Codex' module.");
    return true;
}

function specialtycodex_uninstall() {
    debug("Uninstalled 'Specialty Codex' module.");
    return true;
}

function specialtycodex_dohook($hookname, $args) {
    switch ($hookname) {
        case "faq-toc":
            $t = translate_inline("`@Guide to Specialty Powers`0");
            output_notl("&#149;<a href='runmodule.php?module=specialtycodex&op=view'>%s</a><br/>", $t, true);
            break;
    }
    return $args;
}

function specialtycodex_run() {
    global $session;
    $op = httpget('op');
    
    if ($op == "view") {
        popup_header("Specialty Powers Guide");
        
        output("`c`b`@Specialty Powers Guide`0`b`c`n");
        output("`@Here you will find a complete directory of all combat specialties, their paths, costs, and gameplay effects.`n`n");
        
        $names = modulehook("specialtynames", []);
        $colors = modulehook("specialtycolor", []);
        
        $codex = [
        'BS' => [
            'name' => 'Basic Ninjutsu',
            'color' => '`v',
            'desc' => 'A powerful discipline of elemental Ninjutsu.',
            'techniques' => [
                1 => ['name' => 'Kawarimi no Jutsu', 'cost' => 1, 'desc' => '"Kawarimi no Jutsu!You switch places with a nearby item but can neither attack nor defend." Boosts attack power.'],
                2 => ['name' => 'Kakuremino no Jutsu', 'cost' => 1, 'desc' => '"Kakuremino no Jutsu!You make yourself invisible to get into a better attack position." Lasts 2 rounds.'],
                3 => ['name' => 'Bunshin no Jutsu', 'cost' => 1, 'desc' => '"Bunshin no Jutsu!You create some clones to cover up your current position" Boosts defense, lasts 5 rounds.'],
            ]
        ],
        'ER' => [
            'name' => 'Doton Ninjutsu',
            'color' => '`q',
            'desc' => 'A powerful discipline of elemental Ninjutsu.',
            'techniques' => [
                1 => ['name' => 'Retsudotenshou', 'cost' => 1, 'desc' => '"Doton - Restudotenshou!You attack {badguy} with nearby rocks." Deals damage to all enemies, summons helper minion.'],
                2 => ['name' => 'Doroku Gaeshi', 'cost' => 3, 'desc' => '"Doton - Doroku Gaeshi!You create a large wall of earth." Boosts defense, lasts 5 rounds.'],
                3 => ['name' => 'Iwayado Kuzushi', 'cost' => 5, 'desc' => '"Doton - Iwayado Kuzushi!Rocks are dislodged from above {badguy}." Deals damage to all enemies, summons helper minion.'],
                4 => ['name' => 'Doroudoumu', 'cost' => 10, 'desc' => '"Doton - Doroudoumu!You trap {badguy} inside a self-repairing dome of earth." Deals damage to all enemies, summons helper minion, lasts 10 rounds.'],
                5 => ['name' => 'Doryuu Dango', 'cost' => 15, 'desc' => '"Doton - Doryuu Dango!You hurl a large dumpling-shaped chunk of earth the size of a mausoleum at {badguy}." Deals damage to all enemies, summons helper minion.'],
                6 => ['name' => 'Doryuuheki', 'cost' => 16, 'desc' => '"Doton - Doryuuheki!You spit out a stream of mud that quickly grows and solidifies into a strong protective wall." Boosts defense, lasts 10 rounds.'],
                7 => ['name' => 'Doryuudan', 'cost' => 18, 'desc' => '"Doton - Doryuudan!You create a likeness of a dragons head that launches mud balls from its mouth at {badguy}." Deals damage to all enemies, summons helper minion.'],
            ]
        ],
        'FR' => [
            'name' => 'Katon Ninjutsu',
            'color' => '`$',
            'desc' => 'A powerful discipline of elemental Ninjutsu.',
            'techniques' => [
                1 => ['name' => 'Gōkakyū no Jutsu', 'cost' => 1, 'desc' => '"Katon - Gōkakyū no Jutsu!You utilize your chakra and exhale a large ball of fire from your mouth." Deals damage to all enemies, summons helper minion.'],
                2 => ['name' => 'Hōsenka no Jutsu', 'cost' => 3, 'desc' => '"Katon - Hōsenka!You  send multiple balls of fire at {badguy}." Deals direct damage, summons helper minion, lasts 5 rounds.'],
                3 => ['name' => 'Ryūka no Jutsu', 'cost' => 6, 'desc' => '"Katon - Ryūka no Jutsu!You breath out a burst of flame towards {badguy}." Deals damage to all enemies, summons helper minion.'],
                4 => ['name' => 'Haisekishō', 'cost' => 10, 'desc' => '"Katon - Haisekishō!You breathe out a cloud of superheated ash." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                5 => ['name' => '`\\$Kar`qyū `\\$En`qdan', 'cost' => 15, 'desc' => '"Katon - Karyū Endan! You shoot an enormous ball of flame in the shape of a dragon from your mouth at {badguy}." Deals damage to all enemies, summons helper minion.'],
                6 => ['name' => '`\\$Kar`qyū`Qdan', 'cost' => 18, 'desc' => '"Katon - Karyūdan, You create a likeness of a dragons head out of mud and ignite the mud balls that it launches from its mouth." Deals damage to all enemies, summons helper minion.'],
                7 => ['name' => '`@Gamayo `\\$Emu`qdan', 'cost' => 11, 'desc' => '"Katon -  Gamayou Emudan!You sent a fire blast from your mouth to Gamabuntas oil-blast... and engulf {badguy} in flames!" Deals damage to all enemies, summons helper minion, lasts 10 rounds.'],
            ]
        ],
        'GJ' => [
            'name' => 'Genjutsu',
            'color' => '`%',
            'desc' => 'A powerful discipline of elemental Ninjutsu.',
            'techniques' => [
                1 => ['name' => 'Narakumi no Jutsu', 'cost' => 1, 'desc' => '"Narakumi no JutsuAn imaginary circle of leaves spin around and envelop the enemy, falling away shortly after." Lasts 5 rounds.'],
                2 => ['name' => 'Kokoni Arazu no jutsu', 'cost' => 3, 'desc' => '"Kokoni Arazu no JutsuYou disappear into the surroundings by changing the appearance of the area." Lasts 5 rounds.'],
                3 => ['name' => 'Nijuu Kokoni Arazu no Jutsu', 'cost' => 6, 'desc' => '"Nijuu Kokoni Arazu no JutsuYou disappear into the surroundings and create an illusion around the area." Lasts 5 rounds.'],
                4 => ['name' => 'Jubaku Satsu', 'cost' => 10, 'desc' => '"Jubaku SatsuA tree sprouts from underneath {badguy}." Lasts 3 rounds.'],
                5 => ['name' => 'Suzu - Kiri', 'cost' => 15, 'desc' => '"Suzu - KiriYou disappear into a cloud of rose petals." Lasts 10 rounds.'],
                6 => ['name' => 'Jigoku Kouka no Jutsu', 'cost' => 18, 'desc' => '"Jigoku Kouka no JutsuA large fire ball descend from the heavens and turns the area into a fiery hell." Lasts 3 rounds.'],
                7 => ['name' => 'Kokuangyou no Jutsu', 'cost' => 20, 'desc' => '"Kokuangyou no JutsuYou blind {badguy} with total darkness." Lasts 10 rounds.'],
            ]
        ],
        'IC' => [
            'name' => 'Hyouton Ninjutsu',
            'color' => '`l',
            'desc' => 'A powerful discipline of elemental Ninjutsu.',
            'techniques' => [
                1 => ['name' => 'Hyourou no Jutsu', 'cost' => 1, 'desc' => '"Hyouton - Hyourou no Jutsu!You  trap {badguy} in ice."'],
                2 => ['name' => 'Tsubame Fubuki', 'cost' => 3, 'desc' => '"Hyouton - Tsubame Fubuki no Jutsu!You  create a cluster of ice needles in the shape of miniature swallows." Deals direct damage, summons helper minion, lasts 5 rounds.'],
                3 => ['name' => 'Haryuu Mouko', 'cost' => 6, 'desc' => '"Hyouton - Haryuu Mouko no Jutsu!You create a large tiger out of ice." Deals direct damage, summons helper minion, lasts 5 rounds.'],
                4 => ['name' => 'Ikkaku Hakugei', 'cost' => 8, 'desc' => '"Hyouton - Ikkaku Hakugei!You create a massive narwhal from summoned ice that jumps up and falls back down on {badguy}." Deals damage to all enemies, summons helper minion.'],
                5 => ['name' => 'Rouga Nadare no Jutsu', 'cost' => 12, 'desc' => '"Hyouton - Rouga Nadare no Jutsu!You create an avalanche of snow wolves." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                6 => ['name' => 'Kokuryuu Boufuusetsu', 'cost' => 16, 'desc' => '"Hyouton - Kokuryuu Boufuusetsu!You create an icy black dragon with red eyes and shoot it towards {badguy}." Deals direct damage, summons helper minion.'],
                7 => ['name' => 'Souryuu Boufuusetsu', 'cost' => 20, 'desc' => '"Hyouton - Souryuu Boufuusetsu!You releases two dragons of black snow that merge into a massive tornado at {badguy}." Deals direct damage, summons helper minion, lasts 3 rounds.'],
            ]
        ],
        'LT' => [
            'name' => 'Rai Ninjutsu',
            'color' => '`t',
            'desc' => 'A powerful discipline of elemental Ninjutsu.',
            'techniques' => [
                1 => ['name' => 'Raigeki no Yoroi', 'cost' => 1, 'desc' => '"Raigeki no Yoroi!You surround yourself with electricity." Lasts 5 rounds.'],
                2 => ['name' => 'Raikyuu', 'cost' => 3, 'desc' => '"Raikyuu!You create a ball of electrical energy and launch it at {badguy}." Deals direct damage, summons helper minion, lasts 10 rounds.'],
                3 => ['name' => 'Raiton: Hiraishin', 'cost' => 6, 'desc' => '"Raiton: Hiraishin!You summon lightning from the sky to your hand and then shoot it at your opponent." Deals direct damage, summons helper minion.'],
                4 => ['name' => 'Ikazuchi no Kiba', 'cost' => 10, 'desc' => '"Ikazuchi no Kiba!You send an electrical essence into the clouds, allowing you to create lightning strikes." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                5 => ['name' => 'Raizou Ikazuchi wo Utte', 'cost' => 15, 'desc' => '"Raizou Ikazuchi wo Utte!You create several thunderbolts that cut through the ground and chase after {badguy}." Deals direct damage, summons helper minion, lasts 10 rounds.'],
                6 => ['name' => 'Raiton: Kaminari Shibari', 'cost' => 16, 'desc' => '"Raiton: Kaminari Shibari! You create a three sided wall of electricity to bind {badguy}." Deals direct damage, summons helper minion, lasts 3 rounds.'],
                7 => ['name' => 'Rairyuu no Tatsumaki', 'cost' => 18, 'desc' => '"Rairyuu no Tatsumaki!You spin around very quickly, forming the electricity around you into a likeness of a dragons head." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                8 => ['name' => 'Ikazuchi Hakai', 'cost' => 23, 'desc' => '"Ikazuchi Hakai!You place your hands on the ground and send an enormous bolt of lightning that cuts through the ground towards {badguy}." Deals damage to all enemies, summons helper minion.'],
            ]
        ],
        'MD' => [
            'name' => 'Medical Ninjutsu',
            'color' => '`!',
            'desc' => 'A powerful discipline of elemental Ninjutsu.',
            'techniques' => [
                1 => ['name' => 'Chikatsu Saisei no Jutsu', 'cost' => 1, 'desc' => '"Chikatsu Saisei no Jutsu!You concentrate healing chakra to the palm of your hand." Lasts 5 rounds.'],
                2 => ['name' => 'Dokugiri', 'cost' => 2, 'desc' => '"Dokugiri!You blow a cloud of poison gas at the enemy." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                3 => ['name' => 'Chakra Kyuuin no Jutsu', 'cost' => 5, 'desc' => '"Chakra Kyuuin no Jutsu!You grab on to {badguy} and begin absorbing chakra." Lasts 5 rounds.'],
                4 => ['name' => 'Ranshinshou', 'cost' => 8, 'desc' => '"Ranshinshou!You convert a small portion of chakra into electricity and hit the enemys brain stem." Lasts 10 rounds.'],
                5 => ['name' => 'In\'yu Shoumetsu', 'cost' => 15, 'desc' => '"Inyu Shoumetsu!You concentrate healing chakra to the palm of your hand." Lasts 12 rounds.'],
                6 => ['name' => 'Shousen Jutsu: attack muscles', 'cost' => 16, 'desc' => '"Shousen Jutsu: attack muscles!You focus your chakra into a blade and damage the enemys muscles." Lasts 10 rounds.'],
                7 => ['name' => 'Shousen Jutsu: attack organs', 'cost' => 18, 'desc' => '"Shousen Jutsu: attack organs!You focus your chakra into a blade and damage the enemys organs." Lasts 10 rounds.'],
                8 => ['name' => 'Souzou Saisei', 'cost' => 20, 'desc' => '"Souzou Saisei!You release all the chakra you have stored up and heal all your wounds almost instantaneously." Boosts defense, lasts 10 rounds.'],
            ]
        ],
        'SD' => [
            'name' => 'Suna Ninjutsu',
            'color' => '`6',
            'desc' => 'A powerful discipline of elemental Ninjutsu.',
            'techniques' => [
                1 => ['name' => 'Suna Shuriken', 'cost' => 1, 'desc' => '"Suna Shuriken!You throw shuriken made from sand." Deals damage to all enemies, summons helper minion.'],
                2 => ['name' => 'Suna no Yoroi', 'cost' => 3, 'desc' => '"Suna no Yoroi!You cover yourself in a compacted layer of sand, providing you with additional defense." Boosts defense, lasts 5 rounds.'],
                3 => ['name' => 'Suna Bunshin', 'cost' => 5, 'desc' => '"Suna Bunshin!You create a clone out of sand." Deals direct damage, summons helper minion, lasts 10 rounds.'],
                4 => ['name' => 'Suna no Tate', 'cost' => 10, 'desc' => '"Suna no Tate!You surround and protect yourself with sand." Boosts attack power, boosts defense, lasts 10 rounds.'],
                5 => ['name' => 'Ryuusa Bakuryuu', 'cost' => 15, 'desc' => '"Ryuusa Bakuryuu!You create a massive amount of sand and send it towards {badguy} in the form of a wave." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                6 => ['name' => 'Sabaku Kyuu', 'cost' => 18, 'desc' => '"Sabaku Kyuu!You cover {badguy}s entire body in sand." Lasts 5 rounds.'],
                7 => ['name' => 'Sabaku Sousou', 'cost' => 20, 'desc' => '"Sabaku Sousou!You cover {badguy}s entire body in sand and implode, crushing whatever is within" Deals direct damage, summons helper minion.'],
            ]
        ],
        'WT' => [
            'name' => 'Suiton Ninjutsu',
            'color' => '`1',
            'desc' => 'A powerful discipline of elemental Ninjutsu.',
            'techniques' => [
                1 => ['name' => 'Kirigakure no Jutsu', 'cost' => 1, 'desc' => '"Suiton - Kirigakure no Jutsu!You envelop the surrounding area in a dense mist." Lasts 5 rounds.'],
                2 => ['name' => 'Kaihoudan', 'cost' => 3, 'desc' => '"Suiton - Kaihoudan!You shoot a strong stream of water from your mouth at {badguy}." Deals damage to all enemies, summons helper minion.'],
                3 => ['name' => 'Suijinheki', 'cost' => 5, 'desc' => '"Suiton - Suijinheki!You create a water barrier to protect yourself from attacks." Boosts defense, lasts 5 rounds.'],
                4 => ['name' => 'Suiryuudan no Jutsu', 'cost' => 10, 'desc' => '"Suiton - Suiryuudan no Jutsu!You create a huge current of water in the form of a dragon and sends it towards {badguy}." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                5 => ['name' => 'Goshokuzame', 'cost' => 15, 'desc' => '"Suiton - Goshokuzame!You trap {badguy} in water and send five attacking sharks after him." Deals direct damage, summons helper minion, lasts 5 rounds.'],
                6 => ['name' => 'Daibakufu no Jutsu', 'cost' => 16, 'desc' => '"Suiton - Daibakufu no Jutsu!You create a massive blast of water." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                7 => ['name' => 'Daibaku no Jutsu', 'cost' => 18, 'desc' => '"Suiton - Daibaku no Jutsu!You create a massive tidal wave." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                8 => ['name' => 'Daibakure no Jutsu', 'cost' => 20, 'desc' => '"Suiton - Daibakure no Jutsu!You create an enormous  inescapable maelstrom." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
            ]
        ],
        'WN' => [
            'name' => 'Fuuton Ninjutsu',
            'color' => '`2',
            'desc' => 'A powerful discipline of elemental Ninjutsu.',
            'techniques' => [
                1 => ['name' => 'Kaze no Yoroi', 'cost' => 1, 'desc' => 'Boosts defense, lasts 5 rounds.'],
                2 => ['name' => 'Kamaitachi no Jutsu', 'cost' => 3, 'desc' => 'Deals damage to all enemies, summons helper minion, lasts 3 rounds.'],
                3 => ['name' => 'Kaze Kiri', 'cost' => 6, 'desc' => 'Deals direct damage, summons helper minion.'],
                4 => ['name' => 'Daikamaitachi no Jutsu', 'cost' => 10, 'desc' => 'Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                5 => ['name' => 'Kaze no Yaiba', 'cost' => 15, 'desc' => 'Deals damage to all enemies, summons helper minion.'],
                6 => ['name' => 'Fuuton - Tatsu no Oshigoto', 'cost' => 18, 'desc' => 'Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                7 => ['name' => 'Renkuudan', 'cost' => 20, 'desc' => 'Deals damage to all enemies, summons helper minion.'],
            ]
        ],
        'BA' => [
            'name' => 'Bard Songs',
            'color' => '`5',
            'desc' => 'Melodies that alter the battlefield based on your class path.',
            'subclasses' => [
                'magic' => [
                    'name' => 'Cantor (Magic Path)',
                    'techniques' => [
                        1 => ['name' => 'Fascinating Melody', 'cost' => 1, 'desc' => 'Strum a soft tune. Reduces enemy attack by 40% (lasts 4-9 rounds based on charm).'],
                        3 => ['name' => 'Mass Suggestion', 'cost' => 3, 'desc' => 'Sing an uplifting aria. Summons a sylvan beast helper minion (lasts 4-9 rounds based on charm).']
                    ]
                ],
                'melee' => [
                    'name' => 'Blade (Melee Path)',
                    'techniques' => [
                        1 => ['name' => 'Defensive Flourish', 'cost' => 1, 'desc' => 'Flashy defensive flourish. Increases defense by 40% (lasts 4-9 rounds based on charm).'],
                        3 => ['name' => 'Bardic Inspiration', 'cost' => 3, 'desc' => 'Combat coordination song. Increases attack by 40% and defense by 20% (lasts 4-9 rounds based on charm).']
                    ]
                ],
                'ranging' => [
                    'name' => 'Troubadour (Ranging Path)',
                    'techniques' => [
                        1 => ['name' => 'Discordant Chord', 'cost' => 1, 'desc' => 'Discordant note. Reduces enemy defense by 30% (lasts 4-9 rounds based on charm).'],
                        3 => ['name' => 'Vampiric Melody', 'cost' => 3, 'desc' => 'Haunting tune. Drains life force from the enemy, healing player for 100% of damage dealt (lasts 4-9 rounds based on charm).']
                    ]
                ]
            ]
        ],
        'PL' => [
            'name' => 'Paladin Gifts',
            'color' => '`#',
            'desc' => 'Holy powers tied to your righteous morality.',
            'techniques' => [
                1 => ['name' => 'Smite Evil', 'cost' => 1, 'desc' => 'Righteous strike that boosts attack power by 50% (lasts 5 rounds).'],
                2 => ['name' => 'Divine Grace', 'cost' => 2, 'desc' => 'Angelical shield that boosts defense by 50% (lasts 10 rounds).'],
                3 => ['name' => 'Lay on Hands', 'cost' => 3, 'desc' => 'Miraculous healing power that regenerates 1.5 * level health per round (lasts 5 rounds).'],
                5 => ['name' => 'Paladin Mount', 'cost' => 5, 'desc' => 'Summons helper mount that deals 0.5x - 1.0x attack damage and absorbs half of all incoming blows (lasts 5 rounds).']
            ]
        ],
        ];
        
        if (empty($codex)) {
            output("`7No specialties are currently configured or active.`n");
        } else {
            foreach ($codex as $spec => $info) {
                // Only show if the specialty module itself is active
                // (We check this by looking up the specialty name in $names)
                if (!isset($names[$spec])) continue;
                
                $ccode = $colors[$spec] ?? $info['color'];
                $disp_name = $names[$spec] ?? $info['name'];
                
                output("`n`b%s%s`0`b`n", $ccode, $disp_name);
                if (isset($info['desc']) && $info['desc'] !== "") {
                    output("`&%s`0`n", $info['desc']);
                }
                
                // If it has subclasses (like Bard)
                if (isset($info['subclasses']) && is_array($info['subclasses'])) {
                    foreach ($info['subclasses'] as $subKey => $subInfo) {
                        output("`n  `#`b%s`b`0`n", $subInfo['name']);
                        foreach ($subInfo['techniques'] as $tKey => $tInfo) {
                            output("    `^&#149; %s`0 (Cost: `&%s`0) - `v%s`0`n", $tInfo['name'], $tInfo['cost'], $tInfo['desc']);
                        }
                    }
                } 
                // If flat techniques (like Ninjutsu, Paladin)
                elseif (isset($info['techniques']) && is_array($info['techniques'])) {
                    foreach ($info['techniques'] as $tKey => $tInfo) {
                        output("  `^&#149; %s`0 (Cost: `&%s`0) - `v%s`0`n", $tInfo['name'], $tInfo['cost'], $tInfo['desc']);
                    }
                }
                output("`n`$-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-`0`n");
            }
        }
        
        output_notl("<br/><br/>");
        $back = translate_inline("Back to FAQ Contents");
        rawoutput("<a href='petition.php?op=faq'>&lt;&lt; $back</a>");
        
        popup_footer();
    }
}
