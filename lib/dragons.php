<?php
// translator ready

/**
 * Returns the array of defined Mythos Dragons.
 *
 * @return array
 */
function get_mythos_dragons(): array
{
    return [
        'green' => [
            'name' => 'Green Dragon',
            'color' => '`@',
            'weapon' => 'Corrosive Poison Breath',
            'intro' => "`\$Fighting down every urge to flee, you cautiously enter the cave entrance, intent on catching the great green dragon sleeping, so that you might slay it with a minimum of pain.\n`2Sadly, this is not to be the case, for as you round a corner within the cave you discover the great beast sitting on its haunches on a huge pile of gold, picking its teeth with a rib.",
            'egg_color' => 'speckled green',
        ],
        'red' => [
            'name' => 'Red Dragon',
            'color' => '`$',
            'weapon' => 'Great Flaming Maw',
            'intro' => "`\$Fighting down every urge to flee, you cautiously enter the cave entrance, intent on catching the legendary red dragon sleeping, so that you might slay it with a minimum of pain.\n`2Sadly, this is not to be the case, for as you round a corner within the cave you discover the colossal crimson beast sitting on its haunches on a mountain of molten gold, picking its teeth with a charred rib.",
            'egg_color' => 'glowing ruby red',
        ],
        'blue' => [
            'name' => 'Blue Dragon',
            'color' => '`!',
            'weapon' => 'Crackling Lightning Breath',
            'intro' => "`!Fighting down every urge to flee, you cautiously enter the cave entrance, intent on catching the mythical blue dragon sleeping, so that you might slay it with a minimum of pain.\n`2Sadly, this is not to be the case, for as you round a corner within the cave you discover the great sapphire beast sitting on its haunches on a pile of glowing copper coins, sparks jumping from its scales as it picks its teeth with a shattered spear.",
            'egg_color' => 'crackling electric blue',
        ],
        'white' => [
            'name' => 'White Dragon',
            'color' => '`&',
            'weapon' => 'Freezing Frost Breath',
            'intro' => "`&Fighting down every urge to flee, you cautiously enter the cave entrance, intent on catching the ancient white dragon sleeping, so that you might slay it with a minimum of pain.\n`2Sadly, this is not to be the case, for as you round a corner within the cave you discover the pale frost beast sitting on its haunches on a frozen hoard of silver, frost rising from its claws as it picks its teeth with an icicle.",
            'egg_color' => 'frost-covered crystal white',
        ],
        'black' => [
            'name' => 'Black Dragon',
            'color' => '`)',
            'weapon' => 'Caustic Shadow Stream',
            'intro' => "`)Fighting down every urge to flee, you cautiously enter the cave entrance, intent on catching the shadow-steeped black dragon sleeping, so that you might slay it with a minimum of pain.\n`2Sadly, this is not to be the case, for as you round a corner within the cave you discover the obsidian beast sitting on its haunches on a dark pile of gems, shadows swirling around its claws as it picks its teeth with a bleached skull.",
            'egg_color' => 'shadowy obsidian black',
        ],
        'gold' => [
            'name' => 'Gold Dragon',
            'color' => '`^',
            'weapon' => 'Searing Sunfire Breath',
            'intro' => "`^Fighting down every urge to flee, you cautiously enter the cave entrance, intent on catching the majestic gold dragon sleeping, so that you might slay it with a minimum of pain.\n`2Sadly, this is not to be the case, for as you round a corner within the cave you discover the glittering golden beast sitting on its haunches on a radiant pile of artifacts, picking its teeth with a gilded sword.",
            'egg_color' => 'shining polished gold',
        ],
        'shadow' => [
            'name' => 'Shadow Dragon',
            'color' => '`5',
            'weapon' => 'Void Breath',
            'intro' => "`5Fighting down every urge to flee, you cautiously enter the cave entrance, intent on catching the spectral shadow dragon sleeping, so that you might slay it with a minimum of pain.\n`2Sadly, this is not to be the case, for as you round a corner within the cave you discover the terrifying void beast sitting on its haunches on a pile of dark matter, picking its teeth with a shadow-whip.",
            'egg_color' => 'pulsing dark purple',
        ],
    ];
}

/**
 * Retrieves the player's active Mythos Dragon.
 * Automatically initializes a random dragon if none is selected.
 *
 * @return array
 */
function get_active_dragon(): array
{
    global $session;
    
    if (!isset($session['user']['prefs']['mythos_dragon']) || !$session['user']['prefs']['mythos_dragon']) {
        reset_active_dragon();
    }
    
    $dragons = get_mythos_dragons();
    $key = $session['user']['prefs']['mythos_dragon'];
    
    if (!isset($dragons[$key])) {
        $key = 'green';
        $session['user']['prefs']['mythos_dragon'] = 'green';
    }
    
    return $dragons[$key];
}

/**
 * Assigns a new random Mythos Dragon to the player.
 *
 * @return void
 */
function reset_active_dragon(): void
{
    global $session;
    $dragons = get_mythos_dragons();
    $keys = array_keys($dragons);
    $session['user']['prefs']['mythos_dragon'] = $keys[array_rand($keys)];
}
