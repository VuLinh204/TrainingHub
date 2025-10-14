<?php
return [
    // Dashboard
    'GET /' => 'DashboardController@index',
    'GET /dashboard' => 'DashboardController@index',
    
    // Subjects
    'GET /subjects' => 'SubjectController@index',
    'GET /subject/:id' => 'SubjectController@detail',
    
    // Exams
    'GET /exam/:id' => 'ExamController@show',
    'POST /exam/:id/start' => 'ExamController@start',
    'POST /exam/:id/submit' => 'ExamController@submit',
    
    // Auth
    'GET /login' => 'AuthController@loginForm',
    'POST /login' => 'AuthController@login',
    'GET /logout' => 'AuthController@logout',
    'POST /logout' => 'AuthController@logout',
    
    // Profile
    'GET /profile' => 'EmployeeController@profile',
    'POST /profile/update' => 'EmployeeController@updateProfile',
    'POST /profile/password' => 'EmployeeController@changePassword',
    
    // Certificates
    'GET /certificates' => 'CertificateController@index',
    'GET /certificate/:code' => 'CertificateController@show',
    'GET /certificate/:code/download' => 'CertificateController@download',
    'GET /certificate/:code/print' => 'CertificateController@printView',
    
    // API Routes (AJAX)
    'POST /api/lesson/track' => 'SubjectController@trackProgress',
    'POST /api/lesson/complete' => 'SubjectController@complete',
    'POST /api/exam/check-answer' => 'ExamController@checkAnswer',
    'GET /api/subject/:id/progress' => 'SubjectController@getProgress',
    
    // Search
    'GET /search' => 'SearchController@index',
    
    // Help & Support
    'GET /help' => 'HelpController@index',
    'GET /faq' => 'HelpController@faq',
];