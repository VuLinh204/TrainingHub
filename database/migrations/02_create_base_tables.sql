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