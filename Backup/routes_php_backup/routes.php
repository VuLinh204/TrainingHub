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
    'GET /search' => 'SubjectController@search',
    
    // Exams - FIXED: Added GET route for exam page
    'GET /exam/:id' => 'ExamController@show',
    'GET /exam/:id/start' => 'ExamController@show',
    'POST /exam/:id/start' => 'ExamController@start',
    'POST /exam/:id/submit' => 'ExamController@submit',
    'POST /exam/check-answer' => 'ExamController@checkAnswer',
    'GET /exam/:id/results' => 'ExamController@results',
    
    // Certificates (Employee)
    'GET /certificates' => 'CertificateController@index',
    'GET /certificates/:code' => 'CertificateController@show',
    'GET /certificates/:code/download' => 'CertificateController@download',
    'GET /certificates/:code/print' => 'CertificateController@printView',
    'GET /verify/:code' => 'CertificateController@verify',
    
    // Employee Profile
    'GET /profile' => 'EmployeeController@profile',
    'GET /profile/edit' => 'EmployeeController@editProfile',
    'POST /profile/update' => 'EmployeeController@updateProfile',
    'GET /profile/change-password' => 'EmployeeController@changePasswordForm',
    'POST /profile/change-password' => 'EmployeeController@changePassword',
    'GET /profile/history' => 'EmployeeController@learningHistory',
    'GET /profile/statistics' => 'EmployeeController@statistics',
    
    // ========== ADMIN ROUTES ==========
    
    // Admin Dashboard
    'GET /admin' => 'AdminDashboardController@index',
    'GET /admin/dashboard' => 'AdminDashboardController@index',
    
    // Admin Certificate Management
    'GET /admin/certificates' => 'AdminCertificateController@index',
    'GET /admin/certificates/pending' => 'AdminCertificateController@pendingList',
    'GET /admin/certificates/statistics' => 'AdminCertificateController@statistics',
    'GET /admin/certificates/review/:id' => 'AdminCertificateController@reviewDetail',
    'POST /admin/certificates/approve/:id' => 'AdminCertificateController@approve',
    'POST /admin/certificates/reject' => 'AdminCertificateController@reject',
    'POST /admin/certificates/revoke' => 'AdminCertificateController@revoke',
    'POST /admin/certificates/restore/:id' => 'AdminCertificateController@restore',
    'GET /admin/certificates/export' => 'AdminCertificateController@exportReport',
    
    // Admin Employee Management
    'GET /admin/employees' => 'AdminEmployeeController@index',
    'GET /admin/employees/:id' => 'AdminEmployeeController@show',
    'GET /admin/employees/:id/progress' => 'AdminEmployeeController@progress',
    
    // Admin Subject Management
    'GET /admin/subjects' => 'AdminSubjectController@index',
    'GET /admin/subjects/create' => 'AdminSubjectController@createForm',
    'POST /admin/subjects/create' => 'AdminSubjectController@create',
    'GET /admin/subjects/:id/edit' => 'AdminSubjectController@editForm',
    'POST /admin/subjects/:id/update' => 'AdminSubjectController@update',
    'POST /admin/subjects/:id/delete' => 'AdminSubjectController@delete',
    
    // Admin Assignment Management
    'GET /admin/assignments' => 'AdminAssignmentController@index',
    'POST /admin/assignments/create' => 'AdminAssignmentController@create',
    'POST /admin/assignments/delete' => 'AdminAssignmentController@delete',
    
    // Admin Reports
    'GET /admin/reports' => 'AdminReportController@index',
    'GET /admin/reports/completion' => 'AdminReportController@completion',
    'GET /admin/reports/exams' => 'AdminReportController@exams',
    'GET /admin/reports/certificates' => 'AdminReportController@certificates',

    // Admin Settings
    'GET /admin/settings' => 'AdminSettingsController@index',
    'POST /admin/settings/update-general' => 'AdminSettingsController@updateGeneral',
    'POST /admin/settings/update-notifications' => 'AdminSettingsController@updateNotifications',
    'POST /admin/settings/update-certificate' => 'AdminSettingsController@updateCertificate',
    'POST /admin/settings/update-exam' => 'AdminSettingsController@updateExam',
    'POST /admin/settings/update-exam-security' => 'AdminSettingsController@updateExamSecurity',
    'POST /admin/settings/update-smtp' => 'AdminSettingsController@updateSmtp',
    'POST /admin/settings/update-email-template' => 'AdminSettingsController@updateEmailTemplate',
    'POST /admin/settings/backup' => 'AdminSettingsController@backup',
    'POST /admin/settings/cleanup' => 'AdminSettingsController@cleanup',
    'POST /admin/settings/danger-zone' => 'AdminSettingsController@dangerZone',
    
    // API routes for AJAX
    'POST /subject/track' => 'SubjectController@trackProgress',
    'POST /subject/complete' => 'SubjectController@complete',
    'GET /subject/:id/progress' => 'SubjectController@getProgress',
    'GET /notifications' => 'NotificationsController@index',
    'POST /notifications/:id/read' => 'NotificationsController@markRead',
];
?>