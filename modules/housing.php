<?php
require_once "common.php";
page_header("Drygon Estates - Housing Developments");

$acctid = $session['user']['acctid'];
$myname = $session['user']['name'];
$row = [];

if ($session['user']['housing'] != 0) {
    $sql = "SELECT * FROM housing WHERE ownerid='$acctid'";
    $result = db_query($sql);
    $row = db_fetch_assoc($result);
}

addcommentary();
$op = httpget('op');

switch($op) {
    case "":
        addnav("Drygon Estates");
        if ($session['user']['housing'] == 0) {
            addnav("Purchase a Hut", "housing.php?op=Hut");
            output("`c`b`&Housing Info`c`b`n`n");
            output("`@The Hut`nCost: 10000 Gold`n`n");
            addnav("Purchase a Town House", "housing.php?op=ranchhouse");
            output("`4The Town House`nCost: 100000 Gold`n`n");
            addnav("Purchase a Manor", "housing.php?op=manor");
            output("`%The Manor`nCost: 500000 Gold`n`n");
            addnav("Purchase a Mansion", "housing.php?op=mansion");
            output("`^The Mansion`nCost: 50000000 Gold`n`n");
            addnav("Purchase a Castle", "housing.php?op=castle");
            output("`&The Castle`nCost: 100000000 Gold`n`n");
        } else {
            addnav("My House", "house.php");
            addnav("Sell Home", "housing.php?op=sells");
        }
        addnav("Return to Village", "village.php");
        break;

    case "Hut":
        if ($session['user']['gold'] < 10000) {
            output("`^Error! You need at least 10000 Gold to Purchase a Hut!");
            addnav("Go Back", "housing.php");
        } else {
            $session['user']['gold'] -= 10000;
            $session['user']['housing'] = 1;
            db_query("INSERT INTO housing (ownerid, value, ownername, type) VALUES ('$acctid', '10000', '$myname', 'Hut')");
            addnav("Enter Your House", "house.php");
            addnav("Go Back", "housing.php");
        }
        break;

    case "sells":
        addnav("Return to Village", "village.php");
        addnav("`4I'm Sure", "housing.php?op=sell");
        $val = $row['value'] ?? 0;
        output("`#$myname, `#this is a big step, are you positive you wish to sell your home? I will give you `^$val `#for your home`n");
        break;

    case "sell":
        if ($session['user']['housing'] == 0) {
            output("`#I'm sorry, you need a house to sell first...`n");
            addnav("Return to Village", "village.php");
        } else {
            $val = $row['value'] ?? 0;
            $session['user']['gold'] += ($val / 2);
            $session['user']['housing'] = 0;
            db_query("DELETE FROM housing WHERE ownerid='$acctid'");
            addnav("Return to Village", "village.php");
            output("`@You have sold your house!`n");
        }
        break;
}

page_footer();
?>
