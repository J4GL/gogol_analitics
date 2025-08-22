<?php

namespace VisitorTracking\Database;

use PDO;
use PDOException;

class Connection
{
    private static ?PDO $instance = null;
    private static array $config;

    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::$config = require __DIR__ . '/../../config/database.php';
            self::connect();
        }

        return self::$instance;
    }

    private static function connect(): void
    {
        try {
            $dsn = 'sqlite:' . self::$config['database'];
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            self::$instance = new PDO($dsn, null, null, $options);

            // Enable foreign key constraints
            if (self::$config['foreign_key_constraints']) {
                self::$instance->exec('PRAGMA foreign_keys = ON');
            }

            // Set journal mode for better performance
            self::$instance->exec('PRAGMA journal_mode = ' . self::$config['journal_mode']);
            
            // Set synchronous mode
            self::$instance->exec('PRAGMA synchronous = ' . self::$config['synchronous']);
            
            // Set cache size
            self::$instance->exec('PRAGMA cache_size = ' . self::$config['cache_size']);
            
            // Set temp store to memory
            self::$instance->exec('PRAGMA temp_store = ' . self::$config['temp_store']);
            
            // Set memory-mapped I/O size
            self::$instance->exec('PRAGMA mmap_size = ' . self::$config['mmap_size']);

        } catch (PDOException $e) {
            throw new PDOException("Database connection failed: " . $e->getMessage());
        }
    }

    public static function createTables(): bool
    {
        $db = self::getInstance();
        
        try {
            $db->beginTransaction();

            // Create visitors table
            $db->exec("
                CREATE TABLE IF NOT EXISTS visitors (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    visitor_uuid VARCHAR(36) UNIQUE NOT NULL,
                    ip_address VARCHAR(64) NOT NULL,
                    user_agent TEXT,
                    visitor_type VARCHAR(20) DEFAULT 'new' CHECK(visitor_type IN ('bot', 'new', 'returning')),
                    first_visit INTEGER NOT NULL,
                    last_visit INTEGER NOT NULL,
                    visit_count INTEGER DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )
            ");

            // Create events table
            $db->exec("
                CREATE TABLE IF NOT EXISTS events (
                    id INTEGER PRIMARY KEY AUTOINCREMENT,
                    visitor_id INTEGER NOT NULL,
                    event_type VARCHAR(50) NOT NULL,
                    page_url VARCHAR(500),
                    page_title VARCHAR(200),
                    referrer VARCHAR(500),
                    event_data TEXT,
                    session_id VARCHAR(32) NOT NULL,
                    timestamp INTEGER NOT NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (visitor_id) REFERENCES visitors(id) ON DELETE CASCADE
                )
            ");

            // Create indexes for better performance
            $db->exec("CREATE INDEX IF NOT EXISTS idx_visitors_uuid ON visitors(visitor_uuid)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_visitors_type ON visitors(visitor_type)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_visitors_last_visit ON visitors(last_visit)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_events_visitor_id ON events(visitor_id)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_events_timestamp ON events(timestamp)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_events_type ON events(event_type)");
            $db->exec("CREATE INDEX IF NOT EXISTS idx_events_session ON events(session_id)");

            $db->commit();
            return true;

        } catch (PDOException $e) {
            $db->rollBack();
            throw new PDOException("Failed to create tables: " . $e->getMessage());
        }
    }

    public static function closeConnection(): void
    {
        self::$instance = null;
    }
}