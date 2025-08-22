<?php

namespace VisitorTracking\Services;

use VisitorTracking\Models\Visitor;
use VisitorTracking\Models\Event;
use Ramsey\Uuid\Uuid;

class TrackingService
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
     * Process tracking data from the JavaScript tracker
     */
    public function processTrackingData(array $data): array
    {
        try {
            // Validate tracking data
            $validationErrors = $this->validateTrackingData($data);
            if (!empty($validationErrors)) {
                return [
                    'status' => 'error',
                    'message' => 'Validation failed',
                    'errors' => $validationErrors
                ];
            }

            // Extract visitor information
            $visitorUuid = $data['visitor_uuid'];
            $ipAddress = $this->getClientIpAddress();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown';
            $timestamp = (int)$data['timestamp'];

            // Create or get visitor
            $visitor = $this->visitorModel->createOrGetVisitor(
                $visitorUuid,
                $ipAddress,
                $userAgent,
                $timestamp
            );

            // Generate session ID if not provided
            $sessionId = $data['session_id'] ?? $this->generateSessionId($visitorUuid, $timestamp);

            // Prepare event data
            $eventData = [
                'visitor_id' => $visitor['id'],
                'event_type' => $data['event_type'],
                'page_url' => $data['page_url'] ?? null,
                'page_title' => $data['page_title'] ?? null,
                'referrer' => $data['referrer'] ?? null,
                'event_data' => !empty($data['event_data']) ? json_encode($data['event_data']) : null,
                'session_id' => $sessionId,
                'timestamp' => $timestamp
            ];

            // Validate event data
            $eventValidationErrors = $this->eventModel->validateEventData($eventData);
            if (!empty($eventValidationErrors)) {
                return [
                    'status' => 'error',
                    'message' => 'Event validation failed',
                    'errors' => $eventValidationErrors
                ];
            }

            // Create event
            $eventId = $this->eventModel->createEvent($eventData);

            return [
                'status' => 'success',
                'visitor_id' => $visitor['id'],
                'visitor_uuid' => $visitor['visitor_uuid'],
                'visitor_type' => $visitor['visitor_type'],
                'session_id' => $sessionId,
                'event_id' => $eventId,
                'message' => 'Event tracked successfully'
            ];

        } catch (\Exception $e) {
            error_log("Tracking error: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Internal server error',
                'error_code' => 'TRACKING_ERROR'
            ];
        }
    }

    /**
     * Generate visitor UUID if not provided
     */
    public function generateVisitorUuid(): string
    {
        return Uuid::uuid4()->toString();
    }

    /**
     * Generate session ID
     */
    public function generateSessionId(string $visitorUuid, int $timestamp): string
    {
        return md5($visitorUuid . floor($timestamp / ($this->config['visitor_session_duration'] * 1000)));
    }

    /**
     * Validate tracking data
     */
    private function validateTrackingData(array $data): array
    {
        $errors = [];

        // Required fields
        if (empty($data['visitor_uuid'])) {
            $errors[] = 'visitor_uuid is required';
        } elseif (!$this->isValidUuid($data['visitor_uuid'])) {
            $errors[] = 'visitor_uuid must be a valid UUID';
        }

        if (empty($data['event_type'])) {
            $errors[] = 'event_type is required';
        }

        if (empty($data['timestamp'])) {
            $errors[] = 'timestamp is required';
        } elseif (!is_numeric($data['timestamp'])) {
            $errors[] = 'timestamp must be a number';
        }

        // Validate event type
        $validEventTypes = ['pageview', 'click', 'scroll', 'custom'];
        if (!empty($data['event_type']) && !in_array($data['event_type'], $validEventTypes)) {
            $errors[] = 'Invalid event_type. Must be one of: ' . implode(', ', $validEventTypes);
        }

        // Validate URLs if provided
        if (!empty($data['page_url']) && !filter_var($data['page_url'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid page_url format';
        }

        if (!empty($data['referrer']) && !filter_var($data['referrer'], FILTER_VALIDATE_URL)) {
            $errors[] = 'Invalid referrer format';
        }

        // Validate timestamp range
        if (!empty($data['timestamp'])) {
            $timestamp = (int)$data['timestamp'];
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

    /**
     * Validate UUID format
     */
    private function isValidUuid(string $uuid): bool
    {
        return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
    }

    /**
     * Get client IP address
     */
    private function getClientIpAddress(): string
    {
        $ipKeys = [
            'HTTP_CF_CONNECTING_IP',     // CloudFlare
            'HTTP_CLIENT_IP',            // Proxy
            'HTTP_X_FORWARDED_FOR',      // Load Balancer/Proxy
            'HTTP_X_FORWARDED',          // Proxy
            'HTTP_X_CLUSTER_CLIENT_IP',  // Cluster
            'HTTP_FORWARDED_FOR',        // Proxy
            'HTTP_FORWARDED',            // Proxy
            'REMOTE_ADDR'                // Standard
        ];

        foreach ($ipKeys as $key) {
            if (!empty($_SERVER[$key])) {
                $ips = explode(',', $_SERVER[$key]);
                $ip = trim($ips[0]);
                
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }

        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    /**
     * Check if request is from a bot based on various signals
     */
    public function isBotRequest(): bool
    {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Check user agent patterns
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

        // Check for missing typical browser headers
        $browserHeaders = [
            'HTTP_ACCEPT',
            'HTTP_ACCEPT_LANGUAGE',
            'HTTP_ACCEPT_ENCODING'
        ];

        $missingHeaders = 0;
        foreach ($browserHeaders as $header) {
            if (empty($_SERVER[$header])) {
                $missingHeaders++;
            }
        }

        // If more than half of typical browser headers are missing, likely a bot
        if ($missingHeaders > count($browserHeaders) / 2) {
            return true;
        }

        return false;
    }

    /**
     * Rate limiting check
     */
    public function checkRateLimit(string $identifier): bool
    {
        $cacheKey = 'rate_limit_' . md5($identifier);
        $rateLimitFile = sys_get_temp_dir() . '/' . $cacheKey;
        
        $maxRequests = $this->config['api_rate_limit'];
        $timeWindow = 3600; // 1 hour in seconds
        
        $currentTime = time();
        
        if (file_exists($rateLimitFile)) {
            $data = json_decode(file_get_contents($rateLimitFile), true);
            
            // Reset if time window has passed
            if ($currentTime - $data['window_start'] >= $timeWindow) {
                $data = ['count' => 0, 'window_start' => $currentTime];
            }
        } else {
            $data = ['count' => 0, 'window_start' => $currentTime];
        }
        
        $data['count']++;
        
        // Save updated data
        file_put_contents($rateLimitFile, json_encode($data));
        
        return $data['count'] <= $maxRequests;
    }

    /**
     * Get tracking statistics
     */
    public function getTrackingStats(): array
    {
        try {
            $visitorStats = $this->visitorModel->getVisitorStats();
            $eventStats = $this->eventModel->getEventStats();
            
            return array_merge($visitorStats, $eventStats);
            
        } catch (\Exception $e) {
            error_log("Error getting tracking stats: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Clean up old data
     */
    public function cleanupOldData(): array
    {
        try {
            $days = $this->config['cleanup_old_events_days'];
            $deletedEvents = $this->eventModel->cleanupOldEvents($days);
            
            return [
                'status' => 'success',
                'deleted_events' => $deletedEvents,
                'message' => "Cleaned up {$deletedEvents} old events"
            ];
            
        } catch (\Exception $e) {
            error_log("Error cleaning up old data: " . $e->getMessage());
            
            return [
                'status' => 'error',
                'message' => 'Failed to cleanup old data'
            ];
        }
    }
}