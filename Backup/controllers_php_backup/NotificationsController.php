<?php
require_once __DIR__ . '/../core/Controller.php';

class NotificationsController extends Controller {
    public function index() {
        // Clean output buffer
        if (ob_get_level()) ob_clean();
        
        try {
            $employeeId = $this->checkAuth();  // Giả sử method check session user
            
            // Query notifications từ DB (giả sử bảng tblTrain_Notification với fields: ID, Title, Message, Link, Read, CreatedAt)
            $notificationModel = new NotificationModel();  // Tạo model nếu cần
            $notifications = $notificationModel->getUnreadNotifications($employeeId, 10);  // Lấy 10 unread mới nhất
            
            // Normalize field names: model returns is_read, frontend expects read
            $normalized = array_map(function($n) {
                $n['read'] = isset($n['is_read']) ? (bool)$n['is_read'] : false;
                unset($n['is_read']);
                // ensure keys use lowercase as frontend expects
                return array_change_key_case($n, CASE_LOWER);
            }, $notifications);

            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'notifications' => $normalized
            ]);
            exit;
        } catch (Exception $e) {
            http_response_code(500);
            header('Content-Type: application/json');
            echo json_encode(['error' => 'Failed to load notifications', 'message' => $e->getMessage()]);
            exit;
        }
    }
}