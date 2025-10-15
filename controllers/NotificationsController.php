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
            
            header('Content-Type: application/json');
            echo json_encode([
                'success' => true,
                'notifications' => $notifications  // Array: [{'id':1, 'title':'New course assigned', 'message':'...', 'link':'/subjects/1', 'read':false, 'time':'2025-10-15 10:00'}]
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