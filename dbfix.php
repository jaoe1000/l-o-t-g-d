<?php
require_once('common.php');

// Tell the database to change the 'value' column to a massive TEXT field
$sql = "ALTER TABLE " . db_prefix('settings') . " MODIFY value TEXT";
db_query($sql);

echo "<h2>Database expanded! You can now save massive settings.</h2>";
?>
