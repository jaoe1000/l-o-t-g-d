<?php
$filePath = "accounts-output/$userid.html";
$output = is_file($filePath) ? file_get_contents($filePath) : '';
$output = str_replace("<iframe src=", "<iframe Xsrc=", $output);
$output = str_replace(".focus();",".blur();", $output);
echo $output;
exit();
?>