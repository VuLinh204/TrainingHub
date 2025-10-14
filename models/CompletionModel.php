<?php
class CompletionModel extends Model {
    protected $table = 'tbltrain_completion';

    public function markComplete($employeeId, $subjectId, $method = 'video', $score = null, $examId = null) {
        $data = [
            'EmployeeID' => $employeeId,
            'SubjectID' => $subjectId,
            'Method' => $method,
            'Score' => $score,
            'ExamID' => $examId,
            'CreatedBy' => isset($_SESSION['admin_id']) ? $_SESSION['admin_id'] : null
        ];

        // Kiểm tra xem đã tồn tại completion chưa
        $existing = $this->getCompletion($employeeId, $subjectId);
        if ($existing) {
            return false; // Đã hoàn thành rồi
        }

        return $this->insert($this->table, $data);
    }

    public function getCompletion($employeeId, $subjectId) {
        $sql = "SELECT c.*, e.Title as ExamName, CONCAT(emp.FirstName, ' ', emp.LastName) as CompletedBy
                FROM {$this->table} c
                LEFT JOIN tbltrain_exam e ON c.ExamID = e.ID 
                LEFT JOIN tbltrain_employee emp ON c.CreatedBy = emp.ID
                WHERE c.EmployeeID = ? AND c.SubjectID = ?";
        $result = $this->query($sql, [$employeeId, $subjectId]);
        return $result ? $result[0] : null;
    }

    public function getEmployeeCompletions($employeeId) {
        $sql = "SELECT c.*, s.Title as SubjectName, s.VideoLength,
                e.Title as ExamName, e.TotalQuestions
                FROM {$this->table} c
                JOIN tbltrain_subject s ON c.SubjectID = s.ID
                LEFT JOIN tbltrain_exam e ON c.ExamID = e.ID
                WHERE c.EmployeeID = ?
                ORDER BY c.CompletedAt DESC";
        return $this->query($sql, [$employeeId]);
    }

    public function getSubjectCompletions($subjectId) {
        $sql = "SELECT c.*, 
                CONCAT(emp.FirstName, ' ', emp.LastName) as EmployeeName,
                emp.Department
                FROM {$this->table} c
                JOIN tbltrain_employee emp ON c.EmployeeID = emp.ID
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