<?php
require_once __DIR__ . '/../core/Model.php';

class AssignModel extends Model {
    protected $table = TBL_ASSIGN;

    public function assignToPosition($positionId, $subjectId, $assignDate = null, $expireDate = null) {
        $data = [
            'PositionID' => $positionId,
            'SubjectID' => $subjectId,
            'AssignDate' => $assignDate ?? date('Y-m-d'),
            'ExpireDate' => $expireDate,
            'CreatedAt' => date('Y-m-d H:i:s')
        ];
        return $this->create($data);
    }

    public function removeAssignment($positionId, $subjectId) {
        $sql = "DELETE FROM {$this->table} WHERE PositionID = ? AND SubjectID = ?";
        return $this->execute($sql, [$positionId, $subjectId]);
    }

    public function getAssignmentsByPosition($positionId) {
        $sql = "SELECT a.*, s.Title as SubjectName, s.Duration
                FROM {$this->table} a
                JOIN " . TBL_SUBJECT . " s ON a.SubjectID = s.ID
                WHERE a.PositionID = ? AND s.Status = 1
                AND (a.AssignDate <= CURRENT_DATE)
                AND (a.ExpireDate IS NULL OR a.ExpireDate >= CURRENT_DATE)
                ORDER BY a.AssignDate ASC";
        return $this->query($sql, [$positionId]);
    }

    public function updateAssignment($positionId, $subjectId, $data) {
        $allowedFields = ['AssignDate', 'ExpireDate'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        
        return $this->update(
            $this->table, 
            $updateData, 
            "PositionID = ? AND SubjectID = ?", 
            [$positionId, $subjectId]
        );
    }

    public function getActiveAssignments() {
        $sql = "SELECT a.*, 
                s.Title as SubjectName,
                p.Name as PositionName,
                (SELECT COUNT(DISTINCT e.ID) 
                 FROM " . TBL_EMPLOYEE . " e 
                 WHERE e.PositionID = a.PositionID) as EmployeeCount,
                (SELECT COUNT(DISTINCT e.ID) 
                 FROM " . TBL_EXAM . " e 
                 JOIN " . TBL_EMPLOYEE . " emp ON e.EmployeeID = emp.ID 
                 WHERE emp.PositionID = a.PositionID 
                 AND e.SubjectID = a.SubjectID 
                 AND e.Passed = 1) as CompletedCount
                FROM {$this->table} a
                JOIN " . TBL_SUBJECT . " s ON a.SubjectID = s.ID
                JOIN " . TBL_POSITION . " p ON a.PositionID = p.ID
                WHERE s.Status = 1
                AND (a.AssignDate <= CURRENT_DATE)
                AND (a.ExpireDate IS NULL OR a.ExpireDate >= CURRENT_DATE)
                ORDER BY a.AssignDate DESC";
        return $this->query($sql);
    }
}
