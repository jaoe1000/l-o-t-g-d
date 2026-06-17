<?php
define("ALLOW_ANONYMOUS", true);
require_once('common.php');

$modules = ['clanbarracks', 'villageevents', 'clanoptions', 'bladder', 'odor'];
foreach ($modules as $modulename) {
    $modulefilename = "modules/{$modulename}.php";
    if (file_exists($modulefilename)) {
        $filemoddate = date("Y-m-d H:i:s", filemtime($modulefilename));
        $sql = "SELECT filemoddate, infokeys, version FROM " . db_prefix("modules") . " WHERE modulename='$modulename'";
        $result = db_query($sql);
        $row = db_fetch_assoc($result);
        echo "Module: $modulename\n";
        echo "  Disk mtime: $filemoddate\n";
        echo "  DB mtime:   " . ($row['filemoddate'] ?? 'NULL') . "\n";
        echo "  DB keys:    " . ($row['infokeys'] ?? 'NULL') . "\n";
        echo "  DB version: " . ($row['version'] ?? 'NULL') . "\n";
        $match = ($row['filemoddate'] == $filemoddate) ? 'YES' : 'NO';
        echo "  Match:      $match\n";
    }
}
?>
