<?php
// Path Verification Tool
// Last Updated: 2025-03-05 17:13:31
// Author: Devinbeater

echo "Path Verification Results:\n";
echo "========================\n";
echo "Current Script: " . __FILE__ . "\n";
echo "Current Directory: " . __DIR__ . "\n\n";

$required_files = [
    [
        'description' => 'Database Connection File',
        'path' => 'C:\\xampp\\htdocs\\src\\utils\\db-connect.php'
    ],
    [
        'description' => 'Authentication Handlers',
        'path' => 'C:\\xampp\\htdocs\\src\\auth\\auth-handlers.php'
    ]
];

foreach ($required_files as $file) {
    echo "Checking {$file['description']}:\n";
    echo "Path: {$file['path']}\n";
    
    if (file_exists($file['path'])) {
        echo "Status: ✓ File exists\n";
        echo "Last Modified: " . date("Y-m-d H:i:s", filemtime($file['path'])) . "\n";
        echo "Readable: " . (is_readable($file['path']) ? "Yes" : "No") . "\n";
    } else {
        echo "Status: ✗ File not found\n";
        echo "Parent directory exists: " . 
             (file_exists(dirname($file['path'])) ? "Yes" : "No") . "\n";
    }
    echo "\n";
}