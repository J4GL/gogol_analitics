<?php

namespace VisitorTracking\Models;

use VisitorTracking\Database\Connection;
use PDO;
use PDOException;
use Ramsey\Uuid\Uuid;

class Visitor
{
    private PDO $db;

    public function __construct()
    {
        $this->db = Connection::getInstance();
    }

    /**
     * Create a new visitor or get existing one by UUID
     */
    public function createOrGetVisitor(string $visitorUuid, string $ipAddress, string $userAgent, int $timestamp): array
    {
        try {
            // First, try to get existing visitor
            $existingVisitor = $this->getVisitorByUuid($visitorUuid);
            
            if ($existingVisitor) {
                // Update existing visitor
                $visitorType = $this->determineVisitorType($existingVisitor, $userAgent, $timestamp);
                $this->updateVisitor($existingVisitor['id'], $visitorType, $timestamp);
                
                return array_merge($existingVisitor, [
                    'visitor_type' => $visitorType,
                    'last_visit' => $timestamp
                ]);
            }
            
            // Create new visitor
            $visitorType = $this->detectVisitorType($userAgent);
            $hashedIp = $this->hashIpAddress($ipAddress);
            
            $stmt = $this->db->prepare("
                INSERT INTO visitors (visitor_uuid, ip_address, user_agent, visitor_type, first_visit, last_visit)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $visitorUuid,
                $hashedIp,
                $userAgent,
                $visitorType,
                $timestamp,
                $timestamp
            ]);
            
            $visitorId = $this->db->lastInsertId();
            
            return [
                'id' => $visitorId,
                'visitor_uuid' => $visitorUuid,
                'ip_address' => $hashedIp,
                'user_agent' => $userAgent,
                'visitor_type' => $visitorType,
                'first_visit' => $timestamp,
                'last_visit' => $timestamp,
                'visit_count' => 1
            ];
            
        } catch (PDOException $e) {
            throw new PDOException("Error creating/getting visitor: " . $e->getMessage());
        }
    }

    /**
     * Get visitor by UUID
     */
    public function getVisitorByUuid(string $uuid): ?array
    {
        try {
            $stmt = $this->db->prepare("SELECT * FROM visitors WHERE visitor_uuid = ?");
            $stmt->execute([$uuid]);
            $result = $stmt->fetch();
            
            return $result ?: null;
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting visitor: " . $e->getMessage());
        }
    }

    /**
     * Update visitor information
     */
    private function updateVisitor(int $visitorId, string $visitorType, int $timestamp): void
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE visitors 
                SET visitor_type = ?, last_visit = ?, visit_count = visit_count + 1, updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([$visitorType, $timestamp, $visitorId]);
            
        } catch (PDOException $e) {
            throw new PDOException("Error updating visitor: " . $e->getMessage());
        }
    }

    /**
     * Determine visitor type based on existing data and current visit
     */
    private function determineVisitorType(array $existingVisitor, string $userAgent, int $timestamp): string
    {
        // Check if it's a bot
        if ($this->isBot($userAgent)) {
            return 'bot';
        }
        
        // Check session timeout (30 minutes = 1800 seconds = 1800000 milliseconds)
        $sessionTimeout = 1800000;
        $timeDiff = $timestamp - $existingVisitor['last_visit'];
        
        if ($timeDiff > $sessionTimeout) {
            // New session
            return $existingVisitor['visit_count'] > 0 ? 'returning' : 'new';
        }
        
        // Same session, keep existing type unless it was 'new' and now should be 'returning'
        if ($existingVisitor['visitor_type'] === 'new' && $existingVisitor['visit_count'] > 1) {
            return 'returning';
        }
        
        return $existingVisitor['visitor_type'];
    }

    /**
     * Detect visitor type for new visitors
     */
    private function detectVisitorType(string $userAgent): string
    {
        return $this->isBot($userAgent) ? 'bot' : 'new';
    }

    /**
     * Bot detection logic
     */
    private function isBot(string $userAgent): bool
    {
        $botPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'automated',
            'googlebot', 'bingbot', 'slurp', 'duckduckbot',
            'baiduspider', 'yandexbot', 'facebookexternalhit',
            'twitterbot', 'linkedinbot', 'whatsapp', 'telegram',
            'curl', 'wget', 'python-requests', 'postman',
            'phantom', 'selenium', 'headless'
        ];
        
        $userAgentLower = strtolower($userAgent);
        
        foreach ($botPatterns as $pattern) {
            if (strpos($userAgentLower, $pattern) !== false) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Hash IP address for privacy
     */
    private function hashIpAddress(string $ipAddress): string
    {
        return hash('sha256', $ipAddress . 'visitor_tracking_salt');
    }

    /**
     * Get analytics data for charts
     */
    public function getAnalyticsData(string $period = 'daily', ?string $startDate = null, ?string $endDate = null): array
    {
        try {
            $dateFormat = $this->getDateFormat($period);
            $timeInterval = $this->getTimeInterval($period);
            
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
                    strftime(?, datetime(first_visit/1000, 'unixepoch')) as date,
                    visitor_type,
                    COUNT(*) as count
                FROM visitors 
                WHERE first_visit BETWEEN ? AND ?
                GROUP BY date, visitor_type
                ORDER BY date ASC
            ");
            
            $stmt->execute([$dateFormat, $startTimestamp, $endTimestamp]);
            $results = $stmt->fetchAll();
            
            return $this->formatAnalyticsData($results, $period, $startDate, $endDate);
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting analytics data: " . $e->getMessage());
        }
    }

    /**
     * Get live visitors (active in last 5 minutes)
     */
    public function getLiveVisitors(int $minutes = 5): array
    {
        try {
            $threshold = (time() - ($minutes * 60)) * 1000; // Convert to milliseconds
            
            $stmt = $this->db->prepare("
                SELECT 
                    v.visitor_uuid,
                    v.visitor_type,
                    e.page_url,
                    e.page_title,
                    e.timestamp,
                    e.event_type,
                    e.referrer,
                    e.event_data
                FROM visitors v
                INNER JOIN events e ON v.id = e.visitor_id
                WHERE e.timestamp > ?
                ORDER BY e.timestamp DESC
                LIMIT 50
            ");
            
            $stmt->execute([$threshold]);
            return $stmt->fetchAll();
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting live visitors: " . $e->getMessage());
        }
    }

    /**
     * Get visitor statistics
     */
    public function getVisitorStats(): array
    {
        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(*) as total_visitors,
                    COUNT(CASE WHEN visitor_type = 'new' THEN 1 END) as new_visitors,
                    COUNT(CASE WHEN visitor_type = 'returning' THEN 1 END) as returning_visitors,
                    COUNT(CASE WHEN visitor_type = 'bot' THEN 1 END) as bot_visitors
                FROM visitors
            ");
            
            $stmt->execute();
            return $stmt->fetch();
            
        } catch (PDOException $e) {
            throw new PDOException("Error getting visitor stats: " . $e->getMessage());
        }
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

    private function getTimeInterval(string $period): string
    {
        switch ($period) {
            case 'hourly':
                return '1 hour';
            case 'daily':
                return '1 day';
            case 'weekly':
                return '1 week';
            case 'monthly':
                return '1 month';
            default:
                return '1 day';
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

    private function formatAnalyticsData(array $results, string $period, string $startDate, string $endDate): array
    {
        $formatted = [];
        $dateRange = $this->generateDateRange($period, $startDate, $endDate);
        
        // Initialize with zeros
        foreach ($dateRange as $date) {
            $formatted[] = [
                'date' => $date,
                'bots' => 0,
                'new_visitors' => 0,
                'returning_visitors' => 0,
                'total' => 0
            ];
        }
        
        // Fill with actual data
        foreach ($results as $row) {
            $dateKey = array_search($row['date'], $dateRange);
            if ($dateKey !== false) {
                $formatted[$dateKey][$row['visitor_type'] . '_visitors'] = (int)$row['count'];
                if ($row['visitor_type'] === 'bot') {
                    $formatted[$dateKey]['bots'] = (int)$row['count'];
                }
                $formatted[$dateKey]['total'] += (int)$row['count'];
            }
        }
        
        return $formatted;
    }

    private function generateDateRange(string $period, string $startDate, string $endDate): array
    {
        $dates = [];
        $current = strtotime($startDate);
        $end = strtotime($endDate);
        
        $interval = $this->getTimeInterval($period);
        
        while ($current <= $end) {
            $dates[] = date('Y-m-d', $current);
            $current = strtotime("+{$interval}", $current);
        }
        
        return $dates;
    }
}