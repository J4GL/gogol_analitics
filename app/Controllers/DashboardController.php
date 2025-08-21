<?php

require_once __DIR__ . '/BaseController.php';

class DashboardController extends BaseController {
    
    public function traffic() {
        // Add cache-busting headers
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');

        $data = [
            'title' => 'Traffic Analytics',
            'currentTab' => 'traffic'
        ];

        echo $this->render('traffic', $data);
    }
    
    public function settings() {
        $domain = $_ENV['DOMAIN'] ?? 'https://localhost:8000';
        
        $snippet = '<script>
window.pageStartTime = performance.now();
</script>
<script type="text/javascript" src="' . $domain . '/collect.js"></script>
<noscript>
<img src="' . $domain . '/collect.gif" />
</noscript>';
        
        $data = [
            'title' => 'Settings - Traffic Analytics',
            'currentTab' => 'settings',
            'trackingSnippet' => $snippet,
            'domain' => $domain
        ];
        
        echo $this->render('settings', $data);
    }
}
