<?php
// modified by jaoe1000

/**
 * Clan functions
 * 
 * This file contains functions that are specific to the clan system.
 * 
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub
 * @copyright Copyright © 2006-2009, Dragonprime Development Team
 * @version Lotgd 1.1.2 DragonPrime Edition
 * @package Core
 * @subpackage Library
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 */

require_once(__DIR__ . '/func.php');

function clanHeader(): void
{
    require_once('lib/villagenav.php');
    page_header(loadTranslation('clan.title'));
    villagenav();
    addnav(loadTranslation('clan.nav_options_header'));
    output('clan.header');
}