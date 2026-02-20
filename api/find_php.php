<?php
// find_php.php - Run this in your browser to find your php.ini path
echo "<h1>PHP Configuration Info</h1>";
echo "<b>Loaded php.ini:</b> " . php_ini_loaded_file() . "<br><br>";
echo "<b>PHP.exe Path:</b> " . PHP_BINARY . "<br><br>";

if (extension_loaded('pdo_sqlite')) {
    echo "<span style='color:green;'>✅ SQLite Driver is ENABLED</span>";
} else {
    echo "<span style='color:red;'>❌ SQLite Driver is DISABLED</span>";
}
?>
