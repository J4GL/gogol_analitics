<?php

class Event {
    private $db;
    
    public function __construct() {
        $this->db = DB::getInstance();
    }
    
    public function create($data) {
        return $this->db->insert('events', $data);
    }
    
    public function getRecent($limit = 100) {
        $stmt = $this->db->query(
            "SELECT * FROM events ORDER BY ts_ns DESC LIMIT ?",
            [$limit]
        );
        
        return $stmt->fetchAll();
    }
    
    public function getByTimeRange($startTimeNs, $endTimeNs) {
        $stmt = $this->db->query(
            "SELECT * FROM events WHERE ts_ns >= ? AND ts_ns <= ? ORDER BY ts_ns ASC",
            [$startTimeNs, $endTimeNs]
        );
        
        return $stmt->fetchAll();
    }
    
    public function getStatsForHours($hours = 24) {
        $endTime = time();
        $startTime = $endTime - ($hours * 3600);
        $startTimeNs = $startTime * 1000000000;
        
        // Get events with some buffer for firstSeen calculation
        $bufferHours = 24;
        $bufferStartTimeNs = ($startTime - ($bufferHours * 3600)) * 1000000000;
        
        $stmt = $this->db->query(
            "SELECT id, ts_ns, is_bot FROM events WHERE ts_ns >= ? ORDER BY ts_ns ASC",
            [$bufferStartTimeNs]
        );
        
        return $stmt->fetchAll();
    }
    
    public function getUniqueVisitors($startTimeNs, $endTimeNs) {
        $stmt = $this->db->query(
            "SELECT COUNT(DISTINCT id) as count FROM events 
             WHERE ts_ns >= ? AND ts_ns <= ? AND is_bot = 0",
            [$startTimeNs, $endTimeNs]
        );
        
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    public function getBotCount($startTimeNs, $endTimeNs) {
        $stmt = $this->db->query(
            "SELECT COUNT(DISTINCT id) as count FROM events 
             WHERE ts_ns >= ? AND ts_ns <= ? AND is_bot = 1",
            [$startTimeNs, $endTimeNs]
        );
        
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    }
    
    public function getTopPages($startTimeNs, $endTimeNs, $limit = 10) {
        $stmt = $this->db->query(
            "SELECT page, COUNT(*) as count FROM events 
             WHERE ts_ns >= ? AND ts_ns <= ? AND page IS NOT NULL AND is_bot = 0
             GROUP BY page ORDER BY count DESC LIMIT ?",
            [$startTimeNs, $endTimeNs, $limit]
        );
        
        return $stmt->fetchAll();
    }
    
    public function getTopCountries($startTimeNs, $endTimeNs, $limit = 10) {
        $stmt = $this->db->query(
            "SELECT country, COUNT(DISTINCT id) as count FROM events 
             WHERE ts_ns >= ? AND ts_ns <= ? AND country IS NOT NULL AND is_bot = 0
             GROUP BY country ORDER BY count DESC LIMIT ?",
            [$startTimeNs, $endTimeNs, $limit]
        );
        
        return $stmt->fetchAll();
    }
    
    public function getTopBrowsers($startTimeNs, $endTimeNs, $limit = 10) {
        $stmt = $this->db->query(
            "SELECT browser, COUNT(DISTINCT id) as count FROM events 
             WHERE ts_ns >= ? AND ts_ns <= ? AND browser IS NOT NULL AND is_bot = 0
             GROUP BY browser ORDER BY count DESC LIMIT ?",
            [$startTimeNs, $endTimeNs, $limit]
        );
        
        return $stmt->fetchAll();
    }
    
    public function getTopOS($startTimeNs, $endTimeNs, $limit = 10) {
        $stmt = $this->db->query(
            "SELECT os, COUNT(DISTINCT id) as count FROM events 
             WHERE ts_ns >= ? AND ts_ns <= ? AND os IS NOT NULL AND is_bot = 0
             GROUP BY os ORDER BY count DESC LIMIT ?",
            [$startTimeNs, $endTimeNs, $limit]
        );
        
        return $stmt->fetchAll();
    }
    
    public function cleanupOldEvents($retentionHours = 168) {
        $cutoffTime = time() - ($retentionHours * 3600);
        $cutoffTimeNs = $cutoffTime * 1000000000;
        
        $stmt = $this->db->query(
            "DELETE FROM events WHERE ts_ns < ?",
            [$cutoffTimeNs]
        );
        
        return $stmt->rowCount();
    }
    
    public function formatEvent($event) {
        return [
            'id' => $event['id'],
            'timestamp' => gmdate('Y-m-d H:i:s', intval($event['ts_ns'] / 1000000000)),
            'page' => $event['page'],
            'referrer' => $event['referrer'],
            'country' => $event['country'],
            'os' => $event['os'],
            'browser' => $event['browser'],
            'resolution' => $event['resolution'],
            'page_load_ms' => $event['page_load_ms'],
            'is_bot' => (bool)$event['is_bot'],
            'timezone' => $event['timezone']
        ];
    }
}
