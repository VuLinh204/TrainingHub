<?php
// Enable error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set default timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Define base constants
define('BASE_PATH', __DIR__);
define('BASE_URL', '//' . $_SERVER['HTTP_HOST'] . dirname($_SERVER['PHP_SELF']));

// Autoloader for classes
spl_autoload_register(function ($class) {
    $paths = [
        BASE_PATH . '/controllers/',
        BASE_PATH . '/models/',
        BASE_PATH . '/core/'
    ];
    
    foreach ($paths as $path) {
        $file = $path . $class . '.php';
        error_log("Trying to load class: $class => $file");
        if (file_exists($file)) {
            require_once $file;
            error_log("✅ Loaded $file");
            return;
        }
    }
    error_log("❌ Class not found: $class");
});

// Session handling
session_start();

// Initialize database connection
if (!isset($GLOBALS['db'])) {
    $GLOBALS['db'] = require __DIR__ . '/config/db.php';
}

// Simple security headers
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: strict-origin-when-cross-origin');

// Request handling
$requestUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
error_log("Original Request URI: " . $requestUri);

// Get the base directory of the application
$baseDir = dirname($_SERVER['SCRIPT_NAME']);
error_log("Base directory: " . $baseDir);

// Remove the base directory from the request URI if it's present
if (strpos($requestUri, $baseDir) === 0) {
    $requestUri = substr($requestUri, strlen($baseDir));
}

// Clean up the request path
$request = '/' . trim($requestUri, '/');
$request = preg_replace('#/+#', '/', $request);

error_log("Final processed request: " . $request);

// Debug logging
error_log("Cleaned Request: " . $request);
error_log("Request Method: " . $_SERVER['REQUEST_METHOD']);

// Create route key (METHOD /path)
$routeKey = $_SERVER['REQUEST_METHOD'] . ' ' . $request;
error_log("Looking for route: " . $routeKey);

// Compute login URL for redirects
$loginUrl = $baseDir . '/login';

// API route handling for AJAX calls
if (strpos($request, '/api/') === 0) {
    header('Content-Type: application/json');
    
    // Auth check for API (assuming all API endpoints require login)
    if (!isset($_SESSION['employee_id'])) {
        http_response_code(401);
        echo json_encode(['error' => 'Unauthorized']);
        exit;
    }
    
    // CSRF protection for API calls
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!isset($_SERVER['HTTP_X_REQUESTED_WITH']) || 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) !== 'xmlhttprequest') {
            http_response_code(403);
            die(json_encode(['error' => 'Invalid request']));
        }
    }
    
    // Rate limiting
    if (!checkRateLimit()) {
        http_response_code(429);
        die(json_encode(['error' => 'Too many requests']));
    }
    
    // Handle API routes
    $apiRouter = new ApiRouter();
    $apiRouter->handle(substr($request, 4));
    exit;
}

// Web route handling
$router = new Router();

// Auth middleware for protected routes
if (!in_array($request, ['/login']) && 
    !isset($_SESSION['employee_id'])) {
    error_log("Redirecting to login: " . $loginUrl);
    header('Location: ' . $loginUrl);
    exit;
}

error_log("Starting request handling...");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("Request path: " . $request);

try {
    $router->handle($request);
} catch (Exception $e) {
    // Log error with trace
    error_log("Error handling request: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    
    // If not logged in, redirect to login (with special handling for /login)
    if (!isset($_SESSION['employee_id'])) {
        if ($request === '/login') {
            // Assume login view path; adjust as needed
            if (file_exists(BASE_PATH . '/views/login.php')) {
                require_once BASE_PATH . '/views/login.php';
            } else {
                // Fallback: simple login prompt or redirect
                header('Location: ' . $loginUrl);
            }
        } else {
            header('Location: ' . $loginUrl);
        }
        exit;
    }
    
    // Show error page in production, details in development
    if (getenv('APP_ENV') === 'production') {
        require_once BASE_PATH . '/views/error/500.php';
    } else {
        echo '<pre>' . $e->getMessage() . "\n" . $e->getTraceAsString() . '</pre>';
    }
}

// Rate limiting function
function checkRateLimit() {
    $ip = $_SERVER['REMOTE_ADDR'];
    $cache_key = "rate_limit:$ip";
    
    // Using APCu for rate limiting (can be replaced with Redis/Memcached)
    if (function_exists('apcu_fetch')) {
        $attempts = apcu_fetch($cache_key) ?: 0;
        if ($attempts > 100) { // 100 requests per minute
            return false;
        }
        apcu_inc($cache_key, 1);
        if ($attempts === 0) {
            apcu_store($cache_key, 1, 60); // Reset after 1 minute
        }
    }
    
    return true;
}

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
        
        // Add request method to debug
        foreach ($this->routes as $route => $handler) {
            error_log("Route: " . $route . " => " . $handler);
        }
    }
    
    public function handle($request) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        foreach ($this->routes as $pattern => $handler) {
            // Split pattern into method and path
            $parts = explode(' ', $pattern, 2);
            if (count($parts) !== 2) {
                continue;
            }
            list($methodPattern, $pathPattern) = $parts;
            
            // Skip if methods don't match
            if ($methodPattern !== $method) {
                continue;
            }
            
            // Build regex for path pattern
            $quotedPath = preg_quote($pathPattern, '#');
            $pathRegex = '#^' . preg_replace('#\\\\:([a-zA-Z0-9_]+)#', '(?P<$1>[^/]+)', $quotedPath) . '$#';
            error_log("Checking path regex: " . $pathRegex . " against: " . $request);
            
            if (preg_match($pathRegex, $request, $matches)) {
                error_log("Route match found: " . $pattern);
                
                // Extract named parameters
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key) && !empty($value)) {
                        $params[$key] = $value;
                    }
                }
                
                // Call controller action
                list($controller, $action) = explode('@', $handler);
                $instance = new $controller();
                call_user_func_array([$instance, $action], array_values($params)); // Pass as positional args; adjust if named needed
                return;
            }
        }
        
        // No route found
        error_log("No route found for: " . $method . ' ' . $request);
        
        // If not logged in, redirect to login (with special handling for /login)
        if (!isset($_SESSION['employee_id'])) {
            global $loginUrl; // Use the global loginUrl computed earlier
            if ($request === '/login') {
                // Assume login view path; adjust as needed
                if (file_exists(BASE_PATH . '/views/login.php')) {
                    require_once BASE_PATH . '/views/login.php';
                } else {
                    // Fallback: simple login prompt or redirect (avoid loop)
                    echo '<h1>Please log in</h1><p><a href="' . $loginUrl . '">Login</a></p>';
                }
            } else {
                header('Location: ' . $loginUrl);
            }
            return;
        }
        
        http_response_code(404);
        require BASE_PATH . '/views/error/404.php';
    }
}

class ApiRouter {
    private $apiRoutes = [
        'POST /lesson/track' => 'SubjectController@trackProgress',
        'POST /lesson/complete' => 'SubjectController@complete',
        'POST /exam/start' => 'ExamController@start',
        'POST /exam/check' => 'ExamController@checkAnswer',
        'POST /exam/submit' => 'ExamController@submit',
        'GET /subject/:id/progress' => 'SubjectController@getProgress'
    ];
    
    public function handle($path) {
        $method = $_SERVER['REQUEST_METHOD'];
        
        foreach ($this->apiRoutes as $pattern => $handler) {
            // Split pattern into method and path
            $parts = explode(' ', $pattern, 2);
            if (count($parts) !== 2) {
                continue;
            }
            list($methodPattern, $pathPattern) = $parts;
            
            // Skip if methods don't match
            if ($methodPattern !== $method) {
                continue;
            }
            
            // Build regex for path pattern
            $quotedPath = preg_quote($pathPattern, '#');
            $pathRegex = '#^' . preg_replace('#\\\\:([a-zA-Z0-9_]+)#', '(?P<$1>[^/]+)', $quotedPath) . '$#';
            error_log("API path regex: " . $pathRegex . " against: " . $path);
            
            if (preg_match($pathRegex, $path, $matches)) {
                error_log("API route match: " . $pattern);
                
                // Extract named parameters
                $params = [];
                foreach ($matches as $key => $value) {
                    if (is_string($key) && !empty($value)) {
                        $params[$key] = $value;
                    }
                }
                
                // Add JSON request body for POST requests
                if ($method === 'POST') {
                    $input = file_get_contents('php://input');
                    $json = json_decode($input, true);
                    if ($json !== null) {
                        $params['data'] = $json;
                    } else {
                        error_log("Invalid JSON input: " . $input);
                    }
                }
                
                // Call controller action
                list($controller, $action) = explode('@', $handler);
                $instance = new $controller();
                $result = call_user_func_array([$instance, $action], array_values($params)); // Pass as positional; adjust if needed
                
                // Send JSON response
                header('Content-Type: application/json');
                echo json_encode($result ?? ['success' => true]);
                return;
            }
        }
        
        // 404 for API
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'Endpoint not found']);
    }
}