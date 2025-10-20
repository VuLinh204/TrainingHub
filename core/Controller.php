<?php
class Controller {
    protected $db;

    public function __construct() {
        // Include database configuration
        if (!isset($GLOBALS['db'])) {
            $db = require __DIR__ . '/../config/db.php';
            $GLOBALS['db'] = $db;
        }
        $this->db = $GLOBALS['db'];
    }

    protected function render($view, $data = []) {
        global $db;
        // Add base URL and database connection to all views
        $data['baseUrl'] = $this->getBaseUrl();
        $db = $this->db; // Make it available both as $db and in $GLOBALS
        $GLOBALS['db'] = $this->db;
        extract($data);
        
        ob_start();
        $viewPath = __DIR__ . "/../views/{$view}.php";
        if (!file_exists($viewPath)) {
            error_log("View not found: " . $viewPath);
            require_once __DIR__ . "/../views/error/404.php";
            return;
        }
        require_once $viewPath;
        $content = ob_get_clean();
        
        require_once __DIR__ . "/../views/layout/header.php";
        echo $content;
        require_once __DIR__ . "/../views/layout/footer.php";
    }

    protected function json($data) {
        header('Content-Type: application/json');
        echo json_encode($data);
    }

    protected function checkAuth() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['employee_id'])) {
            header('Location: ' . $this->getBaseUrl() . '/login');
            exit;
        }
        return $_SESSION['employee_id'];
    }
    
    protected function getBaseUrl() {
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        return rtrim($scriptDir, '/');
    }

    protected function redirect($path) {
        $baseUrl = $this->getBaseUrl();
        $url = $baseUrl . '/' . ltrim($path, '/');
        header('Location: ' . $url);
        exit;
    }

    protected function getPostJson() {
        return json_decode(file_get_contents('php://input'), true);
    }
}
