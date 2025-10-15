-- Thêm dữ liệu mẫu
INSERT INTO tblTrain_KnowledgeGroup (Name) VALUES 
('Javascript cơ bản'),
('Javascript nâng cao'),
('Javascript ̃& ReactJS');

INSERT INTO tblTrain_Position (PositionName, Department) VALUES
('Nhân viên', 'IT'),
('Tổ trưởng', 'IT'),
('Quản lý', 'IT'),
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
'IT', 'Admin', 5, 1),
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
('Javascript cơ bản cơ bản',
'Khóa học cung cấp kiến thức cơ bản về an toàn lao động trong nhà máy',
'http://localhost/Training/assets/videos/js-basic.mp4',
1800,
1,
90,
80);

INSERT INTO tblTrain_Question (SubjectID, QuestionText) VALUES
(1, 'Trong JavaScript, var, let, và const khác nhau chủ yếu ở điểm nào?');

INSERT INTO tblTrain_Answer (QuestionID, AnswerText, IsCorrect) VALUES
(1, 'let và const có phạm vi khối (block scope), còn var có phạm vi hàm (function scope)', 1),
(1, 'Chỉ khác nhau ở cách đặt tên biến', 0),
(1, 'var nhanh hơn let và const', 0),
(1, 'Không có sự khác biệt nào, chỉ là cú pháp khác', 0);

-- Assign subjects to positions
INSERT INTO tblTrain_Assign (PositionID, SubjectID, AssignDate, ExpireDate, IsRequired) VALUES
(4, 1, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 6 MONTH), 1),
(5, 1, CURRENT_DATE, DATE_ADD(CURRENT_DATE, INTERVAL 6 MONTH), 1);