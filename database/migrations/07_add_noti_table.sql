CREATE TABLE IF NOT EXISTS `tblTrain_Notification` (
  `ID` INT AUTO_INCREMENT PRIMARY KEY,
  `EmployeeID` INT NOT NULL,
  `Title` VARCHAR(255) NOT NULL,
  `Message` TEXT,
  `Link` VARCHAR(500),
  `Read` TINYINT DEFAULT 0,
  `Type` ENUM('info', 'success', 'warning', 'error') DEFAULT 'info',
  `CreatedAt` DATETIME DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`EmployeeID`) REFERENCES `tblTrain_Employee`(`ID`),
  INDEX `idx_employee_read` (`EmployeeID`, `Read`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;