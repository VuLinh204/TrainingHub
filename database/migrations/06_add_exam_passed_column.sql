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