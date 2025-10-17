BEGIN TRANSACTION;

BEGIN TRY
    -- Bảng: tblTrain_Settings
    CREATE TABLE tblTrain_Settings (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        SettingKey NVARCHAR(255) NOT NULL UNIQUE,
        SettingValue NVARCHAR(MAX),
        Description NVARCHAR(MAX),
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        UpdatedAt DATETIME2 DEFAULT GETDATE()
    );

    -- Bảng: tblTrain_ActivityLog
    CREATE TABLE tblTrain_ActivityLog (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        EmployeeID VARCHAR(20),
        Action NVARCHAR(255) NOT NULL,
        Description NVARCHAR(MAX),
        IPAddress NVARCHAR(45),
        UserAgent NVARCHAR(MAX),
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT FK_tblTrain_ActivityLog_tblEmployee FOREIGN KEY (EmployeeID) REFERENCES tblEmployee(EmployeeID) ON DELETE SET NULL
    );

    -- Bảng: tblTrain_Sessions
    CREATE TABLE tblTrain_Sessions (
        ID NVARCHAR(255) PRIMARY KEY,
        EmployeeID VARCHAR(20),
        Data NVARCHAR(MAX),
        IPAddress NVARCHAR(45),
        UserAgent NVARCHAR(MAX),
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        ExpireAt DATETIME2,
        CONSTRAINT FK_tblTrain_Sessions_tblEmployee FOREIGN KEY (EmployeeID) REFERENCES tblEmployee(EmployeeID) ON DELETE CASCADE
    );

    -- Bảng: tblTrain_AuditLog
    CREATE TABLE tblTrain_AuditLog (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        ActorID VARCHAR(20),
        Action NVARCHAR(20) NOT NULL CHECK (Action IN ('INSERT', 'UPDATE', 'DELETE')),
        TableName NVARCHAR(255) NOT NULL,
        RecordID INT NOT NULL,
        OldValue NVARCHAR(MAX),
        NewValue NVARCHAR(MAX),
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT FK_tblTrain_AuditLog_tblEmployee FOREIGN KEY (ActorID) REFERENCES tblEmployee(EmployeeID) ON DELETE SET NULL
    );

    -- Bảng: tblTrain_KnowledgeGroup
    CREATE TABLE tblTrain_KnowledgeGroup (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        Name NVARCHAR(255) NOT NULL,
        Status TINYINT DEFAULT 1,
        CreatedAt DATETIME2 DEFAULT GETDATE()
    );

    -- Bảng: tblTrain_Subject
    CREATE TABLE tblTrain_Subject (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        Title NVARCHAR(255) NOT NULL,
        Description NVARCHAR(MAX),
        VideoURL NVARCHAR(255),
        Duration INT,
        FileURL NVARCHAR(255),
        KnowledgeGroupID INT,
        MinWatchPercent INT DEFAULT 90,
        MaxSkipSeconds INT DEFAULT 0,
        AllowRewatch TINYINT DEFAULT 1,
        RequiredScore INT DEFAULT 70,
        MinCorrectAnswers INT DEFAULT 0,
        ExamTimeLimit INT DEFAULT 30,
        Status TINYINT DEFAULT 1,
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        DeletedAt DATETIME2,
        CONSTRAINT FK_tblTrain_Subject_tblTrain_KnowledgeGroup FOREIGN KEY (KnowledgeGroupID) REFERENCES tblTrain_KnowledgeGroup(ID) ON DELETE SET NULL
    );

    -- Bảng: tblTrain_Question
    CREATE TABLE tblTrain_Question (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        SubjectID INT,
        QuestionText NVARCHAR(MAX) NOT NULL,
        QuestionType NVARCHAR(20) DEFAULT 'single' CHECK (QuestionType IN ('single', 'multiple')),
        Score INT DEFAULT 1,
        Status TINYINT DEFAULT 1,
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT FK_tblTrain_Question_tblTrain_Subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE
    );

    -- Bảng: tblTrain_Answer
    CREATE TABLE tblTrain_Answer (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        QuestionID INT,
        AnswerText NVARCHAR(MAX) NOT NULL,
        IsCorrect TINYINT DEFAULT 0,
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT FK_tblTrain_Answer_tblTrain_Question FOREIGN KEY (QuestionID) REFERENCES tblTrain_Question(ID) ON DELETE CASCADE
    );

    -- Bảng: tblTrain_Exam
    CREATE TABLE tblTrain_Exam (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        EmployeeID VARCHAR(20),
        SubjectID INT,
        StartTime DATETIME2,
        EndTime DATETIME2,
        CompletedAt DATETIME2,
        Score INT,
        TotalQuestions INT,
        CorrectAnswers INT,
        Passed TINYINT DEFAULT 0,
        Status NVARCHAR(20) DEFAULT 'started' CHECK (Status IN ('started', 'completed', 'timeout')),
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT FK_tblTrain_Exam_tblEmployee FOREIGN KEY (EmployeeID) REFERENCES tblEmployee(EmployeeID) ON DELETE CASCADE,
        CONSTRAINT FK_tblTrain_Exam_tblTrain_Subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE
    );

    -- Trigger: trg_exam_after_update
    CREATE TRIGGER trg_exam_after_update
    ON tblTrain_Exam
    AFTER UPDATE
    AS
    BEGIN
        UPDATE e
        SET CompletedAt = GETDATE(),
            Passed = CASE WHEN i.Score >= 80 THEN 1 ELSE 0 END
        FROM tblTrain_Exam e
        INNER JOIN inserted i ON e.ID = i.ID
        WHERE i.Status = 'completed' AND (e.CompletedAt IS NULL OR e.Passed != CASE WHEN i.Score >= 80 THEN 1 ELSE 0 END);
    END;

    -- Bảng: tblTrain_ExamDetail
    CREATE TABLE tblTrain_ExamDetail (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        ExamID INT,
        QuestionID INT,
        AnswerID INT,
        IsCorrect TINYINT,
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT FK_tblTrain_ExamDetail_tblTrain_Exam FOREIGN KEY (ExamID) REFERENCES tblTrain_Exam(ID) ON DELETE CASCADE,
        CONSTRAINT FK_tblTrain_ExamDetail_tblTrain_Question FOREIGN KEY (QuestionID) REFERENCES tblTrain_Question(ID) ON DELETE CASCADE,
        CONSTRAINT FK_tblTrain_ExamDetail_tblTrain_Answer FOREIGN KEY (AnswerID) REFERENCES tblTrain_Answer(ID) ON DELETE SET NULL
    );

    -- Bảng: tblTrain_Assign
    CREATE TABLE tblTrain_Assign (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        PositionID INT,
        KnowledgeGroupID INT,
        AssignDate DATE,
        ExpireDate DATE,
        IsRequired TINYINT DEFAULT 1,
        Status TINYINT DEFAULT 1,
        CONSTRAINT FK_tblTrain_Assign_tblPosition FOREIGN KEY (PositionID) REFERENCES tblPosition(PositionID) ON DELETE CASCADE,
        CONSTRAINT FK_tblTrain_Assign_tblTrain_KnowledgeGroup FOREIGN KEY (KnowledgeGroupID) REFERENCES tblTrain_KnowledgeGroup(ID) ON DELETE CASCADE
    );

    -- Bảng: tblTrain_WatchLog
    CREATE TABLE tblTrain_WatchLog (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        EmployeeID VARCHAR(20),
        SubjectID INT,
        Event NVARCHAR(20) NOT NULL CHECK (Event IN ('heartbeat', 'ended', 'seek', 'resume', 'pause')),
        WatchedSeconds INT,
        CurrentTime INT,
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        UserAgent NVARCHAR(MAX),
        IPAddress NVARCHAR(45),
        CONSTRAINT FK_tblTrain_WatchLog_tblEmployee FOREIGN KEY (EmployeeID) REFERENCES tblEmployee(EmployeeID) ON DELETE CASCADE,
        CONSTRAINT FK_tblTrain_WatchLog_tblTrain_Subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE
    );

    -- Bảng: tblTrain_Completion
    CREATE TABLE tblTrain_Completion (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        EmployeeID VARCHAR(20),
        SubjectID INT,
        CompletedAt DATETIME2,
        Method NVARCHAR(20) NOT NULL CHECK (Method IN ('video', 'exam', 'manual')),
        Score INT,
        ExamID INT,
        CreatedBy VARCHAR(20),
        CONSTRAINT FK_tblTrain_Completion_tblEmployee FOREIGN KEY (EmployeeID) REFERENCES tblEmployee(EmployeeID) ON DELETE CASCADE,
        CONSTRAINT FK_tblTrain_Completion_tblTrain_Subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE,
        CONSTRAINT FK_tblTrain_Completion_tblTrain_Exam FOREIGN KEY (ExamID) REFERENCES tblTrain_Exam(ID) ON DELETE SET NULL,
        CONSTRAINT FK_tblTrain_Completion_tblEmployee_CreatedBy FOREIGN KEY (CreatedBy) REFERENCES tblEmployee(EmployeeID) ON DELETE SET NULL
    );

    -- Bảng: tblTrain_Certificate
    CREATE TABLE tblTrain_Certificate (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        EmployeeID VARCHAR(20),
        SubjectID INT,
        CertificateCode NVARCHAR(255) NOT NULL UNIQUE,
        CertificateHash NVARCHAR(255),
        IssuedAt DATETIME2,
        ExpiresAt DATETIME2,
        FileURL NVARCHAR(255),
        Status TINYINT DEFAULT 1,
        RevokedAt DATETIME2,
        RevokedBy VARCHAR(20),
        RevokeReason NVARCHAR(MAX),
        ApprovedBy VARCHAR(20),
        ApprovedAt DATETIME2,
        CONSTRAINT FK_tblTrain_Certificate_tblEmployee FOREIGN KEY (EmployeeID) REFERENCES tblEmployee(EmployeeID) ON DELETE CASCADE,
        CONSTRAINT FK_tblTrain_Certificate_tblTrain_Subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE,
        CONSTRAINT FK_tblTrain_Certificate_tblEmployee_RevokedBy FOREIGN KEY (RevokedBy) REFERENCES tblEmployee(EmployeeID) ON DELETE SET NULL,
        CONSTRAINT FK_tblTrain_Certificate_tblEmployee_ApprovedBy FOREIGN KEY (ApprovedBy) REFERENCES tblEmployee(EmployeeID) ON DELETE SET NULL
    );

    -- Trigger: trg_certificate_after_insert
    CREATE TRIGGER trg_certificate_after_insert
    ON tblTrain_Certificate
    AFTER INSERT
    AS
    BEGIN
        UPDATE c
        SET CertificateHash = CONVERT(NVARCHAR(64), HASHBYTES('SHA2_256', CAST(i.EmployeeID AS NVARCHAR(50)) + CAST(i.SubjectID AS NVARCHAR(10)) + i.CertificateCode + CAST(i.IssuedAt AS NVARCHAR(50))), 2)
        FROM tblTrain_Certificate c
        INNER JOIN inserted i ON c.ID = i.ID;
    END;

    -- Bảng: tblTrain_Notification
    CREATE TABLE tblTrain_Notification (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        EmployeeID VARCHAR(20),
        Title NVARCHAR(255) NOT NULL,
        Message NVARCHAR(MAX) NOT NULL,
        Link NVARCHAR(255),
        [Read] BIT DEFAULT 0,
        Type NVARCHAR(20) DEFAULT 'info' CHECK (Type IN ('info', 'success', 'warning', 'error')),
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT FK_tblTrain_Notification_tblEmployee FOREIGN KEY (EmployeeID) REFERENCES tblEmployee(EmployeeID) ON DELETE CASCADE
    );

    -- Bảng: tblTrain_Material
    CREATE TABLE tblTrain_Material (
        ID INT IDENTITY(1,1) PRIMARY KEY,
        SubjectID INT,
        Title NVARCHAR(255) NOT NULL,
        FileURL NVARCHAR(255),
        Status TINYINT DEFAULT 1,
        CreatedAt DATETIME2 DEFAULT GETDATE(),
        CONSTRAINT FK_tblTrain_Material_tblTrain_Subject FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID) ON DELETE CASCADE
    );

    -- Chèn dữ liệu mẫu
    -- Giả sử tblEmployee, tblPosition, tblDepartment đã có dữ liệu phù hợp (EmployeeID '1'-'4' cho Employee, ID 1-3 cho Position)

    -- tblTrain_Settings
    INSERT INTO tblTrain_Settings (SettingKey, SettingValue, Description) VALUES
    ('company_name', N'CÔNG TY ABC', N'Tên công ty'),
    ('min_score', '70', N'Điểm tối thiểu để qua bài thi (%)'),
    ('min_watch_percent', '90', N'Tỷ lệ xem video tối thiểu (%)'),
    ('exam_default_time', '30', N'Thời gian làm bài kiểm tra mặc định (phút)'),
    ('exam_max_attempts', '3', N'Số lần thi tối đa'),
    ('cert_prefix', 'CERT', N'Tiền tố mã chứng chỉ');

    -- tblTrain_KnowledgeGroup
    INSERT INTO tblTrain_KnowledgeGroup (Name, Status) VALUES
    (N'Chung', 1),
    (N'Sales Cơ bản', 1),
    (N'Sales Nâng Cao', 1),
    (N'Học Việc lập trình', 1),
    (N'Triển khai', 1),
    (N'Backend', 1);

    -- tblTrain_Subject
    INSERT INTO tblTrain_Subject (Title, VideoURL, FileURL, KnowledgeGroupID, MinWatchPercent, RequiredScore, MinCorrectAnswers, ExamTimeLimit, Status) VALUES
    (N'Giới thiệu Vietinsoft & Paradise HR', 'http://localhost/Training/assets/videos/intro-vietinsoft.mp4', NULL, 1, 90, 70, 8, 30, 1),
    (N'Thị trường mục tiêu & Buyer Personas', 'http://localhost/Training/assets/videos/market-target.mp4', NULL, 2, 90, 70, 10, 30, 1),
    (N'Cài đặt VS', 'http://localhost/Training/assets/videos/vs-install.mp4', NULL, 4, 90, 70, 10, 30, 1),
    (N'JavaScript cơ bản', 'http://localhost/Training/assets/videos/js-basic.mp4', NULL, 1, 90, 70, 7, 30, 1);

    -- tblTrain_Assign
    -- Giả sử PositionID 1,2 tồn tại trong tblPosition
    INSERT INTO tblTrain_Assign (PositionID, KnowledgeGroupID, AssignDate, ExpireDate, IsRequired, Status) VALUES
    (1, 1, '2025-10-17', '2026-04-17', 1, 1),
    (2, 3, '2025-10-17', '2026-04-17', 1, 1),
    (1, 4, '2025-10-17', '2026-04-17', 1, 1);

    -- tblTrain_Question
    INSERT INTO tblTrain_Question (SubjectID, QuestionText, QuestionType, Score, Status) VALUES
    (1, N'Sứ mệnh của Vietinsoft KHÔNG bao gồm điều nào sau đây?', 'single', 1, 1),
    (1, N'Vietinsoft được thành lập vào năm nào và bởi bao nhiêu thành viên?', 'single', 1, 1),
    (4, N'Trong JavaScript, var, let, và const khác nhau chủ yếu ở điểm nào?', 'single', 1, 1),
    (4, N'Function scope trong JavaScript là gì?', 'single', 1, 1),
    (4, N'Closure trong JavaScript được sử dụng khi nào?', 'single', 1, 1),
    (4, N'Arrow function có tính năng gì khác so với regular function?', 'single', 1, 1),
    (4, N'async/await được sử dụng cho mục đích nào?', 'single', 1, 1),
    (4, N'Promise.all() làm gì?', 'single', 1, 1),
    (4, N'Callback hell là vấn đề gì?', 'single', 1, 1),
    (4, N'== và === khác nhau như thế nào?', 'single', 1, 1),
    (4, N'Hoisting trong JavaScript là gì?', 'single', 1, 1),
    (4, N'This keyword trong JavaScript đề cập đến cái gì?', 'single', 1, 1);

    -- tblTrain_Answer
    INSERT INTO tblTrain_Answer (QuestionID, AnswerText, IsCorrect) VALUES
    (1, N'Xây dựng môi trường làm việc hiện đại, minh bạch.', 0),
    (1, N'Trở thành đơn vị số #1 tại Việt Nam trong lĩnh vực phần mềm quản trị nhân sự.', 1),
    (1, N'Giúp các nhà máy, xí nghiệp giải quyết khó khăn trong quản lý nhân sự.', 0),
    (1, N'Giải phóng thời gian cho bộ phận HR, thay thế công việc giấy tờ.', 0),
    (2, N'Năm 2009 với 3 thành viên.', 0),
    (2, N'Năm 2008 với 2 thành viên.', 1),
    (2, N'Năm 2010 với 2 thành viên.', 0),
    (2, N'Năm 2008 với 1 thành viên.', 0),
    (3, N'let và const có phạm vi khối (block scope), còn var có phạm vi hàm (function scope)', 1),
    (3, N'Chỉ khác nhau ở cách đặt tên biến', 0),
    (3, N'var nhanh hơn let và const', 0),
    (3, N'Không có sự khác biệt nào, chỉ là cú pháp khác', 0),
    (4, N'Phạm vi mà biến chỉ tồn tại bên trong hàm', 1),
    (4, N'Phạm vi toàn cục của chương trình', 0),
    (4, N'Phạm vi chỉ của một khối lệnh', 0),
    (4, N'Phạm vi chỉ tồn tại 1 giây', 0),
    (5, N'Khi một hàm cần truy cập vào biến từ phạm vi cha của nó', 1),
    (5, N'Khi muốn tạo vòng lặp', 0),
    (5, N'Khi muốn gọi hàm nhiều lần', 0),
    (5, N'Khi muốn khai báo biến global', 0),
    (6, N'Arrow function không có binding của this, không có arguments', 1),
    (6, N'Arrow function có thể được dùng như constructor', 0),
    (6, N'Arrow function nhanh hơn regular function', 0),
    (6, N'Không có khác biệt gì', 0),
    (7, N'Viết code bất đồng bộ theo cách đồng bộ hơn', 1),
    (7, N'Làm code chạy nhanh hơn', 0),
    (7, N'Thay thế các vòng lặp', 0),
    (7, N'Tạo timer', 0),
    (8, N'Chờ cho tất cả promises hoàn thành', 1),
    (8, N'Chỉ chờ promise đầu tiên', 0),
    (8, N'Hủy tất cả promises', 0),
    (8, N'Chuyển đổi promise sang callback', 0),
    (9, N'Nhiều callback được lồng nhau làm code khó đọc', 1),
    (9, N'Lỗi cú pháp', 0),
    (9, N'Hiệu năng chậm', 0),
    (9, N'Memory leak', 0),
    (10, N'== so sánh giá trị, === so sánh cả giá trị và kiểu dữ liệu', 1),
    (10, N'Giống nhau hoàn toàn', 0),
    (10, N'=== nhanh hơn ==', 0),
    (10, N'== so sánh địa chỉ ô nhớ', 0),
    (11, N'Quá trình khai báo và khởi tạo biến được di chuyển lên đầu scope', 1),
    (11, N'Quá trình xóa biến', 0),
    (11, N'Quá trình sao chép biến', 0),
    (11, N'Quá trình mã hóa biến', 0),
    (12, N'Đối tượng gọi hàm', 1),
    (12, N'Hàm được định nghĩa', 0),
    (12, N'Biến toàn cục', 0),
    (12, N'Lớp của hàm', 0);

    -- Giả sử SubjectID 3 tồn tại, và EmployeeID '3','4' tồn tại trong tblEmployee
    -- tblTrain_Exam
    INSERT INTO tblTrain_Exam (EmployeeID, SubjectID, StartTime, EndTime, CompletedAt, Score, TotalQuestions, CorrectAnswers, Passed, Status) VALUES
    ('3', 3, '2025-10-17 09:00:00', '2025-10-17 09:30:00', '2025-10-17 09:30:00', 85, 10, 8, 1, 'completed'),
    ('4', 3, '2025-10-17 10:00:00', '2025-10-17 10:30:00', '2025-10-17 10:30:00', 90, 10, 9, 1, 'completed');

    -- Giả sử ExamID 1,2 được tạo từ insert trên
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
    ('3', 3, '2025-10-17 09:30:00', 'exam', 85, 1, NULL),
    ('4', 3, '2025-10-17 10:30:00', 'exam', 90, 2, NULL);

    -- tblTrain_Certificate
    -- Trigger sẽ tự set CertificateHash sau insert
    INSERT INTO tblTrain_Certificate (EmployeeID, SubjectID, CertificateCode, IssuedAt, ExpiresAt, FileURL, Status, ApprovedBy, ApprovedAt) VALUES
    ('3', 3, N'CERT-003-3-20251017', '2025-10-17 09:30:00', '2026-10-17 09:30:00', 'http://localhost/Training/certificates/cert-003-3.pdf', 1, '1', '2025-10-17 09:30:00'),
    ('4', 3, N'CERT-004-3-20251017', '2025-10-17 10:30:00', '2026-10-17 10:30:00', 'http://localhost/Training/certificates/cert-004-3.pdf', 1, '1', '2025-10-17 10:30:00');

    -- tblTrain_Notification
    INSERT INTO tblTrain_Notification (EmployeeID, Title, Message, Link, [Read], Type) VALUES
    ('3', N'Hoàn thành khóa học', N'Bạn đã hoàn thành khóa học Cài đặt VS và được cấp chứng chỉ.', 'http://localhost/Training/certificates/cert-003-3.pdf', 0, 'success'),
    ('4', N'Hoàn thành khóa học', N'Bạn đã hoàn thành khóa học Cài đặt VS và được cấp chứng chỉ.', 'http://localhost/Training/certificates/cert-004-3.pdf', 0, 'success');

    -- tblTrain_WatchLog
    INSERT INTO tblTrain_WatchLog (EmployeeID, SubjectID, Event, WatchedSeconds, CurrentTime, UserAgent, IPAddress) VALUES
    ('3', 3, 'ended', 1800, 1800, 'Mozilla/5.0', '192.168.1.1'),
    ('4', 3, 'ended', 1800, 1800, 'Mozilla/5.0', '192.168.1.2');

    -- tblTrain_Material
    INSERT INTO tblTrain_Material (SubjectID, Title, FileURL, Status) VALUES
    (1, N'Hướng dẫn Vietinsoft', 'http://localhost/Training/materials/intro-vietinsoft.pdf', 1),
    (4, N'Tài liệu JavaScript cơ bản', 'http://localhost/Training/materials/js-basic.pdf', 1);

    -- Nếu thành công, commit transaction
    COMMIT TRANSACTION;
    PRINT N'Script thực thi thành công! Tất cả bảng, trigger và dữ liệu đã được tạo/chèn.';
END TRY
BEGIN CATCH
    -- Nếu có lỗi, rollback transaction
    ROLLBACK TRANSACTION;
    -- In thông tin lỗi để debug
    DECLARE @ErrorMessage NVARCHAR(4000) = ERROR_MESSAGE();
    DECLARE @ErrorSeverity INT = ERROR_SEVERITY();
    DECLARE @ErrorState INT = ERROR_STATE();
    PRINT N'Lỗi xảy ra: ' + @ErrorMessage;
    RAISERROR (@ErrorMessage, @ErrorSeverity, @ErrorState);
END CATCH;