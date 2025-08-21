<?php

return [
    // Dashboard routes (require IP authorization)
    'GET /' => ['DashboardController', 'traffic', true],
    'GET /dashboard' => ['DashboardController', 'traffic', true],
    'GET /settings' => ['DashboardController', 'settings', true],
    
    // API routes (public)
    'GET /api/collect' => ['ApiController', 'collect', false],
    'POST /api/collect' => ['ApiController', 'collect', false],
    'GET /api/stats' => ['ApiController', 'stats', false],
    'GET /api/events' => ['ApiController', 'events', false],
    'GET /collect.gif' => ['ApiController', 'collectGif', false],
    
    // SSE route (require IP authorization) - temporarily disabled for performance
    // 'GET /events' => ['EventStreamController', 'stream', true],
    
    // Static files
    'GET /collect.js' => ['StaticController', 'collectJs', false],
];
