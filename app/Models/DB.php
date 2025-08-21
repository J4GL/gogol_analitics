<?php

class DB {
    private static $instance = null;
    private $pdo;
    
    private function __construct() {
        $dbPath = $_ENV['DATABASE_PATH'] ?? './database.sqlite';
        
        try {
            $this->pdo = new PDO("sqlite:$dbPath");
            $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            // Enable foreign keys
            $this->pdo->exec('PRAGMA foreign_keys = ON');
            
            // Create tables if they don't exist
            $this->createTables();
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw $e;
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->pdo;
    }
    
    private function createTables() {
        $sql = "
        CREATE TABLE IF NOT EXISTS events (
            id TEXT NOT NULL,
            ts_ns INTEGER NOT NULL,
            client_ts_ns INTEGER,
            page TEXT,
            referrer TEXT,
            timezone TEXT,
            country TEXT,
            os TEXT,
            browser TEXT,
            resolution TEXT,
            page_load_ms INTEGER,
            is_bot INTEGER DEFAULT 0,
            user_agent TEXT,
            raw_payload TEXT
        );
        
        CREATE INDEX IF NOT EXISTS idx_events_ts ON events(ts_ns);
        CREATE INDEX IF NOT EXISTS idx_events_id ON events(id);
        ";
        
        $this->pdo->exec($sql);
    }
    
    public function query($sql, $params = []) {
        try {
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            return $stmt;
        } catch (PDOException $e) {
            error_log("Query failed: " . $e->getMessage() . " SQL: $sql");
            throw $e;
        }
    }
    
    public function insert($table, $data) {
        $columns = implode(',', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));
        
        $sql = "INSERT INTO $table ($columns) VALUES ($placeholders)";
        return $this->query($sql, $data);
    }
    
    public function select($table, $conditions = [], $orderBy = '', $limit = '') {
        $sql = "SELECT * FROM $table";
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $column => $value) {
                $whereClause[] = "$column = :$column";
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        if ($orderBy) {
            $sql .= " ORDER BY $orderBy";
        }
        
        if ($limit) {
            $sql .= " LIMIT $limit";
        }
        
        return $this->query($sql, $conditions);
    }
}
