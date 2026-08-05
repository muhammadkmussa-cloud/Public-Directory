<?php
/**
 * Simple Router for Umma Directory
 * Handles URL routing without external dependencies
 */

class Router {
    private $routes = [];
    
    public function get($path, $handler) {
        $this->routes['GET'][$path] = $handler;
    }
    
    public function post($path, $handler) {
        $this->routes['POST'][$path] = $handler;
    }
    
    public function dispatch($url, $method) {
        // Remove leading/trailing slashes and query parameters
        $url = trim(parse_url($url, PHP_URL_PATH), '/');
        
        // Handle empty URL (homepage)
        if (empty($url)) {
            $url = 'home';
        }
        
        // Check for exact match first
        if (isset($this->routes[$method][$url])) {
            return $this->executeHandler($this->routes[$method][$url]);
        }
        
        // Check for pattern matches (e.g., business/123, mosque/456)
        foreach ($this->routes[$method] as $route => $handler) {
            $pattern = preg_replace('/\{([a-zA-Z_]+)\}/', '(?P<$1>[0-9a-zA-Z_-]+)', $route);
            $pattern = '#^' . $pattern . '$#';
            
            if (preg_match($pattern, $url, $matches)) {
                // Remove numeric keys from matches
                $params = array_filter($matches, ARRAY_FILTER_USE_KEY);
                return $this->executeHandler($handler, $params);
            }
        }
        
        // 404 Not Found
        http_response_code(404);
        include __DIR__ . '/../pages/errors/404.php';
    }
    
    private function executeHandler($handler, $params = []) {
        if (is_callable($handler)) {
            return call_user_func_array($handler, $params);
        } elseif (is_string($handler)) {
            // Handler is a file path
            extract($params);
            include __DIR__ . '/../pages/' . $handler;
        }
    }
}
