<?php
// modified by jaoe1000

function getmount($horse = 0) {
	$horse = (int)$horse;
	if (!file_exists('dbconnect.php')) return [];
	$mounts = db_prefix('mounts');
	$sql = "SELECT * FROM $mounts WHERE mountid = '$horse';";
	$result = db_query($sql);
	if (db_num_rows($result) > 0) {
		return db_fetch_assoc($result);
	}
	else {
		return [];
	}
}
