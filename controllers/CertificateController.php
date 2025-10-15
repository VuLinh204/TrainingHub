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
     * List all certificates for current employee
     */
    public function index() {
        $employeeId = $this->checkAuth();
        
        $certificates = $this->certificateModel->getEmployeeCertificates($employeeId);
        $employee = $this->employeeModel->findById($employeeId);
        
        // Get sidebar data
        $sidebarData = $this->getSidebarData($employeeId);
        
        $this->render('certificate/index', [
            'certificates' => $certificates,
            'employee' => $employee,
            'sidebarData' => $sidebarData,
            'pageTitle' => 'Chứng chỉ của tôi'
        ]);
    }

    /**
     * Show certificate details
     */
    public function show($code) {
        $employeeId = $this->checkAuth();
        
        $certificate = $this->certificateModel->getCertificate($code);
        
        if (!$certificate) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        // Security check - only owner can view
        if ($certificate['EmployeeID'] != $employeeId) {
            http_response_code(403);
            $this->render('error/403');
            return;
        }

        $this->render('certificate/show', [
            'certificate' => $certificate,
            'pageTitle' => 'Chứng chỉ - ' . $certificate['SubjectName']
        ]);
    }

    /**
     * Download certificate as PDF
     */
    public function download($code) {
        $employeeId = $this->checkAuth();
        
        $certificate = $this->certificateModel->getCertificate($code);
        
        if (!$certificate || $certificate['EmployeeID'] != $employeeId) {
            http_response_code(404);
            die('Certificate not found');
        }

        // Generate PDF using TCPDF or similar library
        $this->generatePDF($certificate);
    }

    /**
     * Print view for certificate
     */
    public function printView($code) {
        $employeeId = $this->checkAuth();
        
        $certificate = $this->certificateModel->getCertificate($code);
        
        if (!$certificate || $certificate['EmployeeID'] != $employeeId) {
            http_response_code(404);
            $this->render('error/404');
            return;
        }

        $this->render('certificate/print', [
            'certificate' => $certificate,
            'pageTitle' => 'In chứng chỉ'
        ]);
    }

    /**
     * Verify certificate (public endpoint)
     */
    public function verify($code) {
        $certificate = $this->certificateModel->getCertificate($code);
        
        if (!$certificate) {
            $this->render('certificate/verify', [
                'valid' => false,
                'message' => 'Chứng chỉ không tồn tại hoặc đã bị thu hồi'
            ]);
            return;
        }

        $isValid = $certificate['Status'] == 1 && 
                   (empty($certificate['ExpiresAt']) || strtotime($certificate['ExpiresAt']) > time());

        $this->render('certificate/verify', [
            'valid' => $isValid,
            'certificate' => $certificate,
            'message' => $isValid ? 'Chứng chỉ hợp lệ' : 'Chứng chỉ đã hết hạn'
        ]);
    }

    /**
     * Generate certificate PDF
     */
    private function generatePDF($certificate) {
        // This is a placeholder - implement with TCPDF or similar
        // For now, we'll just create a simple HTML page
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="certificate-' . $certificate['CertificateCode'] . '.pdf"');
        
        // In production, use proper PDF library like TCPDF
        // For now, show HTML version
        ob_start();
        require __DIR__ . '/../views/certificate/pdf_template.php';
        $html = ob_get_clean();
        
        // Convert HTML to PDF (requires library)
        // echo $pdfContent;
        echo $html; // Temporary fallback
    }

    /**
     * Get sidebar data
     */
    private function getSidebarData($employeeId) {
        $progress = [
            'total_subjects' => 0,
            'completed_subjects' => 0,
            'total_certificates' => 0
        ];

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
            INNER JOIN " . TBL_ASSIGN . " a ON s.ID = a.SubjectID
            INNER JOIN " . TBL_POSITION . " p ON p.ID = a.PositionID
            WHERE p.ID = (
                SELECT PositionID 
                FROM " . TBL_EMPLOYEE . "
                WHERE ID = ?
            )
            AND s.Status = 1
        ");
        $stmt->execute([$employeeId, $employeeId, $employeeId]);
        $progressResult = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($progressResult) {
            $progress = $progressResult;
        }

        $positionStmt = $this->db->prepare("
            SELECT p.PositionName 
            FROM " . TBL_EMPLOYEE . " e
            LEFT JOIN " . TBL_POSITION . " p ON e.PositionID = p.ID 
            WHERE e.ID = ?
        ");
        $positionStmt->execute([$employeeId]);
        $position = $positionStmt->fetch(PDO::FETCH_ASSOC);

        return [
            'progress' => $progress,
            'position' => $position['PositionName'] ?? 'Nhân viên'
        ];
    }
}