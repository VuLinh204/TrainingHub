<?php
/**
 * Admin Settings Controller
 * Quản lý cài đặt hệ thống
 */
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/EmployeeModel.php';

class AdminSettingsController extends Controller {
    private $employeeModel;

    public function __construct() {
        parent::__construct();
        $this->employeeModel = new EmployeeModel();
    }

    /**
     * Kiểm tra quyền admin
     */
    private function checkAdminAuth() {
        $employeeId = $this->checkAuth();
        $employee = $this->employeeModel->findById($employeeId);
        
        if (!isset($employee['Role']) || $employee['Role'] !== 'admin') {
            http_response_code(403);
            $this->render('error/403', [
                'message' => 'Bạn không có quyền truy cập trang này'
            ]);
            exit;
        }
        
        return $employeeId;
    }

    /**
     * Trang settings chính
     */
    public function index() {
        $adminId = $this->checkAdminAuth();
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        $settings = $this->getAllSettings();
        
        $this->render('admin/settings/index', [
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'settings' => $settings,
            'pageTitle' => 'Cài đặt hệ thống'
        ]);
    }

    /**
     * Cập nhật cài đặt chung
     */
    public function updateGeneral() {
        $this->checkAdminAuth();
        
        try {
            $companyName = $_POST['company_name'] ?? '';
            $contactEmail = $_POST['contact_email'] ?? '';
            $phone = $_POST['phone'] ?? '';
            
            // Lưu vào database hoặc config file
            $this->saveSetting('company_name', $companyName);
            $this->saveSetting('contact_email', $contactEmail);
            $this->saveSetting('phone', $phone);
            
            $_SESSION['success'] = 'Đã cập nhật cài đặt chung';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
        
        $this->redirect('admin/settings');
    }

    /**
     * Cập nhật cài đặt thông báo
     */
    public function updateNotifications() {
        $this->checkAdminAuth();
        
        try {
            $emailNotif = isset($_POST['email_notifications']) ? 1 : 0;
            $certNotif = isset($_POST['cert_notifications']) ? 1 : 0;
            $deadlineNotif = isset($_POST['deadline_notifications']) ? 1 : 0;
            
            $this->saveSetting('email_notifications', $emailNotif);
            $this->saveSetting('cert_notifications', $certNotif);
            $this->saveSetting('deadline_notifications', $deadlineNotif);
            
            $_SESSION['success'] = 'Đã cập nhật cài đặt thông báo';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
        
        $this->redirect('admin/settings');
    }

    /**
     * Cập nhật cài đặt chứng chỉ
     */
    public function updateCertificate() {
        $this->checkAdminAuth();
        
        try {
            $minScore = (int)($_POST['min_score'] ?? 70);
            $minWatchPercent = (int)($_POST['min_watch_percent'] ?? 90);
            $certPrefix = $_POST['cert_prefix'] ?? 'CERT';
            $autoApprove = isset($_POST['auto_approve']) ? 1 : 0;
            
            // Validate
            if ($minScore < 0 || $minScore > 100) {
                throw new Exception('Điểm tối thiểu phải từ 0-100');
            }
            if ($minWatchPercent < 0 || $minWatchPercent > 100) {
                throw new Exception('Thời gian xem phải từ 0-100%');
            }
            
            $this->saveSetting('min_score', $minScore);
            $this->saveSetting('min_watch_percent', $minWatchPercent);
            $this->saveSetting('cert_prefix', $certPrefix);
            $this->saveSetting('auto_approve_cert', $autoApprove);
            
            $_SESSION['success'] = 'Đã cập nhật cài đặt chứng chỉ';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
        
        $this->redirect('admin/settings');
    }

    /**
     * Cập nhật cài đặt bài kiểm tra
     */
    public function updateExam() {
        $this->checkAdminAuth();
        
        try {
            $defaultTime = (int)($_POST['default_time'] ?? 30);
            $maxAttempts = (int)($_POST['max_attempts'] ?? 3);
            $showAnswers = isset($_POST['show_answers']) ? 1 : 0;
            $shuffleQuestions = isset($_POST['shuffle_questions']) ? 1 : 0;
            
            if ($defaultTime < 5) {
                throw new Exception('Thời gian làm bài tối thiểu là 5 phút');
            }
            if ($maxAttempts < 1) {
                throw new Exception('Số lần làm bài tối thiểu là 1');
            }
            
            $this->saveSetting('exam_default_time', $defaultTime);
            $this->saveSetting('exam_max_attempts', $maxAttempts);
            $this->saveSetting('exam_show_answers', $showAnswers);
            $this->saveSetting('exam_shuffle_questions', $shuffleQuestions);
            
            $_SESSION['success'] = 'Đã cập nhật cài đặt bài kiểm tra';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
        
        $this->redirect('admin/settings');
    }

    /**
     * Cập nhật cài đặt bảo mật bài kiểm tra
     */
    public function updateExamSecurity() {
        $this->checkAdminAuth();
        
        try {
            $blockCopy = isset($_POST['block_copy']) ? 1 : 0;
            $blockTabSwitch = isset($_POST['block_tab_switch']) ? 1 : 0;
            $fullscreenMode = isset($_POST['fullscreen_mode']) ? 1 : 0;
            
            $this->saveSetting('exam_block_copy', $blockCopy);
            $this->saveSetting('exam_block_tab_switch', $blockTabSwitch);
            $this->saveSetting('exam_fullscreen_mode', $fullscreenMode);
            
            $_SESSION['success'] = 'Đã cập nhật cài đặt bảo mật bài kiểm tra';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
        
        $this->redirect('admin/settings');
    }

    /**
     * Cập nhật cài đặt SMTP
     */
    public function updateSmtp() {
        $this->checkAdminAuth();
        
        try {
            $smtpHost = $_POST['smtp_host'] ?? '';
            $smtpPort = (int)($_POST['smtp_port'] ?? 587);
            $smtpUser = $_POST['smtp_user'] ?? '';
            $smtpPass = $_POST['smtp_pass'] ?? '';
            
            if (empty($smtpHost) || empty($smtpUser)) {
                throw new Exception('Vui lòng nhập đầy đủ thông tin SMTP');
            }
            
            $this->saveSetting('smtp_host', $smtpHost);
            $this->saveSetting('smtp_port', $smtpPort);
            $this->saveSetting('smtp_user', $smtpUser);
            if (!empty($smtpPass)) {
                $this->saveSetting('smtp_pass', base64_encode($smtpPass)); // Simple encoding
            }
            
            $_SESSION['success'] = 'Đã cập nhật cấu hình SMTP';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
        
        $this->redirect('admin/settings');
    }

    /**
     * Cập nhật template email
     */
    public function updateEmailTemplate() {
        $this->checkAdminAuth();
        
        try {
            $emailSubject = $_POST['email_subject'] ?? '';
            $emailBody = $_POST['email_body'] ?? '';
            
            if (empty($emailSubject) || empty($emailBody)) {
                throw new Exception('Vui lòng nhập đầy đủ thông tin template');
            }
            
            $this->saveSetting('email_subject', $emailSubject);
            $this->saveSetting('email_body', $emailBody);
            
            $_SESSION['success'] = 'Đã cập nhật template email';
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
        
        $this->redirect('admin/settings');
    }

    /**
     * Sao lưu database
     */
    public function backup() {
        $this->checkAdminAuth();
        
        try {
            $action = $_POST['action'] ?? '';
            
            if ($action === 'backup_now') {
                // Tạo backup file
                $backupFile = $this->createDatabaseBackup();
                
                if ($backupFile) {
                    $_SESSION['success'] = 'Đã tạo bản sao lưu thành công';
                } else {
                    throw new Exception('Không thể tạo bản sao lưu');
                }
            }
            
            // Cập nhật auto backup setting
            $autoBackup = isset($_POST['auto_backup']) ? 1 : 0;
            $this->saveSetting('auto_backup', $autoBackup);
            
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
        
        $this->redirect('admin/settings');
    }

    /**
     * Dọn dẹp hệ thống
     */
    public function cleanup() {
        $this->checkAdminAuth();
        
        try {
            $deleteOldLogs = isset($_POST['delete_old_logs']);
            $deleteExpiredSessions = isset($_POST['delete_expired_sessions']);
            
            $cleaned = 0;
            
            if ($deleteOldLogs) {
                // Xóa log cũ hơn 30 ngày
                $sql = "DELETE FROM tblTrain_ActivityLog WHERE CreatedAt < DATE_SUB(NOW(), INTERVAL 30 DAY)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $cleaned += $stmt->rowCount();
            }
            
            if ($deleteExpiredSessions) {
                // Xóa session hết hạn
                $sql = "DELETE FROM tblTrain_Sessions WHERE ExpireAt < NOW()";
                $stmt = $this->db->prepare($sql);
                $stmt->execute();
                $cleaned += $stmt->rowCount();
            }
            
            $_SESSION['success'] = "Đã dọn dẹp thành công {$cleaned} bản ghi";
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
        
        $this->redirect('admin/settings');
    }

    /**
     * Khu vực nguy hiểm
     */
    public function dangerZone() {
        $this->checkAdminAuth();
        
        try {
            $action = $_POST['action'] ?? '';
            
            switch ($action) {
                case 'reset_demo':
                    $this->resetToDemo();
                    $_SESSION['success'] = 'Đã reset về dữ liệu demo';
                    break;
                    
                case 'delete_all_certs':
                    $sql = "DELETE FROM tblTrain_Certificate";
                    $stmt = $this->db->prepare($sql);
                    $stmt->execute();
                    $_SESSION['success'] = 'Đã xóa tất cả chứng chỉ';
                    break;
                    
                default:
                    throw new Exception('Hành động không hợp lệ');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = 'Có lỗi xảy ra: ' . $e->getMessage();
        }
        
        $this->redirect('admin/settings');
    }

    /**
     * Lưu setting vào database
     */
    private function saveSetting($key, $value) {
        // Kiểm tra xem setting đã tồn tại chưa
        $sql = "SELECT COUNT(*) FROM tblTrain_Settings WHERE SettingKey = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$key]);
        $exists = $stmt->fetchColumn() > 0;
        
        if ($exists) {
            // Update
            $sql = "UPDATE tblTrain_Settings SET SettingValue = ?, UpdatedAt = NOW() WHERE SettingKey = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$value, $key]);
        } else {
            // Insert
            $sql = "INSERT INTO tblTrain_Settings (SettingKey, SettingValue, CreatedAt, UpdatedAt) VALUES (?, ?, NOW(), NOW())";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$key, $value]);
        }
    }

    /**
     * Lấy tất cả settings
     */
    private function getAllSettings() {
        $sql = "SELECT SettingKey, SettingValue FROM tblTrain_Settings";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $settings = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $settings[$row['SettingKey']] = $row['SettingValue'];
        }
        
        return $settings;
    }

    /**
     * Tạo database backup
     */
    private function createDatabaseBackup() {
        try {
            $backupDir = __DIR__ . '/../backups';
            if (!is_dir($backupDir)) {
                mkdir($backupDir, 0755, true);
            }
            
            $filename = 'backup_' . date('Y-m-d_H-i-s') . '.sql';
            $filepath = $backupDir . '/' . $filename;
            
            // Lấy thông tin database từ config
            $dbConfig = require __DIR__ . '/../config/database.php';
            
            // Tạo backup command
            $command = sprintf(
                'mysqldump -h %s -u %s -p%s %s > %s',
                $dbConfig['host'],
                $dbConfig['username'],
                $dbConfig['password'],
                $dbConfig['database'],
                $filepath
            );
            
            exec($command, $output, $returnVar);
            
            return $returnVar === 0 ? $filepath : false;
        } catch (Exception $e) {
            error_log('Backup error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Reset về dữ liệu demo
     */
    private function resetToDemo() {
        // Xóa dữ liệu hiện tại (trừ admin)
        $tables = [
            'tblTrain_Certificate',
            'tblTrain_Exam',
            'tblTrain_ExamAnswer',
            'tblTrain_Progress'
        ];
        
        foreach ($tables as $table) {
            $sql = "TRUNCATE TABLE $table";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        
        // Import dữ liệu demo từ file SQL
        $demoFile = __DIR__ . '/../database/demo_data.sql';
        if (file_exists($demoFile)) {
            $sql = file_get_contents($demoFile);
            $this->db->exec($sql);
        }
    }

    /**
     * Lấy sidebar data
     */
    private function getAdminSidebarData($adminId) {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM tblTrain_Certificate WHERE Status = 0) as pending_count,
                (SELECT COUNT(*) FROM tblTrain_Certificate WHERE Status = 1) as approved_count,
                (SELECT COUNT(*) FROM tblTrain_Certificate WHERE Status = 2) as revoked_count,
                (SELECT COUNT(*) FROM tblTrain_Employee WHERE Status = 1) as total_employees,
                (SELECT COUNT(*) FROM tblTrain_Subject WHERE Status = 1) as total_subjects";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        $employee = $this->employeeModel->findById($adminId);
        $position = $employee['PositionID'] == 6 ? 'Quản trị viên' : 'Quản lý';
        
        return [
            'stats' => $stmt->fetch(PDO::FETCH_ASSOC),
            'position' => $position
        ];
    }
}