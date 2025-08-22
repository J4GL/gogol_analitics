<?php

return [
    'driver' => 'sqlite',
    'database' => __DIR__ . '/../storage/analytics.sqlite',
    'charset' => 'utf8mb4',
    'prefix' => '',
    'foreign_key_constraints' => true,
    'journal_mode' => 'WAL', // Write-Ahead Logging for better performance
    'synchronous' => 'NORMAL',
    'cache_size' => 2000,
    'temp_store' => 'MEMORY',
    'mmap_size' => 134217728, // 128MB
];