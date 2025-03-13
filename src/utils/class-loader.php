<?php
function loadClass($className) {
    $paths = [
        'Security' => __DIR__ . '/security-functions.php',
        'Database' => __DIR__ . '/db-connect.php',
        'AuthHandler' => __DIR__ . '/../auth/auth-handlers.php'
    ];

    if (isset($paths[$className])) {
        if (file_exists($paths[$className])) {
            require_once $paths[$className];
            return true;
        }
    }
    return false;
}

spl_autoload_register('loadClass');