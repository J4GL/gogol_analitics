<?php

require_once __DIR__ . '/BaseController.php';

class EventStreamController extends BaseController {
    private static $clients = [];
    
    public function stream() {
        // Set headers for Server-Sent Events
        header('Content-Type: text/event-stream');
        header('Cache-Control: no-cache');
        header('Connection: keep-alive');
        header('Access-Control-Allow-Origin: *');
        header('Access-Control-Allow-Headers: Cache-Control');

        // Disable output buffering
        if (ob_get_level()) {
            ob_end_clean();
        }

        // For development server, limit execution time to avoid timeouts
        set_time_limit(60); // 60 seconds max
        ignore_user_abort(false);

        // Generate unique client ID
        $clientId = uniqid();
        self::$clients[$clientId] = true;

        // Send initial connection message
        $this->sendEvent('connected', ['message' => 'Connected to live events']);

        // Removed mock/demo data - only show real events

        // Keep connection alive for a limited time
        $startTime = time();
        $maxDuration = 50; // Maximum 50 seconds
        $lastHeartbeat = time();
        $eventFile = sys_get_temp_dir() . '/analytics_events.json';
        $lastFileSize = file_exists($eventFile) ? filesize($eventFile) : 0;

        while (connection_status() == CONNECTION_NORMAL &&
               !connection_aborted() &&
               (time() - $startTime) < $maxDuration) {

            // Check for new events in the file
            if (file_exists($eventFile)) {
                $currentFileSize = filesize($eventFile);
                if ($currentFileSize > $lastFileSize) {
                    // Read new events
                    $handle = fopen($eventFile, 'r');
                    if ($handle) {
                        fseek($handle, $lastFileSize);
                        while (($line = fgets($handle)) !== false) {
                            $event = json_decode(trim($line), true);
                            if ($event) {
                                $this->sendEvent('event', $event);
                            }
                        }
                        fclose($handle);
                        $lastFileSize = $currentFileSize;
                    }
                }
            }

            // Send heartbeat every 15 seconds
            if (time() - $lastHeartbeat >= 15) {
                $this->sendEvent('heartbeat', ['timestamp' => time()]);
                $lastHeartbeat = time();
            }

            usleep(1000000); // Sleep for 1 second
        }

        // Send closing message
        $this->sendEvent('close', ['message' => 'Connection closing']);

        // Clean up when client disconnects
        unset(self::$clients[$clientId]);
    }
    
    private function sendEvent($type, $data) {
        echo "event: $type\n";
        echo "data: " . json_encode($data) . "\n\n";
        
        if (ob_get_level()) {
            ob_flush();
        }
        flush();
    }
    
    public static function pushEvent($eventData) {
        // This method would be called from ApiController when new events are received
        // For simplicity, we'll implement a basic file-based approach
        
        $eventFile = sys_get_temp_dir() . '/analytics_events.json';
        
        // Prepare event data for streaming
        $streamEvent = [
            'id' => $eventData['id'],
            'timestamp' => gmdate('Y-m-d H:i:s', intval($eventData['ts_ns'] / 1000000000)),
            'page' => $eventData['page'],
            'referrer' => $eventData['referrer'],
            'country' => $eventData['country'],
            'os' => $eventData['os'],
            'browser' => $eventData['browser'],
            'resolution' => $eventData['resolution'],
            'is_bot' => (bool)$eventData['is_bot'],
            'event_type' => 'page_load'
        ];
        
        // Write to temporary file (in production, use Redis or similar)
        file_put_contents($eventFile, json_encode($streamEvent) . "\n", FILE_APPEND | LOCK_EX);
    }
}
