<?php
require_once __DIR__ . '/../core/Model.php';

class NotificationModel extends Model {
    protected $table = 'tblTrain_Notification';

    public function getUnreadNotifications($employeeId, $limit = 10) {
        $sql = "SELECT n.ID, n.Title, n.Message, n.Link, n.`Read` as read, 
                       DATE_FORMAT(n.CreatedAt, '%d/%m %H:%i') as time
                FROM {$this->table} n
                WHERE n.EmployeeID = ? AND n.`Read` = 0
                ORDER BY n.CreatedAt DESC
                LIMIT ?";
        return $this->query($sql, [$employeeId, $limit]);
    }

    public function markRead($notificationId, $employeeId) {
        $sql = "UPDATE {$this->table} SET `Read` = 1 WHERE ID = ? AND EmployeeID = ?";
        return $this->execute($sql, [$notificationId, $employeeId]);
    }
}