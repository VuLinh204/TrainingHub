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
        error_log("Login attempt - Email: " . $email);
        error_log("Employee data: " . print_r($employee, true));
        
        if (!$employee) {
            error_log("Login failed - Email not found");
            $_SESSION['login_error'] = 'Email hoặc mật khẩu không đúng';
            header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/login');
            exit;
        }

        if (!password_verify($password, $employee['PasswordHash'])) {
            error_log("Login failed - Password incorrect");
            error_log("Input password: " . $password);
            error_log("Stored hash: " . $employee['PasswordHash']);
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
        
        // Update last login
        $this->employeeModel->updateLastLogin($employee['ID']);
        
        // Redirect to dashboard
        header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/dashboard');
        exit;
    }
    
    public function logout() {
        session_destroy();
        header('Location: ' . dirname($_SERVER['PHP_SELF']) . '/login');
        exit;
    }
}
