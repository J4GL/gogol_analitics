<?php

require_once __DIR__ . '/BaseController.php';

class StaticController extends BaseController {
    
    public function collectJs() {
        $jsPath = __DIR__ . '/../Views/collect.js';
        
        if (!file_exists($jsPath)) {
            $this->response('// collect.js not found', 404, ['Content-Type' => 'application/javascript']);
        }
        
        $content = file_get_contents($jsPath);
        
        // Replace DOMAIN placeholder with actual domain from .env
        $domain = $_ENV['DOMAIN'] ?? 'http://localhost:8000';

        // Replace the exact string including quotes
        $content = str_replace("'https://domaine.com'", "'$domain'", $content);
        
        $this->response($content, 200, [
            'Content-Type' => 'application/javascript',
            'Cache-Control' => 'public, max-age=3600',
            'Access-Control-Allow-Origin' => '*'
        ]);
    }
}
