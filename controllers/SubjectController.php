<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/SubjectModel.php';
require_once __DIR__ . '/../models/WatchLogModel.php';

class SubjectController extends Controller {
    private $subjectModel;
    private $watchLogModel;

    public function __construct() {
        parent::__construct(); // IMPORTANT: Call parent constructor first
        $this->subjectModel = new SubjectModel();
        $this->watchLogModel = new WatchLogModel();
    }

    public function index() {
        $employeeId = $this->checkAuth();
        $subjects = $this->subjectModel->getAssignedSubjects($employeeId);
        
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('subject/list', [
            'subjects' => $subjects,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Danh sách khóa học'
        ]);
    }

    public function detail($id) {
        $employeeId = $this->checkAuth();
        $subject = $this->subjectModel->getWithProgress($id, $employeeId);
        
        if (!$subject) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('subject/detail', [
            'subject' => $subject,
            'pageTitle' => $subject['Title'] ?? 'Chi tiết khóa học',
            'sidebarData' => $sidebarData
        ]);
    }

    /**
     * Track video progress (AJAX endpoint)
     * FIXED: Added proper authentication and error handling
     */
    public function trackProgress() {
        // CRITICAL: Clean output buffer to prevent any extra output
        if (ob_get_level()) {
            ob_clean();
        }
        
        // CRITICAL FIX: Check authentication first
        try {
            $employeeId = $this->checkAuth();
        } catch (Exception $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized', 'message' => 'Please login']);
            exit;
        }

        // Get POST data
        $data = $this->getPostJson();
        
        // Validate required fields
        if (!isset($data['lesson_id']) || !isset($data['watched_seconds']) || !isset($data['event'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Missing required fields',
                'required' => ['lesson_id', 'watched_seconds', 'event']
            ]);
            exit;
        }

        // Validate data types
        $lessonId = filter_var($data['lesson_id'], FILTER_VALIDATE_INT);
        $watchedSeconds = filter_var($data['watched_seconds'], FILTER_VALIDATE_INT);
        
        if ($lessonId === false || $watchedSeconds === false) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Invalid data types']);
            exit;
        }

        // Verify employee has access to this subject
        $subject = $this->subjectModel->getWithProgress($lessonId, $employeeId);
        if (!$subject) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Access denied to this subject']);
            exit;
        }

        try {
            // Log the watch progress
            $this->watchLogModel->logWatch(
                $employeeId,
                $lessonId,
                $watchedSeconds,
                $data['current_time'] ?? 0,
                $data['event']
            );

            // Return success with updated progress
            header('Content-Type: application/json');
            echo json_encode([
                'status' => 'ok',
                'watched_seconds' => $watchedSeconds,
                'progress_percent' => $subject['Duration'] > 0 
                    ? round(($watchedSeconds / $subject['Duration']) * 100, 2) 
                    : 0
            ]);
            exit;
        } catch (Exception $e) {
            error_log('Track progress error: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Failed to track progress',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Mark lesson as complete (AJAX endpoint)
     * FIXED: Added proper validation
     */
    public function complete() {
        try {
            $employeeId = $this->checkAuth();
        } catch (Exception $e) {
            http_response_code(401);
            return $this->json(['error' => 'Unauthorized']);
        }

        $data = $this->getPostJson();
        
        if (!isset($data['lesson_id'])) {
            http_response_code(400);
            return $this->json(['error' => 'Missing lesson_id']);
        }

        $lessonId = filter_var($data['lesson_id'], FILTER_VALIDATE_INT);
        if ($lessonId === false) {
            http_response_code(400);
            return $this->json(['error' => 'Invalid lesson_id']);
        }

        // Get subject to check if exam required
        $subject = $this->subjectModel->find($lessonId);
        if (!$subject) {
            http_response_code(404);
            return $this->json(['error' => 'Subject not found']);
        }

        // Check if exam is required
        $questionCount = $subject['QuestionCount'] ?? 0;
        
        if ($questionCount > 0) {
            return $this->json([
                'status' => 'exam_required',
                'question_count' => $questionCount,
                'message' => 'Bạn cần làm bài kiểm tra để hoàn thành khóa học'
            ]);
        }

        // No exam required, mark as complete
        try {
            $stmt = $this->db->prepare("
                INSERT INTO " . TBL_COMPLETION . " 
                (EmployeeID, SubjectID, CompletedAt, Method)
                VALUES (?, ?, NOW(), 'video')
                ON DUPLICATE KEY UPDATE 
                CompletedAt = NOW()
            ");
            $stmt->execute([$employeeId, $lessonId]);
            
            return $this->json([
                'status' => 'completed',
                'message' => 'Đã đánh dấu hoàn thành khóa học'
            ]);
        } catch (Exception $e) {
            error_log('Complete error: ' . $e->getMessage());
            http_response_code(500);
            return $this->json([
                'error' => 'Failed to mark complete',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Get subject progress (AJAX endpoint)
     */
    public function getProgress($id) {
        try {
            $employeeId = $this->checkAuth();
        } catch (Exception $e) {
            http_response_code(401);
            return $this->json(['error' => 'Unauthorized']);
        }

        $subjectId = filter_var($id, FILTER_VALIDATE_INT);
        if ($subjectId === false) {
            http_response_code(400);
            return $this->json(['error' => 'Invalid subject ID']);
        }

        $subject = $this->subjectModel->getWithProgress($subjectId, $employeeId);
        
        if (!$subject) {
            http_response_code(404);
            return $this->json(['error' => 'Subject not found']);
        }

        return $this->json([
            'subject_id' => $subjectId,
            'watched_seconds' => $subject['watched_seconds'] ?? 0,
            'duration' => $subject['Duration'] ?? 0,
            'progress_percent' => $subject['Duration'] > 0 
                ? round(($subject['watched_seconds'] / $subject['Duration']) * 100, 2) 
                : 0,
            'last_exam_score' => $subject['last_exam_score'] ?? null,
            'passed' => $subject['last_exam_passed'] ?? false
        ]);
    }

    /**
     * Get sidebar data
     */
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
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $progress = [
                    'total_subjects' => (int)($result['total_subjects'] ?? 0),
                    'completed_subjects' => (int)($result['completed_subjects'] ?? 0),
                    'total_certificates' => (int)($result['total_certificates'] ?? 0)
                ];
            }
        } catch (Exception $e) {
            error_log('getSidebarData error: ' . $e->getMessage());
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
            error_log('Get position error: ' . $e->getMessage());
            $position = ['PositionName' => 'Nhân viên'];
        }

        return [
            'progress' => $progress,
            'position' => $position['PositionName'] ?? 'Nhân viên'
        ];
    }

    /**
     * Get exam questions (for legacy support)
     */
    public function getExamQuestions($id) {
        try {
            $employeeId = $this->checkAuth();
        } catch (Exception $e) {
            return ['success' => false, 'error' => 'Unauthorized'];
        }

        $questions = $this->subjectModel->getExamQuestions($id);
        return ['success' => true, 'questions' => $questions ?? []];
    }
}