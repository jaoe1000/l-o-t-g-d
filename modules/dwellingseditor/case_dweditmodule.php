<?php
/**
 * @copyright Copyright © 2002-2005, Eric Stevens & JT Traub, © 2006-2009, Dragonprime Development Team
 * @license http://creativecommons.org/licenses/by-nc-sa/2.0/legalcode
 * @author Eric Stevens & JT Traub
 * @author Dragonprime Development Team
 * @author modified by jaoe1000
 */
// modified by jaoe1000

	page_header("Dwelling Listing");
	$dwid = httpget("dwid");
	addnav("Back to Dwelling List","runmodule.php?module=dwellingseditor");
	$mdule = httpget("mdule");
	if($mdule == ""){
		output("Select a pref to edit.");
	}else{
		if (httpget('sub') == "save") {
			// Save module prefs
			$post = httpallpost();
			foreach ($post as $key => $val) {
				set_module_objpref("dwellings", $dwid, $key, $val, $mdule);
			}
			output("`^Saved!`0`n");
		}
		$link = "runmodule.php?module=dwellingseditor&op=dweditmodule&sub=save&dwid=$dwid&mdule=$mdule";
		require_once("lib/showform.php");
		rawoutput("<form action='$link' method='POST'>");
		module_objpref_edit("dwellings", $mdule, $dwid);
		rawoutput("</form>");
		addnav("",$link);
		//code from clan editor by CortalUX
	}
 	addnav("Module Prefs");
	module_editor_navs("prefs-dwellings","runmodule.php?module=dwellingseditor&op=dweditmodule&dwid=$dwid&mdule=");
?>