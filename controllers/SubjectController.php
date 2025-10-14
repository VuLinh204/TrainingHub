<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/SubjectModel.php';
require_once __DIR__ . '/../models/WatchLogModel.php';

class SubjectController extends Controller {
    private $subjectModel;
    private $watchLogModel;

    public function __construct() {
        $this->subjectModel = new SubjectModel();
        $this->watchLogModel = new WatchLogModel();
        $this->db = $GLOBALS['db']; // Ensure $this->db is set
    }

    public function index() {
        $employeeId = $this->checkAuth();
        $subjects = $this->subjectModel->getAssignedSubjects($employeeId);
        
        // Fetch sidebar data
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('subject/list', [
            'subjects' => $subjects,
            'sidebarData' => $sidebarData
        ]);
    }

    public function detail($id) {
        $employeeId = $this->checkAuth();
        $subject = $this->subjectModel->getWithProgress($id, $employeeId);
        
        if (!$subject) {
            $this->redirect('subjects');
        }

        // Fetch sidebar data
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('subject/detail', [
            'subject' => $subject,
            'pageTitle' => $subject['Title'] ?? $subject['Name'] ?? 'Chi tiết khóa học',
            'sidebarData' => $sidebarData
        ]);
    }

    public function trackProgress() {
        $employeeId = $this->checkAuth();
        $data = $this->getPostJson();
        
        if (!isset($data['lesson_id'], $data['watched_seconds'], $data['event'])) {
            http_response_code(400);
            return $this->json(['error' => 'Missing required fields']);
        }

        $this->watchLogModel->logWatch(
            $employeeId,
            $data['lesson_id'],
            $data['watched_seconds'],
            $data['current_time'] ?? 0,
            $data['event']
        );

        return $this->json(['status' => 'ok']);
    }

    public function complete() {
        $employeeId = $this->checkAuth();
        $data = $this->getPostJson();
        
        if (!isset($data['lesson_id'])) {
            http_response_code(400);
            return $this->json(['error' => 'Missing lesson_id']);
        }

        // Get subject to check if exam required
        $subject = $this->subjectModel->find($data['lesson_id']);
        if (!$subject) {
            http_response_code(404);
            return $this->json(['error' => 'Subject not found']);
        }

        // If no exam required, mark as complete
        if (($subject['QuestionCount'] ?? 0) == 0) {
            $stmt = $this->db->prepare("
                INSERT INTO " . TBL_COMPLETION . " 
                (EmployeeID, SubjectID, CompletedAt, Method)
                VALUES (?, ?, NOW(), 'video')
            ");
            $stmt->execute([$employeeId, $data['lesson_id']]);
            return $this->json(['status' => 'completed']);
        }

        return $this->json([
            'status' => 'exam_required',
            'question_count' => $subject['QuestionCount'] ?? 0
        ]);
    }

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
            $progress = [
                'total_subjects' => $progressResult['total_subjects'] ?? 0,
                'completed_subjects' => $progressResult['completed_subjects'] ?? 0,
                'total_certificates' => $progressResult['total_certificates'] ?? 0
            ];
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
}