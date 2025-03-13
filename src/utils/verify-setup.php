<?php
echo "Setup Verification\n";
echo "=================\n";
echo "Current Time: 2025-03-06 08:23:34\n\n";

function verifyFile($path) {
    echo "Checking file: $path\n";
    if (file_exists($path)) {
        echo "✓ File exists\n";
        echo "Last modified: " . date("Y-m-d H:i:s", filemtime($path)) . "\n";
        if (is_readable($path)) {
            echo "✓ File is readable\n";
            // Try to include the file
            try {
                require_once $path;
                echo "✓ File included successfully\n";
            } catch (Exception $e) {
                echo "✗ Error including file: " . $e->getMessage() . "\n";
            }
        } else {
            echo "✗ File is not readable\n";
        }
    } else {
        echo "✗ File not found\n";
    }
    echo "\n";
}

// Verify critical files
$files = [
    'C:/xampp/htdocs/src/utils/security-functions.php',
    'C:/xampp/htdocs/src/utils/db-connect.php',
    'C:/xampp/htdocs/src/utils/class-loader.php',
    'C:/xampp/htdocs/src/auth/auth-handlers.php',
    'C:/xampp/htdocs/src/auth/register.php'
];

foreach ($files as $file) {
    verifyFile($file);
}

// Verify class loading
echo "Verifying class loading:\n";
$classes = ['Security', 'Database', 'AuthHandler'];
foreach ($classes as $class) {
    echo "Checking class: $class\n";
    if (class_exists($class)) {
        echo "✓ Class loaded successfully\n";
    } else {
        echo "✗ Class not found\n";
    }
    echo "\n";
}