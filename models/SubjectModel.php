<?php
require_once __DIR__ . '/../core/Model.php';

class SubjectModel extends Model {
    protected $table = 'tblTrain_Subject';
    
    public function getAssignedSubjects($employeeId) {
        $stmt = $this->db->prepare("
            SELECT s.*, a.ExpireDate,
                   (SELECT COUNT(*) > 0 
                    FROM " . TBL_EXAM . " e 
                    WHERE e.SubjectID = s.ID 
                    AND e.EmployeeID = ? 
                    AND e.Passed = 1) as is_completed,
                   (SELECT COUNT(*) > 0 
                    FROM " . TBL_CERTIFICATE . " cert 
                    WHERE cert.SubjectID = s.ID 
                    AND cert.EmployeeID = ? 
                    AND cert.Status = 1) as has_certificate
            FROM " . TBL_SUBJECT . " s
            INNER JOIN " . TBL_ASSIGN . " a ON s.ID = a.SubjectID
            INNER JOIN " . TBL_EMPLOYEE . " e ON e.PositionID = a.PositionID
            WHERE e.ID = ? AND s.Status = 1
            AND (a.ExpireDate IS NULL OR a.ExpireDate >= CURRENT_DATE)
            ORDER BY a.AssignDate DESC
        ");
        $stmt->execute([$employeeId, $employeeId, $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWithProgress($subjectId, $employeeId) {
        $stmt = $this->db->prepare("
            SELECT s.*,
                   COALESCE(w.WatchedSeconds, 0) as watched_seconds,
                   e.Score as last_exam_score,
                   e.Passed as last_exam_passed,
                   (SELECT COUNT(*) FROM tblTrain_Question q WHERE q.SubjectID = s.ID) as QuestionCount
            FROM " . TBL_SUBJECT . " s
            LEFT JOIN (
                SELECT SubjectID, MAX(WatchedSeconds) as WatchedSeconds
                FROM " . TBL_WATCH_LOG . "
                WHERE EmployeeID = ?
                GROUP BY SubjectID
            ) w ON w.SubjectID = s.ID
            LEFT JOIN " . TBL_EXAM . " e ON e.SubjectID = s.ID 
                AND e.EmployeeID = ?
                AND e.ID = (
                    SELECT MAX(ID) 
                    FROM " . TBL_EXAM . " 
                    WHERE SubjectID = s.ID AND EmployeeID = ?
                )
            WHERE s.ID = ?
        ");
        $stmt->execute([$employeeId, $employeeId, $employeeId, $subjectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getExamQuestions($subjectId) {
        $stmt = $this->db->prepare("
            SELECT q.*, 
                   GROUP_CONCAT(a.ID ORDER BY a.ID) as answer_ids,
                   GROUP_CONCAT(a.AnswerText ORDER BY a.ID SEPARATOR '||') as answer_texts,
                   GROUP_CONCAT(a.IsCorrect ORDER BY a.ID) as is_corrects
            FROM " . TBL_QUESTION . " q
            LEFT JOIN " . TBL_ANSWER . " a ON q.ID = a.QuestionID
            WHERE q.SubjectID = ? AND q.Status = 1
            GROUP BY q.ID
            ORDER BY q.ID
        ");
        $stmt->execute([$subjectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $questions = [];
        foreach ($rows as $row) {
            $answerIds = explode(',', $row['answer_ids']);
            $answerTexts = explode('||', $row['answer_texts']);
            $isCorrects = explode(',', $row['is_corrects']);

            $answers = [];
            foreach ($answerIds as $index => $answerId) {
                if ($answerId) {
                    $answers[] = [
                        'ID' => $answerId,
                        'AnswerText' => $answerTexts[$index],
                        'IsCorrect' => (bool)$isCorrects[$index]
                    ];
                }
            }

            $questions[] = [
                'ID' => $row['ID'],
                'QuestionText' => $row['QuestionText'],
                'QuestionType' => $row['QuestionType'],
                'Score' => $row['Score'],
                'answers' => $answers
            ];
        }

        return $questions;
    }
}