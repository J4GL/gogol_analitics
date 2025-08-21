<?php

// Load environment variables
if (file_exists(__DIR__ . '/../.env')) {
    $lines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoload classes
spl_autoload_register(function ($class) {
    $paths = [
        __DIR__ . "/../app/Controllers/$class.php",
        __DIR__ . "/../app/Models/$class.php",
        __DIR__ . "/../app/$class.php"
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
});

// Load routes
$routes = require __DIR__ . '/../config/routes.php';

// Get current request
$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Remove trailing slash except for root
if ($path !== '/' && substr($path, -1) === '/') {
    $path = rtrim($path, '/');
}

$routeKey = "$method $path";

// Find matching route
$matchedRoute = null;
foreach ($routes as $route => $config) {
    if ($route === $routeKey) {
        $matchedRoute = $config;
        break;
    }
}

if (!$matchedRoute) {
    http_response_code(404);
    echo "404 Not Found";
    exit;
}

list($controllerName, $action, $requiresAuth) = $matchedRoute;

// IP Authorization check
if ($requiresAuth) {
    require_once __DIR__ . '/../app/IPAuth.php';
    
    $ipAuth = new IPAuth();
    if (!$ipAuth->isAuthorized()) {
        http_response_code(403);
        echo "403 Forbidden - Unauthorized IP";
        exit;
    }
}

// Instantiate controller and call action
try {
    $controller = new $controllerName();
    $controller->$action();
} catch (Exception $e) {
    error_log("Controller error: " . $e->getMessage());
    http_response_code(500);
    echo "500 Internal Server Error";
}
