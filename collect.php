<?php
require_once 'config.php';

try {
    $db = Database::getInstance()->getConnection();
    
    // Handle GET request (fallback for image pixel tracking)
    if ($_SERVER['REQUEST_METHOD'] === 'GET' && !empty($_GET)) {
        $data = $_GET;
    }
    // Handle POST request (preferred method)
    elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = file_get_contents('php://input');
        $data = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid JSON data']);
            exit();
        }
    } else {
        http_response_code(405);
        echo json_encode(['error' => 'Method not allowed']);
        exit();
    }
    
    // Validate required fields
    $required_fields = ['visitor_id', 'timestamp', 'page'];
    foreach ($required_fields as $field) {
        if (empty($data[$field])) {
            http_response_code(400);
            echo json_encode(['error' => "Missing required field: $field"]);
            exit();
        }
    }
    
    // Get real IP address
    function getRealIpAddr() {
        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
            return $_SERVER['HTTP_X_FORWARDED'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_FORWARDED_FOR'];
        } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
            return $_SERVER['HTTP_FORWARDED'];
        } else {
            return $_SERVER['REMOTE_ADDR'];
        }
    }
    
    // Enhanced bot detection
    function detectBot($userAgent) {
        $botPatterns = [
            '/googlebot/i', '/bingbot/i', '/slurp/i', '/duckduckbot/i', '/baiduspider/i',
            '/yandexbot/i', '/sogou/i', '/facebookexternalhit/i', '/twitterbot/i',
            '/linkedinbot/i', '/whatsapp/i', '/telegrambot/i', '/applebot/i',
            '/crawler/i', '/spider/i', '/scraper/i', '/bot/i', '/archiver/i',
            '/curl/i', '/wget/i', '/python/i', '/java/i', '/ruby/i'
        ];
        
        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }
        
        return false;
    }
    
    // Sanitize and prepare data
    $ip_address = getRealIpAddr();
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? $data['user_agent'] ?? '';
    $is_bot = detectBot($user_agent) || ($data['is_bot'] ?? false);
    
    // Check if this is a new session for the visitor
    $session_id = $data['session_id'] ?? null;
    $is_new_session = false;
    
    if ($session_id) {
        // Check if this session already exists
        $session_check = $db->prepare("SELECT 1 FROM sessions WHERE visitor_id = ? AND session_id = ?");
        $session_check->execute([$data['visitor_id'], $session_id]);
        $is_new_session = !$session_check->fetch();
    }
    
    // Create/update visitor record using SQLite UPSERT
    // Only increment total_visits if this is a new session
    if ($is_new_session) {
        $visitor_stmt = $db->prepare("
            INSERT INTO visitors (visitor_id, ip_address, user_agent, country, os, browser, device_type, is_bot, total_visits)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON CONFLICT(visitor_id) DO UPDATE SET
                last_seen = CURRENT_TIMESTAMP,
                total_visits = total_visits + 1,
                country = COALESCE(excluded.country, country),
                os = COALESCE(excluded.os, os),
                browser = COALESCE(excluded.browser, browser),
                device_type = COALESCE(excluded.device_type, device_type),
                is_bot = COALESCE(excluded.is_bot, is_bot)
        ");
    } else {
        // Don't increment total_visits if within same session
        $visitor_stmt = $db->prepare("
            INSERT INTO visitors (visitor_id, ip_address, user_agent, country, os, browser, device_type, is_bot, total_visits)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON CONFLICT(visitor_id) DO UPDATE SET
                last_seen = CURRENT_TIMESTAMP,
                country = COALESCE(excluded.country, country),
                os = COALESCE(excluded.os, os),
                browser = COALESCE(excluded.browser, browser),
                device_type = COALESCE(excluded.device_type, device_type),
                is_bot = COALESCE(excluded.is_bot, is_bot)
        ");
    }
    
    $visitor_stmt->execute([
        $data['visitor_id'],
        $ip_address,
        $user_agent,
        $data['country'] ?? null,
        $data['os'] ?? null,
        $data['browser'] ?? null,
        $data['device_type'] ?? 'PC',
        $is_bot ? 1 : 0  // Convert boolean to integer for SQLite
    ]);
    
    // Insert event record
    $event_stmt = $db->prepare("
        INSERT INTO events (
            visitor_id, timestamp, event_type, page, referrer, country, os, browser, 
            device_type, resolution, timezone, page_load_time, is_bot, user_agent, ip_address,
            element_tag_name, element_id, element_class, element_text, element_type, element_href
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    
    $event_stmt->execute([
        $data['visitor_id'],
        $data['timestamp'],
        $data['event_type'] ?? 'pageview',
        $data['page'],
        $data['referrer'] ?? '',
        $data['country'] ?? null,
        $data['os'] ?? null,
        $data['browser'] ?? null,
        $data['device_type'] ?? 'PC',
        $data['resolution'] ?? null,
        $data['timezone'] ?? null,
        $data['page_load_time'] ?? null,
        $is_bot ? 1 : 0,  // Convert boolean to integer for SQLite
        $user_agent,
        $ip_address,
        $data['tag_name'] ?? null,
        $data['element_id'] ?? null,
        $data['element_class'] ?? null,
        $data['element_text'] ?? null,
        $data['element_type'] ?? null,
        $data['element_href'] ?? null
    ]);
    
    // Update or create session using SQLite UPSERT
    if (!empty($data['session_id'])) {
        $session_stmt = $db->prepare("
            INSERT INTO sessions (visitor_id, session_id, page_views)
            VALUES (?, ?, 1)
            ON CONFLICT(visitor_id, session_id) DO UPDATE SET
                end_time = CURRENT_TIMESTAMP,
                page_views = page_views + 1,
                duration = (strftime('%s', 'now') - strftime('%s', start_time))
        ");
        
        $session_stmt->execute([
            $data['visitor_id'],
            $data['session_id']
        ]);
    }
    
    // Return success response
    $response = [
        'status' => 'success',
        'visitor_id' => $data['visitor_id'],
        'timestamp' => $data['timestamp'],
        'event_type' => $data['event_type'] ?? 'pageview'
    ];
    
    if (($data['event_type'] ?? 'pageview') === 'click') {
        $response['message'] = 'Click event tracked successfully';
    }
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $_ENV['DEBUG'] === 'true' ? $e->getMessage() : 'Please try again later'
    ]);
}
?>