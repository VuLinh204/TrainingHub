<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/SubjectModel.php';
require_once __DIR__ . '/../models/CompletionModel.php';

class DashboardController extends Controller {
    protected $employeeModel;
    protected $subjectModel;
    protected $completionModel;
    
    public function __construct() {
        parent::__construct();
        $this->employeeModel = new EmployeeModel();
        $this->subjectModel = new SubjectModel();
        $this->completionModel = new CompletionModel();
    }
    
    public function index() {
        $employeeId = $this->checkAuth();
        $employee = $this->employeeModel->findById($employeeId);
        
        $assignedSubjects = $this->subjectModel->getAssignedSubjectsByKnowledgeGroup($employeeId);
        
        foreach ($assignedSubjects as &$subject) {
            // Đảm bảo có field Name từ Title
            $subject['Name'] = $subject['Title'] ?? 'Khóa học';
            $subject['is_completed'] = !empty($subject['is_completed']);
            $subject['has_certificate'] = !empty($subject['has_certificate']);
        }
        unset($subject);

        $sidebarData = $this->getSidebarData($employeeId);
        $completedSubjects = $this->employeeModel->getCompletedSubjects($employeeId);
        $certificates = $this->employeeModel->getCertificates($employeeId);
        
        // Tính % hoàn thành
        $totalAssigned = count($assignedSubjects);
        $totalCompleted = count($completedSubjects);
        $completionRate = $totalAssigned > 0 ? ($totalCompleted / $totalAssigned) * 100 : 0;
        
        $this->render('dashboard/index', [
            'employee' => $employee,
            'subjects' => $assignedSubjects,
            'assignedSubjects' => $assignedSubjects,
            'completedSubjects' => $completedSubjects,
            'certificates' => $certificates,
            'sidebarData' => $sidebarData,
            'completionRate' => $completionRate,
            'baseUrl' => dirname($_SERVER['PHP_SELF'])
        ]);
    }

    private function getSidebarData($employeeId) {
        $progress = [
            'total_subjects' => 0,
            'completed_subjects' => 0,
            'total_certificates' => 0
        ];

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT s.ID) as total_subjects,
                    SUM(CASE WHEN 
                        EXISTS (
                            SELECT 1 
                            FROM " . TBL_EXAM . " e 
                            WHERE e.SubjectID = s.ID 
                            AND e.EmployeeID = ? 
                            AND e.Score >= s.RequiredScore 
                            AND e.Passed = 1
                        )
                        THEN 1 ELSE 0 
                    END) as completed_subjects,
                    (SELECT COUNT(*) 
                     FROM " . TBL_CERTIFICATE . " c 
                     WHERE c.EmployeeID = ? 
                     AND c.Status = 1) as total_certificates
                FROM " . TBL_SUBJECT . " s
                INNER JOIN " . TBL_KNOWLEDGE_GROUP . " kg ON s.KnowledgeGroupID = kg.ID
                INNER JOIN " . TBL_ASSIGN . " a ON kg.ID = a.KnowledgeGroupID
                INNER JOIN " . TBL_POSITION . " p ON p.ID = a.PositionID
                WHERE p.ID = (
                    SELECT PositionID 
                    FROM " . TBL_EMPLOYEE . "
                    WHERE ID = ?
                )
                AND s.Status = 1
                AND s.DeletedAt IS NULL
                AND kg.Status = 1
                AND a.Status = 1
                AND (a.AssignDate <= CURRENT_DATE)
                AND (a.ExpireDate IS NULL OR a.ExpireDate >= CURRENT_DATE)
            ");
            $stmt->execute([$employeeId, $employeeId, $employeeId]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $progress = [
                    'total_subjects' => (int)($result['total_subjects'] ?? 0),
                    'completed_subjects' => (int)($result['completed_subjects'] ?? 0),
                    'total_certificates' => (int)($result['total_certificates'] ?? 0)
                ];
            }
        } catch (Exception $e) {
            error_log('Lỗi lấy dữ liệu thanh bên: ' . $e->getMessage());
        }

        try {
            $positionStmt = $this->db->prepare("
                SELECT p.PositionName 
                FROM " . TBL_EMPLOYEE . " e
                LEFT JOIN " . TBL_POSITION . " p ON e.PositionID = p.ID 
                WHERE e.ID = ?
            ");
            $positionStmt->execute([$employeeId]);
            $position = $positionStmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Lỗi lấy vị trí: ' . $e->getMessage());
            $position = ['PositionName' => 'Nhân viên'];
        }

        return [
            'progress' => $progress,
            'position' => $position['PositionName'] ?? 'Nhân viên'
        ];
    }
}
?>