<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/CompletionModel.php';
require_once __DIR__ . '/../models/CertificateModel.php';

class EmployeeController extends Controller {
    private $employeeModel;
    private $completionModel;
    private $certificateModel;

    public function __construct() {
        parent::__construct();
        $this->employeeModel = new EmployeeModel();
        $this->completionModel = new CompletionModel();
        $this->certificateModel = new CertificateModel();
    }

    /**
     * Show employee profile
     */
    public function profile() {
        $employeeId = $this->checkAuth();
        
        $employee = $this->employeeModel->findById($employeeId);
        
        if (!$employee) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        // Get completion statistics
        $completionStats = $this->completionModel->getEmployeeCompletionRate($employeeId);
        
        // Get certificates
        $certificates = $this->certificateModel->getEmployeeCertificates($employeeId);
        
        // Get recent completions
        $recentCompletions = $this->completionModel->getRecentCompletions($employeeId, 5);
        
        // Get sidebar data
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('employee/profile', [
            'employee' => $employee,
            'completionStats' => $completionStats,
            'certificates' => $certificates,
            'recentCompletions' => $recentCompletions,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Hồ sơ của tôi'
        ]);
    }

    /**
     * Show edit profile form
     */
    public function editProfile() {
        $employeeId = $this->checkAuth();
        
        $employee = $this->employeeModel->findById($employeeId);
        
        if (!$employee) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        // Get sidebar data
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('employee/edit', [
            'employee' => $employee,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Chỉnh sửa hồ sơ'
        ]);
    }

    /**
     * Update employee profile
     */
    public function updateProfile() {
        $employeeId = $this->checkAuth();
        
        // Validate input
        $errors = [];
        $data = [];

        // First Name
        if (empty($_POST['first_name'])) {
            $errors[] = 'Tên không được để trống';
        } else {
            $data['FirstName'] = trim($_POST['first_name']);
        }

        // Last Name
        if (empty($_POST['last_name'])) {
            $errors[] = 'Họ không được để trống';
        } else {
            $data['LastName'] = trim($_POST['last_name']);
        }

        // Phone (optional)
        if (!empty($_POST['phone'])) {
            $data['Phone'] = trim($_POST['phone']);
        }

        // If there are errors, redirect back with errors
        if (!empty($errors)) {
            $_SESSION['profile_errors'] = $errors;
            $this->redirect('profile/edit');
            return;
        }

        // Update profile
        $success = $this->employeeModel->updateProfile($employeeId, $data);

        if ($success) {
            // Update session name
            $_SESSION['employee_name'] = $data['FirstName'] . ' ' . $data['LastName'];
            $_SESSION['profile_success'] = 'Cập nhật hồ sơ thành công';
        } else {
            $_SESSION['profile_error'] = 'Không thể cập nhật hồ sơ';
        }

        $this->redirect('profile');
    }

    /**
     * Show change password form
     */
    public function changePasswordForm() {
        $employeeId = $this->checkAuth();
        
        $employee = $this->employeeModel->findById($employeeId);
        
        // Get sidebar data
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('employee/change_password', [
            'employee' => $employee,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Đổi mật khẩu'
        ]);
    }

    /**
     * Change password
     */
    public function changePassword() {
        $employeeId = $this->checkAuth();
        
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validate
        $errors = [];

        if (empty($currentPassword)) {
            $errors[] = 'Vui lòng nhập mật khẩu hiện tại';
        }

        if (empty($newPassword)) {
            $errors[] = 'Vui lòng nhập mật khẩu mới';
        } elseif (strlen($newPassword) < 6) {
            $errors[] = 'Mật khẩu mới phải có ít nhất 6 ký tự';
        }

        if ($newPassword !== $confirmPassword) {
            $errors[] = 'Mật khẩu xác nhận không khớp';
        }

        if (!empty($errors)) {
            $_SESSION['password_errors'] = $errors;
            $this->redirect('profile/change-password');
            return;
        }

        // Verify current password
        $employee = $this->employeeModel->findById($employeeId);
        
        if (!password_verify($currentPassword, $employee['PasswordHash'])) {
            $_SESSION['password_error'] = 'Mật khẩu hiện tại không đúng';
            $this->redirect('profile/change-password');
            return;
        }

        // Update password
        $success = $this->employeeModel->updatePassword($employeeId, $newPassword);

        if ($success) {
            $_SESSION['password_success'] = 'Đổi mật khẩu thành công';
            $this->redirect('profile');
        } else {
            $_SESSION['password_error'] = 'Không thể đổi mật khẩu';
            $this->redirect('profile/change-password');
        }
    }

    /**
     * Show employee learning history
     */
    public function learningHistory() {
        $employeeId = $this->checkAuth();
        
        // Get all completions
        $completions = $this->completionModel->getEmployeeCompletions($employeeId);
        
        // Get all exam attempts
        $examAttempts = $this->getExamHistory($employeeId);
        
        // Get sidebar data
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('employee/history', [
            'completions' => $completions,
            'examAttempts' => $examAttempts,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Lịch sử học tập'
        ]);
    }

    /**
     * Get exam history for employee
     */
    private function getExamHistory($employeeId) {
        $sql = "SELECT e.*, 
                s.Title as SubjectName,
                s.RequiredScore
                FROM " . TBL_EXAM . " e
                JOIN " . TBL_SUBJECT . " s ON e.SubjectID = s.ID
                WHERE e.EmployeeID = ?
                ORDER BY e.StartTime DESC
                LIMIT 50";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Show employee statistics dashboard
     */
    public function statistics() {
        $employeeId = $this->checkAuth();
        
        // Get completion rate
        $completionRate = $this->completionModel->getEmployeeCompletionRate($employeeId);
        
        // Get exam statistics
        $examStats = $this->getExamStatistics($employeeId);
        
        // Get learning time statistics
        $watchStats = $this->getWatchStatistics($employeeId);
        
        // Get certificates count
        $certificateStats = $this->certificateModel->getCertificateStats($employeeId);
        
        // Get monthly progress
        $monthlyProgress = $this->getMonthlyProgress($employeeId);
        
        // Get sidebar data
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('employee/statistics', [
            'completionRate' => $completionRate,
            'examStats' => $examStats,
            'watchStats' => $watchStats,
            'certificateStats' => $certificateStats,
            'monthlyProgress' => $monthlyProgress,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Thống kê học tập'
        ]);
    }

    /**
     * Get exam statistics
     */
    private function getExamStatistics($employeeId) {
        $sql = "SELECT 
                COUNT(*) as total_attempts,
                SUM(CASE WHEN Passed = 1 THEN 1 ELSE 0 END) as passed_attempts,
                AVG(Score) as avg_score,
                MAX(Score) as highest_score,
                MIN(Score) as lowest_score
                FROM " . TBL_EXAM . "
                WHERE EmployeeID = ? AND Status = 'completed'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result && $result['total_attempts'] > 0) {
            $result['pass_rate'] = ($result['passed_attempts'] / $result['total_attempts']) * 100;
        } else {
            $result['pass_rate'] = 0;
        }
        
        return $result;
    }

    /**
     * Get watch time statistics
     */
    private function getWatchStatistics($employeeId) {
        $sql = "SELECT 
                COUNT(DISTINCT SubjectID) as subjects_watched,
                SUM(WatchedSeconds) as total_watched_seconds,
                MAX(WatchedSeconds) as longest_watch
                FROM " . TBL_WATCH_LOG . "
                WHERE EmployeeID = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $result['total_watched_hours'] = round($result['total_watched_seconds'] / 3600, 1);
        }
        
        return $result;
    }

    /**
     * Get monthly learning progress
     */
    private function getMonthlyProgress($employeeId) {
        $sql = "SELECT 
                DATE_FORMAT(CompletedAt, '%Y-%m') as month,
                COUNT(*) as completions
                FROM " . TBL_COMPLETION . "
                WHERE EmployeeID = ?
                AND CompletedAt >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(CompletedAt, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get sidebar data for all employee pages
     */
    private function getSidebarData($employeeId) {
        $progress = [
            'total_subjects' => 0,
            'completed_subjects' => 0,
            'total_certificates' => 0
        ];

        $stmt = $this->db->prepare("
            SELECT 
                COUNT(DISTINCT s.ID) as total_subjects,
                SUM(CASE WHEN 
                    EXISTS (
                        SELECT 1 
                        FROM " . TBL_EXAM . " e 
                        WHERE e.SubjectID = s.ID 
                        AND e.EmployeeID = ? 
                        AND e.Passed = 1
                    )
                    THEN 1 ELSE 0 
                END) as completed_subjects,
                (SELECT COUNT(*) 
                 FROM " . TBL_CERTIFICATE . " c 
                 WHERE c.EmployeeID = ? 
                 AND c.Status = 1) as total_certificates
            FROM " . TBL_SUBJECT . " s
            INNER JOIN " . TBL_ASSIGN . " a ON s.ID = a.SubjectID
            INNER JOIN " . TBL_POSITION . " p ON p.ID = a.PositionID
            WHERE p.ID = (
                SELECT PositionID 
                FROM " . TBL_EMPLOYEE . "
                WHERE ID = ?
            )
            AND s.Status = 1
            AND (a.AssignDate <= CURRENT_DATE)
            AND (a.ExpireDate IS NULL OR a.ExpireDate >= CURRENT_DATE)
        ");
        $stmt->execute([$employeeId, $employeeId, $employeeId]);
        $progressResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($progressResult) {
            $progress = $progressResult;
        }

        $positionStmt = $this->db->prepare("
            SELECT p.PositionName 
            FROM " . TBL_EMPLOYEE . " e
            LEFT JOIN " . TBL_POSITION . " p ON e.PositionID = p.ID 
            WHERE e.ID = ?
        ");
        $positionStmt->execute([$employeeId]);
        $position = $positionStmt->fetch(PDO::FETCH_ASSOC);

        return [
            'progress' => $progress,
            'position' => $position['PositionName'] ?? 'Nhân viên'
        ];
    }

    /**
     * Get employee full name helper
     */
    private function getFullName($employee) {
        return trim($employee['FirstName'] . ' ' . $employee['LastName']);
    }
}