<?php

return [
    'app_name' => 'Visitor Tracking Dashboard',
    'timezone' => 'UTC',
    'server_host' => 'localhost',
    'server_port' => 8000,
    'api_rate_limit' => 1000, // requests per hour
    'session_timeout' => 1800, // 30 minutes
    'cors_origins' => ['*'], // Configure for production
    'tracking_script_version' => '1.0.0',
    'enable_bot_detection' => true,
    'visitor_session_duration' => 1800, // 30 minutes in seconds
    'cleanup_old_events_days' => 90, // Keep events for 90 days
    'real_time_update_interval' => 5000, // 5 seconds in milliseconds
    'chart_data_points_limit' => 100,
    'debug_mode' => true, // Set to false in production
];