<?php

require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

use VisitorTracking\Services\TrackingService;

try {
    // Get JSON input
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid JSON data',
            'error_code' => 'INVALID_JSON'
        ]);
        exit;
    }
    
    if (empty($data)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'No data provided',
            'error_code' => 'NO_DATA'
        ]);
        exit;
    }
    
    $trackingService = new TrackingService();
    
    // Check rate limiting
    $clientId = $_SERVER['REMOTE_ADDR'] . '_' . ($data['visitor_uuid'] ?? 'unknown');
    if (!$trackingService->checkRateLimit($clientId)) {
        http_response_code(429);
        echo json_encode([
            'status' => 'error',
            'message' => 'Rate limit exceeded',
            'error_code' => 'RATE_LIMIT_EXCEEDED'
        ]);
        exit;
    }
    
    // Check if request is from a bot (optional - still track but mark appropriately)
    $isBot = $trackingService->isBotRequest();
    if ($isBot && !isset($data['visitor_type'])) {
        $data['visitor_type'] = 'bot';
    }
    
    // Add server-side timestamp if not provided or invalid
    if (empty($data['timestamp']) || !is_numeric($data['timestamp'])) {
        $data['timestamp'] = time() * 1000; // Current timestamp in milliseconds
    }
    
    // Process the tracking data
    $result = $trackingService->processTrackingData($data);
    
    // Set appropriate HTTP status code
    if ($result['status'] === 'success') {
        http_response_code(200);
    } else {
        // Determine error type and set appropriate status code
        if (isset($result['errors']) || strpos($result['message'], 'validation') !== false) {
            http_response_code(400); // Bad Request
        } else {
            http_response_code(500); // Internal Server Error
        }
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Track API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error_code' => 'INTERNAL_ERROR'
    ]);
}
?>