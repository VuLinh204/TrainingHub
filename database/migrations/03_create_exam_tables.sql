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