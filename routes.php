<?php
return [
    // Dashboard
    'GET /' => 'DashboardController@index',
    'GET /dashboard' => 'DashboardController@index',
    
    // Subjects
    'GET /subjects' => 'SubjectController@index',
    'GET /subject/:id' => 'SubjectController@detail',
    
    // Exams
    'GET /exam/:id' => 'ExamController@takeExam',
    
    // Auth
    'GET /login' => 'AuthController@loginForm',
    'POST /login' => 'AuthController@login',
    'GET /logout' => 'AuthController@logout',
    
    // Profile
    'GET /profile' => 'EmployeeController@profile',
    'POST /profile' => 'EmployeeController@updateProfile',
    
    // Certificates
    'GET /certificates' => 'CertificateController@index',
    'GET /certificate/:id' => 'CertificateController@show',
    'GET /certificate/:id/download' => 'CertificateController@download'
];