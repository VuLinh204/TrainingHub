<?php
require_once __DIR__ . '/../core/Model.php';

class EmployeeModel extends Model {
    protected $table = 'tblTrain_Employee';

    public function findByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE Email = ? LIMIT 1";
        $result = $this->query($sql, [$email]);
        return $result ? $result[0] : null;
    }

    public function findById($id) {
        $sql = "SELECT * FROM {$this->table} WHERE ID = ? LIMIT 1";
        $result = $this->query($sql, [$id]);
        return $result ? $result[0] : null;
    }

    public function updateLastLogin($id) {
        $data = [
            'LastLoginAt' => date('Y-m-d H:i:s'),
            'LastLoginIP' => $_SERVER['REMOTE_ADDR']
        ];
        return $this->update($this->table, $data, "ID = ?", [$id]);
    }

    public function updateProfile($id, $data) {
        $allowedFields = ['FirstName', 'LastName', 'Phone', 'PositionID'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        return $this->update($this->table, $updateData, "ID = ?", [$id]);
    }

    public function updatePassword($id, $newPassword) {
        $data = [
            'PasswordHash' => password_hash($newPassword, PASSWORD_DEFAULT)
        ];
        return $this->update($this->table, $data, "ID = ?", [$id]);
    }

    public function getCompletedSubjects($id) {
        $sql = "SELECT s.*, e.CompletedAt as CompletedAt, e.Score
                FROM tblTrain_Exam e
                JOIN tblTrain_Subject s ON e.SubjectID = s.ID
                WHERE e.EmployeeID = ? AND e.Passed = 1
                ORDER BY e.CompletedAt DESC";
        return $this->query($sql, [$id]);
    }

    public function getCertificates($id) {
        $sql = "SELECT c.*, s.Title as SubjectName
                FROM tblTrain_Certificate c
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                WHERE c.EmployeeID = ? AND c.Status = 1
                ORDER BY c.IssuedAt DESC";
        return $this->query($sql, [$id]);
    }

    public function getAssignedSubjects($id) {
        $sql = "SELECT s.*, 
                a.AssignDate,
                a.ExpireDate,
                (SELECT MAX(e.Score) 
                 FROM tblTrain_Exam e 
                 WHERE e.SubjectID = s.ID 
                 AND e.EmployeeID = ? 
                 AND e.Passed = 1) as BestScore,
                (SELECT COUNT(*) > 0 
                 FROM tblTrain_Exam e 
                 WHERE e.SubjectID = s.ID 
                 AND e.EmployeeID = ? 
                 AND e.Passed = 1) as IsCompleted
                FROM tblTrain_Subject s
                INNER JOIN tblTrain_Assign a ON s.KnowledgeGroupID = a.KnowledgeGroupID
                INNER JOIN tblTrain_Position p ON p.ID = a.PositionID
                WHERE p.ID = (
                    SELECT PositionID 
                    FROM tblTrain_Employee 
                    WHERE ID = ?
                )
                AND s.Status = 1
                AND (a.AssignDate <= CURRENT_DATE)
                AND (a.ExpireDate IS NULL OR a.ExpireDate >= CURRENT_DATE)
                ORDER BY a.AssignDate ASC";
        return $this->query($sql, [$id, $id, $id]);
    }

    public function hasCertificate($employeeId, $subjectId) {
        $sql = "SELECT 1 FROM tblTrain_Certificate 
                WHERE EmployeeID = ? AND SubjectID = ? AND Status = 1 
                LIMIT 1";
        $result = $this->query($sql, [$employeeId, $subjectId]);
        return !empty($result);
    }

    public function getProgress($id, $subjectId) {
        $sql = "SELECT MAX(WatchedSeconds) as watch_time,
                COUNT(DISTINCT CASE WHEN Event = 'ended' THEN ID END) as completions
                FROM tblTrain_WatchLog
                WHERE EmployeeID = ? AND SubjectID = ?";
        $watchData = $this->query($sql, [$id, $subjectId])[0];

        $sql = "SELECT MAX(Score) as best_score
                FROM tblTrain_Exam
                WHERE EmployeeID = ? AND SubjectID = ? AND Passed = 1";
        $examData = $this->query($sql, [$id, $subjectId])[0];

        return [
            'watchTime' => $watchData['watch_time'] ?? 0,
            'completions' => $watchData['completions'] ?? 0,
            'bestScore' => $examData['best_score'] ?? null
        ];
    }
}