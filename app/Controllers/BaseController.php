<?php

abstract class BaseController {
    protected $db;
    
    public function __construct() {
        $this->db = DB::getInstance();
    }
    
    protected function render($view, $data = []) {
        extract($data);
        
        $viewPath = __DIR__ . "/../Views/$view.php";
        if (!file_exists($viewPath)) {
            throw new Exception("View not found: $view");
        }
        
        ob_start();
        include $viewPath;
        return ob_get_clean();
    }
    
    protected function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }
    
    protected function response($content, $statusCode = 200, $headers = []) {
        http_response_code($statusCode);
        
        foreach ($headers as $header => $value) {
            header("$header: $value");
        }
        
        echo $content;
        exit;
    }
    
    protected function redirect($url, $statusCode = 302) {
        http_response_code($statusCode);
        header("Location: $url");
        exit;
    }
    
    protected function getInput($key, $default = null) {
        return $_GET[$key] ?? $_POST[$key] ?? $default;
    }
    
    protected function validateRequired($fields) {
        $missing = [];
        
        foreach ($fields as $field) {
            if (empty($this->getInput($field))) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            $this->json(['error' => 'Missing required fields: ' . implode(', ', $missing)], 400);
        }
    }
}
