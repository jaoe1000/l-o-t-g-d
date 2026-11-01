<?php
// modified by jaoe1000

require_once('lib/gamelog.php');
savesetting('lastdboptimize', date('Y-m-d H:i:s'));
$result = db_query('SHOW TABLES', false);
$tables = [];
if ($result) {
    while ($row = db_fetch_assoc($result)) {
        foreach ($row as $val) {
            $tables[] = $val;
        }
    }
    db_free_result($result);
}
$start = getmicrotime();
foreach ($tables as $val) {
    $optRes = db_query("OPTIMIZE TABLE $val", false);
    if ($optRes) {
        db_free_result($optRes);
    }
}
$time = round(getmicrotime() - $start, 2);
$tableString = join(', ', $tables);
gamelog("Optimized tables: $tableString in $time seconds.", 'maintenance');
?>
