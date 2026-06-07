<?php

function staffrules_getmoduleinfo(){

	$info = array(

		"name"=>"Staff Rules",
                "author"=>"Christopher King",
                "version"=>"1.1",
                "category"=>"Rule Modules",
                                "download"=>"http://dragonprime.net/users/Freeze/staffrules.zip",
                "download"=>"Nope",
                "settings"=>array(
                         "Rules,title",
                         "Rule"=>"Add Rule 1,textarea|",
                         "Rule2"=>"Add Rule 2,textarea|",
                         "Rule3"=>"Add Rule 3,textarea|",
                         "Rule4"=>"Add Rule 4,textarea|",
                         "Rule5"=>"Add Rule 5,textarea|",
                         "Rule6"=>"Add Rule 6,textarea|",
                         "Rule7"=>"Add Rule 7,textarea|",
                         "Rule8"=>"Add Rule 8,textarea|",
                         "Rule9"=>"Add Rule 9,textarea|",
                         "Rule10"=>"Add Rule 10,textarea|",


                         "Notes,title",
                         "add"=>"Add Note 1,textarea|",
                         "add2"=>"Add Note 2,textarea|",
                         "add3"=>"Add Note 3,textarea|",
                         "add4"=>"Add Note 4,textarea|",
                         "add5"=>"Add Note 5,textarea|",
                         "add6"=>"Add Note 6,textarea|",
                         "add7"=>"Add Note 7,textarea|",
                         "add8"=>"Add Note 8,textarea|",
                         "add9"=>"Add Note 9,textarea|",
                         "add10"=>"Add Note 10,textarea|",
                         
                         "Other,title",
                          "topblurb"=>"Blurb at Top,textarea|",
                          "midblurb"=>"Middle Blurb,textarea|",
                          "botblurb"=>"Blurb at Bottom,textarea|",
                         "lastupdate"=>"Last Updated,textarea|",
        		)


		);

	return $info;
   }

function staffrules_install(){

	module_addhook("superuser");

	return true;
 }

function staffrules_uninstall(){

	return true;

}

function staffrules_dohook($hookname,$args){

	global $session;

	switch($hookname){

		case "superuser":

			addnav("Specials");

			addnav("Staff Rules","runmodule.php?module=staffrules");

			break;
      	}

	return $args;

}

function staffrules_run(){

	global $session;

$Rule=get_module_setting("Rule");
 $Rule2=get_module_setting("Rule2");
 $Rule3=get_module_setting("Rule3");
 $Rule4=get_module_setting("Rule4");
 $Rule5=get_module_setting("Rule5");
 $Rule6=get_module_setting("Rule6");
 $Rule7=get_module_setting("Rule7");
 $Rule8=get_module_setting("Rule8");
 $Rule9=get_module_setting("Rule9");
 $Rule10=get_module_setting("Rule10");
 
  $lastupdate=get_module_setting("lastupdate");

 $add=get_module_setting("add");
 $add2=get_module_setting("add2");
 $add3=get_module_setting("add3");
 $add4=get_module_setting("add4");
 $add5=get_module_setting("add5");
 $add6=get_module_setting("add6");
 $add7=get_module_setting("add7");
 $add8=get_module_setting("add8");
 $add9=get_module_setting("add9");
 $add10=get_module_setting("add10");

	page_header("The Staff Rules");

	modulehook("staffrules");

	if ($op==""){

output("`c`b`4The Staff Rules`b`c`n`n");

output("`n`n`n");
if ($topblurb <> ""){

	output("%s",$topblurb);
output("`n`n");
}

////

if ($Rule <> ""){

	output("`\$`iRule 1`i. `4%s",$Rule);

}
output("`n`n");
if ($Rule2 <> ""){

	output("`\$`iRule 2`i. `4%s",$Rule2);

}
output("`n`n");
if ($Rule3 <> ""){

	output("`\$`iRule 3`i. `4%s",$Rule3);

}
output("`n`n");
if ($Rule4 <> ""){

	output("`\$`iRule 4`i. `4%s",$Rule4);

}
output("`n`n");
if ($Rule5 <> ""){

	output("`\$`iRule 5`i. `4%s",$Rule5);
output("`n`n");
}
if ($Rule6 <> ""){

	output("`\$`iRule 6`i. `4%s",$Rule6);
output("`n`n");
}

if ($Rule7 <> ""){

	output("`\$`iRule 7`i. `4%s",$Rule7);
output("`n`n");
}

if ($Rule8 <> ""){

	output("`\$`iRule 8`i. `4%s",$Rule8);
output("`n`n");
}

if ($Rule9 <> ""){

	output("`\$`iRule 9`i. `4%s",$Rule9);
output("`n`n");
}

if ($Rule10 <> ""){

	output("`\$`iRule 10`i. `4%s",$Rule10);

}

/////
if ($midblurb <> ""){

	output("%s",$midblurb);
output("`n`n`n");
}

/////


if ($add <> ""){
output("`c`b`i`\$Notes:`i`b`c`n`n");
	output("`\$`iNote 1`i. `4%s",$add);
output("`n`n");
}

if ($add2 <> ""){

	output("`\$`iNote 2`i. `4%s",$add2);
output("`n`n");
}

if ($add3 <> ""){

	output("`\$`iNote 3`i. `4%s",$add3);
output("`n`n");
}

if ($add4 <> ""){

	output("`\$`iNote 4`i. `4%s",$add4);
output("`n`n");
}

if ($add5 <> ""){

	output("`\$`iNote 5`i. `4%s",$add5);
output("`n`n");
}

if ($add6 <> ""){

	output("`\$`iNote 6`i. `4%s",$add6);
output("`n`n");
}

if ($add7 <> ""){

	output("`\$`iNote 7`i. `4%s",$add7);
output("`n`n");
}

if ($add8 <> ""){

	output("`\$`iNote 8`i. `4%s",$add8);
output("`n`n");
}

if ($add9 <> ""){

	output("`\$`iNote 9`i. `4%s",$add9);
output("`n`n");
}

if ($add10 <> ""){

	output("`\$`iNote 10`i. `4%s",$add10);
output("`n`n");
}

output("`c`@`n`n-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-=-`c");
output("`n`n`n");

output("`n`n`n");
if ($botblurb <> ""){

	output("%s",$botblurb);

}

output("`n`n`n");
output("`c");

if ($lastupdate <> ""){

	output("`\$`iLast Updated: `i `4%s",$lastupdate);

}
 output("`c");
 output("`n`n`n");
addnav("Leave");
addnav("Return to the Village","village.php");
addnav("Refresh","runmodule.php?module=staffrules");

}

page_footer();

}

?>
