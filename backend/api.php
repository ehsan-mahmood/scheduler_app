<?php
/**
 * Driving School Scheduler - API
 * Supports both JSON file storage and PostgreSQL database
 */

// Load configuration
require_once __DIR__ . '/config.php';

// Load database functions if in database mode
if (STORAGE_MODE === 'database') {
    require_once __DIR__ . '/db.php';
}

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// ============================================
// STORAGE LAYER (JSON or Database)
// ============================================

$dataDir = __DIR__ . '/data';
if (!is_dir($dataDir)) {
    mkdir($dataDir, 0777, true);
}

function generateId() {
    if (STORAGE_MODE === 'database') {
        require_once __DIR__ . '/db.php';
        return generateUuid();
    }
    return uniqid('', true);
}

// Student storage functions
function getStudentByPhoneStorage($phone) {
    if (STORAGE_MODE === 'database') {
        return getStudentByPhoneDb($phone);
    }
    $students = getDataFile('students');
    foreach ($students as $student) {
        if ($student['phone'] === $phone) {
            return $student;
        }
    }
    return null;
}

function saveStudentStorage($student) {
    if (STORAGE_MODE === 'database') {
        if (isset($student['id']) && $student['id']) {
            // Only pass fields that should be updated (exclude id, created_at, etc.)
            $updateData = [];
            $allowedFields = ['phone', 'name', 'otp_verified', 'otp_code', 'otp_expires_at'];
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $student)) {
                    $value = $student[$field];
                    // Convert otp_verified to proper boolean - handle all edge cases
                    if ($field === 'otp_verified') {
                        if ($value === true || $value === 'true' || $value === 't' || $value === '1' || $value === 1) {
                            $updateData[$field] = true;
                        } else {
                            $updateData[$field] = false; // Everything else (false, '', null, 'false', etc.) becomes false
                        }
                    } else {
                        $updateData[$field] = $value;
                    }
                }
            }
            if (!empty($updateData)) {
                updateStudentDb($student['id'], $updateData);
            }
            return $student['id'];
        } else {
            return createStudentDb($student['phone'], $student['name'] ?? '');
        }
    }
    $students = getDataFile('students');
    $students[$student['id']] = $student;
    saveDataFile('students', $students);
    return $student['id'];
}

function getDataFile($file) {
    global $dataDir;
    $path = $dataDir . '/' . $file . '.json';
    if (!file_exists($path)) {
        return [];
    }
    $content = file_get_contents($path);
    return json_decode($content, true) ?: [];
}

function saveDataFile($file, $data) {
    global $dataDir;
    $path = $dataDir . '/' . $file . '.json';
    file_put_contents($path, json_encode($data, JSON_PRETTY_PRINT));
}

// Lesson storage functions
function createLessonRecord($data) {
    if (STORAGE_MODE === 'database') {
        return createLessonDb($data);
    }
    $lessons = getDataFile('lessons');
    $lessonId = generateId();
    $lessons[$lessonId] = array_merge(['id' => $lessonId, 'created_at' => date('Y-m-d H:i:s')], $data);
    saveDataFile('lessons', $lessons);
    return $lessonId;
}

function getAllLessonsRecord() {
    if (STORAGE_MODE === 'database') {
        return getAllLessonsDb();
    }
    return array_values(getDataFile('lessons'));
}

// Deposit storage functions
function createDepositRecord($data) {
    if (STORAGE_MODE === 'database') {
        return createDepositDb($data['lesson_id'], $data['payid_reference']);
    }
    $deposits = getDataFile('deposits');
    $depositId = generateId();
    $deposits[$depositId] = array_merge(['id' => $depositId, 'created_at' => date('Y-m-d H:i:s')], $data);
    saveDataFile('deposits', $deposits);
    return $depositId;
}

function getDepositRecord($id) {
    if (STORAGE_MODE === 'database') {
        return getDepositDb($id);
    }
    $deposits = getDataFile('deposits');
    return $deposits[$id] ?? null;
}

function updateDepositRecord($id, $data) {
    if (STORAGE_MODE === 'database') {
        updateDepositDb($id, $data);
        return;
    }
    $deposits = getDataFile('deposits');
    if (isset($deposits[$id])) {
        $deposits[$id] = array_merge($deposits[$id], $data);
        saveDataFile('deposits', $deposits);
    }
}

function getAllDepositsRecord() {
    if (STORAGE_MODE === 'database') {
        return getAllDepositsDb();
    }
    return array_values(getDataFile('deposits'));
}

// Instructor storage functions
function createInstructorRecord($name, $phone, $maxHours = 40) {
    if (STORAGE_MODE === 'database') {
        return createInstructorDb($name, $phone, $maxHours);
    }
    $instructors = getDataFile('instructors');
    $instructorId = generateId();
    $instructors[$instructorId] = [
        'id' => $instructorId,
        'name' => $name,
        'phone' => $phone,
        'max_hours_per_week' => $maxHours,
        'is_active' => true,
        'created_at' => date('Y-m-d H:i:s')
    ];
    saveDataFile('instructors', $instructors);
    return $instructorId;
}

// ============================================
// RESPONSE FUNCTIONS
// ============================================

function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit();
}

function sendError($message, $statusCode = 400) {
    sendResponse([
        'success' => false,
        'message' => $message,
        'messageId' => generateId(),
        'receivingId' => $_SERVER['REQUEST_TIME_FLOAT'] ?? time()
    ], $statusCode);
}

// ============================================
// ROUTING
// ============================================

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$input = json_decode(file_get_contents('php://input'), true) ?? [];

$route = trim($path, '/');
$route = explode('?', $route)[0];

// ============================================
// STUDENT ENDPOINTS
// ============================================

if ($route === 'register' && $method === 'POST') {
    $phone = $input['phone'] ?? '';
    if (empty($phone)) {
        sendError('Phone number is required');
    }
    
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $student = getStudentByPhoneStorage($phone);
    
    if (!$student) {
        $studentId = generateId();
        $student = [
            'id' => $studentId,
            'phone' => $phone,
            'name' => '',
            'otp_verified' => false,
            'created_at' => date('Y-m-d H:i:s')
        ];
        $studentId = saveStudentStorage($student);
        $student['id'] = $studentId;
    }
    
    $student['otp_code'] = $otp;
    $student['otp_expires_at'] = date('Y-m-d H:i:s', strtotime('+10 minutes'));
    saveStudentStorage($student);
    
    // OTP is only displayed, not sent via SMS
    sendResponse([
        'success' => true,
        'message' => 'OTP displayed (not sent via SMS)',
        'messageId' => generateId(),
        'receivingId' => $_SERVER['REQUEST_TIME_FLOAT'] ?? time(),
        'otp' => $otp,
        'student_id' => $student['id']
    ]);
}

if ($route === 'verify-otp' && $method === 'POST') {
    $phone = $input['phone'] ?? '';
    $otp = $input['otp'] ?? '';
    
    if (empty($phone) || empty($otp)) {
        sendError('Phone and OTP are required');
    }
    
    $student = getStudentByPhoneStorage($phone);
    if (!$student) {
        sendError('Student not found');
    }
    
    // Handle otp_expires_at from database (could be string or timestamp)
    $otpExpires = null;
    if (isset($student['otp_expires_at']) && $student['otp_expires_at']) {
        if (is_string($student['otp_expires_at'])) {
            $otpExpires = strtotime($student['otp_expires_at']);
        } else {
            $otpExpires = time() + 1; // Assume valid if not string
        }
    }
    
    if (($student['otp_code'] ?? '') === $otp && ($otpExpires === null || $otpExpires > time())) {
        $student['otp_verified'] = true;
        $student['otp_code'] = null;
        $student['otp_expires_at'] = null;
        saveStudentStorage($student);
        
        sendResponse([
            'success' => true,
            'message' => 'OTP verified successfully',
            'messageId' => generateId(),
            'receivingId' => $_SERVER['REQUEST_TIME_FLOAT'] ?? time(),
            'student_id' => $student['id']
        ]);
    } else {
        sendError('Invalid or expired OTP');
    }
}

if ($route === 'book-lesson' && $method === 'POST') {
    $phone = $input['phone'] ?? '';
    $lessonType = $input['lesson_type'] ?? '';
    $instructor = $input['instructor'] ?? '';
    $date = $input['date'] ?? '';
    $time = $input['time'] ?? '';
    
    if (empty($phone) || empty($lessonType) || empty($instructor) || empty($date) || empty($time)) {
        sendError('All fields are required');
    }
    
    $student = getStudentByPhoneStorage($phone);
    if (!$student || !($student['otp_verified'] ?? false)) {
        sendError('Student not verified. Please verify OTP first.');
    }
    
    $lessonId = createLessonRecord([
        'student_id' => $student['id'],
        'lesson_type_id' => $lessonType,
        'instructor_id' => $instructor,
        'scheduled_at' => $date . ' ' . $time,
        'status' => 'pending_deposit',
        'deposit_paid' => false
    ]);
    
    sendResponse([
        'success' => true,
        'message' => 'Lesson booked successfully',
        'messageId' => generateId(),
        'receivingId' => $_SERVER['REQUEST_TIME_FLOAT'] ?? time(),
        'lesson_id' => $lessonId
    ]);
}

if ($route === 'submit-deposit' && $method === 'POST') {
    $lessonId = $input['lesson_id'] ?? '';
    $payidReference = $input['payid_reference'] ?? '';
    
    if (empty($lessonId) || empty($payidReference)) {
        sendError('Lesson ID and PayID reference are required');
    }
    
    $depositId = createDepositRecord([
        'lesson_id' => $lessonId,
        'payid_reference' => $payidReference,
        'status' => 'pending'
    ]);
    
    // Payment info is only stored/displayed, not processed
    sendResponse([
        'success' => true,
        'message' => 'Deposit reference stored (not processed)',
        'messageId' => generateId(),
        'receivingId' => $_SERVER['REQUEST_TIME_FLOAT'] ?? time(),
        'deposit_id' => $depositId,
        'payid_reference' => $payidReference
    ]);
}

// ============================================
// ADMIN ENDPOINTS
// ============================================

if ($route === 'verify-deposit' && $method === 'POST') {
    $depositId = $input['deposit_id'] ?? '';
    $status = $input['status'] ?? '';
    
    if (empty($depositId) || empty($status)) {
        sendError('Deposit ID and status are required');
    }
    
    $deposit = getDepositRecord($depositId);
    if (!$deposit) {
        sendError('Deposit not found');
    }
    
    updateDepositRecord($depositId, [
        'status' => $status,
        'verified_at' => date('Y-m-d H:i:s')
    ]);
    
    sendResponse([
        'success' => true,
        'message' => 'Deposit status updated',
        'messageId' => generateId(),
        'receivingId' => $_SERVER['REQUEST_TIME_FLOAT'] ?? time()
    ]);
}

if ($route === 'manage-instructor' && $method === 'POST') {
    $name = $input['name'] ?? '';
    $phone = $input['phone'] ?? '';
    $maxHours = $input['max_hours'] ?? 40;
    
    if (empty($name) || empty($phone)) {
        sendError('Name and phone are required');
    }
    
    $instructorId = createInstructorRecord($name, $phone, $maxHours);
    
    sendResponse([
        'success' => true,
        'message' => 'Instructor added successfully',
        'messageId' => generateId(),
        'receivingId' => $_SERVER['REQUEST_TIME_FLOAT'] ?? time(),
        'instructor_id' => $instructorId
    ]);
}

if ($route === 'dashboard' && $method === 'GET') {
    $lessons = getAllLessonsRecord();
    $deposits = getAllDepositsRecord();
    
    $pendingDeposits = array_filter($deposits, function($d) {
        return ($d['status'] ?? '') === 'pending';
    });
    
    sendResponse([
        'success' => true,
        'messageId' => generateId(),
        'receivingId' => $_SERVER['REQUEST_TIME_FLOAT'] ?? time(),
        'data' => [
            'total_lessons' => count($lessons),
            'pending_deposits' => count($pendingDeposits),
            'confirmed_lessons' => count(array_filter($lessons, function($l) {
                return ($l['status'] ?? '') === 'confirmed';
            }))
        ]
    ]);
}

// Default 404
sendError('Endpoint not found', 404);
