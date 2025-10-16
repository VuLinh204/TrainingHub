<?php
require_once __DIR__ . '/../core/Model.php';

class CertificateModel extends Model {
    protected $table = 'tblTrain_Certificate';

    /**
     * Tạo chứng chỉ mới (trạng thái chờ duyệt)
     */
    public function generateCertificate($employeeId, $subjectId) {
        try {
            $completion = $this->checkCompletion($employeeId, $subjectId);
            if (!$completion) return false;

            $existing = $this->query(
                "SELECT ID FROM {$this->table} 
                 WHERE EmployeeID = ? AND SubjectID = ? AND Status IN (0,1)",
                [$employeeId, $subjectId]
            );
            if (!empty($existing)) return false;

            $certCode = $this->generateUniqueCode($employeeId, $subjectId);
            $certificateHash = hash('sha256', $employeeId . $subjectId . $certCode . time());
            $expiresAt = date('Y-m-d H:i:s', strtotime('+2 years'));

            $data = [
                'EmployeeID' => $employeeId,
                'SubjectID' => $subjectId,
                'CertificateHash' => $certificateHash,
                'CertificateCode' => $certCode,
                'IssuedAt' => date('Y-m-d H:i:s'),
                'ExpiresAt' => $expiresAt,
                'Status' => 0,
                'ApprovedBy' => null,
                'ApprovedAt' => null
            ];
            return $this->insert($this->table, $data);
        } catch (Exception $e) {
            throw new Exception("Failed to generate certificate: " . $e->getMessage());
        }
    }

    /**
     * Kiểm tra hoàn thành khóa học
     */
    private function checkCompletion($employeeId, $subjectId) {
        $sql = "SELECT 1 FROM tblTrain_Exam 
                WHERE EmployeeID = ? AND SubjectID = ? AND Passed = 1 LIMIT 1";
        return !empty($this->query($sql, [$employeeId, $subjectId]));
    }

    /**
     * Tạo mã chứng chỉ unique
     */
    private function generateUniqueCode($employeeId, $subjectId) {
        do {
            $code = sprintf(
                "CERT-%s-%05d-%03d-%s",
                date('ym'),
                $employeeId,
                $subjectId,
                strtoupper(substr(md5(uniqid()), 0, 6))
            );
        } while ($this->query("SELECT 1 FROM {$this->table} WHERE CertificateCode = ?", [$code]));
        return $code;
    }
    
    /**
     * Lấy danh sách chứng chỉ chờ duyệt
     */
    public function getPendingCertificates() {
        $sql = "SELECT c.*, 
                CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
                e.Email AS EmployeeEmail,
                p.PositionName,
                d.DepartmentName,
                s.Title AS SubjectName,
                ex.Score AS ExamScore,
                ex.CompletedAt AS CompletionDate,
                ex.TotalQuestions,
                ex.CorrectAnswers
            FROM {$this->table} c
            JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
            JOIN tblTrain_Position p ON e.PositionID = p.ID
            JOIN tblTrain_Department d ON p.DepartmentID = d.ID
            JOIN tblTrain_Subject s ON c.SubjectID = s.ID
            LEFT JOIN (
                SELECT EmployeeID, SubjectID, MAX(Score) AS Score, 
                       MAX(EndTime) AS CompletedAt, TotalQuestions, CorrectAnswers
                FROM tblTrain_Exam 
                WHERE Passed = 1 
                GROUP BY EmployeeID, SubjectID
            ) ex ON c.EmployeeID = ex.EmployeeID AND c.SubjectID = ex.SubjectID
            WHERE c.Status = 0
            ORDER BY c.IssuedAt DESC";
        return $this->query($sql);
    }

    /**
     * Lấy tất cả chứng chỉ với bộ lọc
     */
    public function getAllCertificates($status = 'all', $search = '') {
        $where = [];
        $params = [];
        
        if ($status !== 'all') {
            $statusMap = [
                'pending' => 0,
                'approved' => 1,
                'revoked' => 2
            ];
            $where[] = "c.Status = ?";
            $params[] = $statusMap[$status] ?? 0;
        }
        
        if (!empty($search)) {
            $where[] = "(c.CertificateCode LIKE ? OR 
                        CONCAT(e.FirstName, ' ', e.LastName) LIKE ? OR 
                        s.Title LIKE ?)";
            $searchParam = "%{$search}%";
            $params[] = $searchParam;
            $params[] = $searchParam;
            $params[] = $searchParam;
        }
        
        $whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';
        
        $sql = "SELECT c.*, 
                CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                e.Email as EmployeeEmail,
                s.Title as SubjectName,
                CONCAT(approver.FirstName, ' ', approver.LastName) as ApproverName,
                CONCAT(revoker.FirstName, ' ', revoker.LastName) as RevokerName
                FROM {$this->table} c
                JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                LEFT JOIN tblTrain_Employee approver ON c.ApprovedBy = approver.ID
                LEFT JOIN tblTrain_Employee revoker ON c.RevokedBy = revoker.ID
                {$whereClause}
                ORDER BY c.IssuedAt DESC";
        
        return $this->query($sql, $params);
    }

    /**
     * Lấy chi tiết chứng chỉ
     */
    public function getCertificateWithDetails($certId) {
        $sql = "SELECT c.*, 
                CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
                e.Email AS EmployeeEmail,
                p.PositionName,
                d.DepartmentName,
                s.Title AS SubjectName,
                s.Description AS SubjectDescription,
                ex.Score AS ExamScore,
                ex.TotalQuestions,
                ex.CorrectAnswers,
                ex.EndTime AS CompletionDate,
                CONCAT(approver.FirstName, ' ', approver.LastName) AS ApproverName,
                c.ApprovedAt
            FROM {$this->table} c
            JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
            JOIN tblTrain_Position p ON e.PositionID = p.ID
            JOIN tblTrain_Department d ON p.DepartmentID = d.ID
            JOIN tblTrain_Subject s ON c.SubjectID = s.ID
            LEFT JOIN (
                SELECT EmployeeID, SubjectID, Score, TotalQuestions, CorrectAnswers, EndTime
                FROM tblTrain_Exam 
                WHERE Passed = 1 
                ORDER BY EndTime DESC
            ) ex ON c.EmployeeID = ex.EmployeeID AND c.SubjectID = ex.SubjectID
            LEFT JOIN tblTrain_Employee approver ON c.ApprovedBy = approver.ID
            WHERE c.ID = ?";
        $r = $this->query($sql, [$certId]);
        return $r ? $r[0] : null;
    }

    /**
     * Phê duyệt chứng chỉ
     */
    public function approveCertificate($certId, $adminId) {
        $data = [
            'Status' => 1,
            'ApprovedBy' => $adminId,
            'ApprovedAt' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($this->table, $data, "ID = ?", [$certId]);
    }

    /**
     * Từ chối chứng chỉ
     */
    public function rejectCertificate($certId, $reason, $adminId) {
        // Ghi lại lý do từ chối vào bảng log hoặc field riêng
        $data = [
            'Status' => 2, // Hoặc status khác cho "từ chối"
            'RevokedBy' => $adminId,
            'RevokedAt' => date('Y-m-d H:i:s'),
            'RevokeReason' => $reason
        ];
        
        return $this->update($this->table, $data, "ID = ?", [$certId]);
    }

    /**
     * Thu hồi chứng chỉ
     */
    public function revokeCertificate($certId, $reason, $adminId) {
        $data = [
            'Status' => 2,
            'RevokedAt' => date('Y-m-d H:i:s'),
            'RevokedBy' => $adminId,
            'RevokeReason' => $reason
        ];

        return $this->update($this->table, $data, "ID = ?", [$certId]);
    }

    /**
     * Khôi phục chứng chỉ
     */
    public function restoreCertificate($certId, $adminId) {
        $data = [
            'Status' => 1,
            'RevokedAt' => null,
            'RevokedBy' => null,
            'RevokeReason' => null,
            'ApprovedBy' => $adminId,
            'ApprovedAt' => date('Y-m-d H:i:s')
        ];
        
        return $this->update($this->table, $data, "ID = ?", [$certId]);
    }

    /**
     * Thống kê chứng chỉ
     */
    public function getCertificateStatistics() {
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Status = 0 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as revoked,
                SUM(CASE WHEN Status = 1 AND (ExpiresAt IS NULL OR ExpiresAt > NOW()) THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN Status = 1 AND ExpiresAt IS NOT NULL AND ExpiresAt <= NOW() THEN 1 ELSE 0 END) as expired
                FROM {$this->table}";
        
        $result = $this->query($sql);
        $stats = $result ? $result[0] : [];
        
        // Thống kê theo tháng
        $sql = "SELECT 
                DATE_FORMAT(IssuedAt, '%Y-%m') as month,
                COUNT(*) as count
                FROM {$this->table}
                WHERE IssuedAt >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(IssuedAt, '%Y-%m')
                ORDER BY month ASC";
        
        $stats['monthly'] = $this->query($sql);
        
        // Top khóa học có nhiều chứng chỉ nhất
        $sql = "SELECT s.Title, COUNT(*) as cert_count
                FROM {$this->table} c
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                WHERE c.Status = 1
                GROUP BY s.ID, s.Title
                ORDER BY cert_count DESC
                LIMIT 10";
        
        $stats['top_subjects'] = $this->query($sql);
        
        return $stats;
    }

    /**
     * Lấy chứng chỉ cho báo cáo
     */
    public function getCertificatesForReport($dateFrom, $dateTo) {
        $sql = "SELECT 
                    c.*, 
                    CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
                    e.Email AS EmployeeEmail,
                    p.PositionName AS PositionName,
                    d.DepartmentName AS DepartmentName,
                    s.Title AS SubjectName
                FROM {$this->table} c
                JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
                LEFT JOIN tblTrain_Position p ON e.PositionID = p.ID
                LEFT JOIN tblTrain_Department d ON p.DepartmentID = d.ID
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                WHERE DATE(c.IssuedAt) BETWEEN ? AND ?
                ORDER BY c.IssuedAt DESC";

        return $this->query($sql, [$dateFrom, $dateTo]);
    }

    /**
     * Lấy chứng chỉ của nhân viên
     */
    public function getEmployeeCertificates($employeeId) {
        $sql = "SELECT c.*, 
                s.Title as SubjectName,
                s.Description as SubjectDescription,
                CASE 
                    WHEN c.Status = 0 THEN 'pending'
                    WHEN c.Status = 1 AND (c.ExpiresAt IS NULL OR c.ExpiresAt > NOW()) THEN 'active'
                    WHEN c.Status = 1 AND c.ExpiresAt <= NOW() THEN 'expired'
                    WHEN c.Status = 2 THEN 'revoked'
                END as CertStatus
                FROM {$this->table} c
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                WHERE c.EmployeeID = ?
                ORDER BY c.IssuedAt DESC";
        
        return $this->query($sql, [$employeeId]);
    }

    /**
     * Lấy thông tin chứng chỉ theo mã
     */
    public function getCertificate($code) {
        $sql = "SELECT c.*, 
                CONCAT(e.FirstName, ' ', e.LastName) AS EmployeeName,
                e.Email AS EmployeeEmail,
                p.PositionName,
                d.DepartmentName,
                s.Title AS SubjectName,
                s.Description AS SubjectDescription,
                s.Duration AS SubjectDuration,
                CONCAT(approver.FirstName, ' ', approver.LastName) AS ApproverName,
                approver.Email AS ApproverEmail,
                ex.Score AS ExamScore,
                ex.TotalQuestions,
                ex.CorrectAnswers,
                ex.EndTime AS CompletionDate
            FROM {$this->table} c
            JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
            JOIN tblTrain_Position p ON e.PositionID = p.ID
            JOIN tblTrain_Department d ON p.DepartmentID = d.ID
            JOIN tblTrain_Subject s ON c.SubjectID = s.ID
            LEFT JOIN tblTrain_Employee approver ON c.ApprovedBy = approver.ID
            LEFT JOIN (
                SELECT EmployeeID, SubjectID, Score, TotalQuestions, CorrectAnswers, EndTime
                FROM tblTrain_Exam 
                WHERE Passed = 1 
                ORDER BY EndTime DESC
            ) ex ON c.EmployeeID = ex.EmployeeID AND c.SubjectID = ex.SubjectID
            WHERE c.CertificateCode = ?";
        $r = $this->query($sql, [$code]);
        return $r ? $r[0] : null;
    }

    /**
     * Kiểm tra chứng chỉ có hợp lệ không
     */
    public function verifyCertificate($code) {
        $certificate = $this->getCertificate($code);
        
        if (!$certificate) {
            return [
                'valid' => false,
                'message' => 'Chứng chỉ không tồn tại'
            ];
        }

        if ($certificate['Status'] == 0) {
            return [
                'valid' => false,
                'message' => 'Chứng chỉ chưa được phê duyệt',
                'certificate' => $certificate
            ];
        }

        if ($certificate['Status'] == 2) {
            return [
                'valid' => false,
                'message' => 'Chứng chỉ đã bị thu hồi',
                'certificate' => $certificate
            ];
        }

        // Kiểm tra hết hạn
        if (!empty($certificate['ExpiresAt']) && strtotime($certificate['ExpiresAt']) < time()) {
            return [
                'valid' => false,
                'message' => 'Chứng chỉ đã hết hạn',
                'certificate' => $certificate
            ];
        }

        return [
            'valid' => true,
            'message' => 'Chứng chỉ hợp lệ',
            'certificate' => $certificate
        ];
    }

    /**
     * Lấy số lượng chứng chỉ theo trạng thái
     */
    public function countByStatus($employeeId = null) {
        $where = $employeeId ? "WHERE EmployeeID = ?" : "";
        $params = $employeeId ? [$employeeId] : [];
        
        $sql = "SELECT 
                COUNT(*) as total,
                SUM(CASE WHEN Status = 0 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as revoked
                FROM {$this->table}
                {$where}";
        
        $result = $this->query($sql, $params);
        return $result ? $result[0] : null;
    }

    /**
     * Lấy chứng chỉ sắp hết hạn
     */
    public function getExpiringCertificates($days = 30) {
        $sql = "SELECT c.*, 
                CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                e.Email as EmployeeEmail,
                s.Title as SubjectName,
                DATEDIFF(c.ExpiresAt, NOW()) as DaysRemaining
                FROM {$this->table} c
                JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                WHERE c.Status = 1 
                AND c.ExpiresAt IS NOT NULL
                AND c.ExpiresAt > NOW()
                AND c.ExpiresAt <= DATE_ADD(NOW(), INTERVAL ? DAY)
                ORDER BY c.ExpiresAt ASC";
        
        return $this->query($sql, [$days]);
    }

    /**
     * Lấy lịch sử chứng chỉ của nhân viên
     */
    public function getCertificateHistory($employeeId) {
        $sql = "SELECT c.*, 
                s.Title as SubjectName,
                CONCAT(approver.FirstName, ' ', approver.LastName) as ApproverName,
                CONCAT(revoker.FirstName, ' ', revoker.LastName) as RevokerName
                FROM {$this->table} c
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                LEFT JOIN tblTrain_Employee approver ON c.ApprovedBy = approver.ID
                LEFT JOIN tblTrain_Employee revoker ON c.RevokedBy = revoker.ID
                WHERE c.EmployeeID = ?
                ORDER BY c.IssuedAt DESC";
        
        return $this->query($sql, [$employeeId]);
    }

    /**
     * Tìm kiếm chứng chỉ
     */
    public function searchCertificates($keyword, $filters = []) {
        $where = ["(c.CertificateCode LIKE ? OR 
                   CONCAT(e.FirstName, ' ', e.LastName) LIKE ? OR 
                   s.Title LIKE ?)"];
        $params = ["%{$keyword}%", "%{$keyword}%", "%{$keyword}%"];
        
        if (isset($filters['status'])) {
            $where[] = "c.Status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['date_from'])) {
            $where[] = "DATE(c.IssuedAt) >= ?";
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $where[] = "DATE(c.IssuedAt) <= ?";
            $params[] = $filters['date_to'];
        }
        
        if (isset($filters['subject_id'])) {
            $where[] = "c.SubjectID = ?";
            $params[] = $filters['subject_id'];
        }
        
        $whereClause = implode(' AND ', $where);
        
        $sql = "SELECT c.*, 
                CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                e.Email as EmployeeEmail,
                s.Title as SubjectName
                FROM {$this->table} c
                JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                WHERE {$whereClause}
                ORDER BY c.IssuedAt DESC";
        
        return $this->query($sql, $params);
    }

    /**
     * Cập nhật ngày hết hạn chứng chỉ
     */
    public function updateExpiryDate($certId, $expiresAt) {
        $data = [
            'ExpiresAt' => $expiresAt
        ];
        
        return $this->update($this->table, $data, "ID = ?", [$certId]);
    }

    /**
     * Gia hạn chứng chỉ
     */
    public function renewCertificate($certId, $years = 2) {
        $certificate = $this->find($certId);
        
        if (!$certificate) {
            return false;
        }
        
        $currentExpiry = $certificate['ExpiresAt'] ?? date('Y-m-d H:i:s');
        $newExpiry = date('Y-m-d H:i:s', strtotime($currentExpiry . " +{$years} years"));
        
        return $this->updateExpiryDate($certId, $newExpiry);
    }

    /**
     * Xóa chứng chỉ (soft delete)
     */
    public function deleteCertificate($certId) {
        // Có thể thêm field DeletedAt thay vì xóa thật
        return $this->delete($this->table, "ID = ?", [$certId]);
    }

    /**
     * Lấy báo cáo chứng chỉ theo phòng ban
     */
    public function getCertificatesByDepartment() {
        $sql = "SELECT 
                d.DepartmentName,
                COUNT(*) AS total_certificates,
                SUM(CASE WHEN c.Status = 1 THEN 1 ELSE 0 END) AS approved_count,
                COUNT(DISTINCT c.EmployeeID) AS certified_employees
            FROM {$this->table} c
            JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
            JOIN tblTrain_Position p ON e.PositionID = p.ID
            JOIN tblTrain_Department d ON p.DepartmentID = d.ID
            GROUP BY d.ID, d.DepartmentName
            ORDER BY total_certificates DESC";
        return $this->query($sql);
    }

    /**
     * Lấy báo cáo chứng chỉ theo tháng
     */
    public function getMonthlyReport($year = null) {
        $year = $year ?? date('Y');
        
        $sql = "SELECT 
                MONTH(IssuedAt) as month,
                COUNT(*) as total,
                SUM(CASE WHEN Status = 1 THEN 1 ELSE 0 END) as approved,
                SUM(CASE WHEN Status = 0 THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN Status = 2 THEN 1 ELSE 0 END) as revoked
                FROM {$this->table}
                WHERE YEAR(IssuedAt) = ?
                GROUP BY MONTH(IssuedAt)
                ORDER BY month ASC";
        
        return $this->query($sql, [$year]);
    }
}