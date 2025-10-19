<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/SubjectModel.php';
require_once __DIR__ . '/../models/WatchLogModel.php';

class SubjectController extends Controller {
    private $employeeModel;
    private $subjectModel;
    private $watchLogModel;

    public function __construct() {
        parent::__construct();
        $this->employeeModel = new EmployeeModel();
        $this->subjectModel = new SubjectModel();
        $this->watchLogModel = new WatchLogModel();
    }

    public function index() {
        $employeeId = $this->checkAuth();
        $employee = $this->employeeModel->findById($employeeId);

        $subjects = $this->subjectModel->getAssignedSubjectsByKnowledgeGroup($employeeId);
        
        foreach ($subjects as &$subject) {
            $subject['Name'] = $subject['Title'] ?? 'Khóa học';
        }
        unset($subject);
        
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('subject/list', [
            'employee' => $employee,
            'subjects' => $subjects,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Danh sách khóa học'
        ]);
    }

    public function detail($id) {
        $employeeId = $this->checkAuth();
        $employee = $this->employeeModel->findById($employeeId);

        $subject = $this->subjectModel->getWithProgress($id, $employeeId);
        
        if (!$subject) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        // Lấy ExpireDate từ getAssignedSubjectsByKnowledgeGroup
        $assignedSubjects = $this->subjectModel->getAssignedSubjectsByKnowledgeGroup($employeeId);
        foreach ($assignedSubjects as $assigned) {
            if ($assigned['ID'] == $id) {
                $subject['ExpireDate'] = $assigned['ExpireDate'];
                break;
            }
        }

        // Lấy danh sách tài liệu
        $stmt = $this->db->prepare("
            SELECT Title, FileURL 
            FROM tblTrain_Material 
            WHERE SubjectID = ? AND Status = 1
        ");
        $stmt->execute([$id]);
        $lessons = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $subject['Name'] = $subject['Title'] ?? 'Khóa học';
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('subject/detail', [
            'employee' => $employee,
            'subject' => $subject,
            'lessons' => $lessons,
            'pageTitle' => $subject['Title'] ?? 'Chi tiết khóa học',
            'sidebarData' => $sidebarData
        ]);
    }

    public function search() {
        // Allow both guests and authenticated users to search
        $employeeId = null;
        try {
            $employeeId = $this->checkAuth();
        } catch (Exception $e) {
            // not logged in
        }

        $q = trim($_GET['q'] ?? '');
        $results = [];
        if ($q !== '') {
            $results = $this->subjectModel->searchSubjects($q, 50);
        }

        $sidebarData = [];
        if ($employeeId) {
            $sidebarData = $this->getSidebarData($employeeId);
            $employee = $this->employeeModel->findById($employeeId);
        } else {
            $employee = null;
        }

        $this->render('search/index', [
            'employee' => $employee,
            'results' => $results,
            'q' => $q,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Tìm kiếm: ' . ($q ?: '')
        ]);
    }

    /**
     * Theo dõi tiến độ video (API endpoint)
     */
    public function trackProgress() {
        if (ob_get_level()) {
            ob_clean();
        }
        
        try {
            $employeeId = $this->checkAuth();
        } catch (Exception $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized', 'message' => 'Vui lòng đăng nhập']);
            exit;
        }

        $data = $this->getPostJson();
        
        if (!isset($data['lesson_id']) || !isset($data['watched_seconds']) || !isset($data['event'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Thiếu các trường bắt buộc',
                'required' => ['lesson_id', 'watched_seconds', 'event']
            ]);
            exit;
        }

        $lessonId = filter_var($data['lesson_id'], FILTER_VALIDATE_INT);
        $watchedSeconds = filter_var($data['watched_seconds'], FILTER_VALIDATE_INT);
        
        if ($lessonId === false || $watchedSeconds === false) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Kiểu dữ liệu không hợp lệ']);
            exit;
        }

        // Kiểm tra quyền truy cập vào khóa học
        $subject = $this->subjectModel->getWithProgress($lessonId, $employeeId);
        if (!$subject) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Không có quyền truy cập vào khóa học này']);
            exit;
        }

        try {
            $this->watchLogModel->logWatch(
                $employeeId,
                $lessonId,
                $watchedSeconds,
                $data['current_time'] ?? 0,
                $data['event']
            );

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
            error_log('Lỗi theo dõi tiến độ: ' . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Không thể theo dõi tiến độ',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Đánh dấu khóa học hoàn thành (API endpoint)
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
            return $this->json(['error' => 'Thiếu lesson_id']);
        }

        $lessonId = filter_var($data['lesson_id'], FILTER_VALIDATE_INT);
        if ($lessonId === false) {
            http_response_code(400);
            return $this->json(['error' => 'lesson_id không hợp lệ']);
        }

        $subject = $this->subjectModel->getWithProgress($lessonId, $employeeId);
        if (!$subject) {
            http_response_code(404);
            return $this->json(['error' => 'Không tìm thấy khóa học']);
        }

        // Kiểm tra xem có yêu cầu bài kiểm tra không
        $questionCount = $subject['QuestionCount'] ?? 0;
        if ($questionCount > 0) {
            return $this->json([
                'status' => 'exam_required',
                'question_count' => $questionCount,
                'message' => 'Bạn cần làm bài kiểm tra để hoàn thành khóa học'
            ]);
        }

        // Kiểm tra phần trăm xem video tối thiểu
        $minWatchPercent = isset($subject['MinWatchPercent']) && $subject['MinWatchPercent'] > 0 
            ? $subject['MinWatchPercent'] / 100 
            : 0.9;
        
        if ($subject['Duration'] > 0 && $subject['watched_seconds'] / $subject['Duration'] < $minWatchPercent) {
            http_response_code(400);
            return $this->json([
                'error' => 'Chưa xem đủ thời lượng video yêu cầu',
                'required_percent' => $minWatchPercent * 100,
                'current_percent' => $subject['Duration'] > 0 
                    ? round(($subject['watched_seconds'] / $subject['Duration']) * 100, 2) 
                    : 0
            ]);
        }

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
            error_log('Lỗi đánh dấu hoàn thành: ' . $e->getMessage());
            http_response_code(500);
            return $this->json([
                'error' => 'Không thể đánh dấu hoàn thành',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Lấy tiến độ khóa học (API endpoint)
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
            return $this->json(['error' => 'ID khóa học không hợp lệ']);
        }

        $subject = $this->subjectModel->getWithProgress($subjectId, $employeeId);
        if (!$subject) {
            http_response_code(404);
            return $this->json(['error' => 'Không tìm thấy khóa học']);
        }

        return $this->json([
            'subject_id' => $subjectId,
            'watched_seconds' => $subject['watched_seconds'] ?? 0,
            'duration' => $subject['Duration'] ?? 0,
            'progress_percent' => $subject['Duration'] > 0 
                ? round(($subject['watched_seconds'] / $subject['Duration']) * 100, 2) 
                : 0,
            'min_watch_percent' => $subject['MinWatchPercent'] ?? 90,
            'last_exam_score' => $subject['last_exam_score'] ?? null,
            'passed' => $subject['last_exam_passed'] ?? false
        ]);
    }

    /**
     * Lấy dữ liệu thanh bên
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

    /**
     * Lấy câu hỏi bài kiểm tra (hỗ trợ cũ)
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
?>