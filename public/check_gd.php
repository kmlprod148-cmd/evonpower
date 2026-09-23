<?php
if (extension_loaded('gd')) {
    echo "✅ GD extension is ENABLED<br>";
    echo "GD Version: " . gd_info()['GD Version'] . "<br>";
    echo "Supported formats: " . implode(', ', array_keys(array_filter(gd_info()))) . "<br>";
} else {
    echo "❌ GD extension is NOT ENABLED<br>";
}
echo "<br>PHP Version: " . PHP_VERSION . "<br>";
echo "Loaded php.ini: " . php_ini_loaded_file() . "<br>";
?> 