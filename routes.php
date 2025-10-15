<?php
/**
 * Application Routes
 * Format: 'METHOD /path' => 'Controller@action'
 */

return [
    // Authentication routes
    'GET /login' => 'AuthController@loginForm',
    'POST /login' => 'AuthController@login',
    'GET /logout' => 'AuthController@logout',
    
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
    'POST /exam/check-answer' => 'ExamController@checkAnswer',
    'GET /exam/:id/results' => 'ExamController@results',
    
    // Certificates
    'GET /certificates' => 'CertificateController@index',
    'GET /certificate/:code' => 'CertificateController@show',
    'GET /certificate/:code/download' => 'CertificateController@download',
    'GET /certificate/:code/print' => 'CertificateController@printView',
    'GET /verify/:code' => 'CertificateController@verify',
    
    // Employee Profile
    'GET /profile' => 'EmployeeController@profile',
    'GET /profile/edit' => 'EmployeeController@editProfile',
    'POST /profile/update' => 'EmployeeController@updateProfile',
    'GET /profile/change-password' => 'EmployeeController@changePasswordForm',
    'POST /profile/change-password' => 'EmployeeController@changePassword',
    'GET /profile/history' => 'EmployeeController@learningHistory',
    'GET /profile/statistics' => 'EmployeeController@statistics',
    
    // API routes for AJAX
    'POST /api/lesson/track' => 'SubjectController@trackProgress',
    'POST /api/lesson/complete' => 'SubjectController@complete',
    'GET /api/subject/:id/progress' => 'SubjectController@getProgress',
    'GET /api/notifications' => 'NotificationsController@index',
];