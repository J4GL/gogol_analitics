<?php

require_once __DIR__ . '/vendor/autoload.php';

use VisitorTracking\Database\Connection;

try {
    echo "Initializing database...\n";
    
    // Create database directory if it doesn't exist
    $storageDir = __DIR__ . '/../storage';
    if (!is_dir($storageDir)) {
        mkdir($storageDir, 0755, true);
    }
    
    // Create tables
    Connection::createTables();
    
    echo "Database initialized successfully!\n";
    echo "SQLite database created at: " . realpath(__DIR__ . '/../storage/analytics.sqlite') . "\n";
    
} catch (Exception $e) {
    echo "Error initializing database: " . $e->getMessage() . "\n";
    exit(1);
}