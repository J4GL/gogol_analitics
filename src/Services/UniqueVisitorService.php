<?php

namespace VisitorTracking\Services;

use VisitorTracking\Models\Visitor;
use VisitorTracking\Models\Event;
use Ramsey\Uuid\Uuid;

class UniqueVisitorService
{
    private Visitor $visitorModel;
    private Event $eventModel;
    private array $config;

    public function __construct()
    {
        $this->visitorModel = new Visitor();
        $this->eventModel = new Event();
        $this->config = require __DIR__ . '/../../config/app.php';
    }

    /**
     * Generate or retrieve visitor UUID with enhanced tracking
     */
    public function getOrCreateVisitorUuid(array $fingerprint = []): string
    {
        try {
            // Try to identify visitor by fingerprint first
            $existingUuid = $this->findVisitorByFingerprint($fingerprint);
            
            if ($existingUuid) {
                return $existingUuid;
            }
            
            // Generate new UUID
            return $this->generateUniqueUuid();
            
        } catch (\Exception $e) {
            error_log("Error creating visitor UUID: " . $e->getMessage());
            return $this->generateUniqueUuid();
        }
    }

    /**
     * Identify visitor using multiple data points
     */
    public function identifyVisitor(array $data): array
    {
        try {
            $visitorUuid = $data['visitor_uuid'] ?? null;
            $fingerprint = $this->generateFingerprint($data);
            
            // If UUID is provided, verify it exists and is valid
            if ($visitorUuid) {
                $existingVisitor = $this->visitorModel->getVisitorByUuid($visitorUuid);
                
                if ($existingVisitor) {
                    // Update fingerprint if needed
                    $this->updateVisitorFingerprint($existingVisitor['id'], $fingerprint);
                    
                    return [
                        'visitor_uuid' => $visitorUuid,
                        'is_new' => false,
                        'visitor_type' => $existingVisitor['visitor_type']
                    ];
                }
            }
            
            // Try to find by fingerprint
            $existingUuid = $this->findVisitorByFingerprint($fingerprint);
            
            if ($existingUuid) {
                return [
                    'visitor_uuid' => $existingUuid,
                    'is_new' => false,
                    'visitor_type' => 'returning'
                ];
            }
            
            // Create new visitor
            $newUuid = $this->generateUniqueUuid();
            
            return [
                'visitor_uuid' => $newUuid,
                'is_new' => true,
                'visitor_type' => 'new'
            ];
            
        } catch (\Exception $e) {
            error_log("Error identifying visitor: " . $e->getMessage());
            
            return [
                'visitor_uuid' => $this->generateUniqueUuid(),
                'is_new' => true,
                'visitor_type' => 'new'
            ];
        }
    }

    /**
     * Generate device/browser fingerprint for visitor identification
     */
    public function generateFingerprint(array $data): string
    {
        $fingerprintData = [
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'accept_language' => $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '',
            'accept_encoding' => $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '',
            'screen_resolution' => $data['screen_resolution'] ?? '',
            'timezone' => $data['timezone'] ?? '',
            'platform' => $data['platform'] ?? '',
            'plugins' => $data['plugins'] ?? '',
            'canvas_fingerprint' => $data['canvas_fingerprint'] ?? ''
        ];
        
        // Create hash from fingerprint data
        $fingerprintString = implode('|', array_filter($fingerprintData));
        return hash('sha256', $fingerprintString);
    }

    /**
     * Find visitor by fingerprint
     */
    private function findVisitorByFingerprint(string $fingerprint): ?string
    {
        try {
            // For this implementation, we'll use a simple approach
            // In a production system, you might want to store fingerprints separately
            
            // Get visitors with similar characteristics from the last 30 days
            $threshold = (time() - (30 * 24 * 60 * 60)) * 1000; // 30 days ago
            
            // This is a simplified approach - in reality, you'd want more sophisticated matching
            return null;
            
        } catch (\Exception $e) {
            error_log("Error finding visitor by fingerprint: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Update visitor fingerprint
     */
    private function updateVisitorFingerprint(int $visitorId, string $fingerprint): void
    {
        // In a full implementation, you'd store fingerprints in a separate table
        // For now, we'll skip this as our current schema doesn't include fingerprints
    }

    /**
     * Generate unique UUID
     */
    private function generateUniqueUuid(): string
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * Deduplicate visitors based on various criteria
     */
    public function deduplicateVisitors(): array
    {
        try {
            $duplicates = $this->findDuplicateVisitors();
            $mergedCount = 0;
            
            foreach ($duplicates as $duplicateGroup) {
                if (count($duplicateGroup) > 1) {
                    $mergedCount += $this->mergeVisitors($duplicateGroup);
                }
            }
            
            return [
                'status' => 'success',
                'merged_visitors' => $mergedCount,
                'message' => "Merged {$mergedCount} duplicate visitors"
            ];
            
        } catch (\Exception $e) {
            error_log("Error deduplicating visitors: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to deduplicate visitors'
            ];
        }
    }

    /**
     * Find potential duplicate visitors
     */
    private function findDuplicateVisitors(): array
    {
        try {
            // Find visitors with same IP and similar user agents within a short time frame
            // This is a simplified approach
            
            $db = \VisitorTracking\Database\Connection::getInstance();
            
            $stmt = $db->prepare("
                SELECT v1.*, v2.id as duplicate_id
                FROM visitors v1
                JOIN visitors v2 ON v1.ip_address = v2.ip_address
                WHERE v1.id < v2.id
                AND ABS(v1.first_visit - v2.first_visit) < 3600000
                AND v1.user_agent = v2.user_agent
                ORDER BY v1.ip_address
            ");
            
            $stmt->execute();
            $results = $stmt->fetchAll();
            
            // Group duplicates
            $groups = [];
            foreach ($results as $result) {
                $key = $result['ip_address'] . '_' . $result['user_agent'];
                if (!isset($groups[$key])) {
                    $groups[$key] = [];
                }
                $groups[$key][] = $result;
            }
            
            return array_values($groups);
            
        } catch (\Exception $e) {
            error_log("Error finding duplicate visitors: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Merge duplicate visitors
     */
    private function mergeVisitors(array $visitors): int
    {
        try {
            if (count($visitors) < 2) {
                return 0;
            }
            
            $db = \VisitorTracking\Database\Connection::getInstance();
            $db->beginTransaction();
            
            // Keep the first visitor as the primary
            $primaryVisitor = $visitors[0];
            $duplicateIds = [];
            
            for ($i = 1; $i < count($visitors); $i++) {
                $duplicateIds[] = $visitors[$i]['duplicate_id'];
            }
            
            // Update events to point to primary visitor
            $placeholders = str_repeat('?,', count($duplicateIds) - 1) . '?';
            $stmt = $db->prepare("
                UPDATE events 
                SET visitor_id = ? 
                WHERE visitor_id IN ({$placeholders})
            ");
            
            $params = array_merge([$primaryVisitor['id']], $duplicateIds);
            $stmt->execute($params);
            
            // Update primary visitor's visit count and last visit
            $stmt = $db->prepare("
                UPDATE visitors 
                SET visit_count = visit_count + ?, 
                    last_visit = CASE 
                        WHEN last_visit < ? THEN ? 
                        ELSE last_visit 
                    END
                WHERE id = ?
            ");
            
            $additionalVisits = count($duplicateIds);
            $latestVisit = max(array_column($visitors, 'last_visit'));
            
            $stmt->execute([
                $additionalVisits,
                $latestVisit,
                $latestVisit,
                $primaryVisitor['id']
            ]);
            
            // Delete duplicate visitors
            $stmt = $db->prepare("
                DELETE FROM visitors 
                WHERE id IN ({$placeholders})
            ");
            $stmt->execute($duplicateIds);
            
            $db->commit();
            
            return count($duplicateIds);
            
        } catch (\Exception $e) {
            $db->rollBack();
            error_log("Error merging visitors: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get visitor session information
     */
    public function getVisitorSession(string $visitorUuid): array
    {
        try {
            $visitor = $this->visitorModel->getVisitorByUuid($visitorUuid);
            
            if (!$visitor) {
                return [
                    'status' => 'error',
                    'message' => 'Visitor not found'
                ];
            }
            
            // Get visitor's events
            $events = $this->eventModel->getEventsByVisitor($visitor['id'], 100);
            
            // Get session statistics
            $sessionStats = $this->calculateSessionStats($events);
            
            return [
                'status' => 'success',
                'visitor' => $visitor,
                'events' => $events,
                'session_stats' => $sessionStats
            ];
            
        } catch (\Exception $e) {
            error_log("Error getting visitor session: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve visitor session'
            ];
        }
    }

    /**
     * Calculate session statistics for a visitor
     */
    private function calculateSessionStats(array $events): array
    {
        if (empty($events)) {
            return [
                'total_events' => 0,
                'total_pageviews' => 0,
                'session_duration' => 0,
                'pages_visited' => 0
            ];
        }
        
        $pageviews = array_filter($events, fn($event) => $event['event_type'] === 'pageview');
        $uniquePages = array_unique(array_column($pageviews, 'page_url'));
        
        $timestamps = array_column($events, 'timestamp');
        $sessionDuration = (max($timestamps) - min($timestamps)) / 1000; // Convert to seconds
        
        return [
            'total_events' => count($events),
            'total_pageviews' => count($pageviews),
            'session_duration' => round($sessionDuration, 2),
            'pages_visited' => count($uniquePages)
        ];
    }

    /**
     * Get unique visitor trends
     */
    public function getUniqueVisitorTrends(string $period = 'daily', int $days = 30): array
    {
        try {
            $analyticsService = new AnalyticsService();
            $startDate = date('Y-m-d', strtotime("-{$days} days"));
            $endDate = date('Y-m-d');
            
            $data = $analyticsService->getAnalyticsData([
                'period' => $period,
                'start_date' => $startDate,
                'end_date' => $endDate
            ]);
            
            if ($data['status'] === 'success') {
                $visitorData = $data['data']['visitor_data'];
                
                // Calculate trends
                $trends = [];
                for ($i = 1; $i < count($visitorData); $i++) {
                    $current = $visitorData[$i]['total'];
                    $previous = $visitorData[$i - 1]['total'];
                    $change = $previous > 0 ? (($current - $previous) / $previous) * 100 : 0;
                    
                    $trends[] = [
                        'date' => $visitorData[$i]['date'],
                        'visitors' => $current,
                        'change_percent' => round($change, 2)
                    ];
                }
                
                return [
                    'status' => 'success',
                    'trends' => $trends
                ];
            }
            
            return $data;
            
        } catch (\Exception $e) {
            error_log("Error getting visitor trends: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve visitor trends'
            ];
        }
    }

    /**
     * Validate visitor UUID format
     */
    public function isValidVisitorUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Get visitor attribution data
     */
    public function getVisitorAttribution(string $visitorUuid): array
    {
        try {
            $visitor = $this->visitorModel->getVisitorByUuid($visitorUuid);
            
            if (!$visitor) {
                return [
                    'status' => 'error',
                    'message' => 'Visitor not found'
                ];
            }
            
            // Get first pageview event to determine attribution
            $events = $this->eventModel->getEventsByVisitor($visitor['id'], 1000);
            $firstPageview = null;
            
            foreach ($events as $event) {
                if ($event['event_type'] === 'pageview') {
                    if (!$firstPageview || $event['timestamp'] < $firstPageview['timestamp']) {
                        $firstPageview = $event;
                    }
                }
            }
            
            $attribution = [
                'first_visit' => $visitor['first_visit'],
                'landing_page' => $firstPageview['page_url'] ?? null,
                'referrer' => $firstPageview['referrer'] ?? 'Direct',
                'utm_source' => null,
                'utm_medium' => null,
                'utm_campaign' => null
            ];
            
            // Parse UTM parameters from landing page
            if ($firstPageview && $firstPageview['page_url']) {
                $urlParts = parse_url($firstPageview['page_url']);
                if (isset($urlParts['query'])) {
                    parse_str($urlParts['query'], $queryParams);
                    
                    $attribution['utm_source'] = $queryParams['utm_source'] ?? null;
                    $attribution['utm_medium'] = $queryParams['utm_medium'] ?? null;
                    $attribution['utm_campaign'] = $queryParams['utm_campaign'] ?? null;
                }
            }
            
            return [
                'status' => 'success',
                'attribution' => $attribution
            ];
            
        } catch (\Exception $e) {
            error_log("Error getting visitor attribution: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to retrieve visitor attribution'
            ];
        }
    }
}