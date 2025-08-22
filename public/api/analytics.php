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
    $period = $_GET['period'] ?? 'daily';
    $startDate = $_GET['start_date'] ?? null;
    $endDate = $_GET['end_date'] ?? null;
    $format = $_GET['format'] ?? 'analytics'; // 'analytics' or 'chart'
    
    // Validate period
    $validPeriods = ['hourly', 'daily', 'weekly', 'monthly'];
    if (!in_array($period, $validPeriods)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid period. Must be one of: ' . implode(', ', $validPeriods),
            'error_code' => 'INVALID_PERIOD'
        ]);
        exit;
    }
    
    // Validate dates if provided
    if ($startDate && !strtotime($startDate)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid start_date format. Use YYYY-MM-DD',
            'error_code' => 'INVALID_START_DATE'
        ]);
        exit;
    }
    
    if ($endDate && !strtotime($endDate)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'Invalid end_date format. Use YYYY-MM-DD',
            'error_code' => 'INVALID_END_DATE'
        ]);
        exit;
    }
    
    // Check if start date is before end date
    if ($startDate && $endDate && strtotime($startDate) > strtotime($endDate)) {
        http_response_code(400);
        echo json_encode([
            'status' => 'error',
            'message' => 'start_date cannot be after end_date',
            'error_code' => 'INVALID_DATE_RANGE'
        ]);
        exit;
    }
    
    $params = [
        'period' => $period,
        'start_date' => $startDate,
        'end_date' => $endDate
    ];
    
    // Get data based on format requested
    if ($format === 'chart') {
        $result = $analyticsService->getChartData($params);
    } else {
        $result = $analyticsService->getAnalyticsData($params);
    }
    
    // Set appropriate HTTP status code
    if ($result['status'] === 'success') {
        http_response_code(200);
    } else {
        http_response_code(500);
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log("Analytics API Error: " . $e->getMessage());
    
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => 'Internal server error',
        'error_code' => 'INTERNAL_ERROR'
    ]);
}
?>