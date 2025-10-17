<?php
/**
 * Admin Employee Controller
 * Quản lý nhân viên
 */
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/CertificateModel.php';
require_once __DIR__ . '/../models/ExamModel.php';

class AdminEmployeeController extends Controller {
    private $employeeModel;
    private $certificateModel;
    private $examModel;

    public function __construct() {
        parent::__construct();
        $this->employeeModel = new EmployeeModel();
        $this->certificateModel = new CertificateModel();
        $this->examModel = new ExamModel();
    }

    /**
     * Kiểm tra quyền admin
     */
    private function checkAdminAuth() {
        $employeeId = $this->checkAuth();
        
        $employee = $this->employeeModel->findById($employeeId);
        
        // Dựa theo Role, không phụ thuộc vào ID chức danh
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
     * Danh sách nhân viên
     */
    public function index() {
        $adminId = $this->checkAdminAuth();
        
        // Lấy filter
        $department = $_GET['department'] ?? '';
        $position = $_GET['position'] ?? '';
        $status = $_GET['status'] ?? 'active';
        $search = $_GET['search'] ?? '';
        
        // Lấy danh sách nhân viên
        $employees = $this->getEmployeesList($position, $status, $search);
        
        // Lấy departments và positions cho filter
        $departments = $this->getDepartments();
        $positions = $this->getPositions();
        
        // Thống kê
        $stats = $this->getEmployeesStats();
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/employees/index', [
            'employees' => $employees,
            'departments' => $departments,
            'positions' => $positions,
            'stats' => $stats,
            'filters' => [
                'department' => $department,
                'position' => $position,
                'status' => $status,
                'search' => $search
            ],
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Quản lý nhân viên'
        ]);
    }

    /**
     * Chi tiết nhân viên
     */
    public function show($employeeId) {
        $adminId = $this->checkAdminAuth();
        
        $employee = $this->employeeModel->findById($employeeId);
        
        if (!$employee) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }
        
        // Lấy thông tin bổ sung
        $certificates = $this->certificateModel->getEmployeeCertificates($employeeId);
        $examHistory = $this->getExamHistory($employeeId);
        $learningProgress = $this->getLearningProgress($employeeId);
        $stats = $this->getEmployeeStats($employeeId);
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/employees/show', [
            'targetEmployee' => $employee,
            'certificates' => $certificates,
            'examHistory' => $examHistory,
            'learningProgress' => $learningProgress,
            'stats' => $stats,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Chi tiết nhân viên - ' . $employee['FirstName'] . ' ' . $employee['LastName']
        ]);
    }

    /**
     * Tiến độ học tập của nhân viên
     */
    public function progress($employeeId) {
        $adminId = $this->checkAdminAuth();
        
        $employee = $this->employeeModel->findById($employeeId);
        
        if (!$employee) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }
        
        // Lấy tiến độ chi tiết
        $subjectProgress = $this->getDetailedProgress($employeeId);
        $monthlyActivity = $this->getMonthlyActivity($employeeId);
        $recentActivity = $this->getRecentActivity($employeeId);
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/employees/progress', [
            'targetEmployee' => $employee,
            'subjectProgress' => $subjectProgress,
            'monthlyActivity' => $monthlyActivity,
            'recentActivity' => $recentActivity,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Tiến độ học tập - ' . $employee['FirstName']
        ]);
    }

    /**
     * Lấy danh sách nhân viên với filter
     */
    private function getEmployeesList($position, $status, $search) {
        $where = [];
        $params = [];
        
        if ($status === 'active') {
            $where[] = "e.Status = 1";
        } elseif ($status === 'inactive') {
            $where[] = "e.Status = 0";
        }
        
        if (!empty($position)) {
            $where[] = "e.PositionID = ?";
            $params[] = $position;
        }
        
        if (!empty($search)) {
            $where[] = "(CONCAT(e.FirstName, ' ', e.LastName) LIKE ? OR e.Email LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT e.*, 
                p.PositionName,
                COUNT(DISTINCT c.ID) as cert_count,
                COUNT(DISTINCT ex.SubjectID) as completed_subjects,
                AVG(ex.Score) as avg_score,
                MAX(ex.CompletedAt) as last_activity
                FROM tblTrain_Employee e
                LEFT JOIN tblTrain_Position p ON e.PositionID = p.ID
                LEFT JOIN tblTrain_Certificate c ON e.ID = c.EmployeeID AND c.Status = 1
                LEFT JOIN tblTrain_Exam ex ON e.ID = ex.EmployeeID AND ex.Passed = 1
                {$whereClause}
                GROUP BY e.ID
                ORDER BY e.FirstName, e.LastName";

        $stmt = $this->db->prepare($sql);
        if (!$stmt) {
            $error = $this->db->errorInfo();
            die("SQL prepare error: " . $error[2]);
        }

        if (!$stmt->execute($params)) {
            $error = $stmt->errorInfo();
            die("SQL execute error: " . $error[2]);
        }

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy danh sách phòng ban
     */
    private function getDepartments() {
        $sql = "SELECT ID, DepartmentName FROM tblTrain_Department ORDER BY DepartmentName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Lấy danh sách vị trí
     */
    private function getPositions() {
        $sql = "SELECT ID, PositionName FROM tblTrain_Position ORDER BY PositionName";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thống kê nhân viên tổng quan
     */
    private function getEmployeesStats() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN Status = 0 THEN 1 ELSE 0 END) as inactive,
                (SELECT COUNT(DISTINCT EmployeeID) FROM tblTrain_Certificate WHERE Status = 1) as with_certificates,
                (SELECT COUNT(DISTINCT EmployeeID) FROM tblTrain_Exam WHERE CompletedAt >= DATE_SUB(NOW(), INTERVAL 30 DAY)) as active_learners
                FROM tblTrain_Employee";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Thống kê của một nhân viên
     */
    private function getEmployeeStats($employeeId) {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM tblTrain_Certificate WHERE EmployeeID = ? AND Status = 1) as certificates,
                (SELECT COUNT(DISTINCT SubjectID) FROM tblTrain_Exam WHERE EmployeeID = ? AND Passed = 1) as completed_subjects,
                (SELECT AVG(Score) FROM tblTrain_Exam WHERE EmployeeID = ? AND Passed = 1) as avg_score,
                (SELECT COUNT(*) FROM tblTrain_Exam WHERE EmployeeID = ?) as total_exams,
                (SELECT MAX(CompletedAt) FROM tblTrain_Exam WHERE EmployeeID = ?) as last_activity";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $employeeId, $employeeId, $employeeId, $employeeId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lịch sử thi của nhân viên
     */
    private function getExamHistory($employeeId) {
        $sql = "SELECT e.*, s.Title as SubjectName
                FROM tblTrain_Exam e
                JOIN tblTrain_Subject s ON e.SubjectID = s.ID
                WHERE e.EmployeeID = ? AND e.CompletedAt IS NOT NULL
                ORDER BY e.CompletedAt DESC
                LIMIT 20";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tiến độ học tập
     */
    private function getLearningProgress($employeeId) {
        $sql = "SELECT 
            kg.ID AS KnowledgeGroupID,
            kg.Name AS Title,
            SUM(s.Duration) AS Duration,
            MAX(CASE WHEN ex.Passed = 1 THEN 1 ELSE 0 END) AS completed,
            MAX(ex.Score) AS best_score,
            COUNT(ex.ID) AS attempts
        FROM tblTrain_KnowledgeGroup kg
        INNER JOIN tblTrain_Subject s ON s.KnowledgeGroupID = kg.ID
        INNER JOIN tblTrain_Assign a ON a.KnowledgeGroupID = kg.ID
        INNER JOIN tblTrain_Employee e ON e.PositionID = a.PositionID
        LEFT JOIN tblTrain_Exam ex ON ex.SubjectID = s.ID AND ex.EmployeeID = e.ID
        WHERE e.ID = ? AND s.Status = 1
        GROUP BY kg.ID, kg.Name
        ORDER BY kg.Name";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Tiến độ chi tiết theo môn
     */
    private function getDetailedProgress($employeeId) {
        return $this->getLearningProgress($employeeId);
    }

    /**
     * Hoạt động theo tháng
     */
    private function getMonthlyActivity($employeeId) {
        $sql = "SELECT 
                DATE_FORMAT(CompletedAt, '%Y-%m') as month,
                COUNT(*) as exam_count,
                AVG(Score) as avg_score
                FROM tblTrain_Exam
                WHERE EmployeeID = ? 
                AND CompletedAt >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                AND CompletedAt IS NOT NULL
                GROUP BY DATE_FORMAT(CompletedAt, '%Y-%m')
                ORDER BY month ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Hoạt động gần đây
     */
    private function getRecentActivity($employeeId) {
        $sql = "SELECT 'exam' as type,
                s.Title as subject_name,
                e.Score,
                e.Passed,
                e.CompletedAt as activity_date
                FROM tblTrain_Exam e
                JOIN tblTrain_Subject s ON e.SubjectID = s.ID
                WHERE e.EmployeeID = ? AND e.CompletedAt IS NOT NULL
                
                UNION ALL
                
                SELECT 'certificate' as type,
                s.Title as subject_name,
                NULL as Score,
                NULL as Passed,
                c.IssuedAt as activity_date
                FROM tblTrain_Certificate c
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                WHERE c.EmployeeID = ?
                
                ORDER BY activity_date DESC
                LIMIT 20";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId, $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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