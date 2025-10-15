<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ExamModel.php';
require_once __DIR__ . '/../models/SubjectModel.php';
require_once __DIR__ . '/../models/CompletionModel.php';
require_once __DIR__ . '/../models/CertificateModel.php';

class ExamController extends Controller {
    private $examModel;
    private $subjectModel;

    public function __construct() {
        parent::__construct();
        $this->examModel = new ExamModel();
        $this->subjectModel = new SubjectModel();
    }

    /**
     * Show exam page
     */
    public function show($id) {
        $employeeId = $this->checkAuth();
        
        // Get subject with questions
        $subject = $this->subjectModel->getWithProgress($id, $employeeId);
        
        if (!$subject) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        // Check if employee can take exam
        $canTakeExam = $this->examModel->canTakeExam($employeeId, $id);
        
        if (!$canTakeExam['allowed']) {
            $_SESSION['error'] = $canTakeExam['message'];
            $this->redirect('subject/' . $id);
            return;
        }

        // Get previous attempts
        $attempts = $this->examModel->getAttempts($employeeId, $id);
        
        $this->render('exam/take', [
            'subject' => $subject,
            'attempts' => $attempts,
            'canTakeExam' => $canTakeExam
        ]);
    }

    /**
     * Start new exam (API)
     * FIXED: Data transformation for main.js compatibility
     */
    public function start($id) {
        // Clean output buffer
        if (ob_get_level()) {
            ob_clean();
        }
        
        try {
            $employeeId = $this->checkAuth();
        } catch (Exception $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized', 'message' => 'Please login']);
            exit;
        }
        
        try {
            // Validate subject exists
            $subject = $this->subjectModel->find($id);
            if (!$subject) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Khóa học không tồn tại']);
                exit;
            }

            // Check if can take exam
            $canTake = $this->examModel->canTakeExam($employeeId, $id);
            if (!$canTake['allowed']) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => $canTake['message']]);
                exit;
            }

            // Create exam record
            $examId = $this->examModel->startExam($employeeId, $id);
            
            // Get questions with shuffled answers
            $questions = $this->examModel->getExamQuestions($id);
            
            // DEBUG: Log questions structure
            error_log("Questions fetched: " . count($questions));
            if (!empty($questions)) {
                error_log("First question structure: " . json_encode($questions[0]));
            }
            
            if (empty($questions)) {
                http_response_code(500);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Không tìm thấy câu hỏi cho bài kiểm tra']);
                exit;
            }

            // CRITICAL FIX: Transform data to match main.js expected format
            // main.js expects: { id: ..., text: ... } (lowercase)
            // backend returns: { ID: ..., AnswerText: ... } (uppercase)
            $transformedQuestions = array_map(function($question) {
                return [
                    'ID' => $question['ID'],
                    'QuestionText' => $question['QuestionText'],
                    'QuestionType' => $question['QuestionType'] ?? 'single',
                    'Score' => $question['Score'] ?? 1,
                    'answers' => array_map(function($answer) {
                        return [
                            'id' => (int)$answer['ID'],              // lowercase 'id'
                            'text' => $answer['AnswerText']          // 'text' instead of 'AnswerText'
                        ];
                    }, $question['answers'] ?? [])
                ];
            }, $questions);

            // Log transformed data for debugging
            error_log("Transformed first question: " . json_encode($transformedQuestions[0]));

            // Log exam start
            error_log("Exam started successfully: ID=$examId, Employee=$employeeId, Subject=$id, Questions=" . count($transformedQuestions));

            // Return JSON response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'exam_id' => (string)$examId,
                'questions' => $transformedQuestions,
                'total_questions' => count($transformedQuestions),
                'time_limit' => (int)($subject['ExamTimeLimit'] ?? 30)
            ]);
            exit;

        } catch (Exception $e) {
            error_log("Exam start error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Không thể bắt đầu bài kiểm tra',
                'message' => $e->getMessage(),
                'debug' => getenv('APP_ENV') === 'development' ? $e->getTraceAsString() : null
            ]);
            exit;
        }
    }

    /**
     * Check single answer (API)
     */
    public function checkAnswer() {
        if (ob_get_level()) {
            ob_clean();
        }
        
        try {
            $employeeId = $this->checkAuth();
        } catch (Exception $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $data = $this->getPostJson();
        
        if (!isset($data['question_id'], $data['answer_id'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Thiếu thông tin câu hỏi hoặc câu trả lời']);
            exit;
        }

        try {
            $isCorrect = $this->examModel->checkAnswerCorrectness(
                $data['question_id'], 
                $data['answer_id']
            );

            header('Content-Type: application/json');
            echo json_encode([
                'is_correct' => $isCorrect,
                'message' => $isCorrect ? 'Chính xác!' : 'Không chính xác'
            ]);
            exit;

        } catch (Exception $e) {
            error_log("Check answer error: " . $e->getMessage());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Không thể kiểm tra câu trả lời',
                'message' => $e->getMessage()
            ]);
            exit;
        }
    }

    /**
     * Submit exam (API)
     * FIXED: Proper JSON response handling
     */
    public function submit($id) {
        if (ob_get_level()) {
            ob_clean();
        }
        
        try {
            $employeeId = $this->checkAuth();
        } catch (Exception $e) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Unauthorized']);
            exit;
        }
        
        $data = $this->getPostJson();
        
        if (!isset($data['exam_id'], $data['answers'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Thiếu thông tin bài thi']);
            exit;
        }

        if (!is_array($data['answers']) || empty($data['answers'])) {
            http_response_code(400);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Bạn chưa trả lời câu hỏi nào']);
            exit;
        }

        try {
            // Verify exam belongs to employee
            $exam = $this->examModel->find($data['exam_id']);
            if (!$exam || $exam['EmployeeID'] != $employeeId) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Bài thi không hợp lệ']);
                exit;
            }

            // Check if already submitted
            if ($exam['EndTime'] !== null) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Bài thi đã được nộp trước đó']);
                exit;
            }

            // Process exam submission
            $result = $this->examModel->submitExam($data['exam_id'], $data['answers']);
            
            if (!$result) {
                throw new Exception('Failed to process exam submission');
            }

            // Log submission
            error_log("Exam submitted: ID={$data['exam_id']}, Score={$result['score']}, Passed=" . ($result['passed'] ? 'Yes' : 'No'));

            // Return success response
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'passed' => $result['passed'],
                'score' => $result['score'],
                'required' => $result['required'],
                'correct_answers' => $result['correct_count'],
                'total_questions' => $result['total_questions'],
                'percentage' => round(($result['correct_count'] / $result['total_questions']) * 100, 1),
                'message' => $result['passed'] 
                    ? 'Chúc mừng! Bạn đã vượt qua bài kiểm tra.' 
                    : 'Bạn chưa đạt. Hãy học lại và thử lại sau.',
                'exam_id' => $data['exam_id']
            ]);
            exit;

        } catch (Exception $e) {
            error_log("Exam submit error: " . $e->getMessage());
            error_log("Stack trace: " . $e->getTraceAsString());
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode([
                'error' => 'Không thể nộp bài thi',
                'message' => $e->getMessage(),
                'details' => getenv('APP_ENV') === 'development' ? $e->getTraceAsString() : null
            ]);
            exit;
        }
    }

    /**
     * View exam results
     */
    public function results($examId) {
        $employeeId = $this->checkAuth();
        
        $exam = $this->examModel->getExamWithDetails($examId, $employeeId);
        
        if (!$exam) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        // Security check
        if ($exam['EmployeeID'] != $employeeId) {
            http_response_code(403);
            $this->render('error/403');
            return;
        }

        // Get detailed results
        $details = $this->examModel->getExamDetails($examId);

        $this->render('exam/results', [
            'exam' => $exam,
            'details' => $details,
            'pageTitle' => 'Kết quả bài kiểm tra'
        ]);
    }

    /**
     * Retry exam (create new attempt)
     */
    public function retry($subjectId) {
        $employeeId = $this->checkAuth();
        
        // Check if can retry
        $canTake = $this->examModel->canTakeExam($employeeId, $subjectId);
        
        if (!$canTake['allowed']) {
            $_SESSION['error'] = $canTake['message'];
            $this->redirect('subject/' . $subjectId);
            return;
        }

        // Redirect to subject page to start new exam
        $this->redirect('subject/' . $subjectId);
    }

    /**
     * Get exam statistics for employee
     */
    public function statistics() {
        $employeeId = $this->checkAuth();
        
        $sql = "SELECT 
                COUNT(*) as total_attempts,
                SUM(CASE WHEN Passed = 1 THEN 1 ELSE 0 END) as passed_count,
                AVG(Score) as avg_score,
                MAX(Score) as best_score,
                MIN(Score) as lowest_score
                FROM " . TBL_EXAM . "
                WHERE EmployeeID = ? AND Status = 'completed'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$employeeId]);
        $stats = $stmt->fetch(PDO::FETCH_ASSOC);
        
        header('Content-Type: application/json');
        echo json_encode([
            'success' => true,
            'statistics' => $stats
        ]);
    }
}