<?php
require_once __DIR__ . '/../core/Model.php';

class SubjectModel extends Model {
    protected $table = 'tblTrain_Subject';
    
    public function getAssignedSubjectsByKnowledgeGroup($employeeId) {
        $stmt = $this->db->prepare("
            SELECT s.ID,
                   s.Title,
                   s.Description,
                   s.VideoURL,
                   s.Duration,
                   s.FileURL,
                   s.KnowledgeGroupID,
                   s.MinWatchPercent,
                   s.RequiredScore,
                   s.ExamTimeLimit,
                   s.Status,
                   s.CreatedAt,
                   a.ExpireDate,
                   a.AssignDate,
                   a.IsRequired,
                   (SELECT COUNT(*) > 0 
                    FROM " . TBL_EXAM . " e 
                    WHERE e.SubjectID = s.ID 
                    AND e.EmployeeID = ? 
                    AND e.Passed = 1) as is_completed,
                   (SELECT COUNT(*) > 0 
                    FROM " . TBL_CERTIFICATE . " cert 
                    WHERE cert.SubjectID = s.ID 
                    AND cert.EmployeeID = ? 
                    AND cert.Status = 1) as has_certificate,
                   (SELECT MAX(e2.Score)
                    FROM " . TBL_EXAM . " e2
                    WHERE e2.SubjectID = s.ID
                    AND e2.EmployeeID = ?
                    AND e2.Status = 'completed') as BestScore
            FROM " . TBL_SUBJECT . " s
            INNER JOIN " . TBL_KNOWLEDGE_GROUP . " kg ON s.KnowledgeGroupID = kg.ID
            INNER JOIN " . TBL_ASSIGN . " a ON kg.ID = a.KnowledgeGroupID
            INNER JOIN " . TBL_EMPLOYEE . " emp ON emp.PositionID = a.PositionID
            WHERE emp.ID = ? 
            AND s.Status = 1
            AND s.DeletedAt IS NULL
            AND kg.Status = 1
            AND a.Status = 1
            AND (a.ExpireDate IS NULL OR a.ExpireDate >= CURRENT_DATE)
            ORDER BY a.AssignDate DESC
        ");
        $stmt->execute([$employeeId, $employeeId, $employeeId, $employeeId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getWithProgress($subjectId, $employeeId) {
        $stmt = $this->db->prepare("
            SELECT s.ID,
                   s.Title,
                   s.Description,
                   s.VideoURL,
                   s.Duration,
                   s.FileURL,
                   s.KnowledgeGroupID,
                   s.MinWatchPercent,
                   s.MaxSkipSeconds,
                   s.AllowRewatch,
                   s.RequiredScore,
                   s.MinCorrectAnswers,
                   s.ExamTimeLimit,
                   s.Status,
                   COALESCE(w.WatchedSeconds, 0) as watched_seconds,
                   e.Score as last_exam_score,
                   e.Passed as last_exam_passed,
                   (SELECT COUNT(*) FROM " . TBL_QUESTION . " q WHERE q.SubjectID = s.ID AND q.Status = 1) as QuestionCount
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
            AND s.Status = 1
            AND s.DeletedAt IS NULL
        ");
        $stmt->execute([$employeeId, $employeeId, $employeeId, $subjectId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getExamQuestions($subjectId) {
        $stmt = $this->db->prepare("
            SELECT q.ID,
                   q.QuestionText,
                   q.QuestionType,
                   q.Score,
                   GROUP_CONCAT(a.ID ORDER BY a.ID) as answer_ids,
                   GROUP_CONCAT(a.AnswerText ORDER BY a.ID SEPARATOR '||') as answer_texts,
                   GROUP_CONCAT(a.IsCorrect ORDER BY a.ID) as is_corrects
            FROM " . TBL_QUESTION . " q
            LEFT JOIN " . TBL_ANSWER . " a ON q.ID = a.QuestionID
            WHERE q.SubjectID = ? AND q.Status = 1
            GROUP BY q.ID, q.QuestionText, q.QuestionType, q.Score
            ORDER BY RAND()
        ");
        $stmt->execute([$subjectId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $questions = [];
        foreach ($rows as $row) {
            if (empty($row['answer_ids'])) {
                continue;
            }

            $answerIds = explode(',', $row['answer_ids']);
            $answerTexts = explode('||', $row['answer_texts']);
            $isCorrects = explode(',', $row['is_corrects']);

            $answers = [];
            foreach ($answerIds as $index => $answerId) {
                if ($answerId && isset($answerTexts[$index])) {
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
    
    /**
     * Get subject by ID
     * FIXED: Ensure all fields are returned including Title
     */
    public function find($id) {
        $stmt = $this->db->prepare("
            SELECT 
                ID,
                Title,
                Description,
                VideoURL,
                Duration,
                FileURL,
                KnowledgeGroupID,
                MinWatchPercent,
                MaxSkipSeconds,
                AllowRewatch,
                RequiredScore,
                MinCorrectAnswers,
                ExamTimeLimit,
                Status,
                CreatedAt
            FROM " . TBL_SUBJECT . "
            WHERE ID = ? 
            AND Status = 1
            AND DeletedAt IS NULL
        ");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    /**
     * Alias for find() to match parent class
     */
    public function findById($id) {
        return $this->find($id);
    }
}
?>