<?php
// Simple debug file
echo "<h1>Debug Information</h1>";
echo "<h2>Server Information</h2>";
echo "<pre>";
print_r($_SERVER);
echo "</pre>";

echo "<h2>Current Working Directory</h2>";
echo "<p>" . getcwd() . "</p>";

echo "<h2>Files in Current Directory</h2>";
echo "<pre>";
print_r(scandir('.'));
echo "</pre>";

echo "<h2>PHP Info</h2>";
phpinfo();
?>