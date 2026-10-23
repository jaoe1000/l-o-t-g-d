<?php
/**
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 * @author Eric Stevens & JT Traub
 * @author Dragonprime Development Team
 * @author modified by jaoe1000
 */
// modified by jaoe1000

// addnews ready
// translator ready
// mail ready
// Set error reporting to all but notice (for now)
if (defined('E_DEPRECATED')) {
	error_reporting (E_ALL ^ (E_NOTICE | E_DEPRECATED));
} else {
	error_reporting (E_ALL ^ E_NOTICE);
}
#error_reporting (E_ALL);

function set_magic_quotes(&$vars) {
	if (is_array($vars)) {
		foreach ($vars as $key => &$val) {
			set_magic_quotes($val);
		}
	}else{
		$vars = addslashes($vars);
	}
}


//do some cleanup here to make sure magic_quotes_gpc is ON
if (function_exists("get_magic_quotes_runtime") && function_exists("get_magic_quotes_gpc")) {
	set_magic_quotes($_GET);
	set_magic_quotes($_POST);
	set_magic_quotes($_SESSION);
	set_magic_quotes($_COOKIE);
	ini_set("magic_quotes_gpc",1);
}

