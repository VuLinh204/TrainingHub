<?php
require_once __DIR__ . '/../core/Controller.php';
require_once __DIR__ . '/../models/EmployeeModel.php';
require_once __DIR__ . '/../models/SubjectModel.php';
require_once __DIR__ . '/../models/CompletionModel.php';

class DashboardController extends Controller {
    protected $employeeModel;
    protected $subjectModel;
    protected $completionModel;
    
    public function __construct() {
        parent::__construct();
        $this->employeeModel = new EmployeeModel();
        $this->subjectModel = new SubjectModel();
        $this->completionModel = new CompletionModel();
    }
    
    public function index() {
        // Kiểm tra đăng nhập
        $employeeId = $this->checkAuth();
        
        // Lấy thông tin nhân viên
        $employee = $this->employeeModel->findById($employeeId);
        
        // Lấy danh sách khóa học được phân công
        $assignedSubjects = $this->employeeModel->getAssignedSubjects($employeeId);
        
        // Map thông tin thêm vào mỗi khóa học
        foreach ($assignedSubjects as &$subject) {
            $subject['Name'] = $subject['Title'] ?? '';  // Use Title field for Name
            $subject['is_completed'] = !empty($subject['BestScore']);
            $subject['has_certificate'] = $this->employeeModel->hasCertificate($employeeId, $subject['ID']);
        }
        unset($subject); // Break reference
        
        // Lấy danh sách khóa học đã hoàn thành
        $completedSubjects = $this->employeeModel->getCompletedSubjects($employeeId);
        
        // Lấy danh sách chứng chỉ
        $certificates = $this->employeeModel->getCertificates($employeeId);
        
        // Tính % hoàn thành
        $totalAssigned = count($assignedSubjects);
        $totalCompleted = count($completedSubjects);
        $completionRate = $totalAssigned > 0 ? ($totalCompleted / $totalAssigned) * 100 : 0;
        
        $this->render('dashboard/index', [
            'employee' => $employee,
            'assignedSubjects' => $assignedSubjects,
            'completedSubjects' => $completedSubjects,
            'certificates' => $certificates,
            'completionRate' => $completionRate,
            'baseUrl' => dirname($_SERVER['PHP_SELF'])
        ]);
    }
}
