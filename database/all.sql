CREATE DATABASE IF NOT EXISTS training_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;
USE training_db;

-- Tệp SQL để tạo bảng và chèn dữ liệu mẫu cho hệ thống đào tạo
-- Ngày tạo: 17/10/2025

SET FOREIGN_KEY_CHECKS = 0;

-- Bảng: tblTrain_Settings
CREATE TABLE tblTrain_Settings (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    SettingKey VARCHAR(255) NOT NULL UNIQUE,
    SettingValue TEXT,
    Description TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng: tblTrain_Department
CREATE TABLE tblTrain_Department (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    DepartmentName VARCHAR(255) NOT NULL
);

-- Bảng: tblTrain_Position
CREATE TABLE tblTrain_Position (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    PositionName VARCHAR(255) NOT NULL,
    DepartmentID INT,
    Status TINYINT DEFAULT 1,
    FOREIGN KEY (DepartmentID) REFERENCES tblTrain_Department(ID) ON DELETE SET NULL
);

-- Bảng: tblTrain_Employee
CREATE TABLE tblTrain_Employee (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(255) NOT NULL,
    LastName VARCHAR(255) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255),
    PositionID INT,
    Role ENUM('admin', 'staff', 'user') DEFAULT 'user',
    Status TINYINT DEFAULT 1,
    LastLoginAt DATETIME,
    LastLoginIP VARCHAR(45),
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    DeletedAt DATETIME,
    FOREIGN KEY (PositionID) REFERENCES tblTrain_Position(ID) ON DELETE SET NULL
);

-- Bảng: tblTrain_ActivityLog
CREATE TABLE tblTrain_ActivityLog (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT,
    Action VARCHAR(255) NOT NULL,
    Description TEXT,
    IPAddress VARCHAR(45),
    UserAgent TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE SET NULL
);

-- Bảng: tblTrain_Sessions
CREATE TABLE tblTrain_Sessions (
    ID VARCHAR(255) PRIMARY KEY,
    EmployeeID INT,
    Data TEXT,
    IPAddress VARCHAR(45),
    UserAgent TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    ExpireAt DATETIME,
    FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE CASCADE
);

-- Bảng: tblTrain_AuditLog
CREATE TABLE tblTrain_AuditLog (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ActorID INT,
    Action ENUM('INSERT', 'UPDATE', 'DELETE') NOT NULL,
    TableName VARCHAR(255) NOT NULL,
    RecordID INT NOT NULL,
    OldValue JSON,
    NewValue JSON,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ActorID) REFERENCES tblTrain_Employee(ID) ON DELETE SET NULL
);

-- Bảng: tblTrain_KnowledgeGroup
CREATE TABLE tblTrain_KnowledgeGroup (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Status TINYINT DEFAULT 1,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
);

-- Bảng: tblTrain_Subject
CREATE TABLE tblTrain_Subject (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    VideoURL VARCHAR(255),
    Duration INT,
    FileURL VARCHAR(255),
    KnowledgeGroupID INT,
    MinWatchPercent INT DEFAULT 90,
    MaxSkipSeconds INT DEFAULT 0,
    AllowRewatch TINYINT DEFAULT 1,
    RequiredScore INT DEFAULT 70,
    MinCorrectAnswers INT DEFAULT 0,
    ExamTimeLimit INT DEFAULT 30,
    Status TINYINT DEFAULT 1,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    DeletedAt DATETIME,
    FOREIGN KEY (KnowledgeGroupID) REFERENCES tblTrain_KnowledgeGroup(ID) ON DELETE SET NULL
);

-- Bảng: tblTrain_Question
CREATE TABLE tblTrain_Question (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    SubjectID INT,
    QuestionText TEXT NOT NULL,
    QuestionType ENUM('single', 'multiple') DEFAULT 'single',
    Score INT DEFAULT 1,
    Status TINYINT DEFAULT 1,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE
);

-- Bảng: tblTrain_Answer
CREATE TABLE tblTrain_Answer (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    QuestionID INT,
    AnswerText TEXT NOT NULL,
    IsCorrect TINYINT DEFAULT 0,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (QuestionID) REFERENCES tblTrain_Question(ID) ON DELETE CASCADE
);

-- Bảng: tblTrain_Exam
CREATE TABLE tblTrain_Exam (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT,
    SubjectID INT,
    StartTime DATETIME,
    EndTime DATETIME,
    CompletedAt DATETIME,
    Score INT,
    TotalQuestions INT,
    CorrectAnswers INT,
    Passed TINYINT DEFAULT 0,
    Status ENUM('started', 'completed', 'timeout') DEFAULT 'started',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE CASCADE,
    FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE
);

-- Trigger: trg_exam_before_update
DELIMITER //
CREATE TRIGGER trg_exam_before_update
BEFORE UPDATE ON tblTrain_Exam
FOR EACH ROW
BEGIN
    IF NEW.Status = 'completed' THEN
        SET NEW.CompletedAt = NOW();
        IF NEW.Score >= 80 THEN
            SET NEW.Passed = 1;
        END IF;
    END IF;
END //
DELIMITER ;

-- Bảng: tblTrain_ExamDetail
CREATE TABLE tblTrain_ExamDetail (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    ExamID INT,
    QuestionID INT,
    AnswerID INT,
    IsCorrect TINYINT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (ExamID) REFERENCES tblTrain_Exam(ID) ON DELETE CASCADE,
    FOREIGN KEY (QuestionID) REFERENCES tblTrain_Question(ID) ON DELETE CASCADE,
    FOREIGN KEY (AnswerID) REFERENCES tblTrain_Answer(ID) ON DELETE SET NULL
);

-- Bảng: tblTrain_Assign
CREATE TABLE tblTrain_Assign (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    PositionID INT,
    KnowledgeGroupID INT,
    AssignDate DATE,
    ExpireDate DATE,
    IsRequired TINYINT DEFAULT 1,
    Status TINYINT DEFAULT 1,
    FOREIGN KEY (PositionID) REFERENCES tblTrain_Position(ID) ON DELETE CASCADE,
    FOREIGN KEY (KnowledgeGroupID) REFERENCES tblTrain_KnowledgeGroup(ID) ON DELETE CASCADE
);

-- Bảng: tblTrain_WatchLog
CREATE TABLE tblTrain_WatchLog (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT,
    SubjectID INT,
    Event ENUM('heartbeat', 'ended', 'seek', 'resume', 'pause') NOT NULL,
    WatchedSeconds INT,
    CurrentTime INT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UserAgent TEXT,
    IPAddress VARCHAR(45),
    FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE CASCADE,
    FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE
);

-- Bảng: tblTrain_Completion
CREATE TABLE tblTrain_Completion (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT,
    SubjectID INT,
    CompletedAt DATETIME,
    Method ENUM('video', 'exam', 'manual') NOT NULL,
    Score INT,
    ExamID INT,
    CreatedBy INT,
    FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE CASCADE,
    FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE,
    FOREIGN KEY (ExamID) REFERENCES tblTrain_Exam(ID) ON DELETE SET NULL,
    FOREIGN KEY (CreatedBy) REFERENCES tblTrain_Employee(ID) ON DELETE SET NULL
);

-- Bảng: tblTrain_Certificate
CREATE TABLE tblTrain_Certificate (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT,
    SubjectID INT,
    CertificateCode VARCHAR(255) NOT NULL UNIQUE,
    CertificateHash VARCHAR(255),
    IssuedAt DATETIME,
    ExpiresAt DATETIME,
    FileURL VARCHAR(255),
    Status TINYINT DEFAULT 1,
    RevokedAt DATETIME,
    RevokedBy INT,
    RevokeReason TEXT,
    ApprovedBy INT,
    ApprovedAt DATETIME,
    FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE CASCADE,
    FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE,
    FOREIGN KEY (RevokedBy) REFERENCES tblTrain_Employee(ID) ON DELETE SET NULL,
    FOREIGN KEY (ApprovedBy) REFERENCES tblTrain_Employee(ID) ON DELETE SET NULL
);

-- Trigger: trg_certificate_before_insert
DELIMITER //
CREATE TRIGGER trg_certificate_before_insert
BEFORE INSERT ON tblTrain_Certificate
FOR EACH ROW
BEGIN
    SET NEW.CertificateHash = SHA2(CONCAT(NEW.EmployeeID, NEW.SubjectID, NEW.CertificateCode, NOW()), 256);
END //
DELIMITER ;

-- Bảng: tblTrain_Notification
CREATE TABLE tblTrain_Notification (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT,
    Title VARCHAR(255) NOT NULL,
    Message TEXT NOT NULL,
    Link VARCHAR(255),
    `Read` TINYINT DEFAULT 0,
    Type ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID) ON DELETE CASCADE
);

-- Bảng: tblTrain_Material
CREATE TABLE tblTrain_Material (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    SubjectID INT,
    Title VARCHAR(255) NOT NULL,
    FileURL VARCHAR(255),
    Status TINYINT DEFAULT 1,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE
);

-- Chèn dữ liệu mẫu
-- tblTrain_Settings
INSERT INTO tblTrain_Settings (SettingKey, SettingValue, Description) VALUES
('company_name', 'CÔNG TY ABC', 'Tên công ty'),
('min_score', '70', 'Điểm tối thiểu để qua bài thi (%)'),
('min_watch_percent', '90', 'Tỷ lệ xem video tối thiểu (%)'),
('exam_default_time', '30', 'Thời gian làm bài kiểm tra mặc định (phút)'),
('exam_max_attempts', '3', 'Số lần thi tối đa'),
('cert_prefix', 'CERT', 'Tiền tố mã chứng chỉ');

-- tblTrain_Department
INSERT INTO tblTrain_Department (DepartmentName) VALUES
('IT'),
('Sales');

-- tblTrain_Position
INSERT INTO tblTrain_Position (PositionName, DepartmentID, Status) VALUES
('Nhân viên', 1, 1),
('Tổ trưởng', 2, 1),
('Quản lý', 2, 1);

-- tblTrain_Employee
INSERT INTO tblTrain_Employee (FirstName, LastName, Email, PasswordHash, PositionID, Role, Status) VALUES
('Admin', 'User', 'admin@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 3, 'admin', 1),
('Test', 'User', 'test@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'user', 1),
('Nguyen', 'Van A', 'nguyenvana@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 1, 'user', 1),
('Tran', 'Thi B', 'tranthib@company.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 2, 'staff', 1);

-- tblTrain_KnowledgeGroup
INSERT INTO tblTrain_KnowledgeGroup (Name, Status) VALUES
('Chung', 1),
('Sales Cơ bản', 1),
('Sales Nâng Cao', 1),
('Học Việc lập trình', 1),
('Triển khai', 1),
('Backend', 1);

-- tblTrain_Subject
INSERT INTO tblTrain_Subject (Title, VideoURL, FileURL, KnowledgeGroupID, MinWatchPercent, RequiredScore, MinCorrectAnswers, ExamTimeLimit, Status) VALUES
('Giới thiệu Vietinsoft & Paradise HR', 'http://localhost/Training/assets/videos/intro-vietinsoft.mp4', NULL, 1, 90, 70, 8, 30, 1),
('Thị trường mục tiêu & Buyer Personas', 'http://localhost/Training/assets/videos/market-target.mp4', NULL, 2, 90, 70, 10, 30, 1),
('Cài đặt VS', 'http://localhost/Training/assets/videos/vs-install.mp4', NULL, 4, 90, 70, 10, 30, 1),
('JavaScript cơ bản', 'http://localhost/Training/assets/videos/js-basic.mp4', NULL, 1, 90, 70, 7, 30, 1);

-- tblTrain_Assign
INSERT INTO tblTrain_Assign (PositionID, KnowledgeGroupID, AssignDate, ExpireDate, IsRequired, Status) VALUES
(1, 1, '2025-10-17', '2026-04-17', 1, 1),
(2, 3, '2025-10-17', '2026-04-17', 1, 1),
(1, 4, '2025-10-17', '2026-04-17', 1, 1);

-- tblTrain_Question
INSERT INTO tblTrain_Question (SubjectID, QuestionText, QuestionType, Score, Status) VALUES
(1, 'Sứ mệnh của Vietinsoft KHÔNG bao gồm điều nào sau đây?', 'single', 1, 1),
(1, 'Vietinsoft được thành lập vào năm nào và bởi bao nhiêu thành viên?', 'single', 1, 1),
(4, 'Trong JavaScript, var, let, và const khác nhau chủ yếu ở điểm nào?', 'single', 1, 1),
(4, 'Function scope trong JavaScript là gì?', 'single', 1, 1),
(4, 'Closure trong JavaScript được sử dụng khi nào?', 'single', 1, 1),
(4, 'Arrow function có tính năng gì khác so với regular function?', 'single', 1, 1),
(4, 'async/await được sử dụng cho mục đích nào?', 'single', 1, 1),
(4, 'Promise.all() làm gì?', 'single', 1, 1),
(4, 'Callback hell là vấn đề gì?', 'single', 1, 1),
(4, '== và === khác nhau như thế nào?', 'single', 1, 1),
(4, 'Hoisting trong JavaScript là gì?', 'single', 1, 1),
(4, 'This keyword trong JavaScript đề cập đến cái gì?', 'single', 1, 1);

-- tblTrain_Answer
INSERT INTO tblTrain_Answer (QuestionID, AnswerText, IsCorrect) VALUES
(1, 'Xây dựng môi trường làm việc hiện đại, minh bạch.', 0),
(1, 'Trở thành đơn vị số #1 tại Việt Nam trong lĩnh vực phần mềm quản trị nhân sự.', 1),
(1, 'Giúp các nhà máy, xí nghiệp giải quyết khó khăn trong quản lý nhân sự.', 0),
(1, 'Giải phóng thời gian cho bộ phận HR, thay thế công việc giấy tờ.', 0),
(2, 'Năm 2009 với 3 thành viên.', 0),
(2, 'Năm 2008 với 2 thành viên.', 1),
(2, 'Năm 2010 với 2 thành viên.', 0),
(2, 'Năm 2008 với 1 thành viên.', 0),
(3, 'let và const có phạm vi khối (block scope), còn var có phạm vi hàm (function scope)', 1),
(3, 'Chỉ khác nhau ở cách đặt tên biến', 0),
(3, 'var nhanh hơn let và const', 0),
(3, 'Không có sự khác biệt nào, chỉ là cú pháp khác', 0),
(4, 'Phạm vi mà biến chỉ tồn tại bên trong hàm', 1),
(4, 'Phạm vi toàn cục của chương trình', 0),
(4, 'Phạm vi chỉ của một khối lệnh', 0),
(4, 'Phạm vi chỉ tồn tại 1 giây', 0),
(5, 'Khi một hàm cần truy cập vào biến từ phạm vi cha của nó', 1),
(5, 'Khi muốn tạo vòng lặp', 0),
(5, 'Khi muốn gọi hàm nhiều lần', 0),
(5, 'Khi muốn khai báo biến global', 0),
(6, 'Arrow function không có binding của this, không có arguments', 1),
(6, 'Arrow function có thể được dùng như constructor', 0),
(6, 'Arrow function nhanh hơn regular function', 0),
(6, 'Không có khác biệt gì', 0),
(7, 'Viết code bất đồng bộ theo cách đồng bộ hơn', 1),
(7, 'Làm code chạy nhanh hơn', 0),
(7, 'Thay thế các vòng lặp', 0),
(7, 'Tạo timer', 0),
(8, 'Chờ cho tất cả promises hoàn thành', 1),
(8, 'Chỉ chờ promise đầu tiên', 0),
(8, 'Hủy tất cả promises', 0),
(8, 'Chuyển đổi promise sang callback', 0),
(9, 'Nhiều callback được lồng nhau làm code khó đọc', 1),
(9, 'Lỗi cú pháp', 0),
(9, 'Hiệu năng chậm', 0),
(9, 'Memory leak', 0),
(10, '== so sánh giá trị, === so sánh cả giá trị và kiểu dữ liệu', 1),
(10, 'Giống nhau hoàn toàn', 0),
(10, '=== nhanh hơn ==', 0),
(10, '== so sánh địa chỉ ô nhớ', 0),
(11, 'Quá trình khai báo và khởi tạo biến được di chuyển lên đầu scope', 1),
(11, 'Quá trình xóa biến', 0),
(11, 'Quá trình sao chép biến', 0),
(11, 'Quá trình mã hóa biến', 0),
(12, 'Đối tượng gọi hàm', 1),
(12, 'Hàm được định nghĩa', 0),
(12, 'Biến toàn cục', 0),
(12, 'Lớp của hàm', 0);

-- tblTrain_Exam
INSERT INTO tblTrain_Exam (EmployeeID, SubjectID, StartTime, EndTime, CompletedAt, Score, TotalQuestions, CorrectAnswers, Passed, Status) VALUES
(3, 3, '2025-10-17 09:00:00', '2025-10-17 09:30:00', '2025-10-17 09:30:00', 85, 10, 8, 1, 'completed'),
(4, 3, '2025-10-17 10:00:00', '2025-10-17 10:30:00', '2025-10-17 10:30:00', 90, 10, 9, 1, 'completed');

-- tblTrain_ExamDetail
INSERT INTO tblTrain_ExamDetail (ExamID, QuestionID, AnswerID, IsCorrect) VALUES
(1, 1, 1, 0),
(1, 2, 6, 1),
(1, 3, 9, 1),
(1, 4, 13, 1),
(1, 5, 17, 1),
(1, 6, 21, 1),
(1, 7, 25, 1),
(1, 8, 29, 1),
(1, 9, 33, 1),
(1, 10, 37, 1),
(2, 1, 1, 0),
(2, 2, 6, 1),
(2, 3, 9, 1),
(2, 4, 13, 1),
(2, 5, 17, 1),
(2, 6, 21, 1),
(2, 7, 25, 1),
(2, 8, 29, 1),
(2, 9, 33, 1),
(2, 10, 37, 1);

-- tblTrain_Completion
INSERT INTO tblTrain_Completion (EmployeeID, SubjectID, CompletedAt, Method, Score, ExamID, CreatedBy) VALUES
(3, 3, '2025-10-17 09:30:00', 'exam', 85, 1, NULL),
(4, 3, '2025-10-17 10:30:00', 'exam', 90, 2, NULL);

-- tblTrain_Certificate
INSERT INTO tblTrain_Certificate (EmployeeID, SubjectID, CertificateCode, IssuedAt, ExpiresAt, FileURL, Status, ApprovedBy, ApprovedAt) VALUES
(3, 3, 'CERT-003-3-20251017', '2025-10-17 09:30:00', '2026-10-17 09:30:00', 'http://localhost/Training/certificates/cert-003-3.pdf', 1, 1, '2025-10-17 09:30:00'),
(4, 3, 'CERT-004-3-20251017', '2025-10-17 10:30:00', '2026-10-17 10:30:00', 'http://localhost/Training/certificates/cert-004-3.pdf', 1, 1, '2025-10-17 10:30:00');

-- tblTrain_Notification
INSERT INTO tblTrain_Notification (EmployeeID, Title, Message, Link, `Read`, Type) VALUES
(3, 'Hoàn thành khóa học', 'Bạn đã hoàn thành khóa học Cài đặt VS và được cấp chứng chỉ.', 'http://localhost/Training/certificates/cert-003-3.pdf', 0, 'success'),
(4, 'Hoàn thành khóa học', 'Bạn đã hoàn thành khóa học Cài đặt VS và được cấp chứng chỉ.', 'http://localhost/Training/certificates/cert-004-3.pdf', 0, 'success');

-- tblTrain_WatchLog
INSERT INTO tblTrain_WatchLog (EmployeeID, SubjectID, Event, WatchedSeconds, CurrentTime, UserAgent, IPAddress) VALUES
(3, 3, 'ended', 1800, 1800, 'Mozilla/5.0', '192.168.1.1'),
(4, 3, 'ended', 1800, 1800, 'Mozilla/5.0', '192.168.1.2');

-- tblTrain_Material
INSERT INTO tblTrain_Material (SubjectID, Title, FileURL, Status) VALUES
(1, 'Hướng dẫn Vietinsoft', 'http://localhost/Training/materials/intro-vietinsoft.pdf', 1),
(4, 'Tài liệu JavaScript cơ bản', 'http://localhost/Training/materials/js-basic.pdf', 1);

-- Thêm dữ liệu mẫu bổ sung để test
-- Thông báo thêm
INSERT INTO tblTrain_Notification (EmployeeID, Title, Message, Link, `Read`, Type) VALUES
(2, 'Hẹn giờ họp đào tạo', 'Buổi đào tạo kỹ năng bán hàng sẽ diễn ra vào thứ 2 lúc 9:00.', '/events/1', 0, 'info'),
(3, 'Nhiệm vụ mới', 'Bạn được giao 1 nhiệm vụ học mới: Sales - Kỹ năng chốt đơn.', '/assignments', 0, 'info'),
(1, 'Backup hệ thống', 'Hệ thống sẽ backup vào 02:00 sáng mai.', NULL, 0, 'warning');

-- Thêm materials khác
INSERT INTO tblTrain_Material (SubjectID, Title, FileURL, Status) VALUES
(2, 'Mẫu kịch bản bán hàng', '/assets/files/sales-script.docx', 1),
(3, 'Bộ mẫu HTML cơ bản', '/assets/files/html-templates.zip', 1);

-- Thêm certificates mẫu
INSERT INTO tblTrain_Certificate (EmployeeID, SubjectID, CertificateCode, IssuedAt, ExpiresAt, FileURL, Status, ApprovedBy, ApprovedAt) VALUES
(2, 1, 'CERT-2025-0101', NOW() - INTERVAL 60 DAY, DATE_ADD(NOW(), INTERVAL 305 DAY), '/assets/certs/CERT-2025-0101.pdf', 1, 1, NOW() - INTERVAL 59 DAY),
(3, 2, 'CERT-2025-0102', NOW() - INTERVAL 30 DAY, DATE_ADD(NOW(), INTERVAL 335 DAY), '/assets/certs/CERT-2025-0102.pdf', 1, 1, NOW() - INTERVAL 29 DAY);

-- Thêm exam & completion mẫu cho test lịch sử
INSERT INTO tblTrain_Exam (EmployeeID, SubjectID, StartTime, EndTime, CompletedAt, Score, TotalQuestions, CorrectAnswers, Passed, Status) VALUES
(2, 2, '2025-09-20 14:00:00', '2025-09-20 14:30:00', '2025-09-20 14:30:00', 92, 10, 9, 1, 'completed'),
(3, 1, '2025-09-25 10:00:00', '2025-09-25 10:20:00', '2025-09-25 10:20:00', 68, 8, 5, 0, 'completed');

INSERT INTO tblTrain_Completion (EmployeeID, SubjectID, CompletedAt, Method, Score, ExamID, CreatedBy) VALUES
(2, 2, '2025-09-20 14:30:00', 'exam', 92, 3, 1),
(3, 1, '2025-09-25 10:20:00', 'exam', 68, 4, 1);

-- Thêm watch logs
INSERT INTO tblTrain_WatchLog (EmployeeID, SubjectID, Event, WatchedSeconds, CurrentTime, UserAgent, IPAddress) VALUES
(2, 1, 'heartbeat', 300, 300, 'Mozilla/5.0 (Windows NT)', '10.0.0.5'),
(3, 4, 'ended', 1500, 1500, 'Mozilla/5.0 (Macintosh)', '10.0.0.6');

SET FOREIGN_KEY_CHECKS = 1;