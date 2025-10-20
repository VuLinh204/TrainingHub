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
        // Sử dụng named groups để extract dễ dàng (e.g., :id → (?P<id>[^/]+))
        $pattern = preg_replace_callback('/:([a-zA-Z0-9_]+)/', function($matches) {
            return '(?P<' . $matches[1] . '>[^/]+)';
        }, $route);
        return '#^' . $pattern . '$#';
    }
    
    private function extractParams($route, $matches) {
        $params = [];
        // Lấy tên params từ route pattern
        preg_match_all('/:([a-zA-Z0-9_]+)/', $route, $paramNames);
        // Sử dụng named groups từ $matches (không array_shift)
        foreach ($paramNames[1] as $index => $name) {
            if (isset($matches[$name])) {  // Named key trực tiếp
                $params[$name] = $matches[$name];
            } elseif (isset($matches[$index + 1])) {  // Fallback index nếu không named
                $params[$name] = $matches[$index + 1];
            }
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
                
                // Không array_shift để giữ named groups
                list($controllerName, $actionName) = explode('@', $handler);
                $controllerClass = ucfirst($controllerName) . 'Controller';  // Fix ucfirst nếu cần
                $controllerFile = BASE_PATH . '/controllers/' . $controllerClass . '.php';
                
                if (file_exists($controllerFile)) {
                    require_once $controllerFile;
                    $controller = new $controllerClass();
                } else {
                    error_log("Controller file not found: " . $controllerFile);
                    continue;
                }
                
                // Extract named parameters
                $params = $this->extractParams($route, $matches);
                error_log("Extracted params: " . print_r($params, true));
                
                // Truyền positional args (array_values để giữ thứ tự)
                $callArgs = array_values($params);
                $result = call_user_func_array([$controller, $actionName], $callArgs);
                
                // Nếu AJAX, tự động JSON response
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode($result ?? ['success' => true]);
                    exit;
                }
                
                return $result;  // Trả về cho view nếu cần
            }
        }
        
        // No matching route found
        error_log("No route found for: " . $path);
        http_response_code(404);
        require BASE_PATH . '/views/error/404.php';
    }
}