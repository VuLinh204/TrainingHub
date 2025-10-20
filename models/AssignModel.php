<?php
require_once __DIR__ . '/../core/Model.php';

class AssignModel extends Model {
    protected $table = 'tblTrain_Assign';

    public function assignToPosition($positionId, $knowledgeGroupId, $assignDate = null, $expireDate = null) {
        $data = [
            'PositionID' => $positionId,
            'KnowledgeGroupID' => $knowledgeGroupId,
            'AssignDate' => $assignDate ?? date('Y-m-d'),
            'ExpireDate' => $expireDate,
            'CreatedAt' => date('Y-m-d H:i:s')
        ];
        return $this->insert($this->table, $data);
    }

    public function removeAssignment($positionId, $knowledgeGroupId) {
        $sql = "DELETE FROM {$this->table} WHERE PositionID = ? AND KnowledgeGroupID = ?";
        return $this->execute($sql, [$positionId, $knowledgeGroupId]);
    }

    public function getAssignmentsByPosition($positionId) {
        $sql = "SELECT a.*, kg.Name as KnowledgeGroupName
                FROM {$this->table} a
                JOIN tblTrain_KnowledgeGroup kg ON a.KnowledgeGroupID = kg.ID
                WHERE a.PositionID = ? AND kg.Status = 1
                AND (a.AssignDate <= CURRENT_DATE)
                AND (a.ExpireDate IS NULL OR a.ExpireDate >= CURRENT_DATE)
                ORDER BY a.AssignDate ASC";
        return $this->query($sql, [$positionId]);
    }

    public function updateAssignment($positionId, $knowledgeGroupId, $data) {
        $allowedFields = ['AssignDate', 'ExpireDate', 'IsRequired', 'Status'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        return $this->update(
            $this->table, 
            $updateData, 
            "PositionID = ? AND KnowledgeGroupID = ?", 
            [$positionId, $knowledgeGroupId]
        );
    }

    public function getActiveAssignments() {
        $sql = "SELECT a.*, 
                kg.Name as KnowledgeGroupName,
                p.PositionName,
                (SELECT COUNT(DISTINCT e.ID) 
                 FROM tblTrain_Employee e 
                 WHERE e.PositionID = a.PositionID) as EmployeeCount,
                (SELECT COUNT(DISTINCT c.ID) 
                 FROM tblTrain_Completion c 
                 JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                 JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
                 WHERE e.PositionID = a.PositionID 
                 AND s.KnowledgeGroupID = a.KnowledgeGroupID) as CompletedCount
                FROM {$this->table} a
                JOIN tblTrain_KnowledgeGroup kg ON a.KnowledgeGroupID = kg.ID
                JOIN tblTrain_Position p ON a.PositionID = p.ID
                WHERE kg.Status = 1
                AND (a.AssignDate <= CURRENT_DATE)
                AND (a.ExpireDate IS NULL OR a.ExpireDate >= CURRENT_DATE)
                ORDER BY a.AssignDate DESC";
        return $this->query($sql);
    }
}