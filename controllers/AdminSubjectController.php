<?php
/**
 * Admin Subject Controller
 * Quản lý khóa học
 */
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/SubjectModel.php';
require_once __DIR__ . '/../models/EmployeeModel.php';

class AdminSubjectController extends Controller {
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
     * Danh sách khóa học
     */
    public function index() {
        $adminId = $this->checkAdminAuth();
        
        $status = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';
        
        $subjects = $this->getSubjectsList($status, $search);
        $stats = $this->getSubjectsStats();
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/subject/index', [
            'subjects' => $subjects,
            'stats' => $stats,
            'currentStatus' => $status,
            'searchQuery' => $search,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Quản lý khóa học'
        ]);
    }

    /**
     * Form tạo khóa học mới
     */
    public function createForm() {
        $adminId = $this->checkAdminAuth();
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/subject/create', [
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Tạo khóa học mới'
        ]);
    }

    /**
     * Xử lý tạo khóa học
     */
    public function create() {
        $adminId = $this->checkAdminAuth();
        
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $duration = $_POST['duration'] ?? 0;
        $content = $_POST['content'] ?? '';
        $passingScore = $_POST['passing_score'] ?? 70;
        
        if (empty($title)) {
            $_SESSION['error'] = 'Vui lòng nhập tên khóa học';
            $this->redirect('admin/subjects/create');
            return;
        }
        
        try {
            $data = [
                'Title' => $title,
                'Description' => $description,
                'Duration' => $duration,
                'Content' => $content,
                'PassingScore' => $passingScore,
                'Status' => 1,
                'CreatedBy' => $adminId,
                'CreatedAt' => date('Y-m-d H:i:s'),
                'UpdatedAt' => date('Y-m-d H:i:s')
            ];
            
            $subjectId = $this->subjectModel->insert('tblTrain_Subject', $data);
            
            if ($subjectId) {
                $_SESSION['success'] = 'Tạo khóa học thành công';
                $this->redirect('admin/subjects');
            } else {
                throw new Exception('Không thể tạo khóa học');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('admin/subjects/create');
        }
    }

    /**
     * Form chỉnh sửa khóa học
     */
    public function editForm($subjectId) {
        $adminId = $this->checkAdminAuth();
        
        $subject = $this->subjectModel->find($subjectId);
        
        if (!$subject) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/subject/edit', [
            'subject' => $subject,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Chỉnh sửa khóa học'
        ]);
    }

    /**
     * Xử lý cập nhật khóa học
     */
    public function update($subjectId) {
        $adminId = $this->checkAdminAuth();
        
        $subject = $this->subjectModel->find($subjectId);
        
        if (!$subject) {
            $_SESSION['error'] = 'Khóa học không tồn tại';
            $this->redirect('admin/subjects');
            return;
        }
        
        $title = $_POST['title'] ?? '';
        $description = $_POST['description'] ?? '';
        $duration = $_POST['duration'] ?? 0;
        $content = $_POST['content'] ?? '';
        $passingScore = $_POST['passing_score'] ?? 70;
        $status = $_POST['status'] ?? 1;
        
        if (empty($title)) {
            $_SESSION['error'] = 'Vui lòng nhập tên khóa học';
            $this->redirect('admin/subjects/' . $subjectId . '/edit');
            return;
        }
        
        try {
            $data = [
                'Title' => $title,
                'Description' => $description,
                'Duration' => $duration,
                'Content' => $content,
                'PassingScore' => $passingScore,
                'Status' => $status,
                'UpdatedAt' => date('Y-m-d H:i:s')
            ];
            
            $result = $this->subjectModel->update('tblTrain_Subject', $data, 'ID = ?', [$subjectId]);
            
            if ($result) {
                $_SESSION['success'] = 'Cập nhật khóa học thành công';
                $this->redirect('admin/subjects');
            } else {
                throw new Exception('Không thể cập nhật khóa học');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
            $this->redirect('admin/subjects/' . $subjectId . '/edit');
        }
    }

    /**
     * Xóa khóa học
     */
    public function delete($subjectId) {
        $adminId = $this->checkAdminAuth();
        
        try {
            $subject = $this->subjectModel->find($subjectId);
            
            if (!$subject) {
                throw new Exception('Khóa học không tồn tại');
            }
            
            // Kiểm tra có bài thi hoặc chứng chỉ liên quan không
            $hasExams = $this->checkHasExams($subjectId);
            $hasCerts = $this->checkHasCertificates($subjectId);
            
            if ($hasExams || $hasCerts) {
                // Soft delete - chỉ thay đổi status
                $result = $this->subjectModel->update(
                    'tblTrain_Subject',
                    ['Status' => 0, 'UpdatedAt' => date('Y-m-d H:i:s')],
                    'ID = ?',
                    [$subjectId]
                );
                $_SESSION['success'] = 'Đã vô hiệu hóa khóa học';
            } else {
                // Hard delete nếu không có dữ liệu liên quan
                $result = $this->subjectModel->delete('tblTrain_Subject', 'ID = ?', [$subjectId]);
                $_SESSION['success'] = 'Đã xóa khóa học';
            }
            
            if (!$result) {
                throw new Exception('Không thể xóa khóa học');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        $this->redirect('admin/subjects');
    }

    /**
     * Lấy danh sách khóa học
     */
    private function getSubjectsList($status, $search) {
        $where = [];
        $params = [];
        
        if ($status === 'active') {
            $where[] = "s.Status = 1";
        } elseif ($status === 'inactive') {
            $where[] = "s.Status = 0";
        }
        
        if (!empty($search)) {
            $where[] = "(s.Title LIKE ? OR s.Description LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT s.*,
                CONCAT(e.FirstName, ' ', e.LastName) as CreatorName,
                COUNT(DISTINCT ex.EmployeeID) as learner_count,
                COUNT(DISTINCT c.ID) as cert_count,
                AVG(ex.Score) as avg_score
                FROM tblTrain_Subject s
                LEFT JOIN tblTrain_Employee e ON s.CreatedBy = e.ID
                LEFT JOIN tblTrain_Exam ex ON s.ID = ex.SubjectID AND ex.CompletedAt IS NOT NULL
                LEFT JOIN tblTrain_Certificate c ON s.ID = c.SubjectID AND c.Status = 1
                {$whereClause}
                GROUP BY s.ID
                ORDER BY s.CreatedAt DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Thống kê khóa học
     */
    private function getSubjectsStats() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN Status = 0 THEN 1 ELSE 0 END) as inactive,
                (SELECT COUNT(DISTINCT EmployeeID) FROM tblTrain_Exam WHERE CompletedAt IS NOT NULL) as total_learners,
                (SELECT AVG(Score) FROM tblTrain_Exam WHERE Passed = 1) as avg_score
                FROM tblTrain_Subject";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Kiểm tra có bài thi không
     */
    private function checkHasExams($subjectId) {
        $sql = "SELECT COUNT(*) as count FROM tblTrain_Exam WHERE SubjectID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$subjectId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
    }

    /**
     * Kiểm tra có chứng chỉ không
     */
    private function checkHasCertificates($subjectId) {
        $sql = "SELECT COUNT(*) as count FROM tblTrain_Certificate WHERE SubjectID = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$subjectId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['count'] > 0;
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