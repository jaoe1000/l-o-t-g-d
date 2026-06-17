<?php
define("ALLOW_ANONYMOUS", true);
require_once('common.php');

$sql = "UPDATE " . db_prefix("modules") . " SET filemoddate='2026-06-16 20:29:34' WHERE modulename='bladder'";
$res = db_query($sql);
if ($res === false) {
    echo "Update failed!\n";
} else {
    echo "Update query ran. Affected rows: " . db_affected_rows($res) . "\n";
}

$sql = "SELECT filemoddate FROM " . db_prefix("modules") . " WHERE modulename='bladder'";
$res = db_query($sql);
$row = db_fetch_assoc($res);
echo "New DB mtime for bladder: " . $row['filemoddate'] . "\n";
?>
