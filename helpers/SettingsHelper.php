<?php
/**
 * Settings Helper
 * Helper class để lấy và set settings dễ dàng
 */

class SettingsHelper {
    private static $instance = null;
    private $db;
    private $cache = [];
    
    private function __construct() {
        $this->db = Database::getInstance()->getConnection();
        $this->loadAllSettings();
    }
    
    /**
     * Singleton instance
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    /**
     * Load tất cả settings vào cache
     */
    private function loadAllSettings() {
        try {
            $sql = "SELECT SettingKey, SettingValue FROM tblTrain_Settings";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
            
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $this->cache[$row['SettingKey']] = $row['SettingValue'];
            }
        } catch (Exception $e) {
            error_log('Settings load error: ' . $e->getMessage());
        }
    }
    
    /**
     * Lấy giá trị setting
     * 
     * @param string $key - Key của setting
     * @param mixed $default - Giá trị mặc định nếu không tìm thấy
     * @return mixed
     */
    public static function get($key, $default = null) {
        $instance = self::getInstance();
        return $instance->cache[$key] ?? $default;
    }
    
    /**
     * Set giá trị setting
     * 
     * @param string $key - Key của setting
     * @param mixed $value - Giá trị cần set
     * @return bool
     */
    public static function set($key, $value) {
        $instance = self::getInstance();
        
        try {
            // Check if exists
            $sql = "SELECT COUNT(*) FROM tblTrain_Settings WHERE SettingKey = ?";
            $stmt = $instance->db->prepare($sql);
            $stmt->execute([$key]);
            $exists = $stmt->fetchColumn() > 0;
            
            if ($exists) {
                $sql = "UPDATE tblTrain_Settings SET SettingValue = ?, UpdatedAt = NOW() WHERE SettingKey = ?";
                $stmt = $instance->db->prepare($sql);
                $stmt->execute([$value, $key]);
            } else {
                $sql = "INSERT INTO tblTrain_Settings (SettingKey, SettingValue, CreatedAt, UpdatedAt) VALUES (?, ?, NOW(), NOW())";
                $stmt = $instance->db->prepare($sql);
                $stmt->execute([$key, $value]);
            }
            
            // Update cache
            $instance->cache[$key] = $value;
            
            return true;
        } catch (Exception $e) {
            error_log('Settings set error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Lấy nhiều settings cùng lúc
     * 
     * @param array $keys - Mảng các keys cần lấy
     * @return array
     */
    public static function getMany($keys) {
        $instance = self::getInstance();
        $result = [];
        
        foreach ($keys as $key) {
            $result[$key] = $instance->cache[$key] ?? null;
        }
        
        return $result;
    }
    
    /**
     * Kiểm tra setting có tồn tại không
     * 
     * @param string $key
     * @return bool
     */
    public static function has($key) {
        $instance = self::getInstance();
        return isset($instance->cache[$key]);
    }
    
    /**
     * Xóa một setting
     * 
     * @param string $key
     * @return bool
     */
    public static function delete($key) {
        $instance = self::getInstance();
        
        try {
            $sql = "DELETE FROM tblTrain_Settings WHERE SettingKey = ?";
            $stmt = $instance->db->prepare($sql);
            $stmt->execute([$key]);
            
            unset($instance->cache[$key]);
            
            return true;
        } catch (Exception $e) {
            error_log('Settings delete error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Refresh cache từ database
     */
    public static function refresh() {
        $instance = self::getInstance();
        $instance->cache = [];
        $instance->loadAllSettings();
    }
    
    /**
     * Lấy tất cả settings
     * 
     * @return array
     */
    public static function all() {
        $instance = self::getInstance();
        return $instance->cache;
    }
    
    // ========== HELPER METHODS FOR COMMON SETTINGS ==========
    
    /**
     * Lấy tên công ty
     */
    public static function getCompanyName() {
        return self::get('company_name', 'CÔNG TY ABC');
    }
    
    /**
     * Lấy email liên hệ
     */
    public static function getContactEmail() {
        return self::get('contact_email', 'admin@company.com');
    }
    
    /**
     * Lấy điểm tối thiểu để đạt
     */
    public static function getMinScore() {
        return (int)self::get('min_score', 70);
    }
    
    /**
     * Lấy % thời gian xem video tối thiểu
     */
    public static function getMinWatchPercent() {
        return (int)self::get('min_watch_percent', 90);
    }
    
    /**
     * Lấy tiền tố mã chứng chỉ
     */
    public static function getCertPrefix() {
        return self::get('cert_prefix', 'CERT');
    }
    
    /**
     * Kiểm tra có tự động phê duyệt chứng chỉ không
     */
    public static function isAutoApproveCert() {
        return (bool)self::get('auto_approve_cert', false);
    }
    
    /**
     * Lấy thời gian làm bài mặc định
     */
    public static function getExamDefaultTime() {
        return (int)self::get('exam_default_time', 30);
    }
    
    /**
     * Lấy số lần làm bài tối đa
     */
    public static function getExamMaxAttempts() {
        return (int)self::get('exam_max_attempts', 3);
    }
    
    /**
     * Kiểm tra có cho xem đáp án không
     */
    public static function isShowAnswers() {
        return (bool)self::get('exam_show_answers', true);
    }
    
    /**
     * Kiểm tra có xáo trộn câu hỏi không
     */
    public static function isShuffleQuestions() {
        return (bool)self::get('exam_shuffle_questions', true);
    }
    
    /**
     * Kiểm tra có chặn copy/paste không
     */
    public static function isBlockCopy() {
        return (bool)self::get('exam_block_copy', true);
    }
    
    /**
     * Kiểm tra có bật thông báo email không
     */
    public static function isEmailNotificationEnabled() {
        return (bool)self::get('email_notifications', true);
    }
    
    /**
     * Lấy cấu hình SMTP
     */
    public static function getSmtpConfig() {
        return [
            'host' => self::get('smtp_host', 'smtp.gmail.com'),
            'port' => (int)self::get('smtp_port', 587),
            'user' => self::get('smtp_user', ''),
            'pass' => base64_decode(self::get('smtp_pass', '')),
        ];
    }
    
    /**
     * Lấy email template
     */
    public static function getEmailTemplate() {
        return [
            'subject' => self::get('email_subject', 'Chúc mừng bạn hoàn thành khóa học!'),
            'body' => self::get('email_body', ''),
        ];
    }
    
    /**
     * Parse email template với variables
     * 
     * @param string $template - Template string
     * @param array $vars - Biến thay thế ['{name}' => 'John', ...]
     * @return string
     */
    public static function parseEmailTemplate($template, $vars) {
        foreach ($vars as $key => $value) {
            $template = str_replace('{' . $key . '}', $value, $template);
        }
        return $template;
    }
}

// ========== GLOBAL HELPER FUNCTIONS ==========

/**
 * Shorthand function to get setting
 */
function setting($key, $default = null) {
    return SettingsHelper::get($key, $default);
}

/**
 * Shorthand function to set setting
 */
function set_setting($key, $value) {
    return SettingsHelper::set($key, $value);
}
?>