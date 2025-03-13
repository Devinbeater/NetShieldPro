<?php
// Last Updated: 2025-03-05 17:13:31
// Author: Devinbeater

define('ROOT_PATH', dirname(__DIR__));  // Points to C:\xampp\htdocs
define('SRC_PATH', __DIR__);           // Points to C:\xampp\htdocs\src

// Verify paths exist
if (!file_exists(SRC_PATH . '/utils/db-connect.php')) {
    die('Database connection file not found');
}

if (!file_exists(SRC_PATH . '/auth/auth-handlers.php')) {
    die('Authentication handlers file not found');
}