<?php

require_once __DIR__ . '/../../vendor/autoload.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'status' => 'error',
        'message' => 'Method not allowed'
    ]);
    exit;
}

use VisitorTracking\Services\AnalyticsService;

try {
    $analyticsService = new AnalyticsService();
    
    // Get query parameters
    $minutes = (int)($_GET['minutes'] ?? 5);
    $type = $_GET['type'] ?? 'feed'; // 'feed', 'stats', or 'both'
    
    // Validate minutes parameter
    if ($minutes < 1 || $minutes > 60) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Minutes parameter must be between 1 and 60',
            'error_code' => 'INVALID_MINUTES'
        ]);
        exit;
    }
    
    // Validate type parameter
    $validTypes = ['feed', 'stats', 'both'];
    if (!in_array($type, $validTypes)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid type. Must be one of: ' . implode(', ', $validTypes),
            'error_code' => 'INVALID_TYPE'
        ]);
        exit;
    }
    
    $result = ['status' => 'success'];
    
    // Get live visitor feed
    if ($type === 'feed' || $type === 'both') {
        $feedData = $analyticsService->getLiveVisitorFeed($minutes);
        
        if ($feedData['status'] === 'success') {
            $result['live_visitors'] = $feedData['live_visitors'];
            $result['active_count'] = $feedData['active_count'];
            $result['total_events'] = $feedData['total_events'];
            $result['last_updated'] = $feedData['last_updated'];
        } else {
            $result = $feedData; // Return error from feed service
        }
    }
    
    // Get dashboard stats
    if ($type === 'stats' || $type === 'both') {
        $statsData = $analyticsService->getDashboardStats();
        
        if ($statsData['status'] === 'success') {
            $result['stats'] = $statsData['stats'];
        } else {
            // If feed was successful but stats failed, still return partial success
            if ($result['status'] === 'success' && $type === 'both') {
                $result['stats_error'] = $statsData['message'];
            } else {
                $result = $statsData; // Return error from stats service
            }
        }
    }
    
    // Add metadata
    if ($result['status'] === 'success') {
        $result['query_params'] = [
            'minutes' => $minutes,
            'type' => $type
        ];
        $result['server_time'] = time() * 1000; // Current server time in milliseconds
    }
    
    // Set appropriate HTTP status code
    if ($result['status'] === 'success') {
        http_response_code(200);
    } else {
        http_response_code(500);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Live Visitors API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error_code' => 'INTERNAL_ERROR'
    ]);
}
?>