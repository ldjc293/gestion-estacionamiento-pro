<?php
if (function_exists('curl_init')) {
    echo "CURL is enabled\n";
} else {
    echo "CURL is DISABLED\n";
}
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Loaded config file: " . php_ini_loaded_file() . "\n";
