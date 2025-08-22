<?php

namespace VisitorTracking\Models;

use VisitorTracking\Database\Connection;
use PDO;
use PDOException;

class Event
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new event
     */
    public function createEvent(array $eventData): int
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO events (
                    visitor_id, event_type, page_url, page_title, 
                    referrer, event_data, session_id, timestamp
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $eventData['visitor_id'],
                $eventData['event_type'],
                $eventData['page_url'] ?? null,
                $eventData['page_title'] ?? null,
                $eventData['referrer'] ?? null,
                $eventData['event_data'] ?? null,
                $eventData['session_id'],
                $eventData['timestamp']
            ]);
            
            return (int)$this->db->lastInsertId();
            
        } catch (PDOException $e) {
            throw new PDOException("Error creating event: " . $e->getMessage());
        }
    }

    /**
     * Get events by visitor ID
     */
    public function getEventsByVisitor(int $visitorId, int $limit = 100): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM events 
                WHERE visitor_id = ? 
                ORDER BY timestamp DESC 
                LIMIT ?
            ");
            $stmt->execute([$visitorId, $limit]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting events by visitor: " . $e->getMessage());
        }
    }

    /**
     * Get events by session ID
     */
    public function getEventsBySession(string $sessionId): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT e.*, v.visitor_uuid, v.visitor_type 
                FROM events e
                JOIN visitors v ON e.visitor_id = v.id
                WHERE e.session_id = ? 
                ORDER BY e.timestamp ASC
            ");
            $stmt->execute([$sessionId]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting events by session: " . $e->getMessage());
        }
    }

    /**
     * Get recent events for live feed
     */
    public function getRecentEvents(int $minutes = 5, int $limit = 50): array
    {
        try {
            $threshold = (time() - ($minutes * 60)) * 1000; // Convert to milliseconds
            
            $stmt = $this->db->prepare("
                SELECT 
                    e.*,
                    v.visitor_uuid,
                    v.visitor_type
                FROM events e
                JOIN visitors v ON e.visitor_id = v.id
                WHERE e.timestamp > ?
                ORDER BY e.timestamp DESC
                LIMIT ?
            ");
            
            $stmt->execute([$threshold, $limit]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting recent events: " . $e->getMessage());
        }
    }

    /**
     * Get event statistics
     */
    public function getEventStats(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_events,
                    COUNT(CASE WHEN event_type = 'pageview' THEN 1 END) as pageviews,
                    COUNT(CASE WHEN event_type = 'click' THEN 1 END) as clicks,
                    COUNT(CASE WHEN event_type = 'scroll' THEN 1 END) as scrolls,
                    COUNT(CASE WHEN event_type = 'custom' THEN 1 END) as custom_events,
                    COUNT(DISTINCT session_id) as total_sessions
                FROM events
            ");
            
            $stmt->execute();
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting event stats: " . $e->getMessage());
        }
    }

    /**
     * Get page view statistics
     */
    public function getPageViewStats(int $days = 30): array
    {
        try {
            $threshold = (time() - ($days * 24 * 60 * 60)) * 1000;
            
            $stmt = $this->db->prepare("
                SELECT 
                    page_url,
                    page_title,
                    COUNT(*) as views,
                    COUNT(DISTINCT visitor_id) as unique_visitors
                FROM events 
                WHERE event_type = 'pageview' 
                AND timestamp > ?
                AND page_url IS NOT NULL
                GROUP BY page_url, page_title
                ORDER BY views DESC
                LIMIT 20
            ");
            
            $stmt->execute([$threshold]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting page view stats: " . $e->getMessage());
        }
    }

    /**
     * Get referrer statistics
     */
    public function getReferrerStats(int $days = 30): array
    {
        try {
            $threshold = (time() - ($days * 24 * 60 * 60)) * 1000;
            
            $stmt = $this->db->prepare("
                SELECT 
                    CASE 
                        WHEN referrer IS NULL OR referrer = '' THEN 'Direct'
                        ELSE referrer
                    END as referrer_source,
                    COUNT(*) as visits,
                    COUNT(DISTINCT visitor_id) as unique_visitors
                FROM events 
                WHERE event_type = 'pageview' 
                AND timestamp > ?
                GROUP BY referrer_source
                ORDER BY visits DESC
                LIMIT 20
            ");
            
            $stmt->execute([$threshold]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting referrer stats: " . $e->getMessage());
        }
    }

    /**
     * Get event data by time period
     */
    public function getEventsByPeriod(string $period = 'daily', ?string $startDate = null, ?string $endDate = null): array
    {
        try {
            $dateFormat = $this->getDateFormat($period);
            
            // Set default date range if not provided
            if (!$startDate || !$endDate) {
                $dates = $this->getDefaultDateRange($period);
                $startDate = $dates['start'];
                $endDate = $dates['end'];
            }
            
            $startTimestamp = strtotime($startDate . ' 00:00:00') * 1000;
            $endTimestamp = strtotime($endDate . ' 23:59:59') * 1000;
            
            $stmt = $this->db->prepare("
                SELECT 
                    strftime(?, datetime(timestamp/1000, 'unixepoch')) as date,
                    event_type,
                    COUNT(*) as count
                FROM events 
                WHERE timestamp BETWEEN ? AND ?
                GROUP BY date, event_type
                ORDER BY date ASC
            ");
            
            $stmt->execute([$dateFormat, $startTimestamp, $endTimestamp]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting events by period: " . $e->getMessage());
        }
    }

    /**
     * Clean up old events (for maintenance)
     */
    public function cleanupOldEvents(int $days = 90): int
    {
        try {
            $threshold = (time() - ($days * 24 * 60 * 60)) * 1000;
            
            $stmt = $this->db->prepare("DELETE FROM events WHERE timestamp < ?");
            $stmt->execute([$threshold]);
            
            return $stmt->rowCount();
            
        } catch (PDOException $e) {
            throw new PDOException("Error cleaning up old events: " . $e->getMessage());
        }
    }

    /**
     * Get session duration statistics
     */
    public function getSessionStats(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    session_id,
                    MIN(timestamp) as session_start,
                    MAX(timestamp) as session_end,
                    (MAX(timestamp) - MIN(timestamp)) / 1000 as duration_seconds,
                    COUNT(*) as event_count
                FROM events 
                GROUP BY session_id
                HAVING COUNT(*) > 1
                ORDER BY session_start DESC
                LIMIT 100
            ");
            
            $stmt->execute();
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting session stats: " . $e->getMessage());
        }
    }

    /**
     * Validate event data before insertion
     */
    public function validateEventData(array $eventData): array
    {
        $errors = [];
        
        // Required fields
        if (empty($eventData['visitor_id'])) {
            $errors[] = 'visitor_id is required';
        }
        
        if (empty($eventData['event_type'])) {
            $errors[] = 'event_type is required';
        }
        
        if (empty($eventData['session_id'])) {
            $errors[] = 'session_id is required';
        }
        
        if (empty($eventData['timestamp'])) {
            $errors[] = 'timestamp is required';
        }
        
        // Validate event type
        $validEventTypes = ['pageview', 'click', 'scroll', 'custom'];
        if (!empty($eventData['event_type']) && !in_array($eventData['event_type'], $validEventTypes)) {
            $errors[] = 'Invalid event_type. Must be one of: ' . implode(', ', $validEventTypes);
        }
        
        // Validate URLs
        if (!empty($eventData['page_url']) && !filter_var($eventData['page_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid page_url format';
        }
        
        if (!empty($eventData['referrer']) && !filter_var($eventData['referrer'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid referrer format';
        }
        
        // Validate timestamp
        if (!empty($eventData['timestamp'])) {
            $timestamp = (int)$eventData['timestamp'];
            $currentTime = time() * 1000; // Convert to milliseconds
            
            // Check if timestamp is not too far in the future (5 minutes tolerance)
            if ($timestamp > ($currentTime + 300000)) {
                $errors[] = 'Timestamp cannot be in the future';
            }
            
            // Check if timestamp is not too old (24 hours tolerance)
            if ($timestamp < ($currentTime - 86400000)) {
                $errors[] = 'Timestamp is too old';
            }
        }
        
        return $errors;
    }

    private function getDateFormat(string $period): string
    {
        switch ($period) {
            case 'hourly':
                return '%Y-%m-%d %H:00:00';
            case 'daily':
                return '%Y-%m-%d';
            case 'weekly':
                return '%Y-W%W';
            case 'monthly':
                return '%Y-%m';
            default:
                return '%Y-%m-%d';
        }
    }

    private function getDefaultDateRange(string $period): array
    {
        $endDate = date('Y-m-d');
        
        switch ($period) {
            case 'hourly':
                $startDate = date('Y-m-d', strtotime('-1 day'));
                break;
            case 'daily':
                $startDate = date('Y-m-d', strtotime('-30 days'));
                break;
            case 'weekly':
                $startDate = date('Y-m-d', strtotime('-12 weeks'));
                break;
            case 'monthly':
                $startDate = date('Y-m-d', strtotime('-12 months'));
                break;
            default:
                $startDate = date('Y-m-d', strtotime('-30 days'));
        }
        
        return ['start' => $startDate, 'end' => $endDate];
    }
}