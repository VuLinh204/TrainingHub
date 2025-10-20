<?php
require_once __DIR__ . '/../core/Model.php';

class SubjectModel extends Model {
    protected $table = 'tblTrain_Subject';

    public function getAssignedSubjectsByKnowledgeGroup($employeeId) {
        $sql = "SELECT s.ID,
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
                    FROM tblTrain_Exam e 
                    WHERE e.SubjectID = s.ID 
                    AND e.EmployeeID = ? 
                    AND e.Passed = 1) as is_completed,
                   (SELECT COUNT(*) > 0 
                    FROM tblTrain_Certificate cert 
                    WHERE cert.SubjectID = s.ID 
                    AND cert.EmployeeID = ? 
                    AND cert.Status = 1) as has_certificate,
                   (SELECT MAX(e2.Score)
                    FROM tblTrain_Exam e2
                    WHERE e2.SubjectID = s.ID
                    AND e2.EmployeeID = ?
                    AND e2.Status = 'completed') as BestScore
            FROM {$this->table} s
            INNER JOIN tblTrain_KnowledgeGroup kg ON s.KnowledgeGroupID = kg.ID
            INNER JOIN tblTrain_Assign a ON kg.ID = a.KnowledgeGroupID
            INNER JOIN tblTrain_Employee emp ON emp.PositionID = a.PositionID
            WHERE emp.ID = ? 
            AND s.Status = 1
            AND s.DeletedAt IS NULL
            AND kg.Status = 1
            AND a.Status = 1
            AND (a.ExpireDate IS NULL OR a.ExpireDate >= CURRENT_DATE)
            ORDER BY a.AssignDate DESC";
        return $this->query($sql, [$employeeId, $employeeId, $employeeId, $employeeId]);
    }

    public function getWithProgress($subjectId, $employeeId) {
        $sql = "SELECT s.ID,
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
                   (SELECT COUNT(*) FROM tblTrain_Question q WHERE q.SubjectID = s.ID AND q.Status = 1) as QuestionCount
            FROM {$this->table} s
            LEFT JOIN (
                SELECT SubjectID, MAX(WatchedSeconds) as WatchedSeconds
                FROM tblTrain_WatchLog
                WHERE EmployeeID = ?
                GROUP BY SubjectID
            ) w ON w.SubjectID = s.ID
            LEFT JOIN tblTrain_Exam e ON e.SubjectID = s.ID 
                AND e.EmployeeID = ?
                AND e.ID = (
                    SELECT MAX(ID) 
                    FROM tblTrain_Exam 
                    WHERE SubjectID = s.ID AND EmployeeID = ?
                )
            WHERE s.ID = ?
            AND s.Status = 1
            AND s.DeletedAt IS NULL";
        return $this->query($sql, [$employeeId, $employeeId, $employeeId, $subjectId])[0] ?? null;
    }

    public function getExamQuestions($subjectId) {
        $sql = "SELECT q.ID,
                   q.QuestionText,
                   q.QuestionType,
                   q.Score,
                   GROUP_CONCAT(a.ID ORDER BY a.ID) as answer_ids,
                   GROUP_CONCAT(a.AnswerText ORDER BY a.ID SEPARATOR '||') as answer_texts,
                   GROUP_CONCAT(a.IsCorrect ORDER BY a.ID) as is_corrects
            FROM tblTrain_Question q
            LEFT JOIN tblTrain_Answer a ON q.ID = a.QuestionID
            WHERE q.SubjectID = ? AND q.Status = 1
            GROUP BY q.ID, q.QuestionText, q.QuestionType, q.Score
            ORDER BY RAND()";
        $rows = $this->query($sql, [$subjectId]);
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

    public function find($id) {
        $sql = "SELECT 
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
            FROM {$this->table}
            WHERE ID = ? 
            AND Status = 1
            AND DeletedAt IS NULL";
        return $this->query($sql, [$id])[0] ?? null;
    }

    public function findById($id) {
        return $this->find($id);
    }

    /**
     * Search subjects by keyword (used by site search)
     */
    public function searchSubjects($q, $limit = 20) {
        $sql = "SELECT ID, Title, Description, VideoURL, Duration, FileURL
                FROM {$this->table}
                WHERE Status = 1 AND DeletedAt IS NULL
                AND (Title LIKE ? OR Description LIKE ?)
                ORDER BY CreatedAt DESC
                LIMIT ?";
        $like = '%' . str_replace('%', '\\%', $q) . '%';
        return $this->query($sql, [$like, $like, (int)$limit]);
    }
}