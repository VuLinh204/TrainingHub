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