<?php
/**
 * Admin Certificate Controller
 * Quản lý duyệt và thu hồi chứng chỉ
 */
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/CertificateModel.php';
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/SubjectModel.php';

class AdminCertificateController extends Controller {
    private $certificateModel;
    private $employeeModel;
    private $subjectModel;

    public function __construct() {
        parent::__construct();
        $this->certificateModel = new CertificateModel();
        $this->employeeModel = new EmployeeModel();
        $this->subjectModel = new SubjectModel();
    }

    /**
     * Kiểm tra quyền admin/quản lý
     */
    private function checkAdminAuth() {
        $employeeId = $this->checkAuth();
        
        // Lấy thông tin nhân viên
        $employee = $this->employeeModel->findById($employeeId);
        
        // Kiểm tra PositionID (giả sử 5 là quản lý, 6 là admin)
        if (!isset($employee['Role']) || $employee['Role'] !== 'admin') {
            http_response_code(403);
            $this->render('error/403', [
                'message' => 'Bạn không có quyền truy cập trang này'
            ]);
            exit;
        }
        
        return $employeeId;
    }

    /**
     * Danh sách chứng chỉ chờ duyệt
     */
    public function pendingList() {
        $adminId = $this->checkAdminAuth();
        
        // Lấy danh sách chứng chỉ chờ duyệt
        $pendingCerts = $this->certificateModel->getPendingCertificates();
        
        // Lấy sidebar data
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/certificates/pending', [
            'certificates' => $pendingCerts,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Chứng chỉ chờ duyệt'
        ]);
    }

    /**
     * Danh sách tất cả chứng chỉ
     */
    public function index() {
        $adminId = $this->checkAdminAuth();
        
        // Lọc theo trạng thái
        $status = $_GET['status'] ?? 'all';
        $search = $_GET['search'] ?? '';
        
        $certificates = $this->certificateModel->getAllCertificates($status, $search);
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/certificates/list', [
            'certificates' => $certificates,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'currentStatus' => $status,
            'searchQuery' => $search,
            'pageTitle' => 'Quản lý chứng chỉ'
        ]);
    }

    /**
     * Chi tiết chứng chỉ để duyệt
     */
    public function reviewDetail($certId) {
        $adminId = $this->checkAdminAuth();
        
        $certificate = $this->certificateModel->getCertificateWithDetails($certId);
        
        if (!$certificate) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }
        
        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);
        
        $this->render('admin/certificates/review', [
            'certificate' => $certificate,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Duyệt chứng chỉ'
        ]);
    }

    /**
     * Phê duyệt chứng chỉ
     */
    public function approve($certId) {
        $adminId = $this->checkAdminAuth();
        
        try {
            $certificate = $this->certificateModel->find($certId);
            
            if (!$certificate) {
                throw new Exception('Chứng chỉ không tồn tại');
            }
            
            if ($certificate['Status'] == 1) {
                throw new Exception('Chứng chỉ đã được duyệt trước đó');
            }
            
            // Cập nhật trạng thái
            $result = $this->certificateModel->approveCertificate($certId, $adminId);
            
            if ($result) {
                // Gửi thông báo cho nhân viên
                $this->sendNotification(
                    $certificate['EmployeeID'],
                    'Chứng chỉ đã được phê duyệt',
                    'Chứng chỉ ' . $certificate['CertificateCode'] . ' đã được phê duyệt',
                    '/certificates/' . $certificate['CertificateCode']
                );
                
                $_SESSION['success'] = 'Đã phê duyệt chứng chỉ thành công';
            } else {
                throw new Exception('Không thể phê duyệt chứng chỉ');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        $this->redirect('admin/certificates/pending');
    }

    /**
     * Từ chối chứng chỉ
     */
    public function reject() {
        $adminId = $this->checkAdminAuth();
        
        $certId = $_POST['cert_id'] ?? null;
        $reason = $_POST['reason'] ?? '';
        
        if (!$certId || empty($reason)) {
            $_SESSION['error'] = 'Vui lòng nhập lý do từ chối';
            $this->redirect('admin/certificates/pending');
            return;
        }
        
        try {
            $certificate = $this->certificateModel->find($certId);
            
            if (!$certificate) {
                throw new Exception('Chứng chỉ không tồn tại');
            }
            
            // Từ chối chứng chỉ
            $result = $this->certificateModel->rejectCertificate($certId, $reason, $adminId);
            
            if ($result) {
                // Gửi thông báo cho nhân viên
                $this->sendNotification(
                    $certificate['EmployeeID'],
                    'Chứng chỉ bị từ chối',
                    'Chứng chỉ ' . $certificate['CertificateCode'] . ' bị từ chối. Lý do: ' . $reason,
                    '/certificates'
                );
                
                $_SESSION['success'] = 'Đã từ chối chứng chỉ';
            } else {
                throw new Exception('Không thể từ chối chứng chỉ');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        $this->redirect('admin/certificates/pending');
    }

    /**
     * Thu hồi chứng chỉ đã cấp
     */
    public function revoke() {
        $adminId = $this->checkAdminAuth();
        
        $certId = $_POST['cert_id'] ?? null;
        $reason = $_POST['reason'] ?? '';
        
        if (!$certId || empty($reason)) {
            $_SESSION['error'] = 'Vui lòng nhập lý do thu hồi';
            $this->redirect('admin/certificates');
            return;
        }
        
        try {
            $certificate = $this->certificateModel->find($certId);
            
            if (!$certificate) {
                throw new Exception('Chứng chỉ không tồn tại');
            }
            
            if ($certificate['Status'] == 2) {
                throw new Exception('Chứng chỉ đã bị thu hồi');
            }
            
            // Thu hồi chứng chỉ
            $result = $this->certificateModel->revokeCertificate($certId, $reason, $adminId);
            
            if ($result) {
                // Gửi thông báo cho nhân viên
                $this->sendNotification(
                    $certificate['EmployeeID'],
                    'Chứng chỉ bị thu hồi',
                    'Chứng chỉ ' . $certificate['CertificateCode'] . ' đã bị thu hồi. Lý do: ' . $reason,
                    '/certificates'
                );
                
                $_SESSION['success'] = 'Đã thu hồi chứng chỉ';
            } else {
                throw new Exception('Không thể thu hồi chứng chỉ');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        $this->redirect('admin/certificates');
    }

    /**
     * Khôi phục chứng chỉ đã thu hồi
     */
    public function restore($certId) {
        $adminId = $this->checkAdminAuth();
        
        try {
            $certificate = $this->certificateModel->find($certId);
            
            if (!$certificate) {
                throw new Exception('Chứng chỉ không tồn tại');
            }
            
            if ($certificate['Status'] != 2) {
                throw new Exception('Chỉ có thể khôi phục chứng chỉ đã bị thu hồi');
            }
            
            // Khôi phục chứng chỉ
            $result = $this->certificateModel->restoreCertificate($certId, $adminId);
            
            if ($result) {
                // Gửi thông báo cho nhân viên
                $this->sendNotification(
                    $certificate['EmployeeID'],
                    'Chứng chỉ được khôi phục',
                    'Chứng chỉ ' . $certificate['CertificateCode'] . ' đã được khôi phục',
                    '/certificates/' . $certificate['CertificateCode']
                );
                
                $_SESSION['success'] = 'Đã khôi phục chứng chỉ';
            } else {
                throw new Exception('Không thể khôi phục chứng chỉ');
            }
        } catch (Exception $e) {
            $_SESSION['error'] = $e->getMessage();
        }
        
        $this->redirect('admin/certificates');
    }

    /**
     * Thống kê chứng chỉ
     */
    public function statistics() {
        $adminId = $this->checkAdminAuth();

        // Lấy tham số lọc từ URL
        $dateFrom = $_GET['date_from'] ?? null;
        $dateTo = $_GET['date_to'] ?? null;

        // Gọi thống kê với bộ lọc
        $stats = $this->certificateModel->getCertificateStatistics($dateFrom, $dateTo);

        $admin = $this->employeeModel->findById($adminId);
        $sidebarData = $this->getAdminSidebarData($adminId);

        $this->render('admin/certificates/statistics', [
            'stats' => $stats,
            'employee' => $admin,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Thống kê chứng chỉ'
        ]);
    }

    /**
     * Xuất báo cáo chứng chỉ
     */
    public function exportReport() {
        $adminId = $this->checkAdminAuth();
        
        $format = $_GET['format'] ?? 'excel';
        $dateFrom = $_GET['date_from'] ?? date('Y-m-01');
        $dateTo = $_GET['date_to'] ?? date('Y-m-d');
        
        $certificates = $this->certificateModel->getCertificatesForReport($dateFrom, $dateTo);
        
        if ($format === 'excel') {
            $this->exportToExcel($certificates, $dateFrom, $dateTo);
        } else {
            $this->exportToPDF($certificates, $dateFrom, $dateTo);
        }
    }

    /**
     * Gửi thông báo cho nhân viên
     */
    private function sendNotification($employeeId, $title, $message, $link) {
        try {
            $sql = "INSERT INTO tblTrain_Notification 
                    (EmployeeID, Title, Message, Link, Type, CreatedAt)
                    VALUES (?, ?, ?, ?, 'info', NOW())";
            $this->db->prepare($sql)->execute([$employeeId, $title, $message, $link]);
        } catch (Exception $e) {
            error_log('Failed to send notification: ' . $e->getMessage());
        }
    }

    /**
     * Lấy sidebar data cho admin
     */
    private function getAdminSidebarData($adminId) {
        $sql = "SELECT 
                (SELECT COUNT(*) FROM tblTrain_Certificate WHERE Status = 0) as pending_count,
                (SELECT COUNT(*) FROM tblTrain_Certificate WHERE Status = 1) as approved_count,
                (SELECT COUNT(*) FROM tblTrain_Certificate WHERE Status = 2) as revoked_count,
                (SELECT COUNT(*) FROM tblTrain_Employee WHERE Status = 1) as total_employees,
                (SELECT COUNT(*) FROM tblTrain_Subject WHERE Status = 1) as total_subjects";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return [
            'stats' => $stmt->fetch(PDO::FETCH_ASSOC),
            'position' => 'Quản trị viên'
        ];
    }

    /**
     * Xuất báo cáo Excel
     */
    private function exportToExcel($certificates, $dateFrom, $dateTo) {
        header('Content-Type: application/vnd.ms-excel');
        header('Content-Disposition: attachment; filename="certificate-report-' . date('Y-m-d') . '.xls"');
        
        echo "<!DOCTYPE html>\n<html>\n<head>\n<meta charset='UTF-8'>\n</head>\n<body>\n";
        echo "<table border='1'>\n";
        echo "<tr>
                <th>Mã chứng chỉ</th>
                <th>Nhân viên</th>
                <th>Khóa học</th>
                <th>Ngày cấp</th>
                <th>Ngày hết hạn</th>
                <th>Trạng thái</th>
              </tr>\n";
        
        foreach ($certificates as $cert) {
            $status = ['Chờ duyệt', 'Đã duyệt', 'Đã thu hồi'][$cert['Status']];
            echo "<tr>
                    <td>{$cert['CertificateCode']}</td>
                    <td>{$cert['EmployeeName']}</td>
                    <td>{$cert['SubjectName']}</td>
                    <td>" . date('d/m/Y', strtotime($cert['IssuedAt'])) . "</td>
                    <td>" . (!empty($cert['ExpiresAt']) ? date('d/m/Y', strtotime($cert['ExpiresAt'])) : 'Vô thời hạn') . "</td>
                    <td>{$status}</td>
                  </tr>\n";
        }
        
        echo "</table>\n</body>\n</html>";
        exit;
    }

    /**
     * Xuất báo cáo PDF (sử dụng TCPDF hoặc tương tự)
     */
    private function exportToPDF($certificates, $dateFrom, $dateTo) {
        // Triển khai xuất PDF nếu có thư viện TCPDF
        // Tạm thời redirect về trang thống kê
        $_SESSION['info'] = 'Chức năng xuất PDF đang được phát triển';
        $this->redirect('admin/certificates/statistics');
    }
}