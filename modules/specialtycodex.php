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
        "prefs" => [],
        "allowanonymous" => true,
        "override_forced_nav" => true
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
            'name' => 'Arcane Basics',
            'color' => '`v',
            'desc' => 'A fundamental magic path focused on utility and illusion.',
            'techniques' => [
                1 => ['name' => 'Blink Decoy', 'cost' => 1, 'desc' => '"Blink Decoy! You blink away, leaving a decoy in your place. You can neither attack nor defend." Boosts attack power.'],
                2 => ['name' => 'Invisibility', 'cost' => 1, 'desc' => '"Invisibility! You turn invisible to get into a better attack position." Lasts 2 rounds.'],
                3 => ['name' => 'Mirror Image', 'cost' => 1, 'desc' => '"Mirror Image! You create some mirror images to cover up your current position" Boosts defense, lasts 5 rounds.'],
            ]
        ],
        'ER' => [
            'name' => 'Earth Magic',
            'color' => '`q',
            'desc' => 'A powerful discipline of earth and stone manipulation.',
            'techniques' => [
                1 => ['name' => 'Stone Spike', 'cost' => 1, 'desc' => '"Earth Magic - Stone Spike! You summon sharp stone spikes from the ground." Deals damage to all enemies, summons helper minion.'],
                2 => ['name' => 'Earthen Wall', 'cost' => 3, 'desc' => '"Earth Magic - Earthen Wall! You create a large wall of earth." Boosts defense, lasts 5 rounds.'],
                3 => ['name' => 'Rockfall', 'cost' => 5, 'desc' => '"Earth Magic - Rockfall! Boulders are dislodged from above." Deals damage to all enemies, summons helper minion.'],
                4 => ['name' => 'Tomb of Stone', 'cost' => 10, 'desc' => '"Earth Magic - Tomb of Stone! You trap {badguy} inside a self-repairing dome of earth." Deals damage to all enemies, summons helper minion, lasts 10 rounds.'],
                5 => ['name' => 'Boulder Toss', 'cost' => 15, 'desc' => '"Earth Magic - Boulder Toss! You hurl a massive boulder at {badguy}." Deals damage to all enemies, summons helper minion.'],
                6 => ['name' => 'Clay Barrier', 'cost' => 16, 'desc' => '"Earth Magic - Clay Barrier! You raise a strong protective wall of clay." Boosts defense, lasts 10 rounds.'],
                7 => ['name' => 'Gargoyle Breath', 'cost' => 18, 'desc' => '"Earth Magic - Gargoyle Breath! You summon an earthen gargoyle head to launch stone projectiles." Deals damage to all enemies, summons helper minion.'],
            ]
        ],
        'FR' => [
            'name' => 'Fire Magic',
            'color' => '`$',
            'desc' => 'A destructive magical discipline of flame and combustion.',
            'techniques' => [
                1 => ['name' => 'Fireball', 'cost' => 1, 'desc' => '"Fire Magic - Fireball! You exhale a large ball of fire from your mouth." Deals damage to all enemies, summons helper minion.'],
                2 => ['name' => 'Fiery Embers', 'cost' => 3, 'desc' => '"Fire Magic - Fiery Embers! You send multiple balls of fire at {badguy}." Deals direct damage, summons helper minion, lasts 5 rounds.'],
				3 => ['name' => 'Dragon\'s Breath', 'cost' => 6, 'desc' => '"Fire Magic - Dragon\'s Breath! You breathe out a burst of flame towards {badguy}." Deals damage to all enemies, summons helper minion.'],
                4 => ['name' => 'Cinder Cloud', 'cost' => 10, 'desc' => '"Fire Magic - Cinder Cloud! You breathe out a cloud of superheated ash." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                5 => ['name' => '`\\$In`qci`\\$ne`qrate', 'cost' => 15, 'desc' => '"Fire Magic - Incinerate! You shoot an enormous ball of flame at {badguy}." Deals damage to all enemies, summons helper minion.'],
                6 => ['name' => '`\\$La`qva `\\$Bu`qrst', 'cost' => 18, 'desc' => '"Fire Magic - Lava Burst! You create and ignite lava projectiles." Deals damage to all enemies, summons helper minion.'],
                7 => ['name' => '`@Infernal `\\$Blas`qt', 'cost' => 11, 'desc' => '"Fire Magic - Infernal Blast! You unleash a devastating storm of flame and engulf {badguy} in flames!" Deals damage to all enemies, summons helper minion, lasts 10 rounds.'],
            ]
        ],
        'GJ' => [
            'name' => 'Illusion Magic',
            'color' => '`%',
            'desc' => 'A deceptive magic path focused on sensory manipulation and blinding.',
            'techniques' => [
                1 => ['name' => 'Horrific Vision', 'cost' => 1, 'desc' => '"Horrific Vision! A swirl of illusory dark leaves surrounds the enemy, inflicting a terrifying phantasm." Lasts 5 rounds.'],
                2 => ['name' => 'Disguise Self', 'cost' => 3, 'desc' => '"Disguise Self! You disappear into the surroundings by altering the appearance of the area." Lasts 5 rounds.'],
                3 => ['name' => 'Veil of Shadows', 'cost' => 6, 'desc' => '"Veil of Shadows! You disappear into the surroundings and cast a heavy shroud of illusion." Lasts 5 rounds.'],
                4 => ['name' => 'Spectral Bind', 'cost' => 10, 'desc' => '"Spectral Bind! Illusory vines sprout from underneath {badguy}." Lasts 3 rounds.'],
                5 => ['name' => 'Floral Veil', 'cost' => 15, 'desc' => '"Floral Veil! You disappear into an illusory cloud of rose petals." Lasts 10 rounds.'],
                6 => ['name' => 'Infernal Illusion', 'cost' => 18, 'desc' => '"Infernal Illusion! An illusory fire ball descends from the heavens, making the area appear to be a fiery hell." Lasts 3 rounds.'],
                7 => ['name' => 'Shroud of Night', 'cost' => 20, 'desc' => '"Shroud of Night! You blind {badguy} with total illusory darkness." Lasts 10 rounds.'],
            ]
        ],
        'IC' => [
            'name' => 'Ice Magic',
            'color' => '`l',
            'desc' => 'A freezing magic path focused on slowing and binding.',
            'techniques' => [
                1 => ['name' => 'Frost Tomb', 'cost' => 1, 'desc' => '"Ice Magic - Frost Tomb! You trap {badguy} in solid ice."'],
                2 => ['name' => 'Ice Shards', 'cost' => 3, 'desc' => '"Ice Magic - Ice Shards! You create a cluster of sharp ice shards." Deals direct damage, summons helper minion, lasts 5 rounds.'],
                3 => ['name' => 'Frost Beast', 'cost' => 6, 'desc' => '"Ice Magic - Frost Beast! You create a large beast out of solid ice." Deals direct damage, summons helper minion, lasts 5 rounds.'],
                4 => ['name' => 'Glacial Wave', 'cost' => 8, 'desc' => '"Ice Magic - Glacial Wave! You create a massive glacial wave that crashes down on {badguy}." Deals damage to all enemies, summons helper minion.'],
                5 => ['name' => 'Frost Wolves', 'cost' => 12, 'desc' => '"Ice Magic - Frost Wolves! You summon a hunting pack of frost wolves." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                6 => ['name' => 'Blackfrost Wyrm', 'cost' => 16, 'desc' => '"Ice Magic - Blackfrost Wyrm! You create a freezing blackfrost wyrm." Deals direct damage, summons helper minion.'],
                7 => ['name' => 'Blizzard Tempest', 'cost' => 20, 'desc' => '"Ice Magic - Blizzard Tempest! You release a massive blizzard tempest." Deals direct damage, summons helper minion, lasts 3 rounds.'],
            ]
        ],
        'LT' => [
            'name' => 'Lightning Magic',
            'color' => '`t',
            'desc' => 'A shocking magic path of storm and electric fury.',
            'techniques' => [
                1 => ['name' => 'Storm Armor', 'cost' => 1, 'desc' => '"Storm Armor! You surround yourself with crackling electricity." Lasts 5 rounds.'],
                2 => ['name' => 'Spark Sphere', 'cost' => 3, 'desc' => '"Spark Sphere! You create a ball of electrical energy." Deals direct damage, summons helper minion, lasts 10 rounds.'],
                3 => ['name' => 'Lightning Spear', 'cost' => 6, 'desc' => '"Lightning Spear! You summon lightning from the sky into your hand and hurl it." Deals direct damage, summons helper minion.'],
                4 => ['name' => 'Thunderous Roar', 'cost' => 10, 'desc' => '"Thunderous Roar! You send electrical energy into the clouds to call down strikes." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                5 => ['name' => 'Forked Lightning', 'cost' => 15, 'desc' => '"Forked Lightning! You create thunderbolts that arc through the ground." Deals direct damage, summons helper minion, lasts 10 rounds.'],
                6 => ['name' => 'Storm Prison', 'cost' => 16, 'desc' => '"Storm Prison! You create a wall of crackling electricity to bind {badguy}." Deals direct damage, summons helper minion, lasts 3 rounds.'],
                7 => ['name' => 'Storm Vortex', 'cost' => 18, 'desc' => '"Storm Vortex! You form the electricity around you into a swirling tempest." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                8 => ['name' => 'Thunderous Ruin', 'cost' => 23, 'desc' => '"Thunderous Ruin! You unleash an enormous bolt of lightning that tears through the ground." Deals damage to all enemies, summons helper minion.'],
            ]
        ],
        'MD' => [
            'name' => 'Restoration Magic',
            'color' => '`!',
            'desc' => 'A benevolent magic path of healing and life force siphon.',
            'techniques' => [
                1 => ['name' => 'Restoration', 'cost' => 1, 'desc' => '"Restoration! You concentrate healing magic to the palm of your hand." Lasts 5 rounds.'],
                2 => ['name' => 'Noxious Mist', 'cost' => 2, 'desc' => '"Noxious Mist! You blow a cloud of poison gas at the enemy." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                3 => ['name' => 'Life Siphon', 'cost' => 5, 'desc' => '"Life Siphon! You grab on to {badguy} and begin absorbing life essence." Lasts 5 rounds.'],
                4 => ['name' => 'Synaptic Shock', 'cost' => 8, 'desc' => '"Synaptic Shock! You convert mana into electricity to disrupt the enemy\'s brain stem." Lasts 10 rounds.'],
                5 => ['name' => 'Rejuvenation', 'cost' => 15, 'desc' => '"Rejuvenation! You concentrate strong healing magic." Lasts 12 rounds.'],
                6 => ['name' => 'Cripple', 'cost' => 16, 'desc' => '"Cripple! You focus magic into a spectral blade to damage muscles." Lasts 10 rounds.'],
                7 => ['name' => 'Sundering Blow', 'cost' => 18, 'desc' => '"Sundering Blow! You focus magic into a spectral blade to damage organs." Lasts 10 rounds.'],
                8 => ['name' => 'Ascendant Recovery', 'cost' => 20, 'desc' => '"Ascendant Recovery! You release stored magic to heal all wounds instantaneously." Boosts defense, lasts 10 rounds.'],
            ]
        ],
        'SD' => [
            'name' => 'Sand Magic',
            'color' => '`6',
            'desc' => 'A shifting magic path of grit and desert tomb manipulation.',
            'techniques' => [
                1 => ['name' => 'Sand Daggers', 'cost' => 1, 'desc' => '"Sand Daggers! You throw sharp daggers made of sand." Deals damage to all enemies, summons helper minion.'],
                2 => ['name' => 'Earthen Shell', 'cost' => 3, 'desc' => '"Earthen Shell! You cover yourself in compacted sand." Boosts defense, lasts 5 rounds.'],
                3 => ['name' => 'Sand Clone', 'cost' => 5, 'desc' => '"Sand Clone! You create a simulacrum out of sand." Deals direct damage, summons helper minion, lasts 10 rounds.'],
                4 => ['name' => 'Aegis of Sand', 'cost' => 10, 'desc' => '"Aegis of Sand! You protect yourself with a shifting aegis." Boosts attack, boosts defense, lasts 10 rounds.'],
                5 => ['name' => 'Quicksand Torrent', 'cost' => 15, 'desc' => '"Quicksand Torrent! You send sand in a crushing wave." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                6 => ['name' => 'Desert Tomb', 'cost' => 18, 'desc' => '"Desert Tomb! You encase the enemy in heavy sand." Lasts 5 rounds.'],
                7 => ['name' => 'Sand Crush', 'cost' => 20, 'desc' => '"Sand Crush! You implode sand around the enemy, crushing them." Deals direct damage, summons helper minion.'],
            ]
        ],
        'WT' => [
            'name' => 'Water Magic',
            'color' => '`1',
            'desc' => 'A fluid magic path of tidal waves and geysers.',
            'techniques' => [
                1 => ['name' => 'Dense Mist', 'cost' => 1, 'desc' => '"Water Magic - Dense Mist! You envelop the surrounding area in a dense mist." Lasts 5 rounds.'],
                2 => ['name' => 'Water Jet', 'cost' => 3, 'desc' => '"Water Magic - Water Jet! You shoot a strong stream of water." Deals damage to all enemies, summons helper minion.'],
                3 => ['name' => 'Water Barrier', 'cost' => 5, 'desc' => '"Water Magic - Water Barrier! You create a water barrier." Boosts defense, lasts 5 rounds.'],
                4 => ['name' => 'Water Dragon', 'cost' => 10, 'desc' => '"Water Magic - Water Dragon! You send a water current in dragon form." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                5 => ['name' => 'Feeding Frenzy', 'cost' => 15, 'desc' => '"Water Magic - Feeding Frenzy! You trap the enemy in water and send sharks." Deals direct damage, summons helper minion, lasts 5 rounds.'],
                6 => ['name' => 'Geyser Blast', 'cost' => 16, 'desc' => '"Water Magic - Geyser Blast! You create a geyser eruption." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                7 => ['name' => 'Tidal Wave', 'cost' => 18, 'desc' => '"Water Magic - Tidal Wave! You create a massive wave." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                8 => ['name' => 'Maelstrom', 'cost' => 20, 'desc' => '"Water Magic - Maelstrom! You create a maelstrom." Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
            ]
        ],
        'WN' => [
            'name' => 'Wind Magic',
            'color' => '`2',
            'desc' => 'A swift magic path of wind armor and slicing gales.',
            'techniques' => [
                1 => ['name' => 'Wind Armor', 'cost' => 1, 'desc' => 'Boosts defense, lasts 5 rounds.'],
                2 => ['name' => 'Gale Slash', 'cost' => 3, 'desc' => 'Deals damage to all enemies, summons helper minion, lasts 3 rounds.'],
                3 => ['name' => 'Wind Blade', 'cost' => 6, 'desc' => 'Deals direct damage, summons helper minion.'],
                4 => ['name' => 'Gale Torrent', 'cost' => 10, 'desc' => 'Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                5 => ['name' => 'Blade of Wind', 'cost' => 15, 'desc' => 'Deals damage to all enemies, summons helper minion.'],
                6 => ['name' => 'Tornado Strike', 'cost' => 18, 'desc' => 'Deals damage to all enemies, summons helper minion, lasts 5 rounds.'],
                7 => ['name' => 'Wind Blast', 'cost' => 20, 'desc' => 'Deals damage to all enemies, summons helper minion.'],
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
