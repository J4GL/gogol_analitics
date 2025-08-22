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
use VisitorTracking\Services\UniqueVisitorService;

try {
    $action = $_GET['action'] ?? 'pages';
    
    switch ($action) {
        case 'pages':
            $analyticsService = new AnalyticsService();
            $result = $analyticsService->getPageAnalytics();
            break;
            
        case 'visitor':
            $visitorUuid = $_GET['visitor_uuid'] ?? '';
            
            if (empty($visitorUuid)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'visitor_uuid parameter is required',
                    'error_code' => 'MISSING_VISITOR_UUID'
                ]);
                exit;
            }
            
            $uniqueVisitorService = new UniqueVisitorService();
            
            if (!$uniqueVisitorService->isValidVisitorUuid($visitorUuid)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid visitor UUID format',
                    'error_code' => 'INVALID_VISITOR_UUID'
                ]);
                exit;
            }
            
            $result = $uniqueVisitorService->getVisitorSession($visitorUuid);
            break;
            
        case 'attribution':
            $visitorUuid = $_GET['visitor_uuid'] ?? '';
            
            if (empty($visitorUuid)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'visitor_uuid parameter is required',
                    'error_code' => 'MISSING_VISITOR_UUID'
                ]);
                exit;
            }
            
            $uniqueVisitorService = new UniqueVisitorService();
            
            if (!$uniqueVisitorService->isValidVisitorUuid($visitorUuid)) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Invalid visitor UUID format',
                    'error_code' => 'INVALID_VISITOR_UUID'
                ]);
                exit;
            }
            
            $result = $uniqueVisitorService->getVisitorAttribution($visitorUuid);
            break;
            
        case 'trends':
            $period = $_GET['period'] ?? 'daily';
            $days = (int)($_GET['days'] ?? 30);
            
            if ($days < 1 || $days > 365) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Days parameter must be between 1 and 365',
                    'error_code' => 'INVALID_DAYS'
                ]);
                exit;
            }
            
            $uniqueVisitorService = new UniqueVisitorService();
            $result = $uniqueVisitorService->getUniqueVisitorTrends($period, $days);
            break;
            
        case 'trending':
            $analyticsService = new AnalyticsService();
            $result = $analyticsService->getTrendingPages();
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'status' => 'error',
                'message' => 'Invalid action. Must be one of: pages, visitor, attribution, trends, trending',
                'error_code' => 'INVALID_ACTION'
            ]);
            exit;
    }
    
    // Set appropriate HTTP status code
    if ($result['status'] === 'success') {
        http_response_code(200);
    } else {
        // Determine error type
        if (strpos($result['message'], 'not found') !== false) {
            http_response_code(404);
        } else {
            http_response_code(500);
        }
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Data API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error_code' => 'INTERNAL_ERROR'
    ]);
}
?>