<?php
/**
 * Admin Report Controller
 * Báo cáo và thống kê
 */
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/CertificateModel.php';
require_once __DIR__ . '/../models/ExamModel.php';
require_once __DIR__ . '/../models/SubjectModel.php';

class AdminReportController extends Controller {
    private $employeeModel;
    private $certificateModel;
    private $examModel;
    private $subjectModel;

    public function __construct() {
        parent::__construct();
        $this->employeeModel = new EmployeeModel();
        $this->certificateModel = new CertificateModel();
        $this->examModel = new ExamModel();
        $this->subjectModel = new SubjectModel();
    }

    /**
     * Kiểm tra quyền admin
     */
    private function checkAdminAuth() {
        $employeeId = $this->checkAuth();
        $employee = $this->employeeModel->findById($employeeId);
        
        if (!in_array($employee['PositionID'], [5, 6])) {
            http_response_code(403);
            $this->render('error/403');
            exit;
        }
        
        return $employeeId;
    }

    /**
     * Trang báo cáo tổng quan
     */
    public function index() {
        $adminId = $this->checkAdminAuth();
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Tổng quan
        $overview = $this->getOverview($dateFrom, $dateTo);
        
        // Báo cáo theo phòng ban
        $byDepartment = $this->getReportByDepartment($dateFrom, $dateTo);
        
        // Báo cáo theo khóa học
        $bySubject = $this->getReportBySubject($dateFrom, $dateTo);
        
        // Xu hướng theo thời gian
        $trends = $this->getTrends($dateFrom, $dateTo);
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/report/index', [
            'overview' => $overview,
            'byDepartment' => $byDepartment,
            'bySubject' => $bySubject,
            'trends' => $trends,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Báo cáo tổng hợp'
        ]);
    }

    /**
     * Báo cáo tỷ lệ hoàn thành
     */
    public function completion() {
        $adminId = $this->checkAdminAuth();
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Tỷ lệ hoàn thành theo phòng ban
        $completionByDept = $this->getCompletionByDepartment($dateFrom, $dateTo);
        
        // Tỷ lệ hoàn thành theo khóa học
        $completionBySubject = $this->getCompletionBySubject($dateFrom, $dateTo);
        
        // Chi tiết nhân viên
        $employeeDetails = $this->getEmployeeCompletionDetails($dateFrom, $dateTo);
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/report/completion', [
            'completionByDept' => $completionByDept,
            'completionBySubject' => $completionBySubject,
            'employeeDetails' => $employeeDetails,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Báo cáo tỷ lệ hoàn thành'
        ]);
    }

    /**
     * Báo cáo kết quả thi
     */
    public function exams() {
        $adminId = $this->checkAdminAuth();
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Thống kê bài thi
        $examStats = $this->getExamStats($dateFrom, $dateTo);
        
        // Phân bổ điểm
        $scoreDistribution = $this->getScoreDistribution($dateFrom, $dateTo);
        
        // Kết quả theo khóa học
        $examsBySubject = $this->getExamsBySubject($dateFrom, $dateTo);
        
        // Danh sách bài thi chi tiết
        $examDetails = $this->getExamDetails($dateFrom, $dateTo);
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/report/exams', [
            'examStats' => $examStats,
            'scoreDistribution' => $scoreDistribution,
            'examsBySubject' => $examsBySubject,
            'examDetails' => $examDetails,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Báo cáo kết quả thi'
        ]);
    }

    /**
     * Báo cáo chứng chỉ
     */
    public function certificates() {
        $adminId = $this->checkAdminAuth();
        
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        // Thống kê chứng chỉ
        $certStats = $this->getCertificateStats($dateFrom, $dateTo);
        
        // Chứng chỉ theo phòng ban
        $certsByDept = $this->certificateModel->getCertificatesByDepartment();
        
        // Chứng chỉ theo khóa học
        $certsBySubject = $this->getCertsBySubject($dateFrom, $dateTo);
        
        // Danh sách chi tiết
        $certificates = $this->certificateModel->getCertificatesForReport($dateFrom, $dateTo);
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/report/certificates', [
            'certStats' => $certStats,
            'certsByDept' => $certsByDept,
            'certsBySubject' => $certsBySubject,
            'certificates' => $certificates,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Báo cáo chứng chỉ'
        ]);
    }

    // ========== PRIVATE METHODS ==========

    private function getOverview($dateFrom, $dateTo) {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM tblTrain_Employee WHERE Status = 1) as total_employees,
                (SELECT COUNT(*) FROM tblTrain_Subject WHERE Status = 1) as total_subjects,
                (SELECT COUNT(*) FROM tblTrain_Exam WHERE DATE(CompletedAt) BETWEEN ? AND ?) as total_exams,
                (SELECT COUNT(*) FROM tblTrain_Certificate WHERE DATE(IssuedAt) BETWEEN ? AND ?) as total_certificates,
                (SELECT AVG(Score) FROM tblTrain_Exam WHERE DATE(CompletedAt) BETWEEN ? AND ? AND Passed = 1) as avg_score,
                (SELECT COUNT(*) FROM tblTrain_Exam WHERE DATE(CompletedAt) BETWEEN ? AND ? AND Passed = 1) as passed_exams,
                (SELECT COUNT(*) FROM tblTrain_Exam WHERE DATE(CompletedAt) BETWEEN ? AND ? AND Passed = 0) as failed_exams";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo, $dateFrom, $dateTo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getReportByDepartment($dateFrom, $dateTo) {
        $sql = "SELECT 
                d.DepartmentName,
                COUNT(DISTINCT e.ID) as employee_count,
                COUNT(DISTINCT ex.ID) as exam_count,
                COUNT(DISTINCT c.ID) as cert_count,
                AVG(ex.Score) as avg_score
                FROM tblTrain_Employee e
                LEFT JOIN tblTrain_Position p ON e.PositionID = p.ID
                LEFT JOIN tblTrain_Department d ON p.DepartmentID = d.ID
                LEFT JOIN tblTrain_Exam ex ON e.ID = ex.EmployeeID 
                    AND DATE(ex.CompletedAt) BETWEEN ? AND ?
                LEFT JOIN tblTrain_Certificate c ON e.ID = c.EmployeeID 
                    AND DATE(c.IssuedAt) BETWEEN ? AND ?
                WHERE e.Status = 1
                GROUP BY d.DepartmentName
                ORDER BY cert_count DESC, exam_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getReportBySubject($dateFrom, $dateTo) {
        $sql = "SELECT 
                s.Title,
                COUNT(DISTINCT ex.EmployeeID) as learner_count,
                COUNT(ex.ID) as exam_count,
                AVG(ex.Score) as avg_score,
                COUNT(DISTINCT c.ID) as cert_count
                FROM tblTrain_Subject s
                LEFT JOIN tblTrain_Exam ex ON s.ID = ex.SubjectID 
                    AND DATE(ex.CompletedAt) BETWEEN ? AND ?
                LEFT JOIN tblTrain_Certificate c ON s.ID = c.SubjectID 
                    AND DATE(c.IssuedAt) BETWEEN ? AND ?
                WHERE s.Status = 1
                GROUP BY s.ID
                ORDER BY learner_count DESC, cert_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getTrends($dateFrom, $dateTo) {
        $sql = "SELECT 
                DATE(activity_date) as date,
                SUM(CASE WHEN type = 'exam' THEN 1 ELSE 0 END) as exams,
                SUM(CASE WHEN type = 'cert' THEN 1 ELSE 0 END) as certificates
                FROM (
                    SELECT CompletedAt as activity_date, 'exam' as type
                    FROM tblTrain_Exam
                    WHERE DATE(CompletedAt) BETWEEN ? AND ?
                    
                    UNION ALL
                    
                    SELECT IssuedAt as activity_date, 'cert' as type
                    FROM tblTrain_Certificate
                    WHERE DATE(IssuedAt) BETWEEN ? AND ?
                ) activities
                GROUP BY DATE(activity_date)
                ORDER BY date ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo, $dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCompletionByDepartment($dateFrom, $dateTo) {
        $sql = "SELECT 
                d.DepartmentName,
                COUNT(DISTINCT e.ID) as total_employees,
                COUNT(DISTINCT CASE WHEN ex.Passed = 1 THEN e.ID END) as completed_employees,
                COUNT(DISTINCT CASE WHEN ex.Passed = 1 THEN ex.SubjectID END) as completed_subjects,
                ROUND(COUNT(DISTINCT CASE WHEN ex.Passed = 1 THEN e.ID END) * 100.0 / COUNT(DISTINCT e.ID), 2) as completion_rate
                FROM tblTrain_Employee e
                LEFT JOIN tblTrain_Position p ON e.PositionID = p.ID
                LEFT JOIN tblTrain_Department d ON p.DepartmentID = d.ID
                LEFT JOIN tblTrain_Exam ex ON e.ID = ex.EmployeeID 
                    AND DATE(ex.CompletedAt) BETWEEN ? AND ?
                WHERE e.Status = 1
                GROUP BY d.DepartmentName
                ORDER BY completion_rate DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCompletionBySubject($dateFrom, $dateTo) {
        $sql = "SELECT 
                s.Title,
                COUNT(DISTINCT a.PositionID) as required_positions,
                COUNT(DISTINCT ex.EmployeeID) as started_count,
                COUNT(DISTINCT CASE WHEN ex.Passed = 1 THEN ex.EmployeeID END) as completed_count,
                ROUND(COUNT(DISTINCT CASE WHEN ex.Passed = 1 THEN ex.EmployeeID END) * 100.0 / 
                      NULLIF(COUNT(DISTINCT ex.EmployeeID), 0), 2) as completion_rate
                FROM tblTrain_Subject s
                LEFT JOIN tblTrain_Assign a ON s.ID = a.SubjectID
                LEFT JOIN tblTrain_Exam ex ON s.ID = ex.SubjectID 
                    AND DATE(ex.CompletedAt) BETWEEN ? AND ?
                WHERE s.Status = 1
                GROUP BY s.ID
                ORDER BY completion_rate DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getEmployeeCompletionDetails($dateFrom, $dateTo) {
        $sql = "SELECT 
                CONCAT(e.FirstName, ' ', e.LastName) as employee_name,
                e.Email,
                d.DepartmentName,
                p.PositionName,
                COUNT(DISTINCT s.ID) as required_subjects,
                COUNT(DISTINCT CASE WHEN ex.Passed = 1 THEN ex.SubjectID END) as completed_subjects,
                COUNT(DISTINCT c.ID) as certificates,
                ROUND(COUNT(DISTINCT CASE WHEN ex.Passed = 1 THEN ex.SubjectID END) * 100.0 / 
                      NULLIF(COUNT(DISTINCT s.ID), 0), 2) as completion_rate
                FROM tblTrain_Employee e
                LEFT JOIN tblTrain_Position p ON e.PositionID = p.ID
                LEFT JOIN tblTrain_Department d ON p.DepartmentID = d.ID
                LEFT JOIN tblTrain_Assign a ON p.ID = a.PositionID
                LEFT JOIN tblTrain_Subject s ON a.SubjectID = s.ID AND s.Status = 1
                LEFT JOIN tblTrain_Exam ex ON (e.ID = ex.EmployeeID AND s.ID = ex.SubjectID)
                LEFT JOIN tblTrain_Certificate c ON (e.ID = c.EmployeeID AND DATE(c.IssuedAt) BETWEEN ? AND ?)
                WHERE e.Status = 1
                GROUP BY e.ID
                ORDER BY completion_rate DESC, certificates DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getExamStats($dateFrom, $dateTo) {
        $sql = "SELECT 
                COUNT(*) as total_exams,
                COUNT(DISTINCT EmployeeID) as total_students,
                COUNT(DISTINCT SubjectID) as total_subjects,
                AVG(Score) as avg_score,
                MAX(Score) as max_score,
                MIN(Score) as min_score,
                SUM(CASE WHEN Passed = 1 THEN 1 ELSE 0 END) as passed_count,
                SUM(CASE WHEN Passed = 0 THEN 1 ELSE 0 END) as failed_count,
                ROUND(SUM(CASE WHEN Passed = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as pass_rate
                FROM tblTrain_Exam
                WHERE DATE(CompletedAt) BETWEEN ? AND ?
                AND CompletedAt IS NOT NULL";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getScoreDistribution($dateFrom, $dateTo) {
        $sql = "SELECT 
                CASE 
                    WHEN Score >= 90 THEN '90-100'
                    WHEN Score >= 80 THEN '80-89'
                    WHEN Score >= 70 THEN '70-79'
                    WHEN Score >= 60 THEN '60-69'
                    ELSE '<60'
                END as score_range,
                COUNT(*) as count
                FROM tblTrain_Exam
                WHERE DATE(CompletedAt) BETWEEN ? AND ?
                AND CompletedAt IS NOT NULL
                GROUP BY score_range
                ORDER BY score_range DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getExamsBySubject($dateFrom, $dateTo) {
        $sql = "SELECT 
                s.Title,
                COUNT(*) as exam_count,
                COUNT(DISTINCT e.EmployeeID) as student_count,
                AVG(e.Score) as avg_score,
                SUM(CASE WHEN e.Passed = 1 THEN 1 ELSE 0 END) as passed_count,
                ROUND(SUM(CASE WHEN e.Passed = 1 THEN 1 ELSE 0 END) * 100.0 / COUNT(*), 2) as pass_rate
                FROM tblTrain_Exam e
                JOIN tblTrain_Subject s ON e.SubjectID = s.ID
                WHERE DATE(e.CompletedAt) BETWEEN ? AND ?
                AND e.CompletedAt IS NOT NULL
                GROUP BY s.ID
                ORDER BY exam_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getExamDetails($dateFrom, $dateTo) {
        $sql = "SELECT 
                e.ID,
                CONCAT(emp.FirstName, ' ', emp.LastName) as employee_name,
                emp.Email,
                s.Title as subject_name,
                e.Score,
                e.Passed,
                e.TotalQuestions,
                e.CorrectAnswers,
                e.CompletedAt
                FROM tblTrain_Exam e
                JOIN tblTrain_Employee emp ON e.EmployeeID = emp.ID
                JOIN tblTrain_Subject s ON e.SubjectID = s.ID
                WHERE DATE(e.CompletedAt) BETWEEN ? AND ?
                AND e.CompletedAt IS NOT NULL
                ORDER BY e.CompletedAt DESC
                LIMIT 100";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function getCertificateStats($dateFrom, $dateTo) {
        $sql = "SELECT 
                COUNT(*) as total_certificates,
                COUNT(DISTINCT EmployeeID) as certified_employees,
                COUNT(DISTINCT SubjectID) as certified_subjects,
                SUM(CASE WHEN Status = 0 THEN 1 ELSE 0 END) as pending_count,
                SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as approved_count,
                SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as revoked_count,
                SUM(CASE WHEN Status = 1 AND (ExpiresAt IS NULL OR ExpiresAt > NOW()) THEN 1 ELSE 0 END) as active_count
                FROM tblTrain_Certificate
                WHERE DATE(IssuedAt) BETWEEN ? AND ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function getCertsBySubject($dateFrom, $dateTo) {
        $sql = "SELECT 
                s.Title,
                COUNT(*) as cert_count,
                COUNT(DISTINCT c.EmployeeID) as employee_count,
                SUM(CASE WHEN c.Status = 1 THEN 1 ELSE 0 END) as active_count
                FROM tblTrain_Certificate c
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                WHERE DATE(c.IssuedAt) BETWEEN ? AND ?
                GROUP BY s.ID
                ORDER BY cert_count DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$dateFrom, $dateTo]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

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
