<?php


require_once("common.php");

function housecellar_getmoduleinfo() {
	$info = array(
		"name"=>"House Rooms - Cellar",
		"author"=>"`!Rowne-Wuff Mastaile/`#Lonny Luberts<br>Altered by `1Enderandrew",
		"version"=>"2.56",
		"category"=>"House System",
		"download"=>"http://www.pqcomp.com/modules/mydownloads/visit.php?cid=3&lid=48",
		"download"=>"http://dragonprime.net/users/enderwiggin/housecellar.zip",
		"vertxtloc"=>"http://dragonprime.net/users/enderwiggin/",
		"settings"=>array(
			"Cellar Travel,title",
			"opmain"=>"Enable Travel?,bool|1",
			"loc1"=>"Location 1:|sanctuary",
			"city1"=>"City for Location 1:|Degolburg",
			"desc1"=>"Desc for Location 1:|You crawl out of a dusty cellar tunnel and find yourself in the serene halls of the Sanctuary.",
			"on1"=>"Is Location 1 enabled?,bool|1",
			"loc2"=>"Location 2:|elvinbank",
			"city2"=>"City for Location 2:|Degolburg",
			"desc2"=>"Desc for Location 2:|You pull yourself up through a loose floorboard and find yourself inside the Elvin Bank.",
			"on2"=>"Is Location 2 enabled?,bool|1",
			"loc3"=>"Location 3:|petshop",
			"city3"=>"City for Location 3:|Degolburg",
			"desc3"=>"Desc for Location 3:|You push open a cellar trapdoor and emerge amidst the sounds of the Pet Shop.",
			"on3"=>"Is Location 3 enabled?,bool|1",
			"loc4"=>"Location 4:|clanbarracks",
			"city4"=>"City for Location 4:|Degolburg",
			"desc4"=>"Desc for Location 4:|You squeeze through a tight wall crevice and climb up into the Clan Barracks.",
			"on4"=>"Is Location 4 enabled?,bool|1",
			"loc5"=>"Location 5:|applebob",
			"city5"=>"City for Location 5:|Degolburg",
			"desc5"=>"Desc for Location 5:|You climb up a dark ladder and pop out right next to the Apple Bobbing tub.",
			"on5"=>"Is Location 5 enabled?,bool|1",
			"loc6"=>"Location 6:|modulename",
			"city6"=>"City for Location 6:|Degolburg",
			"desc6"=>"Desc for Location 6:|You push yourself up from under a rock and find yourself in ...",
			"on6"=>"Is Location 6 enabled?,bool|0",
			"loc7"=>"Location 7:|modulename",
			"city7"=>"City for Location 7:|Degolburg",
			"desc7"=>"Desc for Location 7:|You push yourself up from under a rock and find yourself in ...",
			"on7"=>"Is Location 7 enabled?,bool|0",
			"loc8"=>"Location 8:|modulename",
			"city8"=>"City for Location 8:|Degolburg",
			"desc8"=>"Desc for Location 8:|You push yourself up from under a rock and find yourself in ...",
			"on8"=>"Is Location 8 enabled?,bool|0",
			"loc9"=>"Location 9:|modulename",
			"city9"=>"City for Location 9:|Degolburg",
			"desc9"=>"Desc for Location 9:|You push yourself up from under a rock and find yourself in ...",
			"on9"=>"Is Location 9 enabled?,bool|0",
			"loc10"=>"Location 10:|modulename",
			"city10"=>"City for Location 10:|Degolburg",
			"desc10"=>"Desc for Location 10:|You push yourself up from under a rock and find yourself in ...",
			"on10"=>"Is Location 10 enabled?,bool|0",
			"loc11"=>"Location 11:|modulename",
			"city11"=>"City for Location 11:|Degolburg",
			"desc11"=>"Desc for Location 11:|You push yourself up from under a rock and find yourself in ...",
			"on11"=>"Is Location 11 enabled?,bool|0",
			"loc12"=>"Location 12:|modulename",
			"city12"=>"City for Location 12:|Degolburg",
			"desc12"=>"Desc for Location 12:|You push yourself up from under a rock and find yourself in ...",
			"on12"=>"Is Location 12 enabled?,bool|0",
			"loc13"=>"Location 13:|modulename",
			"city13"=>"City for Location 13:|Degolburg",
			"desc13"=>"Desc for Location 13:|You push yourself up from under a rock and find yourself in ...",
			"on13"=>"Is Location 13 enabled?,bool|0",
			"loc14"=>"Location 14:|modulename",
			"city14"=>"City for Location 14:|Degolburg",
			"desc14"=>"Desc for Location 14:|You push yourself up from under a rock and find yourself in ...",
			"on14"=>"Is Location 14 enabled?,bool|0",
			"loc15"=>"Location 15:|modulename",
			"city15"=>"City for Location 15:|Degolburg",
			"desc15"=>"Desc for Location 15:|You push yourself up from under a rock and find yourself in ...",
			"on15"=>"Is Location 15 enabled?,bool|0",
			"Cellar General,title",
			"brand"=>"Brand of torch used by intruder|MusTEK",
		),
		"prefs"=>array(
			"Cellar General,title",
			"hasused"=>"Has player used the Cellar today?,bool|0",
		),
		"requires"=>array(
			"houserooms"=>"2.5|By `!Rowne-Wuff Mastaile/`#Lonny Luberts<br>Altered by `1Enderandrew",
		),
	);
	return $info;
}

function housecellar_install() {
		if (!is_module_active("housecellar")) set_module_setting ("cellarmods", get_module_setting("cellarmods","houserooms") + 1, "houserooms");
	module_addhook("cellarcontents");
	module_addhook("newday");
	output ("`$");
	output ("You have to define the locations for cellar travel yourself.");
	output ("You can define the module locations for it via the Settings.");
	output ("It can go just about everywhere. For example:");
	output ("If you want it to take you to Lonny's Castle in Glorfindal");
	output ("then you'd be looking at the module filename for the module");
	output ("in question; which is lonnycastle.php and that would");
	output ("translate over as lonnycastle (without the .php) and the");
	output ("village would stay the same, as Glorfindal.");
	output ("`n`n");
	output ("`c`bMost Importantly ...`b`c");
	output ("`n`n");
	output ("Please don't enable the travel unless you have roughly five");
	output ("destinations set `iat least`i.  If you can do much more then");
	output ("you should do so. The less your server has to turn back a");
	output ("negative, the happier it'll be.");
	output ("`n`n");
	output ("`7");
	return true;
}

function housecellar_uninstall() {
	set_module_setting ("cellarmods", get_module_setting("cellarmods","houserooms") - 1, "houserooms");
	$modcheck = get_module_setting ("cellarmods", "houserooms") - 1;
	if ($modcheck == 0) set_module_setting	("cellaron", 0, "houserooms");
	return true;
}

function housecellar_dohook($hookname, $args) {
	global $session;
	$id = httpget("id");
	switch ($hookname){
	
	case "newday":
		/*
		if (get_module_pref ("cellarempty","houserooms") || !get_module_setting("cellaron","houserooms")) {
			output ("`n`n");
			output ("`^The Cellar at your house has been installed!");
			output ("`n`n");
			set_module_pref		("cellarempty", 0, "houserooms");
			set_module_setting	("cellaron", 1, "houserooms");
		}
		*/
		set_module_pref ("hasused",0);
		break;
		
	case "cellarcontents":
		output ("`7");
		output ("Before you is the trapdoor leading down to your Cellar.");
		output ("You pull it up and it creaks loudly, it's dark down there and it smells funny.");
		output ("Are you sure you even want to know what's down there?");
		output ("`n`n");
		addnav ("Enter!","runmodule.php?module=housecellar&id=".$id);
		break;
	}

	return $args;
}

function housecellar_run() {
	global $session;
	$id = httpget("id");
	$op		= httpget					("op");
	$opmain		= get_module_setting		("opmain");
	$hasused		= get_module_pref			("hasused");
	$cellchance	= e_rand						(1,4);
	$sql			= "SELECT login,acctid,location,name,loggedin,laston FROM ".db_prefix("accounts")." WHERE acctid <> ".$session['user']['acctid']." ORDER BY RAND(".e_rand().") LIMIT 1";
	$result		= db_query					($sql);
	$row			= db_fetch_assoc			($result);
	$intruder	= $row['name'];
	$brand		= get_module_setting		("brand");
	if (is_module_active('usechow')){
		$chow			= array						();
		$uchow		= get_module_pref			("chow", "usechow");
	}
	$done			= 1;
	$trvchance	=	e_rand					(1,15);
	
	page_header();
	output ("`^`b`cThe Cellar`b");
	output ("`c");
	output ("`n");
	output("`7");
	
	if ($op == "") {
		if ($hasused) {
			output ("You swing open the Cellar door and take your first step ...");
			output ("`n`n");
			output ("... but strangely, a strong gust of wind pushes you back.");
			output ("It's almost as if the Cellar has a mind of its own and");
			output ("it doesn't want you to go down there again today.");
			output ("`n`n");
			output ("Perhaps tomorrow, your Cellar will be in a better mood.");
			output ("`n`n");
		}
		
		switch ($cellchance) {
		case 1:
			if (!$session['user']['superuser']) set_module_pref ("hasused",1);
			output ("You step down into the dank, dark depths of the Cellar.");
			output ("After a little while of wandering, you hear an ear-shattering howl,");
			output ("this is quickly followed by a clanking of chains and a");
			output ("aged, echo-y voice calling 'r~o~s~e~b~u~d' and as a spine-");
			output ("chilling encore to this spooky foray, a hand lands on your shoulder.");
			output ("`n`n");
			output ("...");
			output ("`n`n");
			output ("You scream and run away like a little girl!  That was just too much.");
			output ("`n`n");
			break;

		case 2:
			if (!$session['user']['superuser']) set_module_pref ("hasused",1);
			output ("You step down into the dank, dark depths of the Cellar.");
			output ("After a little while of wandering, %s pounces at you out of nowhere!", $intruder);
			output ("carrying one of those new-fangled %s portable torches.", $brand);
			output ("\"`^I'M THE LEPRECHAUN!`7\", %s yells. You stare in horror for a few", $intruder);
			output ("moments, having the bejeezus scared out of you. Upon regaining");
			output ("your perspicacity, you yell, \"`6`iWhat the bloody, bloody, bloody");
			output ("hell are you doing in my basement?!`i`7\" but %s's only respons is", $intruder);
			output ("a quick egress back up the stairs, out of the Cellar and your house.");
			output ("`n`n");
			output ("Much to your chagrin, you'll probably never be able to get %s back", $intruder);
			output ("for that one. ... probably.");
			output ("`n`n");
			output ("`&");
			output ("You lose `$%s `&hitpoints from being scared half to death!", $session['user']['hitpoints'] / 2);
			$session['user']['hitpoints'] = $session['user']['hitpoints'] / 2;
			break;

		case 3:
			if (!$session['user']['superuser']) set_module_pref ("hasused",1);
			output ("You step down into the dank, dark depths of the Cellar.");
			output ("After a little while of wandering, you hear a voice!");
			output ("`n`n");
			output ("Now, what's most strange about this voice is that it");
			output ("is scary but not in the way you might expect.  It sounds");
			output ("more like a gameshow host. You press on to find %s presenting", $intruder);
			output ("a cooking show to the residents of your basement. Which");
			output ("happen to be a Spider, a Rat and a Mouse that's either");
			output ("stupifyingly bored or dead. You can't quite tell.");
			output ("`n`n");
			output ("%s is explaining the merits of the triple-decker sandwich", $intruder);
			output ("to them and rather well at that, though quite how almost");
			output ("an entire movie studio has been smuggled into your basement");
			output ("is a mystery you can't stand to bear. You want answers!");
			output ("`n`n");
			output ("You stride boldly up to %s and demand, \"`6`iWhat the bloody,", $intruder);
			output ("bloody, bloody hell are you doing in my basement?`7`i\".");
			output ("To this, %s has only one response, a quick egress up the", $intruder);
			output ("up the stairs, out of your Cellar and your home. Leaving you");
			output ("quite startled.");
			output ("`n`n");
			output ("He did leave behind his foodstuffs, however. So you might");
			output ("aswell make use of them.");
			output ("`n`n");
			if (is_module_active('usechow')) {
				output ("`c");
				switch(e_rand(1,7)){
				case 1:
				output("`^You snap up a slice of bread from the once-cooking show!`0");
				for ($i=0;$i<6;$i+=1){
						$chow[$i]=substr(strval($uchow),$i,1);
						if ($chow[$i]=="0" and $done < 1){
							$chow[$i]="1";
							$done = 1;
						}
						$newchow.=$chow[$i];
					}
				break;
				case 2:
				output("`^You snap up a Pork Chop from the once-cooking show!`0");
				for ($i=0;$i<6;$i+=1){
						$chow[$i]=substr(strval($uchow),$i,1);
						if ($chow[$i]=="0" and $done < 1){
							$chow[$i]="2";
							$done = 1;
						}
						$newchow.=$chow[$i];
					}
				break;
				case 3:
				output("`^You snap up a Ham Steak from the once-cooking show!`0");
				for ($i=0;$i<6;$i+=1){
						$chow[$i]=substr(strval($uchow),$i,1);
						if ($chow[$i]=="0" and $done < 1){
							$chow[$i]="3";
							$done = 1;
						}
						$newchow.=$chow[$i];
					}
				break;
				case 4:
				output("`^You snap up a Steak from the once-cooking show!`0");
				for ($i=0;$i<6;$i+=1){
						$chow[$i]=substr(strval($uchow),$i,1);
						if ($chow[$i]=="0" and $done < 1){
							$chow[$i]="4";
							$done = 1;
						}
						$newchow.=$chow[$i];
					}
				break;
				case 5:
				output("`^You snap up a Whole Chicken from the once-cooking show!`0");
				for ($i=0;$i<6;$i+=1){
						$chow[$i]=substr(strval($uchow),$i,1);
						if ($chow[$i]=="0" and $done < 1){
							$chow[$i]="5";
							$done = 1;
						}
						$newchow.=$chow[$i];
					}
				break;
				case 6:
				output("`^You snap up a bottle of milk from the once-cooking show!`0");
				for ($i=0;$i<6;$i+=1){
						$chow[$i]=substr(strval($uchow),$i,1);
						if ($chow[$i]=="0" and $done < 1){
							$chow[$i]="6";
							$done = 1;
						}
						$newchow.=$chow[$i];
					}
				break;
				case 7:
				output("`^You snap up a bottle of Water from the once-cooking show!`0");
				for ($i=0;$i<6;$i+=1){
						$chow[$i]=substr(strval($uchow),$i,1);
						if ($chow[$i]=="0" and $done < 1){
							$chow[$i]="7";
							$done = 1;
						}
						$newchow.=$chow[$i];
					}
				break;
				}
				set_module_pref('chow',$newchow,'usechow');
			}
			
			else {
				$foodhits = e_rand(1,10);
				output ("`&");
				output ("`c");
				output ("You scout around the food display and scrount up enough food");
				output ("to restore `2%s `&hitpoints", $foodhits);
				$session['user']['hitpoints']+=$foodhits;
			}
			break;

		case 4:
			if (!$session['user']['superuser']) set_module_pref ("hasused",1);
			output ("You step down into the dank, dark depths of the Cellar.");
			output ("After a little while of wandering you find something that");
			output ("seems to be the entrance of a tunnel that leads ...");
			output ("`n`n");
			output ("Well, actually, you have no clue where it will go.");
			output ("Will curiousity get the better of you?");
			output ("`n`n");
			addnav ("Mysterious Tunnel", "runmodule.php?module=housecellar&op=tunnel&id=".$id);
			break;
		}
		modulehook ("gobacknav");
		addnav ("Main Hall", "runmodule.php?module=house&lo=house&id=".$id);
	}
	
	if ($op == "tunnel") {
		if (!$opmain) {
			output ("You follow the winding tunnel for a while but strangely,");
			output ("it only takes you back to your house and nowhere else!");
			output ("`n`n");
		}

		else {
			$target_module = get_module_setting("loc" . $trvchance);
			$target_on = get_module_setting("on" . $trvchance);
			$tunnel_continues = false;
			if ($target_on && $target_module != "" && !is_module_active($target_module)) {
				$tunnel_continues = true;
			}

			if ($tunnel_continues) {
				output ("The tunnel seems to continue...`n");
				addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");
			} else {
				switch($trvchance) {
	
			case "1":
				if (!get_module_setting("on1")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city1");
					$location = get_module_setting("loc1");
					$descript = get_module_setting("desc1");
				}
				break;
		
			case "2":
				if (!get_module_setting("on2")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city2");
					$location = get_module_setting("loc2");
					$descript = get_module_setting("desc2");
				}
				break;
		
			case "3":
				if (!get_module_setting("on3")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city3");
					$location = get_module_setting("loc3");
					$descript = get_module_setting("desc3");
				}
				break;
		
			case "4":
				if (!get_module_setting("on4")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city4");
					$location = get_module_setting("loc4");
					$descript = get_module_setting("desc4");
				}
				break;
		
			case "5":
				if (!get_module_setting("on5")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city5");
					$location = get_module_setting("loc5");
					$descript = get_module_setting("desc5");
				}
				break;
		
			case "6":
				if (!get_module_setting("on6")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city6");
					$location = get_module_setting("loc6");
					$descript = get_module_setting("desc6");
				}
				break;
		
			case "7":
				if (!get_module_setting("on7")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city7");
					$location = get_module_setting("loc7");
					$descript = get_module_setting("desc7");
				}
				break;
		
			case "8":
				if (!get_module_setting("on8")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city8");
					$location = get_module_setting("loc8");
					$descript = get_module_setting("desc8");
				}
				break;	

			case "9":
				if (!get_module_setting("on9")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city9");
					$location = get_module_setting("loc9");
					$descript = get_module_setting("desc9");
				}
				break;

			case "10":
				if (!get_module_setting("on1")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city10");
					$location = get_module_setting("loc10");
					$descript = get_module_setting("desc10");
				}
				break;

			case "11":
				if (!get_module_setting("on11")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city11");
					$location = get_module_setting("loc11");
					$descript = get_module_setting("desc11");
				}
				break;

			case "12":
				if (!get_module_setting("on12")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city12");
					$location = get_module_setting("loc12");
					$descript = get_module_setting("desc12");
				}
				break;

			case "13":
				if (!get_module_setting("on13")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city13");
					$location = get_module_setting("loc13");
					$descript = get_module_setting("desc13");
				}
				break;

			case "14":
				if (!get_module_setting("on14")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city14");
					$location = get_module_setting("loc14");
					$descript = get_module_setting("desc14");
				}
				break;

			case "15":
				if (!get_module_setting("on15")) {
					output ("The tunnel seems to continue...`n");
					addnav ("Continue Down The Tunnel", "runmodule.php?module=housecellar&op=tunnel");;
				}
			
				else {
					$cityloc = get_module_setting("city15");
					$location = get_module_setting("loc15");
					$descript = get_module_setting("desc15");
				}
				break;		
			}
	
			if (isset($location) && $location != "") {
				output ("%s", $descript);
				addnav ("Continue", "runmodule.php?module=".$location);
				$session['user']['location'] = $cityloc;
			}
			}
		}
	}
	page_footer ();
}

?>