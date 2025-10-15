<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ExamModel.php';
require_once __DIR__ . '/../models/SubjectModel.php';

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
     */
    public function start($id) {
        $employeeId = $this->checkAuth();
        
        try {
            // Validate subject exists
            $subject = $this->subjectModel->find($id);
            if (!$subject) {
                http_response_code(404);
                return $this->json(['error' => 'Khóa học không tồn tại']);
            }

            // Check if can take exam
            $canTake = $this->examModel->canTakeExam($employeeId, $id);
            if (!$canTake['allowed']) {
                http_response_code(403);
                return $this->json(['error' => $canTake['message']]);
            }

            // Create exam record
            $examId = $this->examModel->startExam($employeeId, $id);
            
            // Get questions with shuffled answers
            $questions = $this->examModel->getExamQuestions($id);
            
            if (empty($questions)) {
                http_response_code(500);
                return $this->json(['error' => 'Không tìm thấy câu hỏi cho bài kiểm tra']);
            }

            // Log exam start
            error_log("Exam started: ID=$examId, Employee=$employeeId, Subject=$id");

            return $this->json([
                'success' => true,
                'exam_id' => $examId,
                'questions' => $questions,
                'total_questions' => count($questions),
                'time_limit' => $subject['ExamTimeLimit'] ?? 30 // minutes
            ]);

        } catch (Exception $e) {
            error_log("Exam start error: " . $e->getMessage());
            http_response_code(500);
            return $this->json(['error' => 'Không thể bắt đầu bài kiểm tra: ' . $e->getMessage()]);
        }
    }

    /**
     * Check single answer (API)
     */
    public function checkAnswer() {
        $employeeId = $this->checkAuth();
        $data = $this->getPostJson();
        
        if (!isset($data['question_id'], $data['answer_id'])) {
            http_response_code(400);
            return $this->json(['error' => 'Thiếu thông tin câu hỏi hoặc câu trả lời']);
        }

        try {
            $isCorrect = $this->examModel->checkAnswerCorrectness(
                $data['question_id'], 
                $data['answer_id']
            );

            return $this->json([
                'is_correct' => $isCorrect,
                'message' => $isCorrect ? 'Chính xác!' : 'Không chính xác'
            ]);

        } catch (Exception $e) {
            error_log("Check answer error: " . $e->getMessage());
            http_response_code(500);
            return $this->json(['error' => 'Không thể kiểm tra câu trả lời: ' . $e->getMessage()]);
        }
    }

    /**
     * Submit exam (API)
     */
    public function submit($id) {
        $employeeId = $this->checkAuth();
        $data = $this->getPostJson();
        
        if (!isset($data['exam_id'], $data['answers'])) {
            http_response_code(400);
            return $this->json(['error' => 'Thiếu thông tin bài thi']);
        }

        if (!is_array($data['answers']) || empty($data['answers'])) {
            http_response_code(400);
            return $this->json(['error' => 'Bạn chưa trả lời câu hỏi nào']);
        }

        try {
            // Verify exam belongs to employee
            $exam = $this->examModel->find($data['exam_id']);
            if (!$exam || $exam['EmployeeID'] != $employeeId) {
                http_response_code(403);
                return $this->json(['error' => 'Bài thi không hợp lệ']);
            }

            // Check if already submitted
            if ($exam['EndTime'] !== null) {
                http_response_code(400);
                return $this->json(['error' => 'Bài thi đã được nộp trước đó']);
            }

            // Process exam submission
            $result = $this->examModel->submitExam($data['exam_id'], $data['answers']);
            
            if (!$result) {
                throw new Exception('Failed to process exam submission');
            }

            // Log submission
            error_log("Exam submitted: ID={$data['exam_id']}, Score={$result['score']}, Passed=" . ($result['passed'] ? 'Yes' : 'No'));

            return $this->json([
                'success' => true,
                'passed' => $result['passed'],
                'score' => $result['score'],
                'required' => $result['required'],
                'correct_answers' => $result['correct_count'],
                'total_questions' => $result['total_questions'],
                'percentage' => round(($result['correct_count'] / $result['total_questions']) * 100, 1),
                'message' => $result['passed'] 
                    ? 'Chúc mừng! Bạn đã vượt qua bài kiểm tra.' 
                    : 'Bạn chưa đạt. Hãy học lại và thử lại sau.'
            ]);

        } catch (Exception $e) {
            error_log("Exam submit error: " . $e->getMessage());
            http_response_code(500);
            return $this->json([
                'error' => 'Không thể nộp bài thi: ' . $e->getMessage(),
                'details' => $e->getMessage()
            ]);
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

        $this->render('exam/results', [
            'exam' => $exam,
            'details' => $this->examModel->getExamDetails($examId)
        ]);
    }
}