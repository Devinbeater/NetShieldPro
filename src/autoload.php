<?php
// Autoloader for NetShield Pro
// Last Updated: 2025-03-05 17:13:31
// Author: Devinbeater

spl_autoload_register(function ($class) {
    // Convert namespace to full file path
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . DIRECTORY_SEPARATOR . $path . '.php';
    
    if (file_exists($file)) {
        require_once $file;
        return true;
    }
    return false;
});

// Define base paths
define('BASE_PATH', dirname(__DIR__));
define('SRC_PATH', __DIR__);
define('UTILS_PATH', SRC_PATH . DIRECTORY_SEPARATOR . 'utils');
define('AUTH_PATH', SRC_PATH . DIRECTORY_SEPARATOR . 'auth');