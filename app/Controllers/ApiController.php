<?php

require_once __DIR__ . '/BaseController.php';

class ApiController extends BaseController {
    
    public function collect() {
        // Set CORS headers to allow cross-origin requests
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->response('', 200);
        }

        // Get and validate data parameter
        $dataB64 = $this->getInput('data');
        if (empty($dataB64)) {
            $this->response('', 400);
        }
        
        // Check payload size limit
        if (strlen($dataB64) > 4000) {
            $this->response('', 413); // Payload too large
        }
        
        // Decode base64
        $raw = base64_decode($dataB64, true);
        if ($raw === false) {
            $this->response('', 400);
        }

        // Decode JSON
        $json = json_decode($raw, true);
        if (!$json) {
            $this->response('', 400);
        }
        
        // Process the event
        try {
            $this->processEvent($json, $raw);
            $this->response('', 204); // No content
        } catch (Exception $e) {
            error_log("Event processing error: " . $e->getMessage());
            $this->response('', 500);
        }
    }
    
    public function collectGif() {
        // Set CORS headers to allow cross-origin requests
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
        header('Access-Control-Allow-Headers: Content-Type');

        // Handle preflight requests
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            $this->response('', 200);
        }

        // Process same as collect but return 1x1 GIF
        $dataB64 = $this->getInput('data');
        if (!empty($dataB64) && strlen($dataB64) <= 4000) {
            $raw = base64_decode($dataB64, true);
            if ($raw !== false) {
                $json = json_decode($raw, true);
                if ($json) {
                    try {
                        $this->processEvent($json, $raw);
                    } catch (Exception $e) {
                        error_log("GIF event processing error: " . $e->getMessage());
                    }
                }
            }
        }
        
        // Return 1x1 transparent GIF
        $gif = base64_decode('R0lGODlhAQABAIAAAAAAAP///yH5BAEAAAAALAAAAAABAAEAAAIBRAA7');
        $this->response($gif, 200, [
            'Content-Type' => 'image/gif',
            'Cache-Control' => 'no-cache, no-store, must-revalidate',
            'Pragma' => 'no-cache',
            'Expires' => '0'
        ]);
    }
    
    public function stats() {
        $hours = (int)($this->getInput('hours', 24));
        $hours = max(1, min(168, $hours)); // Limit between 1 and 168 hours (7 days)

        try {
            $stats = $this->getHourlyStats($hours);
            $this->json($stats);
        } catch (Exception $e) {
            error_log("Stats error: " . $e->getMessage());
            $this->json(['error' => 'Failed to fetch stats'], 500);
        }
    }

    public function events() {
        $limit = (int)($this->getInput('limit', 20));
        $limit = max(1, min(100, $limit)); // Limit between 1 and 100 events

        // Only show events from the last 30 seconds (since events auto-remove after 7 seconds)
        $thirtySecondsAgo = (time() - 30) * 1000000000; // 30 seconds in nanoseconds

        try {
            // Include all events (bots and visitors)
            $stmt = $this->db->query(
                "SELECT id, ts_ns, page, referrer, country, os, browser, is_bot, raw_payload, page_load_ms
                 FROM events
                 WHERE ts_ns >= ?
                 ORDER BY ts_ns DESC
                 LIMIT ?",
                [$thirtySecondsAgo, $limit]
            );

            $events = $stmt->fetchAll();

            // Format events for frontend
            $formattedEvents = [];
            foreach ($events as $event) {
                // Extract event type from raw payload
                $eventType = 'page_view'; // default
                if (!empty($event['raw_payload'])) {
                    $payload = json_decode($event['raw_payload'], true);
                    if ($payload && isset($payload['event_type'])) {
                        $eventType = $payload['event_type'];
                    }
                }

                $formattedEvents[] = [
                    'id' => $event['id'],
                    'timestamp' => intval($event['ts_ns'] / 1000000000), // Send raw Unix timestamp for client-side formatting
                    'page' => $event['page'] ?: '/',
                    'referrer' => $event['referrer'],
                    'country' => $event['country'],
                    'os' => $event['os'],
                    'browser' => $event['browser'],
                    'is_bot' => (bool)$event['is_bot'],
                    'event_type' => $eventType,
                    'page_load_ms' => $event['page_load_ms'] ? intval($event['page_load_ms']) : null
                ];
            }

            $this->json($formattedEvents);
        } catch (Exception $e) {
            error_log("Events error: " . $e->getMessage());
            $this->json(['error' => 'Failed to fetch events'], 500);
        }
    }
    
    private function processEvent($json, $raw) {
        $remoteIP = $_SERVER['REMOTE_ADDR'] ?? '';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? ($json['ua'] ?? '');
        
        // Generate ID
        $id = md5($remoteIP . '|' . $userAgent);
        
        // Server timestamp (current Unix timestamp in nanoseconds)
        $tsNs = time() * 1000000000;
        
        // Client timestamp
        $clientTsNs = isset($json['client_ts_ns']) ? (int)$json['client_ts_ns'] : null;
        
        // Bot detection - mark bots but don't reject them
        $isBot = $this->detectBot($userAgent, $json['webdriver'] ?? false);

        // Map timezone to country
        $timezone = $json['timezone'] ?? null;
        $country = $this->mapTimezoneToCountry($timezone);
        
        // Prepare event data
        $eventData = [
            'id' => $id,
            'ts_ns' => $tsNs,
            'client_ts_ns' => $clientTsNs,
            'page' => $this->sanitizeString($json['page'] ?? '', 500),
            'referrer' => $this->sanitizeString($json['referrer'] ?? '', 500),
            'timezone' => $this->sanitizeString($timezone, 50),
            'country' => $country,
            'os' => $this->sanitizeString($json['os'] ?? '', 50),
            'browser' => $this->sanitizeString($json['browser'] ?? '', 50),
            'resolution' => $this->sanitizeString($json['resolution'] ?? '', 20),
            'page_load_ms' => isset($json['page_load_ms']) ? (int)$json['page_load_ms'] : null,
            'is_bot' => $isBot ? 1 : 0,
            'user_agent' => $this->sanitizeString($userAgent, 512),
            'raw_payload' => strlen($raw) <= 1000 ? $raw : null
        ];
        
        // Insert into database
        $this->db->insert('events', $eventData);

        // SSE disabled for performance - EventStreamController::pushEvent($eventData);
    }
    
    private function detectBot($userAgent, $webdriver = false) {
        if ($webdriver === true) {
            return true;
        }

        $userAgent = strtolower($userAgent);

        // Bot indicators
        $botIndicators = [
            'http://', 'https://', 'curl', 'wget', 'bot', 'spider', 'crawler',
            'test', 'debug', 'manual', 'postman', 'insomnia', 'python',
            'java', 'php', 'ruby', 'perl', 'go-http', 'node', 'axios'
        ];

        foreach ($botIndicators as $indicator) {
            if (stripos($userAgent, $indicator) !== false) {
                return true;
            }
        }

        // Must contain browser indicators for legitimate traffic
        $browserIndicators = ['mozilla', 'webkit', 'chrome', 'safari', 'firefox', 'edge'];
        $hasBrowserIndicator = false;

        foreach ($browserIndicators as $indicator) {
            if (stripos($userAgent, $indicator) !== false) {
                $hasBrowserIndicator = true;
                break;
            }
        }

        // If no browser indicators, likely a bot
        if (!$hasBrowserIndicator) {
            return true;
        }

        return false;
    }

    private function isObviousBot($userAgent) {
        $userAgent = strtolower($userAgent);

        // Only reject very obvious bots/tools
        $obviousBots = [
            'curl', 'wget', 'python', 'java', 'php', 'ruby', 'perl',
            'postman', 'insomnia', 'http://', 'https://', 'test', 'debug'
        ];

        foreach ($obviousBots as $bot) {
            if (stripos($userAgent, $bot) !== false) {
                return true;
            }
        }

        return false;
    }
    
    private function mapTimezoneToCountry($timezone) {
        if (empty($timezone)) {
            return 'unknown';
        }
        
        $mapPath = __DIR__ . '/../../config/timezone_country_map.json';
        if (!file_exists($mapPath)) {
            return 'unknown';
        }
        
        $map = json_decode(file_get_contents($mapPath), true);
        return $map[$timezone] ?? 'unknown';
    }
    
    private function sanitizeString($str, $maxLength) {
        if (empty($str)) {
            return null;
        }
        
        return substr(trim($str), 0, $maxLength);
    }
    
    private function getHourlyStats($hours) {
        // Simple cache to avoid recalculating frequently
        static $lastCalculation = 0;
        static $cachedResult = null;

        if (time() - $lastCalculation < 60 && $cachedResult !== null) {
            return $cachedResult;
        }

        // Calculate time range
        $endTime = time();
        $startTime = $endTime - ($hours * 3600);
        $startTimeNs = $startTime * 1000000000;

        // Simplified query - just get recent events, limit to 1000 for performance
        $stmt = $this->db->query(
            "SELECT id, ts_ns, is_bot FROM events WHERE ts_ns >= ? ORDER BY ts_ns DESC LIMIT 1000",
            [$startTimeNs]
        );

        $events = $stmt->fetchAll();
        
        // Build firstSeen map
        $firstSeen = [];
        foreach ($events as $event) {
            $id = $event['id'];
            if (!isset($firstSeen[$id])) {
                $firstSeen[$id] = $event['ts_ns'];
            }
        }
        
        // Initialize hourly buckets
        $buckets = [];
        for ($i = 0; $i < $hours; $i++) {
            $hourStart = $endTime - (($hours - $i - 1) * 3600);
            $hourStartNs = $hourStart * 1000000000;
            $hourEndNs = $hourStartNs + (3600 * 1000000000);
            
            $buckets[] = [
                'hour' => gmdate('Y-m-d\TH:00:00\Z', $hourStart),
                'hour_start_ns' => $hourStartNs,
                'hour_end_ns' => $hourEndNs,
                'bots' => [],
                'unique' => [],
                'returning' => []
            ];
        }
        
        // Process events into buckets
        foreach ($events as $event) {
            $eventTime = $event['ts_ns'];
            $id = $event['id'];
            $isBot = (bool)$event['is_bot'];
            
            // Skip events outside our main time range
            if ($eventTime < $startTimeNs) {
                continue;
            }
            
            // Find the appropriate bucket
            foreach ($buckets as &$bucket) {
                if ($eventTime >= $bucket['hour_start_ns'] && $eventTime < $bucket['hour_end_ns']) {
                    if ($isBot) {
                        $bucket['bots'][$id] = true;
                    } else {
                        // Check if this is first time seeing this ID in this hour
                        $firstSeenTime = $firstSeen[$id];
                        if ($firstSeenTime >= $bucket['hour_start_ns'] && $firstSeenTime < $bucket['hour_end_ns']) {
                            $bucket['unique'][$id] = true;
                        } else {
                            $bucket['returning'][$id] = true;
                        }
                    }
                    break;
                }
            }
        }
        
        // Convert to final format
        $result = [];
        foreach ($buckets as $bucket) {
            $result[] = [
                'hour' => $bucket['hour'],
                'bots' => count($bucket['bots']),
                'unique' => count($bucket['unique']),
                'returning' => count($bucket['returning'])
            ];
        }

        // Cache the result
        $lastCalculation = time();
        $cachedResult = $result;

        return $result;
    }
}
