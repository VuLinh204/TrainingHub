-- WARNING: backup your DB before running

CREATE DATABASE IF NOT EXISTS training_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
USE training_db;

-- -------------------------
-- Audit log table
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_AuditLog (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ActorID INT NULL,
    Action VARCHAR(50) NOT NULL,
    TableName VARCHAR(100) NOT NULL,
    RecordID INT NULL,
    OldValue JSON NULL,
    NewValue JSON NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_actor (ActorID),
    INDEX idx_table (TableName)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Positions
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_Position (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    PositionName VARCHAR(255) NOT NULL,
    DepartmentID INT,
    Status TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Departments
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_Department (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    DepartmentName VARCHAR(255) NOT NULL
);

-- -------------------------
-- Employees (with soft delete)
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_Employee (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL COMMENT 'Use bcrypt/argon2; do NOT use MD5/SHA1',
    PositionID INT NULL,
    `Role` VARCHAR(50) NOT NULL DEFAULT 'user' COMMENT 'admin|staff|user etc',
    Status TINYINT DEFAULT 1,
    LastLoginAt DATETIME DEFAULT NULL,
    LastLoginIP VARCHAR(45),
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    DeletedAt DATETIME DEFAULT NULL,
    INDEX idx_email (Email),
    INDEX idx_role (`Role`),
    CONSTRAINT fk_employee_position FOREIGN KEY (PositionID) REFERENCES tblTrain_Position(ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Knowledge groups and subjects
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_KnowledgeGroup (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Status TINYINT DEFAULT 1,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblTrain_Subject (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    VideoURL VARCHAR(500),
    Duration INT DEFAULT 0 COMMENT 'seconds',
    FileURL VARCHAR(500),
    KnowledgeGroupID INT NULL,
    MinWatchPercent FLOAT DEFAULT 90,
    MaxSkipSeconds INT DEFAULT 5,
    AllowRewatch TINYINT DEFAULT 1,
    RequiredScore FLOAT DEFAULT 70 COMMENT 'Percentage score required to pass (70 = 70%)',
    MinCorrectAnswers INT DEFAULT 0 COMMENT 'Minimum correct answers required to pass (0 = disabled, use RequiredScore instead)',
    ExamTimeLimit INT DEFAULT 30 COMMENT 'Exam time limit in minutes',
    Status TINYINT DEFAULT 1,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    DeletedAt DATETIME DEFAULT NULL,
    CONSTRAINT fk_subject_group FOREIGN KEY (KnowledgeGroupID) REFERENCES tblTrain_KnowledgeGroup(ID) ON DELETE SET NULL,
    INDEX idx_min_correct (MinCorrectAnswers)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Questions & Answers
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_Question (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    SubjectID INT NOT NULL,
    QuestionText TEXT NOT NULL,
    QuestionType ENUM('single','multiple') DEFAULT 'single',
    Score FLOAT DEFAULT 1,
    Status TINYINT DEFAULT 1,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_question_subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblTrain_Answer (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    QuestionID INT NOT NULL,
    AnswerText TEXT NOT NULL,
    IsCorrect TINYINT DEFAULT 0,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_answer_question FOREIGN KEY (QuestionID) REFERENCES tblTrain_Question(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Exams
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_Exam (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT NOT NULL,
    SubjectID INT NOT NULL,
    StartTime DATETIME NOT NULL,
    EndTime DATETIME NULL,
    CompletedAt DATETIME NULL,
    Score FLOAT DEFAULT 0,
    TotalQuestions INT DEFAULT 0,
    CorrectAnswers INT DEFAULT 0,
    Passed TINYINT DEFAULT 0,
    Status ENUM('started','completed','timeout') DEFAULT 'started',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_exam_employee FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE CASCADE,
    CONSTRAINT fk_exam_subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE,
    INDEX idx_exam_completed (CompletedAt),
    INDEX idx_exam_status (Status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Exam detail
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_ExamDetail (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ExamID INT NOT NULL,
    QuestionID INT NOT NULL,
    AnswerID INT NULL,
    IsCorrect TINYINT DEFAULT 0,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_examdetail_exam FOREIGN KEY (ExamID) REFERENCES tblTrain_Exam(ID) ON DELETE CASCADE,
    CONSTRAINT fk_examdetail_question FOREIGN KEY (QuestionID) REFERENCES tblTrain_Question(ID) ON DELETE CASCADE,
    CONSTRAINT fk_examdetail_answer FOREIGN KEY (AnswerID) REFERENCES tblTrain_Answer(ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Assignments (Updated to use KnowledgeGroupID instead of SubjectID)
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_Assign (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    PositionID INT NOT NULL,
    KnowledgeGroupID INT NOT NULL,
    AssignDate DATE NOT NULL,
    ExpireDate DATE NULL,
    IsRequired TINYINT DEFAULT 1,
    Status TINYINT DEFAULT 1,
    UNIQUE KEY unique_assign (PositionID, KnowledgeGroupID),
    CONSTRAINT fk_assign_position FOREIGN KEY (PositionID) REFERENCES tblTrain_Position(ID) ON DELETE CASCADE,
    CONSTRAINT fk_assign_knowledgegroup FOREIGN KEY (KnowledgeGroupID) REFERENCES tblTrain_KnowledgeGroup(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Watch log
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_WatchLog (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT NOT NULL,
    SubjectID INT NOT NULL,
    Event VARCHAR(50) NOT NULL COMMENT 'heartbeat/ended/seek/resume/pause',
    WatchedSeconds INT DEFAULT 0,
    CurrentTime INT DEFAULT 0,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UserAgent VARCHAR(255),
    IPAddress VARCHAR(45),
    INDEX idx_employee_subject (EmployeeID, SubjectID),
    INDEX idx_created (CreatedAt),
    CONSTRAINT fk_watchlog_employee FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE CASCADE,
    CONSTRAINT fk_watchlog_subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Completion
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_Completion (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT NOT NULL,
    SubjectID INT NOT NULL,
    CompletedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    Method ENUM('video','exam','manual') DEFAULT 'video',
    Score FLOAT DEFAULT NULL,
    ExamID INT DEFAULT NULL,
    CreatedBy INT NULL COMMENT 'admin id if manual',
    UNIQUE KEY unique_completion (EmployeeID, SubjectID),
    INDEX idx_method (Method),
    CONSTRAINT fk_completion_employee FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE CASCADE,
    CONSTRAINT fk_completion_subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE,
    CONSTRAINT fk_completion_exam FOREIGN KEY (ExamID) REFERENCES tblTrain_Exam(ID) ON DELETE SET NULL,
    CONSTRAINT fk_completion_createdby FOREIGN KEY (CreatedBy) REFERENCES tblTrain_Employee(ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Certificates
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_Certificate (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT NOT NULL,
    SubjectID INT NOT NULL,
    CertificateCode VARCHAR(64) NOT NULL,
    CertificateHash VARCHAR(128) NOT NULL COMMENT 'SHA256 of (CertificateCode|EmployeeID|SubjectID|IssuedAt|SALT)',
    IssuedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    ExpiresAt DATETIME NOT NULL,
    FileURL VARCHAR(500) DEFAULT NULL,
    Status TINYINT DEFAULT 1 COMMENT '1: active, 0: expired/revoked',
    RevokedAt DATETIME DEFAULT NULL,
    RevokedBy INT DEFAULT NULL,
    RevokeReason TEXT,
    ApprovedBy INT DEFAULT NULL,
    ApprovedAt DATETIME DEFAULT NULL,
    UNIQUE KEY unique_cert_code (CertificateCode),
    UNIQUE KEY unique_employee_subject (EmployeeID, SubjectID),
    INDEX idx_expires (ExpiresAt),
    CONSTRAINT fk_certificate_employee FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE CASCADE,
    CONSTRAINT fk_certificate_subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE,
    CONSTRAINT fk_certificate_revokedby FOREIGN KEY (RevokedBy) REFERENCES tblTrain_Employee(ID) ON DELETE SET NULL,
    CONSTRAINT fk_certificate_approvedby FOREIGN KEY (ApprovedBy) REFERENCES tblTrain_Employee(ID) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Notifications
-- -------------------------
CREATE TABLE IF NOT EXISTS tblTrain_Notification (
  ID INT AUTO_INCREMENT PRIMARY KEY,
  EmployeeID INT NOT NULL,
  Title VARCHAR(255) NOT NULL,
  Message TEXT,
  Link VARCHAR(500),
  `Read` TINYINT DEFAULT 0,
  `Type` ENUM('info','success','warning','error') DEFAULT 'info',
  CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notification_employee FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE CASCADE,
  INDEX idx_employee_read (EmployeeID, `Read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -------------------------
-- Helper: seed a secret salt for DB-side hashing (USER MUST REPLACE)
-- -------------------------
SET @CERT_SALT = 'REPLACE_WITH_SECRET_SALT';

-- -------------------------
-- Triggers
-- -------------------------
DELIMITER $$

-- 1) On certificate INSERT/UPDATE -> compute CertificateHash
CREATE TRIGGER trg_certificate_before_insert
BEFORE INSERT ON tblTrain_Certificate
FOR EACH ROW
BEGIN
  SET NEW.CertificateHash = SHA2(CONCAT(NEW.CertificateCode, '|', NEW.EmployeeID, '|', NEW.SubjectID, '|', COALESCE(NEW.IssuedAt, NOW()), '|', @CERT_SALT), 256);
END$$

CREATE TRIGGER trg_certificate_before_update
BEFORE UPDATE ON tblTrain_Certificate
FOR EACH ROW
BEGIN
  IF (OLD.CertificateCode <> NEW.CertificateCode) OR (OLD.EmployeeID <> NEW.EmployeeID) OR (OLD.SubjectID <> NEW.SubjectID) OR (OLD.IssuedAt <> NEW.IssuedAt) THEN
    SET NEW.CertificateHash = SHA2(CONCAT(NEW.CertificateCode, '|', NEW.EmployeeID, '|', NEW.SubjectID, '|', COALESCE(NEW.IssuedAt, NOW()), '|', @CERT_SALT), 256);
  END IF;
END$$

-- 2) Certificate audit log (INSERT)
CREATE TRIGGER trg_certificate_after_insert
AFTER INSERT ON tblTrain_Certificate
FOR EACH ROW
BEGIN
  INSERT INTO tblTrain_AuditLog (ActorID, Action, TableName, RecordID, OldValue, NewValue)
  VALUES (NULL, 'INSERT', 'tblTrain_Certificate', NEW.ID, NULL, JSON_OBJECT(
    'CertificateCode', NEW.CertificateCode,
    'EmployeeID', NEW.EmployeeID,
    'SubjectID', NEW.SubjectID,
    'IssuedAt', DATE_FORMAT(NEW.IssuedAt, '%Y-%m-%d %H:%i:%s')
  ));
END$$

-- Certificate audit log (UPDATE)
CREATE TRIGGER trg_certificate_after_update
AFTER UPDATE ON tblTrain_Certificate
FOR EACH ROW
BEGIN
  INSERT INTO tblTrain_AuditLog (ActorID, Action, TableName, RecordID, OldValue, NewValue)
  VALUES (NULL, 'UPDATE', 'tblTrain_Certificate', NEW.ID,
    JSON_OBJECT(
      'CertificateCode', OLD.CertificateCode,
      'EmployeeID', OLD.EmployeeID,
      'SubjectID', OLD.SubjectID,
      'Status', OLD.Status,
      'IssuedAt', DATE_FORMAT(OLD.IssuedAt, '%Y-%m-%d %H:%i:%s')
    ),
    JSON_OBJECT(
      'CertificateCode', NEW.CertificateCode,
      'EmployeeID', NEW.EmployeeID,
      'SubjectID', NEW.SubjectID,
      'Status', NEW.Status,
      'IssuedAt', DATE_FORMAT(NEW.IssuedAt, '%Y-%m-%d %H:%i:%s')
    )
  );
END$$

-- Certificate audit log (DELETE)
CREATE TRIGGER trg_certificate_after_delete
AFTER DELETE ON tblTrain_Certificate
FOR EACH ROW
BEGIN
  INSERT INTO tblTrain_AuditLog (ActorID, Action, TableName, RecordID, OldValue, NewValue)
  VALUES (NULL, 'DELETE', 'tblTrain_Certificate', OLD.ID,
    JSON_OBJECT(
      'CertificateCode', OLD.CertificateCode,
      'EmployeeID', OLD.EmployeeID,
      'SubjectID', OLD.SubjectID,
      'IssuedAt', DATE_FORMAT(OLD.IssuedAt, '%Y-%m-%d %H:%i:%s')
    ),
    NULL
  );
END$$

-- 3) Exams: BEFORE UPDATE -> set CompletedAt when status becomes 'completed', and set Passed based on Score
CREATE TRIGGER trg_exam_before_update
BEFORE UPDATE ON tblTrain_Exam
FOR EACH ROW
BEGIN
  IF NEW.Status = 'completed' AND (OLD.Status <> 'completed' OR OLD.CompletedAt IS NULL) THEN
    IF NEW.CompletedAt IS NULL THEN
      SET NEW.CompletedAt = NOW();
    END IF;
  END IF;

  IF NEW.Status = 'completed' THEN
    IF NEW.Score >= 80 THEN
      SET NEW.Passed = 1;
    ELSE
      SET NEW.Passed = 0;
    END IF;
  ELSE
    SET NEW.Passed = 0;
  END IF;
END$$

-- 4) Exam audit log (UPDATE)
CREATE TRIGGER trg_exam_after_update
AFTER UPDATE ON tblTrain_Exam
FOR EACH ROW
BEGIN
  INSERT INTO tblTrain_AuditLog (ActorID, Action, TableName, RecordID, OldValue, NewValue)
  VALUES (NULL, 'UPDATE', 'tblTrain_Exam', NEW.ID,
    JSON_OBJECT('Status', OLD.Status, 'Score', OLD.Score, 'CompletedAt', DATE_FORMAT(OLD.CompletedAt, '%Y-%m-%d %H:%i:%s')),
    JSON_OBJECT('Status', NEW.Status, 'Score', NEW.Score, 'CompletedAt', DATE_FORMAT(NEW.CompletedAt, '%Y-%m-%d %H:%i:%s'))
  );
END$$

DELIMITER ;

-- -------------------------
-- Index & maintenance
-- -------------------------
CREATE INDEX IF NOT EXISTS idx_employee_email ON tblTrain_Employee (Email);

-- -------------------------
-- Sample data
-- -------------------------
INSERT INTO tblTrain_KnowledgeGroup (Name) VALUES 
('Javascript cơ bản'),
('Javascript nâng cao'),
('Javascript & ReactJS');

INSERT INTO tblTrain_Subject (
    Title,
    Description,
    VideoURL,
    Duration,
    KnowledgeGroupID,
    MinWatchPercent,
    RequiredScore,
    MinCorrectAnswers,
    ExamTimeLimit,
    Status
) VALUES 
('JavaScript cơ bản',
'Khóa học cung cấp kiến thức cơ bản về JavaScript',
'http://localhost/Training/assets/videos/js-basic.mp4',
472,
1,
50,
70,
7,
30,
1);

INSERT INTO tblTrain_Question (SubjectID, QuestionText, QuestionType, Score, Status) VALUES
(1, 'Trong JavaScript, var, let, và const khác nhau chủ yếu ở điểm nào?', 'single', 1, 1),
(1, 'Function scope trong JavaScript là gì?', 'single', 1, 1),
(1, 'Closure trong JavaScript được sử dụng khi nào?', 'single', 1, 1),
(1, 'Arrow function có tính năng gì khác so với regular function?', 'single', 1, 1),
(1, 'async/await được sử dụng cho mục đích nào?', 'single', 1, 1),
(1, 'Promise.all() làm gì?', 'single', 1, 1),
(1, 'Callback hell là vấn đề gì?', 'single', 1, 1),
(1, '== và === khác nhau như thế nào?', 'single', 1, 1),
(1, 'Hoisting trong JavaScript là gì?', 'single', 1, 1),
(1, 'This keyword trong JavaScript đề cập đến cái gì?', 'single', 1, 1);

INSERT INTO tblTrain_Answer (QuestionID, AnswerText, IsCorrect) VALUES
(1, 'let và const có phạm vi khối (block scope), còn var có phạm vi hàm (function scope)', 1),
(1, 'Chỉ khác nhau ở cách đặt tên biến', 0),
(1, 'var nhanh hơn let và const', 0),
(1, 'Không có sự khác biệt nào, chỉ là cú pháp khác', 0),
(2, 'Phạm vi mà biến chỉ tồn tại bên trong hàm', 1),
(2, 'Phạm vi toàn cục của chương trình', 0),
(2, 'Phạm vi chỉ của một khối lệnh', 0),
(2, 'Phạm vi chỉ tồn tại 1 giây', 0),
(3, 'Khi một hàm cần truy cập vào biến từ phạm vi cha của nó', 1),
(3, 'Khi muốn tạo vòng lặp', 0),
(3, 'Khi muốn gọi hàm nhiều lần', 0),
(3, 'Khi muốn khai báo biến global', 0),
(4, 'Arrow function không có binding của this, không có arguments', 1),
(4, 'Arrow function có thể được dùng như constructor', 0),
(4, 'Arrow function nhanh hơn regular function', 0),
(4, 'Không có khác biệt gì', 0),
(5, 'Viết code bất đồng bộ theo cách đồng bộ hơn', 1),
(5, 'Làm code chạy nhanh hơn', 0),
(5, 'Thay thế các vòng lặp', 0),
(5, 'Tạo timer', 0),
(6, 'Chờ cho tất cả promises hoàn thành', 1),
(6, 'Chỉ chờ promise đầu tiên', 0),
(6, 'Hủy tất cả promises', 0),
(6, 'Chuyển đổi promise sang callback', 0),
(7, 'Nhiều callback được lồng nhau làm code khó đọc', 1),
(7, 'Lỗi cú pháp', 0),
(7, 'Hiệu năng chậm', 0),
(7, 'Memory leak', 0),
(8, '== so sánh giá trị, === so sánh cả giá trị và kiểu dữ liệu', 1),
(8, 'Giống nhau hoàn toàn', 0),
(8, '=== nhanh hơn ==', 0),
(8, '== so sánh địa chỉ ô nhớ', 0),
(9, 'Quá trình khai báo và khởi tạo biến được di chuyển lên đầu scope', 1),
(9, 'Quá trình xóa biến', 0),
(9, 'Quá trình sao chép biến', 0),
(9, 'Quá trình mã hóa biến', 0),
(10, 'Đối tượng gọi hàm', 1),
(10, 'Hàm được định nghĩa', 0),
(10, 'Biến toàn cục', 0),
(10, 'Lớp của hàm', 0);

INSERT INTO tblTrain_Position (PositionName, DepartmentID) VALUES
('Nhân viên', '1'),
('Tổ trưởng', '2'),
('Quản lý', '2');

INSERT INTO tblTrain_Department (DepartmentName) VALUES
('IT'),
('IT'),
('Sales');

INSERT INTO tblTrain_Employee (
    FirstName,
    LastName,
    Email,
    PasswordHash,
    PositionID,
    `Role`,
    Status
) VALUES 
('Admin', 'User', 'admin@company.com', 
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
3, 'admin', 1),
('Test', 'User', 'test@company.com',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
1, 'user', 1);

-- Assign knowledge group to position (Updated to use KnowledgeGroupID)
INSERT INTO tblTrain_Assign (PositionID, KnowledgeGroupID, AssignDate, ExpireDate, IsRequired) VALUES
(3, 1, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 6 MONTH), 1);


CREATE TABLE IF NOT EXISTS tblTrain_Material (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    SubjectID INT NOT NULL,
    Title VARCHAR(255) NOT NULL,
    FileURL VARCHAR(500) NOT NULL,
    Status TINYINT DEFAULT 1,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_material_subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE
);
-- -------------------------
-- End of script
-- -------------------------