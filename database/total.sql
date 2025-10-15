-- Tạo database nếu chưa có
CREATE DATABASE IF NOT EXISTS training_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE training_db;

-- Tạo các bảng cơ bản
CREATE TABLE IF NOT EXISTS tblTrain_KnowledgeGroup (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Name VARCHAR(255) NOT NULL,
    Status TINYINT DEFAULT 1,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblTrain_Position (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    PositionName VARCHAR(255) NOT NULL,
    Department VARCHAR(255),
    Status TINYINT DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblTrain_Employee (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    FirstName VARCHAR(100) NOT NULL,
    LastName VARCHAR(100) NOT NULL,
    Email VARCHAR(255) NOT NULL UNIQUE,
    PasswordHash VARCHAR(255) NOT NULL,
    Department VARCHAR(100),
    Position VARCHAR(100),
    PositionID INT,
    Status TINYINT DEFAULT 1,
    LastLoginAt DATETIME DEFAULT NULL,
    LastLoginIP VARCHAR(45),
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (PositionID) REFERENCES tblTrain_Position(ID),
    INDEX idx_email (Email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblTrain_Subject (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    Title VARCHAR(255) NOT NULL,
    Description TEXT,
    VideoURL VARCHAR(500),
    Duration INT DEFAULT 0 COMMENT 'Thời lượng video tính bằng giây',
    FileURL VARCHAR(500) COMMENT 'URL tài liệu đính kèm',
    KnowledgeGroupID INT,
    MinWatchPercent FLOAT DEFAULT 90 COMMENT 'Phần trăm tối thiểu phải xem (90%)',
    MaxSkipSeconds INT DEFAULT 5 COMMENT 'Số giây tối đa được phép tua',
    AllowRewatch TINYINT DEFAULT 1 COMMENT 'Cho phép xem lại sau khi hoàn thành',
    RequiredScore FLOAT DEFAULT 70 COMMENT 'Điểm tối thiểu để đạt',
    Status TINYINT DEFAULT 1,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (KnowledgeGroupID) REFERENCES tblTrain_KnowledgeGroup(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Tạo các bảng quản lý bài thi
    CREATE TABLE IF NOT EXISTS tblTrain_Question (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        SubjectID INT NOT NULL,
        QuestionText TEXT NOT NULL,
        QuestionType ENUM('single', 'multiple') DEFAULT 'single',
        Score FLOAT DEFAULT 1,
        Status TINYINT DEFAULT 1,
        CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS tblTrain_Answer (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        QuestionID INT NOT NULL,
        AnswerText TEXT NOT NULL,
        IsCorrect TINYINT DEFAULT 0,
        CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (QuestionID) REFERENCES tblTrain_Question(ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS tblTrain_Exam (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        EmployeeID INT NOT NULL,
        SubjectID INT NOT NULL,
        StartTime DATETIME NOT NULL,
        EndTime DATETIME,
        Score FLOAT DEFAULT 0,
        TotalQuestions INT DEFAULT 0,
        CorrectAnswers INT DEFAULT 0,
        Status ENUM('started', 'completed', 'timeout') DEFAULT 'started',
        CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID),
        FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    CREATE TABLE IF NOT EXISTS tblTrain_ExamDetail (
        ID INT AUTO_INCREMENT PRIMARY KEY,
        ExamID INT NOT NULL,
        QuestionID INT NOT NULL,
        AnswerID INT,
        IsCorrect TINYINT DEFAULT 0,
        CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (ExamID) REFERENCES tblTrain_Exam(ID),
        FOREIGN KEY (QuestionID) REFERENCES tblTrain_Question(ID),
        FOREIGN KEY (AnswerID) REFERENCES tblTrain_Answer(ID)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

    -- Tạo các bảng theo dõi và chứng chỉ
CREATE TABLE IF NOT EXISTS tblTrain_Assign (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    PositionID INT NOT NULL,
    SubjectID INT NOT NULL,
    AssignDate DATE NOT NULL,
    ExpireDate DATE,
    IsRequired TINYINT DEFAULT 1,
    Status TINYINT DEFAULT 1,
    FOREIGN KEY (PositionID) REFERENCES tblTrain_Position(ID),
    FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID),
    UNIQUE KEY unique_assign (PositionID, SubjectID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

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
    FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID),
    FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblTrain_Completion (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT NOT NULL,
    SubjectID INT NOT NULL,
    CompletedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    Method ENUM('video', 'exam', 'manual') DEFAULT 'video',
    Score FLOAT DEFAULT NULL,
    ExamID INT DEFAULT NULL,
    CreatedBy INT COMMENT 'ID của admin nếu complete manual',
    UNIQUE KEY unique_completion (EmployeeID, SubjectID),
    INDEX idx_method (Method),
    FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID),
    FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID),
    FOREIGN KEY (ExamID) REFERENCES tblTrain_Exam(ID),
    FOREIGN KEY (CreatedBy) REFERENCES tblTrain_Employee(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tblTrain_Certificate (
    ID INT AUTO_INCREMENT PRIMARY KEY,
    EmployeeID INT NOT NULL,
    SubjectID INT NOT NULL,
    CertificateCode VARCHAR(64) NOT NULL,
    IssuedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    ExpiresAt DATETIME NOT NULL,
    FileURL VARCHAR(500) DEFAULT NULL,
    Status TINYINT DEFAULT 1 COMMENT '1: active, 0: expired/revoked',
    RevokedAt DATETIME DEFAULT NULL,
    RevokedBy INT DEFAULT NULL,
    RevokeReason TEXT,
    UNIQUE KEY unique_cert_code (CertificateCode),
    INDEX idx_expires (ExpiresAt),
    FOREIGN KEY (EmployeeID) REFERENCES tblTrain_Employee(ID),
    FOREIGN KEY (SubjectID) REFERENCES tblTrain_Subject(ID),
    FOREIGN KEY (RevokedBy) REFERENCES tblTrain_Employee(ID)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add Passed column to tblTrain_Exam table
ALTER TABLE tblTrain_Exam
ADD COLUMN Passed TINYINT DEFAULT 0 AFTER Score;

-- Update existing records
-- Mark as passed if score >= 80%
UPDATE tblTrain_Exam 
SET Passed = CASE 
    WHEN Score >= 80 AND Status = 'completed' THEN 1 
    ELSE 0 
END;