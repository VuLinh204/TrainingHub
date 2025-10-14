<?php
class Router {
    private $routes = [];
    
    public function __construct() {
        // Load routes from routes.php
        $routesFile = BASE_PATH . '/routes.php';
        if (!file_exists($routesFile)) {
            error_log("Routes file not found: " . $routesFile);
            throw new Exception("Routes configuration file not found");
        }
        
        $this->routes = require $routesFile;
        if (!is_array($this->routes)) {
            error_log("Invalid routes configuration");
            throw new Exception("Invalid routes configuration");
        }
    }
    
    private function convertRouteToRegex($route) {
        $pattern = preg_replace('/:[a-zA-Z]+/', '([^/]+)', $route);
        return "#^" . $pattern . "$#";
    }
    
    private function extractParams($route, $matches) {
        $params = [];
        preg_match_all('/:([a-zA-Z]+)/', $route, $paramNames);
        foreach ($paramNames[1] as $index => $name) {
            $params[$name] = $matches[$index + 1];
        }
        return $params;
    }
    
    public function handle($path) {
        error_log("Handling path: " . $path);
        $requestMethod = $_SERVER['REQUEST_METHOD'];
        
        foreach ($this->routes as $routePattern => $handler) {
            list($method, $route) = explode(' ', $routePattern);
            
            if ($requestMethod !== $method) {
                continue;
            }
            
            // Convert route pattern to regex
            $routeRegex = $this->convertRouteToRegex($route);
            error_log("Checking route: " . $routeRegex . " against path: " . $path);
            
            if (preg_match($routeRegex, $path, $matches)) {
                error_log("Route matched: " . $routePattern);
                array_shift($matches); // Remove full match
                
                list($controllerName, $actionName) = explode('@', $handler);
                $controller = new $controllerName();
                
                // Extract named parameters
                $params = $this->extractParams($route, $matches);
                error_log("Extracted params: " . print_r($params, true));
                
                return call_user_func_array([$controller, $actionName], $params);
            }
        }
        
        // No matching route found
        error_log("No route found for: " . $path);
        http_response_code(404);
        require BASE_PATH . '/views/error/404.php';
    }
}
