<?php
// Simple router for single PHP server setup
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Handle fake_website routes
if (strpos($request_uri, '/fake_website') === 0) {
    // Remove /fake_website prefix
    $file_path = substr($request_uri, 13);
    
    // Default to index.html if no specific file requested
    if ($file_path === '' || $file_path === '/') {
        $file_path = '/index.html';
    }
    
    // Map to fake_website directory
    $full_path = __DIR__ . '/fake_website' . $file_path;
    
    // Serve file if it exists
    if (file_exists($full_path) && is_file($full_path)) {
        $mime_type = getMimeType($full_path);
        header("Content-Type: $mime_type");
        readfile($full_path);
        exit;
    } else {
        http_response_code(404);
        echo "Test website file not found: $file_path";
        exit;
    }
}

// Handle test website routes (legacy support)
if (strpos($request_uri, '/test') === 0) {
    // Remove /test prefix
    $file_path = substr($request_uri, 5);
    
    // Default to index.html if no specific file requested
    if ($file_path === '' || $file_path === '/') {
        $file_path = '/index.html';
    }
    
    // Map to fake_website directory
    $full_path = __DIR__ . '/fake_website' . $file_path;
    
    // Serve file if it exists
    if (file_exists($full_path) && is_file($full_path)) {
        $mime_type = getMimeType($full_path);
        header("Content-Type: $mime_type");
        readfile($full_path);
        exit;
    } else {
        http_response_code(404);
        echo "Test website file not found: $file_path";
        exit;
    }
}

// Handle API routes
if (strpos($request_uri, '/api/') === 0) {
    // Remove query string if present
    $clean_path = strtok($request_uri, '?');
    
    // Map API routes to actual PHP files
    $api_mapping = [
        '/api/collect' => '/collect.php',
        '/api/collect.php' => '/collect.php',
        '/api/dashboard' => '/dashboard.php',
        '/api/dashboard.php' => '/dashboard.php',
        '/api/config' => '/config.php',
        '/api/config.php' => '/config.php'
    ];
    
    if (isset($api_mapping[$clean_path])) {
        $api_file = __DIR__ . $api_mapping[$clean_path];
        if (file_exists($api_file)) {
            include $api_file;
            exit;
        }
    }
    
    // Fallback: try direct path with .php extension
    $api_file = __DIR__ . str_replace('/api/', '/', $clean_path) . '.php';
    if (file_exists($api_file)) {
        include $api_file;
        exit;
    }
    
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'API endpoint not found: ' . $clean_path]);
    exit;
}

// Handle direct PHP file access (for backwards compatibility)
if ($request_uri === '/collect.php') {
    include __DIR__ . '/collect.php';
    exit;
}
if ($request_uri === '/dashboard.php') {
    include __DIR__ . '/dashboard.php';
    exit;
}

// Handle dashboard.html
if ($request_uri === '/dashboard.html' || $request_uri === '/dashboard') {
    $dashboard_file = __DIR__ . '/dashboard.html';
    if (file_exists($dashboard_file)) {
        header('Content-Type: text/html');
        readfile($dashboard_file);
        exit;
    }
}

// Handle asset files (CSS, JS, images)
if (strpos($request_uri, '/assets/') === 0) {
    $asset_file = __DIR__ . $request_uri;
    if (file_exists($asset_file) && is_file($asset_file)) {
        $mime_type = getMimeType($asset_file);
        header("Content-Type: $mime_type");
        readfile($asset_file);
        exit;
    }
}

// Default to dashboard (index.php)
if ($request_uri === '/' || $request_uri === '/index.php' || $request_uri === '') {
    include __DIR__ . '/index.php';
    exit;
}

// 404 for everything else
http_response_code(404);
echo "Page not found: $request_uri";

function getMimeType($file_path) {
    $extension = strtolower(pathinfo($file_path, PATHINFO_EXTENSION));
    
    $mime_types = [
        'html' => 'text/html',
        'css' => 'text/css',
        'js' => 'application/javascript',
        'json' => 'application/json',
        'png' => 'image/png',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'php' => 'text/html'
    ];
    
    return $mime_types[$extension] ?? 'text/plain';
}
?>