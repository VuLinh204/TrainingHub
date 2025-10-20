<?php
class Model {
    protected $db;
    protected $table;

    public function __construct() {
        if (!isset($GLOBALS['db'])) {
            $GLOBALS['db'] = require __DIR__ . '/../config/db.php';
        }
        $this->db = $GLOBALS['db'];
    }

    public function find($id) {
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} WHERE ID = ?");
        $stmt->execute([$id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function findBy($conditions = [], $orderBy = '') {
        $where = '';
        $params = [];
        
        if (!empty($conditions)) {
            $where = 'WHERE ' . implode(' AND ', array_map(function($key) {
                return "$key = ?";
            }, array_keys($conditions)));
            $params = array_values($conditions);
        }

        $order = $orderBy ? "ORDER BY $orderBy" : '';
        
        $stmt = $this->db->prepare("SELECT * FROM {$this->table} $where $order");
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create($data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        
        $stmt = $this->db->prepare("INSERT INTO {$this->table} ($columns) VALUES ($values)");
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }

    public function update($table, $data, $where = '', $params = []) {
        $set = implode(', ', array_map(function($key) {
            return "$key = ?";
        }, array_keys($data)));

        $sql = "UPDATE {$table} SET $set";
        if ($where) {
            $sql .= " WHERE $where";
        }
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([...array_values($data), ...$params]);
    }

    protected function query($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    protected function execute($sql, $params = []) {
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }

    protected function insert($table, $data) {
        $columns = implode(', ', array_keys($data));
        $values = implode(', ', array_fill(0, count($data), '?'));
        
        $sql = "INSERT INTO {$table} ($columns) VALUES ($values)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_values($data));
        
        return $this->db->lastInsertId();
    }
}
