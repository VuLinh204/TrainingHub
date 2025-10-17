<?php
if (!isset($GLOBALS['db'])) {
    try {
        $db = new PDO(
            "mysql:host=localhost;dbname=training_db;charset=utf8mb4",
            "root",
            "",
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
            ]
        );
        
    // Constants for table names
    define('TBL_ANSWERS', 'tblTrain_Answer');
    define('TBL_ASSIGN', 'tblTrain_Assign');
    define('TBL_EMPLOYEE', 'tblTrain_Employee');
    define('TBL_EXAM', 'tblTrain_Exam'); 
    define('TBL_EXAMDETAIL', 'tblTrain_ExamDetail');
    define('TBL_KNOWLEDGE_GROUP', 'tblTrain_KnowledgeGroup');
    define('TBL_POSITION', 'tblTrain_Position');
    define('TBL_QUESTION', 'tblTrain_Question');
    define('TBL_SUBJECT', 'tblTrain_Subject');
    define('TBL_WATCH_LOG', 'tblTrain_WatchLog');
    define('TBL_COMPLETION', 'tblTrain_Completion');
    define('TBL_CERTIFICATE', 'tblTrain_Certificate');
    define('TBL_NOTIFICATION', 'tblTrain_Notification');
        
        return $db;
    } catch (PDOException $e) {
        if (getenv('APP_ENV') === 'production') {
            error_log($e->getMessage());
            die('Database connection failed');
        } else {
            die('Connection failed: ' . $e->getMessage());
        }
    }
}
