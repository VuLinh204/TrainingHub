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

    public function show($id) {
        $employeeId = $this->checkAuth();
        
        $subject = $this->subjectModel->getWithProgress($id, $employeeId);
        
        if (!$subject) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        if (!isset($subject['SubjectName'])) {
            $subject['SubjectName'] = $subject['Title'] ?? 'Khóa học';
        }

        $canTakeExam = $this->examModel->canTakeExam($employeeId, $id);
        
        if (!$canTakeExam['allowed']) {
            $_SESSION['error'] = $canTakeExam['message'];
            $this->redirect('subject/' . $id);
            return;
        }

        $attempts = $this->examModel->getAttempts($employeeId, $id);
        
        $this->render('exam/take', [
            'subject' => $subject,
            'attempts' => $attempts,
            'canTakeExam' => $canTakeExam,
            'pageTitle' => 'Bài kiểm tra: ' . $subject['SubjectName']
        ]);
    }

    public function start($id) {
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
            $subject = $this->subjectModel->find($id);
            if (!$subject) {
                http_response_code(404);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Khóa học không tồn tại']);
                exit;
            }

            $canTake = $this->examModel->canTakeExam($employeeId, $id);
            if (!$canTake['allowed']) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => $canTake['message']]);
                exit;
            }

            // Xóa các phiên thi chưa hoàn thành
            $stmt = $this->db->prepare("
                DELETE FROM tblTrain_Exam 
                WHERE EmployeeID = ? AND SubjectID = ? AND Status = 'started'
            ");
            $stmt->execute([$employeeId, $id]);

            $examId = $this->examModel->startExam($employeeId, $id);
            $questions = $this->examModel->getExamQuestions($id);
            
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

            $transformedQuestions = array_map(function($question) {
                return [
                    'ID' => $question['ID'],
                    'QuestionText' => $question['QuestionText'],
                    'QuestionType' => $question['QuestionType'] ?? 'single',
                    'Score' => $question['Score'] ?? 1,
                    'answers' => array_map(function($answer) {
                        return [
                            'id' => (int)$answer['ID'],
                            'text' => $answer['AnswerText']
                        ];
                    }, $question['answers'] ?? [])
                ];
            }, $questions);

            error_log("Transformed first question: " . json_encode($transformedQuestions[0]));
            error_log("Exam started successfully: ID=$examId, Employee=$employeeId, Subject=$id, Questions=" . count($transformedQuestions));

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

        // Kiểm tra định dạng answers
        foreach ($data['answers'] as $answer) {
            if (!isset($answer['question_id'], $answer['answer_id']) ||
                !is_numeric($answer['question_id']) || !is_numeric($answer['answer_id'])) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Dữ liệu câu trả lời không hợp lệ']);
                exit;
            }
        }

        try {
            $exam = $this->examModel->find($data['exam_id']);
            if (!$exam || $exam['EmployeeID'] != $employeeId) {
                http_response_code(403);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Bài thi không hợp lệ']);
                exit;
            }

            if ($exam['EndTime'] !== null) {
                http_response_code(400);
                header('Content-Type: application/json');
                echo json_encode(['error' => 'Bài thi đã được nộp trước đó']);
                exit;
            }

            error_log("Submit payload: " . json_encode($data));

            $result = $this->examModel->submitExam($data['exam_id'], $data['answers']);
            
            if (!$result) {
                throw new Exception('Failed to process exam submission');
            }

            error_log("Exam submitted: ID={$data['exam_id']}, Score={$result['score']}, Passed=" . ($result['passed'] ? 'Yes' : 'No'));

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'passed' => $result['passed'],
                'score' => $result['score'],
                'required' => $result['required_percentage'] ?? 70,
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

    public function results($examId) {
        $employeeId = $this->checkAuth();
        
        $exam = $this->examModel->getExamWithDetails($examId, $employeeId);
        
        if (!$exam) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        if ($exam['EmployeeID'] != $employeeId) {
            http_response_code(403);
            $this->render('error/403');
            return;
        }

        $details = $this->examModel->getExamDetails($examId);

        $this->render('exam/results', [
            'exam' => $exam,
            'details' => $details,
            'pageTitle' => 'Kết quả bài kiểm tra'
        ]);
    }

    public function retry($subjectId) {
        $employeeId = $this->checkAuth();
        
        $canTake = $this->examModel->canTakeExam($employeeId, $subjectId);
        
        if (!$canTake['allowed']) {
            $_SESSION['error'] = $canTake['message'];
            $this->redirect('exam/' . $subjectId . '/start');
            return;
        }

        $this->redirect('exam/' . $subjectId . '/start');
    }

    public function statistics() {
        $employeeId = $this->checkAuth();
        
        $sql = "SELECT 
                COUNT(*) as total_attempts,
                SUM(CASE WHEN Passed = 1 THEN 1 ELSE 0 END) as passed_count,
                AVG(Score) as avg_score,
                MAX(Score) as best_score,
                MIN(Score) as lowest_score
                FROM tblTrain_Exam
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
?>