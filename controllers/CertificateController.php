<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/CertificateModel.php';
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/SubjectModel.php';

class CertificateController extends Controller {
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
     * Liệt kê tất cả chứng chỉ của nhân viên hiện tại
     */
    public function index() {
        $employeeId = $this->checkAuth();
        
        $certificates = $this->employeeModel->getCertificates($employeeId);
        $employee = $this->employeeModel->findById($employeeId);
        
        // Lấy dữ liệu thanh bên
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('certificates/index', [
            'certificates' => $certificates,
            'employee' => $employee,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Chứng chỉ của tôi'
        ]);
    }

    /**
     * Hiển thị chi tiết chứng chỉ
     */
    public function show($code) {
        $employeeId = $this->checkAuth();
        
        $certificate = $this->certificateModel->getCertificate($code);
        
        if (!$certificate) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        // Kiểm tra quyền - chỉ chủ sở hữu được xem
        if ($certificate['EmployeeID'] != $employeeId) {
            http_response_code(403);
            $this->render('error/403');
            return;
        }

        $this->render('certificates/show', [
            'certificate' => $certificate,
            'pageTitle' => 'Chứng chỉ - ' . $certificate['SubjectName']
        ]);
    }

    /**
     * Tải chứng chỉ dưới dạng PDF
     */
    public function download($code) {
        $employeeId = $this->checkAuth();
        
        $certificate = $this->certificateModel->getCertificate($code);
        
        if (!$certificate || $certificate['EmployeeID'] != $employeeId) {
            http_response_code(404);
            die('Chứng chỉ không tồn tại');
        }

        // Tạo PDF bằng TCPDF hoặc thư viện tương tự
        $this->generatePDF($certificate);
    }

    /**
     * Hiển thị giao diện in chứng chỉ
     */
    public function printView($code) {
        $employeeId = $this->checkAuth();
        
        $certificate = $this->certificateModel->getCertificate($code);
        
        if (!$certificate || $certificate['EmployeeID'] != $employeeId) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        $this->render('certificates/print', [
            'certificate' => $certificate,
            'pageTitle' => 'In chứng chỉ'
        ]);
    }

    /**
     * Xác minh chứng chỉ (endpoint công khai)
     */
    public function verify($code) {
        $certificate = $this->certificateModel->getCertificate($code);
        
        if (!$certificate) {
            $this->render('certificates/verify', [
                'valid' => false,
                'message' => 'Chứng chỉ không tồn tại hoặc đã bị thu hồi'
            ]);
            return;
        }

        $isValid = $certificate['Status'] == 1 && 
                   (empty($certificate['ExpiresAt']) || strtotime($certificate['ExpiresAt']) > time());

        $this->render('certificates/verify', [
            'valid' => $isValid,
            'certificate' => $certificate,
            'message' => $isValid ? 'Chứng chỉ hợp lệ' : 'Chứng chỉ đã hết hạn'
        ]);
    }

    /**
     * Tạo PDF chứng chỉ
     */
    private function generatePDF($certificate) {
        // Đây là placeholder - triển khai với TCPDF hoặc thư viện tương tự
        // Hiện tại, chỉ tạo trang HTML đơn giản
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="certificate-' . $certificate['CertificateCode'] . '.pdf"');
        
        // Trong môi trường thực tế, sử dụng thư viện PDF như TCPDF
        // Hiện tại, hiển thị phiên bản HTML
        ob_start();
        require __DIR__ . '/../views/certificates/pdf_template.php';
        $html = ob_get_clean();
        
        // Chuyển HTML sang PDF (yêu cầu thư viện)
        // echo $pdfContent;
        echo $html; // Tạm thời trả về HTML
    }

    /**
     * Lấy dữ liệu thanh bên
     */
    private function getSidebarData($employeeId) {
        $progress = [
            'total_subjects' => 0,
            'completed_subjects' => 0,
            'total_certificates' => 0
        ];

        try {
            $stmt = $this->db->prepare("
                SELECT 
                    COUNT(DISTINCT s.ID) as total_subjects,
                    SUM(CASE WHEN 
                        EXISTS (
                            SELECT 1 
                            FROM " . TBL_EXAM . " e 
                            WHERE e.SubjectID = s.ID 
                            AND e.EmployeeID = ? 
                            AND e.Passed = 1
                        )
                        THEN 1 ELSE 0 
                    END) as completed_subjects,
                    (SELECT COUNT(*) 
                     FROM " . TBL_CERTIFICATE . " c 
                     WHERE c.EmployeeID = ? 
                     AND c.Status = 1) as total_certificates
                FROM " . TBL_SUBJECT . " s
                INNER JOIN " . TBL_KNOWLEDGE_GROUP . " kg ON s.KnowledgeGroupID = kg.ID
                INNER JOIN " . TBL_ASSIGN . " a ON kg.ID = a.KnowledgeGroupID
                INNER JOIN " . TBL_POSITION . " p ON p.ID = a.PositionID
                WHERE p.ID = (
                    SELECT PositionID 
                    FROM " . TBL_EMPLOYEE . "
                    WHERE ID = ?
                )
                AND s.Status = 1
                AND s.DeletedAt IS NULL
                AND kg.Status = 1
                AND a.Status = 1
                AND (a.AssignDate <= CURRENT_DATE)
                AND (a.ExpireDate IS NULL OR a.ExpireDate >= CURRENT_DATE)
            ");
            $stmt->execute([$employeeId, $employeeId, $employeeId]);
            $progressResult = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($progressResult) {
                $progress = [
                    'total_subjects' => (int)($progressResult['total_subjects'] ?? 0),
                    'completed_subjects' => (int)($progressResult['completed_subjects'] ?? 0),
                    'total_certificates' => (int)($progressResult['total_certificates'] ?? 0)
                ];
            }
        } catch (Exception $e) {
            error_log('Lỗi lấy dữ liệu thanh bên: ' . $e->getMessage());
        }

        try {
            $positionStmt = $this->db->prepare("
                SELECT p.PositionName 
                FROM " . TBL_EMPLOYEE . " e
                LEFT JOIN " . TBL_POSITION . " p ON e.PositionID = p.ID 
                WHERE e.ID = ?
            ");
            $positionStmt->execute([$employeeId]);
            $position = $positionStmt->fetch(PDO::FETCH_ASSOC);
        } catch (Exception $e) {
            error_log('Lỗi lấy vị trí: ' . $e->getMessage());
            $position = ['PositionName' => 'Nhân viên'];
        }

        return [
            'progress' => $progress,
            'position' => $position['PositionName'] ?? 'Nhân viên'
        ];
    }
}
?>