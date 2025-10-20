<?php
require_once __DIR__ . '/../core/Model.php';

class CompletionModel extends Model {
    protected $table = 'tblTrain_Completion';

    public function markComplete($employeeId, $subjectId, $method = 'video', $score = null, $examId = null) {
        $data = [
            'EmployeeID' => $employeeId,
            'SubjectID' => $subjectId,
            'CompletedAt' => date('Y-m-d H:i:s'),
            'Method' => $method,
            'Score' => $score,
            'ExamID' => $examId,
            'CreatedBy' => isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null
        ];

        $existing = $this->getCompletion($employeeId, $subjectId);
        if ($existing) {
            return false;
        }
        return $this->insert($this->table, $data);
    }

    public function getRecentCompletions($employeeId, $limit = 5) {
        $sql = "SELECT c.*, 
                    s.Title AS SubjectName, 
                    s.Duration,
                    e.Score, 
                    e.TotalQuestions, 
                    e.Passed
                FROM {$this->table} c
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                LEFT JOIN tblTrain_Exam e ON c.ExamID = e.ID
                WHERE c.EmployeeID = ?
                ORDER BY c.CompletedAt DESC
                LIMIT ?";
        return $this->query($sql, [$employeeId, $limit]);
    }

    public function getEmployeeCompletions($employeeId) {
        $sql = "SELECT c.*, 
                    s.Title AS SubjectName, 
                    s.Duration,
                    e.Score, 
                    e.TotalQuestions, 
                    e.Passed
                FROM {$this->table} c
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                LEFT JOIN tblTrain_Exam e ON c.ExamID = e.ID
                WHERE c.EmployeeID = ?
                ORDER BY c.CompletedAt DESC";
        return $this->query($sql, [$employeeId]);
    }

    public function getEmployeeCompletionRate($employeeId) {
        $sql = "SELECT 
                    COUNT(*) as total_subjects,
                    SUM(CASE WHEN Method IN ('video', 'exam', 'manual') THEN 1 ELSE 0 END) as completed_subjects
                FROM {$this->table}
                WHERE EmployeeID = ?";
        $result = $this->query($sql, [$employeeId])[0] ?? [];
        $total = $result['total_subjects'] ?? 0;
        $completed = $result['completed_subjects'] ?? 0;
        $completionRate = ($total > 0) ? ($completed / $total) * 100 : 0;
        return [
            'completion_rate' => $completionRate,
            'total_completed' => $completed,
            'total_assigned' => $total
        ];
    }

    public function getCompletion($employeeId, $subjectId) {
        $sql = "SELECT c.*, s.Title as SubjectName, CONCAT(emp.FirstName, ' ', emp.LastName) as CompletedBy
                FROM {$this->table} c
                LEFT JOIN tblTrain_Exam e ON c.ExamID = e.ID 
                LEFT JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                LEFT JOIN tblTrain_Employee emp ON c.CreatedBy = emp.ID
                WHERE c.EmployeeID = ? AND c.SubjectID = ?";
        $result = $this->query($sql, [$employeeId, $subjectId]);
        return $result ? $result[0] : null;
    }

    public function getSubjectCompletions($subjectId) {
        $sql = "SELECT c.*, 
                CONCAT(emp.FirstName, ' ', emp.LastName) as EmployeeName,
                emp.Department
                FROM {$this->table} c
                JOIN tblTrain_Employee emp ON c.EmployeeID = emp.ID
                WHERE c.SubjectID = ?
                ORDER BY c.CompletedAt DESC";
        return $this->query($sql, [$subjectId]);
    }

    public function getCompletionStats($subjectId = null) {
        $where = $subjectId ? "WHERE c.SubjectID = ?" : "";
        $params = $subjectId ? [$subjectId] : [];
        $sql = "SELECT 
                COUNT(*) as total_completions,
                AVG(CASE WHEN Method = 'exam' THEN Score END) as avg_exam_score,
                COUNT(CASE WHEN Method = 'video' THEN 1 END) as video_completions,
                COUNT(CASE WHEN Method = 'exam' THEN 1 END) as exam_completions,
                COUNT(CASE WHEN Method = 'manual' THEN 1 END) as manual_completions
                FROM {$this->table} c
                {$where}";
        return $this->query($sql, $params)[0];
    }
}