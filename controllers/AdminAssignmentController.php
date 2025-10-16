<?php
/**
 * Admin Assignment Controller
 * Quản lý phân công khóa học cho vị trí
 */
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/SubjectModel.php';
require_once __DIR__ . '/../models/EmployeeModel.php';

class AdminAssignmentController extends Controller {
    private $subjectModel;
    private $employeeModel;

    public function __construct() {
        parent::__construct();
        $this->subjectModel = new SubjectModel();
        $this->employeeModel = new EmployeeModel();
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
     * Danh sách phân công
     */
    public function index() {
        $adminId = $this->checkAdminAuth();
        
        $positionFilter = $_GET['position'] ?? '';
        $subjectFilter = $_GET['subject'] ?? '';
        
        // Lấy danh sách phân công
        $assignments = $this->getAssignments($positionFilter, $subjectFilter);
        
        // Lấy danh sách positions và subjects cho dropdown
        $positions = $this->getPositions();
        $subjects = $this->getSubjects();
        
        // Thống kê
        $stats = $this->getAssignmentStats();
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/assignment/index', [
            'assignments' => $assignments,
            'positions' => $positions,
            'subjects' => $subjects,
            'stats' => $stats,
            'filters' => [
                'position' => $positionFilter,
                'subject' => $subjectFilter
            ],
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Quản lý phân công'
        ]);
    }

    /**
     * Tạo phân công mới
     */
    public function create() {
        $adminId = $this->checkAdminAuth();
        
        $positionId = $_POST['position_id'] ?? null;
        $subjectId = $_POST['subject_id'] ?? null;
        $isRequired = isset($_POST['is_required']) ? 1 : 0;
        $deadline = $_POST['deadline'] ?? null;
        
        if (!$positionId || !$subjectId) {
            $_SESSION['error'] = 'Vui lòng chọn đầy đủ thông tin';
            $this->redirect('admin/assignments');
            return;
        }
        
        try {
            // Kiểm tra đã tồn tại chưa
            $existing = $this->checkAssignmentExists($positionId, $subjectId);
            
            if ($existing) {
                throw new Exception('Phân công này đã tồn tại');
            }
            
            $data = [
                'PositionID' => $positionId,
                'SubjectID' => $subjectId,
                'IsRequired' => $isRequired,
                'Deadline' => $deadline,
                'AssignedBy' => $adminId,
                'AssignedAt' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->subjectModel->insert('tblTrain_Assign', $data);
            
            if ($result) {
                // Gửi thông báo cho nhân viên có vị trí này
                $this->notifyEmployees($positionId, $subjectId);
                
                $_SESSION['success'] = 'Phân công thành công';
            } else {
                throw new Exception('Không thể tạo phân công');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        $this->redirect('admin/assignments');
    }

    /**
     * Xóa phân công
     */
    public function delete() {
        $adminId = $this->checkAdminAuth();
        
        $assignmentId = $_POST['assignment_id'] ?? null;
        
        if (!$assignmentId) {
            $_SESSION['error'] = 'Không tìm thấy phân công';
            $this->redirect('admin/assignments');
            return;
        }
        
        try {
            // Kiểm tra có dữ liệu liên quan không
            $assignment = $this->getAssignmentById($assignmentId);
            
            if (!$assignment) {
                throw new Exception('Phân công không tồn tại');
            }
            
            $hasData = $this->checkHasRelatedData($assignment['PositionID'], $assignment['SubjectID']);
            
            if ($hasData) {
                throw new Exception('Không thể xóa vì đã có nhân viên học tập hoặc thi');
            }
            
            $result = $this->subjectModel->delete('tblTrain_Assign', 'ID = ?', [$assignmentId]);
            
            if ($result) {
                $_SESSION['success'] = 'Đã xóa phân công';
            } else {
                throw new Exception('Không thể xóa phân công');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        $this->redirect('admin/assignments');
    }

    /**
     * Lấy danh sách phân công
     */
    private function getAssignments($positionFilter, $subjectFilter) {
        $where = [];
        $params = [];
        
        if (!empty($positionFilter)) {
            $where[] = "a.PositionID = ?";
            $params[] = $positionFilter;
        }
        
        if (!empty($subjectFilter)) {
            $where[] = "a.SubjectID = ?";
            $params[] = $subjectFilter;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT a.*,
                p.PositionName,
                s.Title as SubjectName,
                s.Duration,
                s.PassingScore,
                CONCAT(e.FirstName, ' ', e.LastName) as AssignedByName,
                COUNT(DISTINCT emp.ID) as affected_employees,
                COUNT(DISTINCT ex.EmployeeID) as completed_count
                FROM tblTrain_Assign a
                JOIN tblTrain_Position p ON a.PositionID = p.ID
                JOIN tblTrain_Subject s ON a.SubjectID = s.ID
                LEFT JOIN tblTrain_Employee e ON a.AssignedBy = e.ID
                LEFT JOIN tblTrain_Employee emp ON emp.PositionID = a.PositionID AND emp.Status = 1
                LEFT JOIN tblTrain_Exam ex ON (ex.SubjectID = a.SubjectID AND ex.EmployeeID = emp.ID AND ex.Passed = 1)
                {$whereClause}
                GROUP BY a.ID
                ORDER BY a.AssignedAt DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
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
     * Lấy danh sách khóa học
     */
    private function getSubjects() {
        $sql = "SELECT ID, Title FROM tblTrain_Subject WHERE Status = 1 ORDER BY Title";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thống kê phân công
     */
    private function getAssignmentStats() {
        $sql = "SELECT 
                COUNT(*) as total_assignments,
                COUNT(DISTINCT PositionID) as total_positions,
                COUNT(DISTINCT SubjectID) as total_subjects,
                SUM(CASE WHEN IsRequired = 1 THEN 1 ELSE 0 END) as required_count,
                (SELECT COUNT(DISTINCT e.ID) 
                 FROM tblTrain_Employee e 
                 INNER JOIN tblTrain_Assign a ON e.PositionID = a.PositionID 
                 WHERE e.Status = 1) as affected_employees
                FROM tblTrain_Assign";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra phân công đã tồn tại
     */
    private function checkAssignmentExists($positionId, $subjectId) {
        $sql = "SELECT ID FROM tblTrain_Assign WHERE PositionID = ? AND SubjectID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$positionId, $subjectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lấy thông tin phân công
     */
    private function getAssignmentById($assignmentId) {
        $sql = "SELECT * FROM tblTrain_Assign WHERE ID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$assignmentId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra có dữ liệu liên quan
     */
    private function checkHasRelatedData($positionId, $subjectId) {
        $sql = "SELECT COUNT(*) as count
                FROM tblTrain_Exam ex
                INNER JOIN tblTrain_Employee emp ON ex.EmployeeID = emp.ID
                WHERE emp.PositionID = ? AND ex.SubjectID = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$positionId, $subjectId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['count'] > 0;
    }

    /**
     * Gửi thông báo cho nhân viên
     */
    private function notifyEmployees($positionId, $subjectId) {
        try {
            // Lấy thông tin khóa học
            $subject = $this->subjectModel->find($subjectId);
            
            if (!$subject) {
                return;
            }
            
            // Lấy danh sách nhân viên có vị trí này
            $sql = "SELECT ID, Email, CONCAT(FirstName, ' ', LastName) as FullName
                    FROM tblTrain_Employee
                    WHERE PositionID = ? AND Status = 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$positionId]);
            $employees = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Tạo thông báo cho từng nhân viên
            foreach ($employees as $employee) {
                $notifSql = "INSERT INTO tblTrain_Notification 
                            (EmployeeID, Title, Message, Link, Type, CreatedAt)
                            VALUES (?, ?, ?, ?, 'info', NOW())";
                
                $title = 'Khóa học mới được phân công';
                $message = 'Bạn được phân công khóa học: ' . $subject['Title'];
                $link = '/subject/' . $subjectId;
                
                $notifStmt = $this->db->prepare($notifSql);
                $notifStmt->execute([$employee['ID'], $title, $message, $link]);
            }
        } catch (Exception $e) {
            error_log('Failed to notify employees: ' . $e->getMessage());
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