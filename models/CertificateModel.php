<?php
class CertificateModel extends Model {
    protected $table = 'tbltrain_certificate';

    public function generateCertificate($employeeId, $subjectId) {
        // Kiểm tra điều kiện cấp chứng chỉ
        $completion = (new CompletionModel())->getCompletion($employeeId, $subjectId);
        if (!$completion) {
            return false; // Chưa hoàn thành khóa học
        }

        // Tạo mã chứng chỉ unique
        $certCode = $this->generateUniqueCode($employeeId, $subjectId);
        
        // Tính ngày hết hạn (mặc định 2 năm)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+2 years'));

        $data = [
            'EmployeeID' => $employeeId,
            'SubjectID' => $subjectId,
            'CertificateCode' => $certCode,
            'ExpiresAt' => $expiresAt,
            'Status' => 1
        ];

        return $this->insert($this->table, $data);
    }

    private function generateUniqueCode($employeeId, $subjectId) {
        do {
            // Format: CERT-{YY}{MM}-{EmployeeID}-{SubjectID}-{Random6}
            $code = sprintf(
                "CERT-%s-%05d-%03d-%s",
                date('ym'),
                $employeeId,
                $subjectId,
                strtoupper(substr(md5(uniqid()), 0, 6))
            );
            
            // Kiểm tra xem mã đã tồn tại chưa
            $exists = $this->query(
                "SELECT 1 FROM {$this->table} WHERE CertificateCode = ?",
                [$code]
            );
        } while ($exists);

        return $code;
    }

    public function getCertificate($certCode) {
        $sql = "SELECT c.*, 
                CONCAT(e.FirstName, ' ', e.LastName) as EmployeeName,
                e.Department, e.Position,
                s.Title as SubjectName,
                comp.CompletedAt, comp.Score
                FROM {$this->table} c
                JOIN tbltrain_employee e ON c.EmployeeID = e.ID
                JOIN tbltrain_subject s ON c.SubjectID = s.ID
                JOIN tbltrain_completion comp ON (c.EmployeeID = comp.EmployeeID AND c.SubjectID = comp.SubjectID)
                WHERE c.CertificateCode = ?";
        
        $result = $this->query($sql, [$certCode]);
        return $result ? $result[0] : null;
    }

    public function getEmployeeCertificates($employeeId) {
        $sql = "SELECT c.*, s.Title as SubjectName
                FROM {$this->table} c
                JOIN tbltrain_subject s ON c.SubjectID = s.ID
                WHERE c.EmployeeID = ? AND c.Status = 1
                ORDER BY c.IssuedAt DESC";
        return $this->query($sql, [$employeeId]);
    }

    public function revokeCertificate($certCode, $reason) {
        if (!isset($_SESSION['admin_id'])) {
            return false;
        }

        $data = [
            'Status' => 0,
            'RevokedAt' => date('Y-m-d H:i:s'),
            'RevokedBy' => $_SESSION['admin_id'],
            'RevokeReason' => $reason
        ];

        return $this->update(
            $this->table,
            $data,
            "CertificateCode = ?",
            [$certCode]
        );
    }

    public function checkExpiredCertificates() {
        // Tự động đánh dấu chứng chỉ hết hạn
        $sql = "UPDATE {$this->table}
                SET Status = 0
                WHERE Status = 1 
                AND ExpiresAt < NOW()
                AND RevokedAt IS NULL";
        return $this->execute($sql);
    }
}