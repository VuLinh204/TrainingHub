-- Thêm dữ liệu mẫu
INSERT INTO tblTrain_KnowledgeGroup (Name) VALUES 
('An toàn lao động'),
('Quy trình sản xuất'),
('Kỹ năng mềm');

INSERT INTO tblTrain_Position (PositionName, Department) VALUES
('Nhân viên', 'Sản xuất'),
('Tổ trưởng', 'Sản xuất'),
('Quản lý', 'Sản xuất'),
('Nhân viên', 'IT'),
('Quản lý', 'IT');

INSERT INTO tblTrain_Employee (
    FirstName,
    LastName,
    Email,
    PasswordHash,
    Department,
    Position,
    PositionID,
    Status
) VALUES 
-- Admin account: admin@company.com / admin123
('Admin', 'User', 'admin@company.com', 
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
'IT', 'Administrator', 5, 1),
-- Test account: test@company.com / password
('Test', 'User', 'test@company.com',
'$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
'IT', 'Staff', 4, 1);

INSERT INTO tblTrain_Subject (
    Title,
    Description,
    VideoURL,
    Duration,
    KnowledgeGroupID,
    MinWatchPercent,
    RequiredScore
) VALUES 
('An toàn lao động cơ bản',
'Khóa học cung cấp kiến thức cơ bản về an toàn lao động trong nhà máy',
'https://example.com/videos/safety-basic.mp4',
1800,
1,
90,
80);

INSERT INTO tblTrain_Question (SubjectID, QuestionText) VALUES
(1, 'Đâu là thiết bị bảo hộ lao động bắt buộc khi vào xưởng sản xuất?');

INSERT INTO tblTrain_Answer (QuestionID, AnswerText, IsCorrect) VALUES
(1, 'Mũ bảo hộ', 1),
(1, 'Đồng hồ', 0),
(1, 'Điện thoại', 0),
(1, 'Nhẫn', 0);

-- Assign subjects to positions
INSERT INTO tblTrain_Assign (PositionID, SubjectID, AssignDate, ExpireDate, IsRequired) VALUES
(4, 1, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 6 MONTH), 1),
(5, 1, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 6 MONTH), 1);