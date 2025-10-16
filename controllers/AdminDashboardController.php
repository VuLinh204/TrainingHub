<?php
/**
 * Admin Dashboard Controller
 * Trang chủ quản trị
 */
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/SubjectModel.php';
require_once __DIR__ . '/../models/CertificateModel.php';
require_once __DIR__ . '/../models/ExamModel.php';

class AdminDashboardController extends Controller {
    private $employeeModel;
    private $subjectModel;
    private $certificateModel;
    private $examModel;

    public function __construct() {
        parent::__construct();
        $this->employeeModel = new EmployeeModel();
        $this->subjectModel = new SubjectModel();
        $this->certificateModel = new CertificateModel();
        $this->examModel = new ExamModel();
    }

    /**
     * Kiểm tra quyền admin
     */
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

    /**
     * Trang chủ admin
     */
    public function index() {
        $adminId = $this->checkAdminAuth();
        
        // Lấy thống kê tổng quan
        $stats = $this->getOverviewStats();
        
        // Lấy hoạt động gần đây
        $recentActivities = $this->getRecentActivities();
        
        // Lấy top performers
        $topPerformers = $this->getTopPerformers();
        
        // Lấy chứng chỉ chờ duyệt
        $pendingCerts = $this->certificateModel->getPendingCertificates();
        
        // Lấy chứng chỉ sắp hết hạn
        $expiringCerts = $this->certificateModel->getExpiringCertificates(30);
        
        // Lấy thống kê theo tháng
        $monthlyStats = $this->getMonthlyStats();
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/dashboard/index', [
            'stats' => $stats,
            'recentActivities' => $recentActivities,
            'topPerformers' => $topPerformers,
            'pendingCerts' => $pendingCerts,
            'expiringCerts' => $expiringCerts,
            'monthlyStats' => $monthlyStats,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Trang quản trị'
        ]);
    }

    /**
     * Lấy thống kê tổng quan
     */
    private function getOverviewStats() {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM tblTrain_Employee WHERE Status = 1) as total_employees,
                (SELECT COUNT(*) FROM tblTrain_Subject WHERE Status = 1) as total_subjects,
                (SELECT COUNT(*) FROM tblTrain_Certificate WHERE Status = 1) as total_certificates,
                (SELECT COUNT(*) FROM tblTrain_Certificate WHERE Status = 0) as pending_certificates,
                (SELECT COUNT(*) FROM tblTrain_Exam WHERE CompletedAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as exams_this_month,
                (SELECT AVG(Score) FROM tblTrain_Exam WHERE Passed = 1 AND CompletedAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as avg_score,
                (SELECT COUNT(DISTINCT EmployeeID) FROM tblTrain_Exam WHERE CompletedAt >= DATE_SUB(NOW(), INTERVAL 7 DAY)) as active_learners";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy hoạt động gần đây
     */
    private function getRecentActivities() {
        $sql = "SELECT 
                'exam' as type,
                CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                s.Title as subject_name,
                ex.Score as score,
                ex.Passed as passed,
                ex.CompletedAt as created_at
                FROM tblTrain_Exam ex
                JOIN tblTrain_Employee e ON ex.EmployeeID = e.ID
                JOIN tblTrain_Subject s ON ex.SubjectID = s.ID
                WHERE ex.CompletedAt IS NOT NULL
                
                UNION ALL
                
                SELECT 
                'certificate' as type,
                CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                s.Title as subject_name,
                NULL as score,
                NULL as passed,
                c.IssuedAt as created_at
                FROM tblTrain_Certificate c
                JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                
                ORDER BY created_at DESC
                LIMIT 15";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy top performers
     */
    private function getTopPerformers() {
        $sql = "SELECT 
                CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                e.Email,
                COUNT(DISTINCT c.ID) as cert_count,
                AVG(ex.Score) as avg_score,
                COUNT(DISTINCT ex.SubjectID) as completed_subjects
                FROM tblTrain_Employee e
                LEFT JOIN tblTrain_Certificate c ON e.ID = c.EmployeeID AND c.Status = 1
                LEFT JOIN tblTrain_Exam ex ON e.ID = ex.EmployeeID AND ex.Passed = 1
                WHERE e.Status = 1
                GROUP BY e.ID
                HAVING cert_count > 0
                ORDER BY cert_count DESC, avg_score DESC
                LIMIT 10";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thống kê theo tháng (6 tháng gần nhất)
     */
    private function getMonthlyStats() {
        $sql = "SELECT 
                DATE_FORMAT(month_date, '%Y-%m') as month,
                COALESCE(cert.cert_count, 0) as certificates,
                COALESCE(exam.exam_count, 0) as exams,
                COALESCE(exam.avg_score, 0) as avg_score
                FROM (
                    SELECT DATE_FORMAT(DATE_SUB(NOW(), INTERVAL n MONTH), '%Y-%m-01') as month_date
                    FROM (
                        SELECT 0 as n UNION SELECT 1 UNION SELECT 2 UNION 
                        SELECT 3 UNION SELECT 4 UNION SELECT 5
                    ) numbers
                ) months
                LEFT JOIN (
                    SELECT 
                        DATE_FORMAT(IssuedAt, '%Y-%m') as month,
                        COUNT(*) as cert_count
                    FROM tblTrain_Certificate
                    WHERE IssuedAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    GROUP BY DATE_FORMAT(IssuedAt, '%Y-%m')
                ) cert ON DATE_FORMAT(months.month_date, '%Y-%m') = cert.month
                LEFT JOIN (
                    SELECT 
                        DATE_FORMAT(CompletedAt, '%Y-%m') as month,
                        COUNT(*) as exam_count,
                        AVG(Score) as avg_score
                    FROM tblTrain_Exam
                    WHERE CompletedAt >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
                    AND CompletedAt IS NOT NULL
                    GROUP BY DATE_FORMAT(CompletedAt, '%Y-%m')
                ) exam ON DATE_FORMAT(months.month_date, '%Y-%m') = exam.month
                ORDER BY months.month_date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy sidebar data cho admin
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