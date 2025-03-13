<?php
/**
 * Database Verification Script
 * Last Updated: 2025-03-05 17:30:31
 * Author: Devinbeater
 */

echo "Database Connection Test\n";
echo "=======================\n";
echo "Timestamp: 2025-03-05 17:30:31\n";
echo "User: Devinbeater\n\n";

// Try to connect to MySQL server
try {
    $conn = new PDO(
        "mysql:host=localhost",
        "root",
        ""
    );
    echo "✓ MySQL Server Connection: SUCCESS\n";
} catch (PDOException $e) {
    echo "✗ MySQL Server Connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Check if database exists
try {
    $result = $conn->query("SHOW DATABASES LIKE 'netshield_pro'");
    if ($result->rowCount() > 0) {
        echo "✓ Database 'netshield_pro' exists\n";
    } else {
        echo "✗ Database 'netshield_pro' not found\n";
        echo "Please run setup-database.sql first\n";
        exit(1);
    }
} catch (PDOException $e) {
    echo "✗ Database check failed\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Try to connect to the specific database
try {
    $conn = new PDO(
        "mysql:host=localhost;dbname=netshield_pro",
        "root",
        ""
    );
    echo "✓ Database Connection: SUCCESS\n";
} catch (PDOException $e) {
    echo "✗ Database Connection: FAILED\n";
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}

// Check required tables
$required_tables = ['users', 'sessions', 'error_logs'];
echo "\nChecking Required Tables:\n";

foreach ($required_tables as $table) {
    try {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        if ($result->rowCount() > 0) {
            echo "✓ Table '$table': EXISTS\n";
        } else {
            echo "✗ Table '$table': MISSING\n";
        }
    } catch (PDOException $e) {
        echo "✗ Table check failed for '$table'\n";
        echo "Error: " . $e->getMessage() . "\n";
    }
}