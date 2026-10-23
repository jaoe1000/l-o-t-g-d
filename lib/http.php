<?php
/**
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 * @author Eric Stevens & JT Traub
 * @author Dragonprime Development Team
 * @author modified by jaoe1000
 */
// modified by jaoe1000

// translator ready
// addnews ready
// mail ready
function httpget($var){
	return isset($_GET[$var]) ? $_GET[$var] : false;
}

function httpallget() {
	return $_GET;
}

function httpset($var, $val,$force=false){
	if (isset($_GET[$var]) || $force) $_GET[$var] = $val;
}

function httppost($var){
	return isset($_POST[$var]) ? $_POST[$var] : false;
}

function httppostisset($var) {
	return isset($_POST[$var]) ? 1 : 0;
}

function httppostset($var, $val, $sub=false){
	if ($sub === false) {
		if (isset($_POST[$var])) $_POST[$var] = $val;
	} else {
		if (isset($_POST[$var]) && isset($_POST[$var][$sub]))
			$_POST[$var][$sub]=$val;
	}
}

function httpallpost(){
	return $_POST;
}

function postparse($verify=false, $subval=false){
	if (is_array($subval)) {
		$var = $subval;
	} elseif ($subval && isset($_POST[$subval])) {
		$var = $_POST[$subval];
	} else {
		$var = $_POST;
	}

	$sql = "";
	$keys = "";
	$vals = "";
	$i = 0;
	foreach ($var as $key => $val) {
		if ($verify === false || isset($verify[$key])) {
			if (is_array($val)) $val = addslashes(serialize($val));
			$sql .= (($i > 0) ? "," : "") . "$key='$val'";
			$keys .= (($i > 0) ? "," : "") . "$key";
			$vals .= (($i > 0) ? "," : "") . "'$val'";
			$i++;
		}
	}
	return array($sql, $keys, $vals);
}
?>
