<?php
require_once __DIR__ . '/../core/Model.php';

class ExamModel extends Model {
    protected $table = TBL_EXAM;
    
    public function startExam($employeeId, $subjectId) {
        return $this->create([
            'EmployeeID' => $employeeId,
            'SubjectID' => $subjectId,
            'StartTime' => date('Y-m-d H:i:s'),
            'Score' => 0,
            'Passed' => 0
        ]);
    }

    public function submitExam($examId, $answers) {
        // Get exam and subject info
        $stmt = $this->db->prepare("
            SELECT e.*, s.RequiredCorrect, s.QuestionCount
            FROM {$this->table} e
            INNER JOIN " . TBL_SUBJECT . " s ON s.ID = e.SubjectID
            WHERE e.ID = ?
        ");
        $stmt->execute([$examId]);
        $exam = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$exam) return false;

        // Process answers
        $correctCount = 0;
        foreach ($answers as $answer) {
            $isCorrect = $this->checkAnswer($answer['question_id'], $answer['answer_id']);
            $this->saveAnswer($examId, $answer['question_id'], $answer['answer_id'], $isCorrect);
            if ($isCorrect) $correctCount++;
        }

        // Calculate score and passed status
        $score = ($correctCount / $exam['QuestionCount']) * 100;
        $passed = $correctCount >= $exam['RequiredCorrect'];

        // Update exam
        $this->update($examId, [
            'EndTime' => date('Y-m-d H:i:s'),
            'Score' => $score,
            'Passed' => $passed ? 1 : 0
        ]);

        // If passed, create completion and certificate
        if ($passed) {
            $this->markComplete($exam['EmployeeID'], $exam['SubjectID'], $examId);
            $this->createCertificate($exam['EmployeeID'], $exam['SubjectID']);
        }

        return [
            'passed' => $passed,
            'score' => $correctCount,
            'required' => $exam['RequiredCorrect']
        ];
    }

    private function checkAnswer($questionId, $answerId) {
        $stmt = $this->db->prepare("
            SELECT IsCorrect 
            FROM " . TBL_ANSWERS . "
            WHERE ID = ? AND QuestionID = ?
        ");
        $stmt->execute([$answerId, $questionId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['IsCorrect'] == 1;
    }

    private function saveAnswer($examId, $questionId, $answerId, $isCorrect) {
        $stmt = $this->db->prepare("
            INSERT INTO " . TBL_EXAMDETAIL . " 
            (ExamID, QuestionID, AnswerID, IsCorrect)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([$examId, $questionId, $answerId, $isCorrect ? 1 : 0]);
    }

    private function markComplete($employeeId, $subjectId, $examId) {
        $stmt = $this->db->prepare("
            INSERT INTO " . TBL_COMPLETION ."
            (EmployeeID, SubjectID, CompletedAt, Method, ExamID)
            VALUES (?, ?, NOW(), 'exam', ?)
        ");
        $stmt->execute([$employeeId, $subjectId, $examId]);
    }

    private function createCertificate($employeeId, $subjectId) {
        $stmt = $this->db->prepare("
            INSERT INTO " . TBL_CERTIFICATE . " 
            (EmployeeID, SubjectID, CertificateCode, IssuedAt, ExpiresAt)
            VALUES (?, ?, ?, NOW(), DATE_ADD(NOW(), INTERVAL 12 MONTH))
        ");
        $code = $this->generateCertificateCode($employeeId, $subjectId);
        $stmt->execute([$employeeId, $subjectId, $code]);
    }

    private function generateCertificateCode($employeeId, $subjectId) {
        return strtoupper(uniqid("CERT-{$employeeId}-{$subjectId}-"));
    }
}
