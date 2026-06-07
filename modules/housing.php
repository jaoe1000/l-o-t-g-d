<?
require_once "common.php";
page_header("Drygon Estates - Housing Developments");
##################
####Settings######
##################
$acctid=$session[user][acctid];
$myname=$session[user][name];
$config = unserialize($session['user']['donationconfig']);
if ($session[user][housing]!=0){
$sql = "SELECT * FROM housing WHERE ownerid='$acctid'";
$result = db_query($sql);
$row = db_fetch_assoc($result);}
$session[user][location]=H;
addcommentary();
####################
####End Settings####
####################
{
{
switch($HTTP_GET_VARS[op])
    {
case "":

//Purchase Your House Here
addnav("Drygon Estates");
if ($session[user][housing]==0){
addnav("Purchase a Hut","housing.php?op=Hut");
output("`c`b`&Housing Info`c`b`n`n");
output("`@The Hut`nCost: 10000 Gold`n`n");
addnav("Purchase a Town House","housing.php?op=ranchhouse");
output("`4The Town House`nCost: 100000 Gold`n`n");
addnav("Purchase a Manor","housing.php?op=manor");
output("`%The Manor`nCost: 500000 Gold`n`n");
addnav("Purchase a Mansion","housing.php?op=mansion");
output("`^The Mansion`nCost: 50000000 Gold`n`n");
addnav("Purchase a Castle","housing.php?op=castle");
output("`&The Castle`nCost: 100000000 Gold`n`n");
}else{
addnav("My House","house.php");
addnav("Sell Home","housing.php?op=sells");
}
addnav("Return to Village","village.php");
break;
/*
|------------------------------------|
|-Begin the House Purchasing Scripts-|
|------------------------------------|
*/

//Purchase Hut
case "Hut":
        if ($session[user][gold]<10000){
output("`^Error! You need at least 10000 Gold to Purchase a Hut!");
addnav("Go Back","housing.php");

}else{
        $session[user][gold]-=10000;
        $session[user][housing]=1;
        $sql = "INSERT INTO housing
                   (ownerid,
                    value,
                                           ownername,
                                           type
                ) VALUES (
                    '$acctid',
                   '10000',
                                           '$myname',
                                           'Hut'
                )";
                db_query($sql) or die(db_error(LINK));

$sql = "SELECT * FROM housing WHERE ownerid='$acctid'";
$result1 = db_query($sql);
$row1 = db_fetch_assoc($result1);
addnav("Enter Your House","house.php");
addnav("Go Back","housing.php");
}
break;

//Purchase Ranch House
case "ranchhouse":
        if ($session[user][gold]<100000){
output("`4Error! `^You need atleast 100000 Gold to Purchase a Ranch House!");
addnav("Go Back","housing.php");

}else{
        $session[user][gold]-=100000;
        $session[user][housing]=2;
        $sql = "INSERT INTO housing
                   (ownerid,
                    value,
                                           ownername,
                                           type
                ) VALUES (
                    '$acctid',
                   '100000',
                   
                                           '$myname',
                                           'Town House'
                )";
                db_query($sql) or die(db_error(LINK));

$sql = "SELECT * FROM housing WHERE ownerid='$acctid'";
$result1 = db_query($sql);
$row1 = db_fetch_assoc($result1);
addnav("Enter Your House","house.php");
addnav("Go Back","housing.php");
}
break;

//Purchase Manor
case "manor":
        if ($session[user][gold]<500000){
output("`^Error! You need atleast 500000 Gold to Purchase a Manor!");
addnav("Go Back","housing.php");

}else{
        $session[user][gold]-=500000;
        $session[user][housing]=3;
        $sql = "INSERT INTO housing
                   (ownerid,
                    value,
                 
                                           ownername,
                                           type
                ) VALUES (
                    '$acctid',
                   '725000',
                                           
                                           '$myname',
                                           'Manor'
                )";
                db_query($sql) or die(db_error(LINK));

$sql = "SELECT * FROM housing WHERE ownerid='$acctid'";
$result1 = db_query($sql);
$row1 = db_fetch_assoc($result1);
addnav("Enter Your House","house.php");
addnav("Go Back","housing.php");
}
break;

//Purchase Mansion
case "mansion":
        if ($session[user][gold]<50000000){
output("`^Error! You need atleast 50000000 Gold to Purchase a Mansion!");
addnav("Go Back","housing.php");

}else{
        $session[user][gold]-=50000000;
        $session[user][housing]=4;
        $sql = "INSERT INTO housing
                   (ownerid,
                    value,
                                           
                                           ownername,
                                           type
                ) VALUES (
                    '$acctid',
                   '50000000',
                                           
                                           '$myname',
                                           'Mansion'
                )";
                db_query($sql) or die(db_error(LINK));

$sql = "SELECT * FROM housing WHERE ownerid='$acctid'";
$result1 = db_query($sql);
$row1 = db_fetch_assoc($result1);
addnav("Enter Your House","house.php");
addnav("Go Back","housing.php");
}
break;

//Purchase Castle
case "castle":
        if ($session[user][gold]<100000000){
output("`^Error! You need at least 100000000 Gold to Purchase a Caslte!");
addnav("Go Back","housing.php");

}else{
        $session[user][gold]-=100000000;
        $session[user][housing]=5;
        $sql = "INSERT INTO housing
                   (ownerid,
                    value,
                                           
                                           ownername,
                                           type
                ) VALUES (
                    '$acctid',
                   '100000000',
                                           '$myname',
                                           'Castle'
                )";
                db_query($sql) or die(db_error(LINK));

$sql = "SELECT * FROM housing WHERE ownerid='$acctid'";
$result1 = db_query($sql);
$row1 = db_fetch_assoc($result1);
addnav("Enter Your House","house.php");
addnav("Go Back","housing.php");
}
break;

//Sell your House
    case "sells":
addnav("Return to Village","village.php");
addnav("`4Im Sure","housing.php?op=sell");
output("`#$myname, `#this is a big step, are you positive you wish to sell your home? If your sure click the `4IM SURE`# button, otherwise click the back to village button. I will give you `^$row[value] `#for your home`n");


break;

case "sell":
    if ($session[user][housing]==0){
output("`#Im sorry you need a house to sell first...`n");
addnav("Return to Village","village.php");
}else{
$session[user][gold]+=$row[value]/2;
$session[user][housing]=0;
   $sql = "DELETE FROM housing WHERE ownerid='$acctid'";
        db_query($sql);
addnav("Return to Village","village.php");
output("`@You have sold your house!`n");
}
break;

}
}
}
    page_footer();
?>
