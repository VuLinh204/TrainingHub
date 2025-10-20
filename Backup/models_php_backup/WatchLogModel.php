<?php
require_once __DIR__ . '/../core/Model.php';

class WatchLogModel extends Model {
    protected $table = 'tblTrain_WatchLog';

    public function logWatch($employeeId, $subjectId, $seconds, $currentTime, $event) {
        return $this->create([
            'EmployeeID' => $employeeId,
            'SubjectID' => $subjectId,
            'Event' => $event,
            'WatchedSeconds' => $seconds,
            'CurrentTime' => $currentTime,
            'CreatedAt' => date('Y-m-d H:i:s'),
            'IPAddress' => $_SERVER['REMOTE_ADDR'] ?? null,
            'UserAgent' => $_SERVER['HTTP_USER_AGENT'] ?? null
        ]);
    }

    public function getTotalWatchedSeconds($employeeId, $subjectId) {
        $sql = "SELECT MAX(WatchedSeconds) as total 
                FROM {$this->table} 
                WHERE EmployeeID = ? AND SubjectID = ?";
        $result = $this->query($sql, [$employeeId, $subjectId])[0] ?? [];
        return (int)($result['total'] ?? 0);
    }
}