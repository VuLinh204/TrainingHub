<?php
require_once __DIR__ . '/../core/Model.php';

class ExamModel extends Model {
    protected $table = 'tblTrain_Exam';
    
    /**
     * Start a new exam
     */
    public function startExam($employeeId, $subjectId) {
        try {
            return $this->create([
                'EmployeeID' => $employeeId,
                'SubjectID' => $subjectId,
                'StartTime' => date('Y-m-d H:i:s'),
                'Score' => 0,
                'Passed' => 0,
                'Status' => 'started'
            ]);
        } catch (Exception $e) {
            error_log("startExam error: " . $e->getMessage() . " for employee $employeeId, subject $subjectId");
            throw new Exception("Failed to create exam record: " . $e->getMessage());
        }
    }

    /**
     * Get exam questions with shuffled answers
     */
    public function getExamQuestions($subjectId) {
        try {
            $sql = "SELECT q.ID, q.QuestionText, q.QuestionType, q.Score
                    FROM tblTrain_Question q
                    WHERE q.SubjectID = ? AND q.Status = 1
                    ORDER BY RAND()";
            
            $questions = $this->query($sql, [$subjectId]);
            
            if (empty($questions)) {
                throw new Exception("No questions found for subject ID: $subjectId");
            }
            
            // Get answers for each question and shuffle them
            foreach ($questions as &$question) {
                $answerSql = "SELECT ID, AnswerText 
                             FROM tblTrain_Answer 
                             WHERE QuestionID = ?
                             ORDER BY RAND()";
                $answers = $this->query($answerSql, [$question['ID']]);
                
                if (empty($answers)) {
                    throw new Exception("No answers found for question ID: " . $question['ID']);
                }
                
                $question['answers'] = $answers;
            }
            
            return $questions;
        } catch (Exception $e) {
            error_log("getExamQuestions error: " . $e->getMessage() . " for subject $subjectId");
            throw new Exception("Failed to fetch questions: " . $e->getMessage());
        }
    }

    /**
     * Check if answer is correct
     */
    public function checkAnswerCorrectness($questionId, $answerId) {
        try {
            $sql = "SELECT IsCorrect FROM tblTrain_Answer 
                    WHERE ID = ? AND QuestionID = ?";
            $result = $this->query($sql, [$answerId, $questionId]);
            return !empty($result) && $result[0]['IsCorrect'] == 1;
        } catch (Exception $e) {
            error_log("checkAnswerCorrectness error: " . $e->getMessage());
            return false;  // Fallback to false on error
        }
    }

    /**
     * Submit exam and calculate results
     */
    public function submitExam($examId, $answers) {
        try {
            // Get exam info
            $exam = $this->find($examId);
            if (!$exam) {
                throw new Exception("Exam not found: $examId");
            }

            // Get subject info
            $subjectSql = "SELECT RequiredScore FROM tblTrain_Subject WHERE ID = ?";
            $subjectResult = $this->query($subjectSql, [$exam['SubjectID']]);
            if (empty($subjectResult)) {
                throw new Exception("Subject not found: " . $exam['SubjectID']);
            }
            $subject = $subjectResult[0];
            
            // Get all questions for this subject
            $questionsSql = "SELECT COUNT(*) as total FROM tblTrain_Question 
                            WHERE SubjectID = ? AND Status = 1";
            $totalResult = $this->query($questionsSql, [$exam['SubjectID']]);
            $totalQuestions = $totalResult[0]['total'];

            // Process answers
            $correctCount = 0;
            foreach ($answers as $answer) {
                $isCorrect = $this->checkAnswerCorrectness(
                    $answer['question_id'], 
                    $answer['answer_id']
                );
                
                // Save answer detail
                $this->saveExamDetail($examId, $answer['question_id'], $answer['answer_id'], $isCorrect);
                
                if ($isCorrect) $correctCount++;
            }

            // Calculate score (percentage)
            $score = $totalQuestions > 0 ? ($correctCount / $totalQuestions) * 100 : 0;
            $passed = $score >= $subject['RequiredScore'];

            // Update exam record
            $updateData = [
                'EndTime' => date('Y-m-d H:i:s'),
                'Score' => $score,
                'TotalQuestions' => $totalQuestions,
                'CorrectAnswers' => $correctCount,
                'Status' => 'completed',
                'Passed' => $passed ? 1 : 0
            ];
            $this->update($this->table, $updateData, 'ID = ?', [$examId]);

            // If passed, create completion record and certificate
            if ($passed) {
                $completionModel = new CompletionModel();
                $completionModel->markComplete(
                    $exam['EmployeeID'], 
                    $exam['SubjectID'], 
                    'exam', 
                    $score, 
                    $examId
                );

                $certificateModel = new CertificateModel();
                $certificateModel->generateCertificate(
                    $exam['EmployeeID'], 
                    $exam['SubjectID']
                );
            }

            return [
                'passed' => $passed,
                'score' => $score,
                'correct_count' => $correctCount,
                'total_questions' => $totalQuestions,
                'required' => $subject['RequiredScore']
            ];
        } catch (Exception $e) {
            error_log("submitExam error: " . $e->getMessage() . " for exam $examId");
            throw new Exception("Failed to submit exam: " . $e->getMessage());
        }
    }

    /**
     * Save exam detail (question answer)
     */
    private function saveExamDetail($examId, $questionId, $answerId, $isCorrect) {
        try {
            $sql = "INSERT INTO tblTrain_ExamDetail 
                    (ExamID, QuestionID, AnswerID, IsCorrect, CreatedAt)
                    VALUES (?, ?, ?, ?, NOW())";
            return $this->execute($sql, [$examId, $questionId, $answerId, $isCorrect ? 1 : 0]);
        } catch (Exception $e) {
            error_log("saveExamDetail error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if employee can take exam
     */
    public function canTakeExam($employeeId, $subjectId) {
        try {
            // Check if subject has questions
            $questionCountSql = "SELECT COUNT(*) as total FROM tblTrain_Question 
                                 WHERE SubjectID = ? AND Status = 1";
            $questionCount = $this->query($questionCountSql, [$subjectId])[0]['total'];

            if ($questionCount == 0) {
                return [
                    'allowed' => false,
                    'message' => 'Khóa học này chưa có câu hỏi kiểm tra'
                ];
            }

            // Check if already passed
            $passedExamSql = "SELECT 1 FROM tblTrain_Exam 
                              WHERE EmployeeID = ? AND SubjectID = ? AND Passed = 1 
                              LIMIT 1";
            $passedExam = $this->query($passedExamSql, [$employeeId, $subjectId]);

            if (!empty($passedExam)) {
                return [
                    'allowed' => false,
                    'message' => 'Bạn đã vượt qua bài kiểm tra này'
                ];
            }

            // Check number of attempts today
            $attemptsTodaySql = "SELECT COUNT(*) as total FROM tblTrain_Exam 
                                 WHERE EmployeeID = ? AND SubjectID = ? 
                                 AND DATE(StartTime) = CURDATE()";
            $attemptsToday = $this->query($attemptsTodaySql, [$employeeId, $subjectId])[0]['total'];

            if ($attemptsToday >= 10) {
                return [
                    'allowed' => false,
                    'message' => 'Bạn đã hết lượt thi trong ngày. Vui lòng thử lại vào ngày mai.'
                ];
            }

            return [
                'allowed' => true,
                'remaining_attempts' => 10 - $attemptsToday
            ];
        } catch (Exception $e) {
            error_log("canTakeExam error: " . $e->getMessage());
            return [
                'allowed' => false,
                'message' => 'Lỗi hệ thống khi kiểm tra điều kiện thi'
            ];
        }
    }

    /**
     * Get exam attempts
     */
    public function getAttempts($employeeId, $subjectId) {
        try {
            $sql = "SELECT * FROM {$this->table}
                    WHERE EmployeeID = ? AND SubjectID = ?
                    ORDER BY StartTime DESC
                    LIMIT 10";
            return $this->query($sql, [$employeeId, $subjectId]);
        } catch (Exception $e) {
            error_log("getAttempts error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get exam with details
     */
    public function getExamWithDetails($examId, $employeeId) {
        try {
            $sql = "SELECT e.*, s.Title as SubjectName, s.RequiredScore
                    FROM {$this->table} e
                    JOIN tblTrain_Subject s ON e.SubjectID = s.ID
                    WHERE e.ID = ? AND e.EmployeeID = ?";
            
            $result = $this->query($sql, [$examId, $employeeId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log("getExamWithDetails error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get exam details (questions and answers)
     */
    public function getExamDetails($examId) {
        try {
            $sql = "SELECT ed.*, 
                    q.QuestionText,
                    a.AnswerText,
                    ca.AnswerText as CorrectAnswerText
                    FROM tblTrain_ExamDetail ed
                    JOIN tblTrain_Question q ON ed.QuestionID = q.ID
                    JOIN tblTrain_Answer a ON ed.AnswerID = a.ID
                    LEFT JOIN tblTrain_Answer ca ON (ca.QuestionID = q.ID AND ca.IsCorrect = 1)
                    WHERE ed.ExamID = ?
                    ORDER BY ed.ID";
            
            return $this->query($sql, [$examId]);
        } catch (Exception $e) {
            error_log("getExamDetails error: " . $e->getMessage());
            return [];
        }
    }
}