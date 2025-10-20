<?php
require_once __DIR__ . '/../core/Model.php';

class QuestionModel extends Model {
    protected $table = 'tblTrain_Question';

    public function getBySubject($subjectId, $status = 1) {
        try {
            $sql = "SELECT * FROM {$this->table}
                    WHERE SubjectID = ? AND Status = ?
                    ORDER BY RAND()";
            return $this->query($sql, [$subjectId, $status]);
        } catch (Exception $e) {
            error_log("getBySubject error: " . $e->getMessage() . " for subject $subjectId");
            return [];
        }
    }

    public function find($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE ID = ?";
            $result = $this->query($sql, [$id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log("find question error: " . $e->getMessage() . " for ID $id");
            return null;
        }
    }

    public function getCountBySubject($subjectId) {
        try {
            $sql = "SELECT COUNT(*) as total FROM {$this->table}
                    WHERE SubjectID = ? AND Status = 1";
            $result = $this->query($sql, [$subjectId]);
            return $result[0]['total'];
        } catch (Exception $e) {
            error_log("getCountBySubject error: " . $e->getMessage());
            return 0;
        }
    }

    public function create($data) {
        try {
            return $this->insert($this->table, $data);
        } catch (Exception $e) {
            error_log("create question error: " . $e->getMessage());
            throw new Exception("Failed to create question: " . $e->getMessage());
        }
    }

    public function update($id, $data) {
        try {
            return $this->update($this->table, $data, 'ID = ?', [$id]);
        } catch (Exception $e) {
            error_log("update question error: " . $e->getMessage());
            throw new Exception("Failed to update question: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            return $this->update($this->table, ['Status' => 0], 'ID = ?', [$id]);
        } catch (Exception $e) {
            error_log("delete question error: " . $e->getMessage());
            return false;
        }
    }

    public function getAll($limit = 20, $offset = 0, $status = 1) {
        try {
            $sql = "SELECT q.*, s.Title as SubjectTitle 
                    FROM {$this->table} q
                    LEFT JOIN tblTrain_Subject s ON q.SubjectID = s.ID
                    WHERE q.Status = ?
                    ORDER BY q.CreatedAt DESC
                    LIMIT ? OFFSET ?";
            return $this->query($sql, [$status, $limit, $offset]);
        } catch (Exception $e) {
            error_log("getAll questions error: " . $e->getMessage());
            return [];
        }
    }
}