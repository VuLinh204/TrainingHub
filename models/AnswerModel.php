<?php
require_once __DIR__ . '/../core/Model.php';

class AnswerModel extends Model {
    protected $table = 'tblTrain_Answer';

    public function getByQuestion($questionId, $status = 1) {
        try {
            $sql = "SELECT * FROM {$this->table}
                    WHERE QuestionID = ?
                    ORDER BY RAND()";
            return $this->query($sql, [$questionId]);
        } catch (Exception $e) {
            error_log("getByQuestion error: " . $e->getMessage() . " for question $questionId");
            return [];
        }
    }

    public function getCorrectAnswer($questionId) {
        try {
            $sql = "SELECT * FROM {$this->table}
                    WHERE QuestionID = ? AND IsCorrect = 1
                    LIMIT 1";
            $result = $this->query($sql, [$questionId]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log("getCorrectAnswer error: " . $e->getMessage());
            return null;
        }
    }

    public function isCorrect($answerId, $questionId) {
        try {
            $sql = "SELECT IsCorrect FROM {$this->table}
                    WHERE ID = ? AND QuestionID = ?";
            $result = $this->query($sql, [$answerId, $questionId]);
            return !empty($result) && $result[0]['IsCorrect'] == 1;
        } catch (Exception $e) {
            error_log("isCorrect error: " . $e->getMessage());
            return false;
        }
    }

    public function find($id) {
        try {
            $sql = "SELECT * FROM {$this->table} WHERE ID = ?";
            $result = $this->query($sql, [$id]);
            return $result ? $result[0] : null;
        } catch (Exception $e) {
            error_log("find answer error: " . $e->getMessage() . " for ID $id");
            return null;
        }
    }

    public function create($data) {
        try {
            return $this->insert($this->table, $data);
        } catch (Exception $e) {
            error_log("create answer error: " . $e->getMessage());
            throw new Exception("Failed to create answer: " . $e->getMessage());
        }
    }

    public function update($id, $data) {
        try {
            return $this->update($this->table, $data, 'ID = ?', [$id]);
        } catch (Exception $e) {
            error_log("update answer error: " . $e->getMessage());
            throw new Exception("Failed to update answer: " . $e->getMessage());
        }
    }

    public function delete($id) {
        try {
            return $this->deleteRow($this->table, 'ID = ?', [$id]);
        } catch (Exception $e) {
            error_log("delete answer error: " . $e->getMessage());
            return false;
        }
    }

    public function getByQuestionPaginated($questionId, $limit = 50, $offset = 0) {
        try {
            $sql = "SELECT * FROM {$this->table}
                    WHERE QuestionID = ?
                    ORDER BY CreatedAt DESC
                    LIMIT ? OFFSET ?";
            return $this->query($sql, [$questionId, $limit, $offset]);
        } catch (Exception $e) {
            error_log("getByQuestionPaginated error: " . $e->getMessage());
            return [];
        }
    }
}