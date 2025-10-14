<?php
require_once __DIR__ . '/../core/Model.php';

class WatchLogModel extends Model {
    protected $table = 'tblTrain_WatchLog';
    
    public function logWatch($employeeId, $subjectId, $seconds, $currentTime, $event) {
        return $this->create([
            'EmployeeID' => $employeeId,
            'SubjectID' => $subjectId,
            'WatchedSeconds' => $seconds,
            'CurrentTime' => $currentTime,
            'Event' => $event,
            'CreatedAt' => date('Y-m-d H:i:s')
        ]);
    }

    public function getTotalWatchedSeconds($employeeId, $subjectId) {
        $stmt = $this->db->prepare("
            SELECT MAX(WatchedSeconds) as total 
            FROM {$this->table} 
            WHERE EmployeeID = ? AND SubjectID = ?
        ");
        $stmt->execute([$employeeId, $subjectId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (int)($result['total'] ?? 0);
    }
}