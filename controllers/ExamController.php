<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/ExamModel.php';

class ExamController extends Controller {
    private $examModel;

    public function __construct() {
        $this->examModel = new ExamModel();
    }

    public function start() {
        $employeeId = $this->checkAuth();
        $data = $this->getPostJson();
        
        if (!isset($data['subject_id'])) {
            http_response_code(400);
            return $this->json(['error' => 'Missing subject_id']);
        }

        $examId = $this->examModel->startExam($employeeId, $data['subject_id']);
        
        // Get questions for exam
        $stmt = $this->db->prepare("
            SELECT q.*, GROUP_CONCAT(
                JSON_OBJECT(
                    'id', a.ID,
                    'text', a.AnswerText
                )
            ) as answers
            FROM " . TBL_QUESTION . " q
            LEFT JOIN " . TBL_ANSWERS . " a ON a.QuestionID = q.ID
            WHERE q.SubjectID = ?
            GROUP BY q.ID
            ORDER BY RAND()
        ");
        $stmt->execute([$data['subject_id']]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Parse answers JSON
        foreach ($questions as &$q) {
            $q['answers'] = json_decode('[' . $q['answers'] . ']', true);
        }

        return $this->json([
            'exam_id' => $examId,
            'questions' => $questions
        ]);
    }

    public function checkAnswer() {
        $employeeId = $this->checkAuth();
        $data = $this->getPostJson();
        
        if (!isset($data['question_id'], $data['answer_id'])) {
            http_response_code(400);
            return $this->json(['error' => 'Missing fields']);
        }

        $stmt = $this->db->prepare("
            SELECT IsCorrect 
            FROM " . TBL_ANSWERS . "
            WHERE ID = ? AND QuestionID = ?
        ");
        $stmt->execute([$data['answer_id'], $data['question_id']]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $this->json([
            'is_correct' => $result && $result['IsCorrect'] == 1
        ]);
    }

    public function submit() {
        $employeeId = $this->checkAuth();
        $data = $this->getPostJson();
        
        if (!isset($data['exam_id'], $data['answers'])) {
            http_response_code(400);
            return $this->json(['error' => 'Missing fields']);
        }

        $result = $this->examModel->submitExam($data['exam_id'], $data['answers']);
        if (!$result) {
            http_response_code(404);
            return $this->json(['error' => 'Exam not found']);
        }

        return $this->json($result);
    }
}
