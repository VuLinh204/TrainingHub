<?php
require_once __DIR__ . '/../core/Model.php';

class CertificateModel extends Model {
    protected $table = 'tblTrain_Certificate';

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
            $expiresAt = date('Y-m-d H:i:s', strtotime('+2 years'));

            $data = [
                'EmployeeID' => $employeeId,
                'SubjectID' => $subjectId,
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

    private function checkCompletion($employeeId, $subjectId) {
        $sql = "SELECT 1 FROM tblTrain_Exam 
                WHERE EmployeeID = ? AND SubjectID = ? AND Passed = 1 LIMIT 1";
        return !empty($this->query($sql, [$employeeId, $subjectId]));
    }

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
                       MAX(CompletedAt) AS CompletedAt, TotalQuestions, CorrectAnswers
                FROM tblTrain_Exam 
                WHERE Passed = 1 
                GROUP BY EmployeeID, SubjectID
            ) ex ON c.EmployeeID = ex.EmployeeID AND c.SubjectID = ex.SubjectID
            WHERE c.Status = 0
            ORDER BY c.IssuedAt DESC";
        return $this->query($sql);
    }

    public function getAllCertificates($status = 'all', $search = '') {
        $where = [];
        $params = [];
        if ($status !== 'all') {
            $statusMap = ['pending' => 0, 'approved' => 1, 'revoked' => 2];
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
     * Lấy thông tin chi tiết chứng chỉ theo ID
     */
    public function getCertificateWithDetails($id) {
        $sql = "SELECT c.*,
                    s.Title as SubjectName,
                    s.Description as SubjectDescription,
                    CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                    e.Email as EmployeeEmail,
                    p.PositionName,
                    d.DepartmentName,
                    CONCAT(approver.FirstName, ' ', approver.LastName) as ApproverName,
                    CONCAT(revoker.FirstName, ' ', revoker.LastName) as RevokerName,
                    ex.Score as ExamScore,
                    ex.TotalQuestions,
                    ex.CorrectAnswers,
                    ex.CompletedAt as CompletionDate  -- 👈 Đảm bảo có dòng này
                FROM {$this->table} c
                JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
                JOIN tblTrain_Position p ON e.PositionID = p.ID
                JOIN tblTrain_Department d ON p.DepartmentID = d.ID
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                LEFT JOIN tblTrain_Employee approver ON c.ApprovedBy = approver.ID
                LEFT JOIN tblTrain_Employee revoker ON c.RevokedBy = revoker.ID
                LEFT JOIN (
                    SELECT EmployeeID, SubjectID, Score, TotalQuestions, CorrectAnswers, CompletedAt
                    FROM tblTrain_Exam
                    WHERE Passed = 1
                    ORDER BY CompletedAt DESC
                ) ex ON c.EmployeeID = ex.EmployeeID AND c.SubjectID = ex.SubjectID
                WHERE c.ID = ?
                LIMIT 1";
        $result = $this->query($sql, [$id]);
        return $result ? $result[0] : null;
    }

    public function approveCertificate($certId, $adminId) {
        $data = [
            'Status' => 1,
            'ApprovedBy' => $adminId,
            'ApprovedAt' => date('Y-m-d H:i:s')
        ];
        return $this->update($this->table, $data, 'ID = ?', [$certId]);
    }

    public function rejectCertificate($certId, $reason, $adminId) {
        // Có thể thêm cột `RejectReason` nếu cần, hoặc xóa chứng chỉ
        return $this->delete($this->table, 'ID = ?', [$certId]);
    }

    public function revokeCertificate($certId, $reason, $adminId) {
        $data = [
            'Status' => 2,
            'RevokedBy' => $adminId,
            'RevokedAt' => date('Y-m-d H:i:s')
            // Nếu có cột `RevokeReason`, thêm vào đây
        ];
        return $this->update($this->table, $data, 'ID = ?', [$certId]);
    }

    public function restoreCertificate($certId, $adminId) {
        $data = [
            'Status' => 1,
            'RevokedBy' => null,
            'RevokedAt' => null
        ];
        return $this->update($this->table, $data, 'ID = ?', [$certId]);
    }

    public function getExpiringSoon($days = 30) {
        $sql = "SELECT c.*, 
                CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                s.Title as SubjectName
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
     * Backwards-compatible alias for older controllers
     * Some controllers may call `getEmployeeCertificates` — provide alias to avoid fatal errors.
     */
    public function getEmployeeCertificates($employeeId) {
        return $this->getCertificateHistory($employeeId);
    }

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

    public function updateExpiryDate($certId, $expiresAt) {
        $data = ['ExpiresAt' => $expiresAt];
        return $this->update($this->table, $data, "ID = ?", [$certId]);
    }

    public function renewCertificate($certId, $years = 2) {
        $certificate = $this->find($certId);
        if (!$certificate) return false;
        $currentExpiry = $certificate['ExpiresAt'] ?? date('Y-m-d H:i:s');
        $newExpiry = date('Y-m-d H:i:s', strtotime($currentExpiry . " +{$years} years"));
        return $this->updateExpiryDate($certId, $newExpiry);
    }

    public function deleteCertificate($certId) {
        return $this->delete($this->table, "ID = ?", [$certId]);
    }

    public function getCertificate($code) {
        $sql = "SELECT c.*, 
                    s.Title as SubjectName,
                    CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                    e.Email as EmployeeEmail
                FROM {$this->table} c
                JOIN tblTrain_Subject s ON c.SubjectID = s.ID
                JOIN tblTrain_Employee e ON c.EmployeeID = e.ID
                WHERE c.CertificateCode = ? 
                LIMIT 1";
        $result = $this->query($sql, [$code]);
        return $result ? $result[0] : null;
    }

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

    public function getMonthlyReport($year = null) {
        $year = $year ?? date('Y');
        $sql = "SELECT 
                DATE_FORMAT(IssuedAt, '%Y-%m') AS month,
                COUNT(*) AS count
                FROM {$this->table}
                WHERE YEAR(IssuedAt) = ?
                GROUP BY DATE_FORMAT(IssuedAt, '%Y-%m')
                ORDER BY month ASC";
        return $this->query($sql, [$year]);
    }

    /**
     * Lấy thống kê tổng quan về chứng chỉ (hỗ trợ lọc theo ngày)
     */
    public function getCertificateStatistics($dateFrom = null, $dateTo = null) {
        // Xây dựng điều kiện WHERE động
        $whereConditions = [];
        $params = [];

        if ($dateFrom) {
            $whereConditions[] = "c.IssuedAt >= ?";
            $params[] = $dateFrom;
        }
        if ($dateTo) {
            $whereConditions[] = "c.IssuedAt <= ?";
            $params[] = $dateTo;
        }

        $whereClause = !empty($whereConditions) ? "WHERE " . implode(" AND ", $whereConditions) : "";

        // 1. Thống kê trạng thái + hiệu lực
        $statusQuery = "
            SELECT 
                COUNT(*) AS total,
                SUM(CASE WHEN c.Status = 0 THEN 1 ELSE 0 END) AS pending,
                SUM(CASE WHEN c.Status = 1 THEN 1 ELSE 0 END) AS approved,
                SUM(CASE WHEN c.Status = 2 THEN 1 ELSE 0 END) AS revoked,
                SUM(CASE WHEN c.Status = 1 AND (c.ExpiresAt IS NULL OR c.ExpiresAt > NOW()) THEN 1 ELSE 0 END) AS active,
                SUM(CASE WHEN c.Status = 1 AND c.ExpiresAt IS NOT NULL AND c.ExpiresAt <= NOW() THEN 1 ELSE 0 END) AS expired
            FROM {$this->table} c
            {$whereClause}
        ";

        $statusStats = $this->query($statusQuery, $params)[0] ?? [
            'total' => 0, 'pending' => 0, 'approved' => 0,
            'revoked' => 0, 'active' => 0, 'expired' => 0
        ];

        // 2. Top 10 khóa học
        $topQuery = "
            SELECT s.Title, COUNT(*) as cert_count
            FROM {$this->table} c
            JOIN tblTrain_Subject s ON c.SubjectID = s.ID
            {$whereClause}
            GROUP BY s.ID, s.Title
            ORDER BY cert_count DESC
            LIMIT 10
        ";
        $topSubjects = $this->query($topQuery, $params);

        // 3. Dữ liệu theo tháng (12 tháng gần nhất hoặc theo khoảng thời gian)
        // Nếu có lọc ngày, lấy trong khoảng đó; nếu không, lấy 12 tháng gần nhất
        if ($dateFrom || $dateTo) {
            $monthlyQuery = "
                SELECT 
                    DATE_FORMAT(c.IssuedAt, '%Y-%m') AS month,
                    COUNT(*) AS count
                FROM {$this->table} c
                {$whereClause}
                GROUP BY DATE_FORMAT(c.IssuedAt, '%Y-%m')
                ORDER BY month ASC
            ";
        } else {
            $monthlyQuery = "
                SELECT 
                    DATE_FORMAT(c.IssuedAt, '%Y-%m') AS month,
                    COUNT(*) AS count
                FROM {$this->table} c
                WHERE c.IssuedAt >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
                GROUP BY DATE_FORMAT(c.IssuedAt, '%Y-%m')
                ORDER BY month ASC
            ";
            $params = []; // reset params vì không dùng điều kiện
        }

        $monthly = $this->query($monthlyQuery, $params);

        return [
            'total' => (int)($statusStats['total'] ?? 0),
            'pending' => (int)($statusStats['pending'] ?? 0),
            'approved' => (int)($statusStats['approved'] ?? 0),
            'revoked' => (int)($statusStats['revoked'] ?? 0),
            'active' => (int)($statusStats['active'] ?? 0),
            'expired' => (int)($statusStats['expired'] ?? 0),
            'top_subjects' => $topSubjects,
            'monthly' => $monthly
        ];
    }
}