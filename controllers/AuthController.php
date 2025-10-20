<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/EmployeeModel.php';

class AuthController extends Controller {
    private $employeeModel;
    
    public function __construct() {
        $this->employeeModel = new EmployeeModel();
    }
    
    public function loginForm() {
        // Redirect if already logged in
        if (isset($_SESSION['employee_id'])) {
            header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/dashboard');
            exit;
        }
        
        $this->render('auth/login', [
            'error' => $_SESSION['login_error'] ?? null,
            'baseUrl' => dirname($_SERVER['PHP_SELF'])
        ]);
        unset($_SESSION['login_error']);
    }

    private function checkAdminAuth() {
        $employeeId = $this->checkAuth();
        
        $employee = $this->employeeModel->findById($employeeId);
        
        // Kiểm tra PositionID (5 = quản lý, 6 = admin)
        if (!isset($employee['Role']) || $employee['Role'] !== 'admin') {
            http_response_code(403);
            $this->render('error/403', [
                'message' => 'Bạn không có quyền truy cập trang này'
            ]);
            exit;
        }
        
        return $employeeId;
    }
    
    public function login() {
        $email = $_POST['email'] ?? '';
        $password = $_POST['password'] ?? '';

        // Basic validation
        if (empty($email) || empty($password)) {
            $_SESSION['login_error'] = 'Vui lòng nhập email và mật khẩu';
            header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/login');
            exit;
        }

        // Find employee by email
        $employee = $this->employeeModel->findByEmail($email);

        if (!$employee || !password_verify($password, $employee['PasswordHash'])) {
            $_SESSION['login_error'] = 'Email hoặc mật khẩu không đúng';
            header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/login');
            exit;
        }

        if ($employee['Status'] != 1) {
            $_SESSION['login_error'] = 'Tài khoản đã bị vô hiệu hóa';
            header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/login');
            exit;
        }

        // Set session
        $_SESSION['employee_id'] = $employee['ID'];
        $_SESSION['employee_name'] = $employee['FullName'];
        $_SESSION['position_id'] = $employee['PositionID'];
        $_SESSION['role'] = $employee['Role'] ?? '';

        // Update last login
        $this->employeeModel->updateLastLogin($employee['ID']);

        // Redirect based on role
        if (isset($employee['Role']) && $employee['Role'] === 'admin') {
            header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/admin');
        } else {
            header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/dashboard');
        }
        exit;
    }
    
    public function logout() {
        session_destroy();
        header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/login');
        exit;
    }
}
