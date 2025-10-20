<?php
require_once __DIR__ . '/../core/Model.php';

class ExamModel extends Model {
    protected $table = 'tblTrain_Exam';

    public function startExam($employeeId, $subjectId) {
        try {
            return $this->create([
                'EmployeeID' => $employeeId,
                'SubjectID' => $subjectId,
                'StartTime' => date('Y-m-d H:i:s'),
                'Score' => 0,
                'TotalQuestions' => 0,
                'CorrectAnswers' => 0,
                'Passed' => 0,
                'Status' => 'started'
            ]);
        } catch (Exception $e) {
            error_log("startExam error: " . $e->getMessage() . " for employee $employeeId, subject $subjectId");
            throw new Exception("Failed to create exam record: " . $e->getMessage());
        }
    }

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

    public function checkAnswerCorrectness($questionId, $answerId) {
        try {
            $sql = "SELECT IsCorrect FROM tblTrain_Answer 
                    WHERE ID = ? AND QuestionID = ?";
            $result = $this->query($sql, [$answerId, $questionId]);
            return !empty($result) && $result[0]['IsCorrect'] == 1;
        } catch (Exception $e) {
            error_log("checkAnswerCorrectness error: " . $e->getMessage());
            return false;
        }
    }

    public function submitExam($examId, $answers) {
        try {
            $exam = $this->find($examId);
            if (!$exam) {
                throw new Exception("Exam not found: $examId");
            }

            $subjectSql = "SELECT RequiredScore, MinCorrectAnswers FROM tblTrain_Subject WHERE ID = ?";
            $subjectResult = $this->query($subjectSql, [$exam['SubjectID']]);
            if (empty($subjectResult)) {
                throw new Exception("Subject not found: " . $exam['SubjectID']);
            }
            $subject = $subjectResult[0];

            $totalQuestions = count($answers);

            $questionIds = $this->query("SELECT ID FROM tblTrain_Question WHERE SubjectID = ? AND Status = 1", [$exam['SubjectID']]);
            $questionIds = array_column($questionIds, 'ID');

            foreach ($answers as $answer) {
                if (!in_array($answer['question_id'], $questionIds)) {
                    throw new Exception("Invalid question_id: " . $answer['question_id']);
                }
                $answerCheck = $this->query("SELECT 1 FROM tblTrain_Answer WHERE ID = ? AND QuestionID = ?", 
                    [$answer['answer_id'], $answer['question_id']]);
                if (empty($answerCheck)) {
                    throw new Exception("Invalid answer_id: " . $answer['answer_id'] . " for question_id: " . $answer['question_id']);
                }
            }

            $correctCount = 0;
            foreach ($answers as $answer) {
                $isCorrect = $this->checkAnswerCorrectness(
                    $answer['question_id'], 
                    $answer['answer_id']
                );
                $this->saveExamDetail($examId, $answer['question_id'], $answer['answer_id'], $isCorrect);
                if ($isCorrect) $correctCount++;
            }

            $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100) : 0;

            $previousExamSql = "SELECT Score FROM tblTrain_Exam 
                                WHERE EmployeeID = ? AND SubjectID = ? AND Status = 'completed' 
                                ORDER BY Score DESC LIMIT 1";
            $previousExam = $this->query($previousExamSql, [$exam['EmployeeID'], $exam['SubjectID']]);
            $previousScore = !empty($previousExam) ? $previousExam[0]['Score'] : 0;

            $passed = $this->determinePassed(
                $correctCount, 
                $totalQuestions, 
                $score,
                $subject['MinCorrectAnswers'],
                $subject['RequiredScore']
            );

            $updateData = [
                'EndTime' => date('Y-m-d H:i:s'),
                'CompletedAt' => date('Y-m-d H:i:s'),
                'Score' => max($previousScore, $score),
                'TotalQuestions' => $totalQuestions,
                'CorrectAnswers' => $correctCount,
                'Passed' => $passed ? 1 : 0,
                'Status' => 'completed'
            ];
            $this->update($this->table, $updateData, 'ID = ?', [$examId]);

            if ($passed) {
                try {
                    $certificateModel = new CertificateModel();
                    $certificateModel->generateCertificate(
                        $exam['EmployeeID'], 
                        $exam['SubjectID']
                    );
                } catch (Exception $e) {
                    error_log("Failed to generate certificate for EmployeeID={$exam['EmployeeID']}, SubjectID={$exam['SubjectID']}: " . $e->getMessage());
                }
            }

            return [
                'success' => true,
                'passed' => $passed,
                'score' => $score,
                'correct_count' => $correctCount,
                'total_questions' => $totalQuestions,
                'required_percentage' => $subject['RequiredScore'],
                'required_correct_count' => $subject['MinCorrectAnswers']
            ];
        } catch (Exception $e) {
            error_log("submitExam error: " . $e->getMessage() . " for exam $examId");
            throw new Exception("Failed to submit exam: " . $e->getMessage());
        }
    }

    private function determinePassed($correctCount, $totalQuestions, $scorePercentage, $minCorrectAnswers, $requiredScore) {
        if ($minCorrectAnswers > 0) {
            return $correctCount >= $minCorrectAnswers;
        }
        return $scorePercentage >= $requiredScore;
    }

    private function saveExamDetail($examId, $questionId, $answerId, $isCorrect) {
        try {
            $sql = "INSERT INTO tblTrain_ExamDetail 
                    (ExamID, QuestionID, AnswerID, IsCorrect, CreatedAt)
                    VALUES (?, ?, ?, ?, NOW())";
            return $this->execute($sql, [$examId, $questionId, $answerId, $isCorrect ? 1 : 0]);
        } catch (Exception $e) {
            error_log("saveExamDetail error: " . $e->getMessage() . " for ExamID=$examId, QuestionID=$questionId, AnswerID=$answerId");
            throw new Exception("Failed to save exam detail: " . $e->getMessage());
        }
    }

    public function canTakeExam($employeeId, $subjectId) {
        try {
            $questionCountSql = "SELECT COUNT(*) as total FROM tblTrain_Question 
                                WHERE SubjectID = ? AND Status = 1";
            $questionCount = $this->query($questionCountSql, [$subjectId])[0]['total'];
            if ($questionCount == 0) {
                return [
                    'allowed' => false,
                    'message' => 'Khóa học này chưa có câu hỏi kiểm tra'
                ];
            }

            $attemptsTodaySql = "SELECT COUNT(*) as total FROM tblTrain_Exam 
                                WHERE EmployeeID = ? AND SubjectID = ? 
                                AND DATE(StartTime) = CURDATE()";
            $attemptsToday = $this->query($attemptsTodaySql, [$employeeId, $subjectId])[0]['total'];
            if ($attemptsToday >= 999) {
                return [
                    'allowed' => false,
                    'message' => 'Bạn đã hết lượt thi trong ngày. Vui lòng thử lại vào ngày mai.'
                ];
            }

            return [
                'allowed' => true,
                'remaining_attempts' => 999 - $attemptsToday
            ];
        } catch (Exception $e) {
            error_log("canTakeExam error: " . $e->getMessage());
            return [
                'allowed' => false,
                'message' => 'Lỗi hệ thống khi kiểm tra điều kiện thi'
            ];
        }
    }

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

    public function getExamWithDetails($examId, $employeeId) {
        try {
            $sql = "SELECT e.*, s.Title as SubjectName, s.RequiredScore, s.MinCorrectAnswers
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