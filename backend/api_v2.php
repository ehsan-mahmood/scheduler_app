<?php
/**
 * Driving School Scheduler - Multi-Tenant API
 * Path-based business routing: /{business_slug}/api/endpoint
 */

// Load configuration
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/db.php';

// Load notification system
require_once __DIR__ . '/notifications/NotificationHelper.php';
require_once __DIR__ . '/notifications/NotificationTimelineHelper.php';

// CORS Headers
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
// BUSINESS CONTEXT MIDDLEWARE (Path-Based)
// ============================================

/**
 * Extract business from URL path
 * Format: /{business_slug}/api/endpoint
 * Example: /acme-driving/api/lesson-types
 */
function getBusinessContext($path) {
    $db = getDbConnection();
    
    // Parse path: /business_slug/api/...
    $parts = explode('/', trim($path, '/'));
    
    if (count($parts) < 2) {
        sendError('Invalid URL format. Expected: /{business_slug}/api/endpoint', 400);
    }
    
    $businessSlug = $parts[0];
    
    // Get business from database
    $stmt = $db->prepare("
        SELECT id, business_name, subdomain, status, plan, 
               max_instructors, max_students, max_monthly_bookings
        FROM businesses 
        WHERE subdomain = ?
          AND status = 'active'
          AND deleted_at IS NULL
        LIMIT 1
    ");
    $stmt->execute([$businessSlug]);
    $business = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$business) {
        sendError("Business '$businessSlug' not found or inactive", 404);
    }
    
    return $business;
}

/**
 * Extract API route from path
 * /business_slug/api/lesson-types -> api/lesson-types
 */
function getApiRoute($path) {
    $parts = explode('/', trim($path, '/'));
    
    if (count($parts) < 2) {
        return '';
    }
    
    // Remove business slug (first part)
    array_shift($parts);
    
    return implode('/', $parts);
}

// Parse request path
$fullPath = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Set business context from path
$BUSINESS = getBusinessContext($fullPath);
$BUSINESS_ID = $BUSINESS['id'];

// Get the actual API route (without business prefix)
$route = getApiRoute($fullPath);

// Helper function to get business config
function getBusinessConfig($key, $default = null) {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT config_value FROM business_configs 
        WHERE business_id = ? AND config_key = ?
    ");
    $stmt->execute([$BUSINESS_ID, $key]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result ? $result['config_value'] : $default;
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
        'timestamp' => time()
    ], $statusCode);
}

// ============================================
// DATABASE HELPER FUNCTIONS (Business-Scoped)
// ============================================

function getStudentByPhone($phone) {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT * FROM students 
        WHERE business_id = ? AND phone = ?
        LIMIT 1
    ");
    $stmt->execute([$BUSINESS_ID, $phone]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function createStudent($phone, $name = '') {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        INSERT INTO students (business_id, phone, name, otp_verified)
        VALUES (?, ?, ?, false)
        RETURNING id
    ");
    $stmt->execute([$BUSINESS_ID, $phone, $name]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['id'];
}

function updateStudent($studentId, $data) {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    $fields = [];
    $values = [];
    
    foreach ($data as $key => $value) {
        // Handle boolean fields - convert empty strings to proper booleans
        if ($key === 'otp_verified') {
            $value = ($value === true || $value === 'true' || $value === 't' || $value === '1' || $value === 1);
        }
        // Convert empty strings to null for nullable fields
        elseif ($value === '') {
            $value = null;
        }
        
        $fields[] = "$key = ?";
        $values[] = $value;
    }
    
    $values[] = $BUSINESS_ID;
    $values[] = $studentId;
    
    $sql = "UPDATE students SET " . implode(', ', $fields) . " 
            WHERE business_id = ? AND id = ?";
    
    $stmt = $db->prepare($sql);
    $stmt->execute($values);
}

function getInstructors($activeOnly = true) {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    $sql = "SELECT id, name, email, phone, max_hours_per_week, is_active 
            FROM instructors 
            WHERE business_id = ?";
    
    if ($activeOnly) {
        $sql .= " AND is_active = true";
    }
    
    $sql .= " ORDER BY name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$BUSINESS_ID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function getLessonTypes($activeOnly = true) {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    $sql = "SELECT id, name, description, duration_minutes, price, color_code 
            FROM lesson_types 
            WHERE business_id = ?";
    
    if ($activeOnly) {
        $sql .= " AND is_active = true";
    }
    
    $sql .= " ORDER BY display_order, name";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([$BUSINESS_ID]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check instructor availability for a specific time slot
 * Uses optimized partial index: idx_lessons_business_active
 */
function checkInstructorAvailability($instructorId, $scheduledAt) {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT EXISTS(
            SELECT 1 FROM lessons 
            WHERE business_id = ?
              AND instructor_id = ?
              AND scheduled_at = ?
              AND status IN ('confirmed', 'in_progress', 'pending_deposit')
        ) as is_booked
    ");
    $stmt->execute([$BUSINESS_ID, $instructorId, $scheduledAt]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return !$result['is_booked']; // Return true if available
}

/**
 * Get available time slots for instructor(s)
 * Optimized: Single query using generate_series
 */
function getAvailableSlots($instructorId, $date, $durationMinutes = 60) {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    // Get business hours from config
    $businessHours = json_decode(getBusinessConfig('business_hours', '{"start":"08:00","end":"18:00"}'), true);
    $startTime = $businessHours['start'] ?? '08:00';
    $endTime = $businessHours['end'] ?? '18:00';
    
    $sql = "
        SELECT 
            slot_time,
            NOT EXISTS (
                SELECT 1 FROM lessons 
                WHERE business_id = ?
                  AND instructor_id = ?
                  AND scheduled_at = slot_time
                  AND status IN ('confirmed', 'in_progress', 'pending_deposit')
            ) AS available
        FROM generate_series(
            (? || ' ' || ?)::timestamp,
            (? || ' ' || ?)::timestamp,
            (? || ' minutes')::interval
        ) AS slot_time
        ORDER BY slot_time
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        $BUSINESS_ID,
        $instructorId,
        $date, $startTime,
        $date, $endTime,
        $durationMinutes
    ]);
    
    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Transform to frontend expected format
    $formattedSlots = [];
    foreach ($slots as $slot) {
        $startTime = new DateTime($slot['slot_time']);
        $endTime = clone $startTime;
        $endTime->modify("+{$durationMinutes} minutes");
        
        $formattedSlots[] = [
            'startISO' => $startTime->format('c'), // ISO 8601 format
            'endISO' => $endTime->format('c'),
            'available' => (bool)$slot['available']
        ];
    }
    
    return $formattedSlots;
}

/**
 * Create a lesson
 */
function createLesson($data) {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        INSERT INTO lessons (
            business_id, lesson_type_id, instructor_id, student_id,
            scheduled_at, status, deposit_paid, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        RETURNING id
    ");
    
    // Ensure deposit_paid is a proper boolean
    $depositPaid = false;
    if (isset($data['deposit_paid']) && $data['deposit_paid'] !== '' && $data['deposit_paid'] !== null) {
        $depositPaid = ($data['deposit_paid'] === true || $data['deposit_paid'] === 'true' || 
                       $data['deposit_paid'] === 't' || $data['deposit_paid'] === '1' || 
                       $data['deposit_paid'] === 1);
    }
    
    // Bind parameters with explicit types
    $stmt->bindValue(1, $BUSINESS_ID);
    $stmt->bindValue(2, $data['lesson_type_id']);
    $stmt->bindValue(3, $data['instructor_id']);
    $stmt->bindValue(4, $data['student_id']);
    $stmt->bindValue(5, $data['scheduled_at']);
    $stmt->bindValue(6, $data['status'] ?? 'pending_deposit');
    $stmt->bindValue(7, $depositPaid, PDO::PARAM_BOOL);
    $stmt->bindValue(8, $data['notes'] ?? null);
    
    $stmt->execute();
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['id'];
}

/**
 * Get student's lessons
 */
function getStudentLessons($studentId, $limit = 10) {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT 
            l.id,
            l.scheduled_at,
            l.status,
            l.notes,
            i.name as instructor_name,
            i.phone as instructor_phone,
            lt.name as lesson_type,
            lt.duration_minutes,
            lt.price,
            pd.status as payment_status,
            pd.payid_reference
        FROM lessons l
        JOIN instructors i ON l.instructor_id = i.id
        JOIN lesson_types lt ON l.lesson_type_id = lt.id
        LEFT JOIN payment_deposits pd ON pd.lesson_id = l.id
        WHERE l.business_id = ? 
          AND l.student_id = ?
        ORDER BY l.scheduled_at DESC
        LIMIT ?
    ");
    
    $stmt->execute([$BUSINESS_ID, $studentId, $limit]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Create payment deposit
 */
function createDeposit($lessonId, $payidReference, $amount) {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        INSERT INTO payment_deposits (
            business_id, lesson_id, amount, payid_reference, 
            status, currency
        ) VALUES (?, ?, ?, ?, 'pending', ?)
        RETURNING id
    ");
    
    $currency = getBusinessConfig('currency', 'AUD');
    
    $stmt->execute([
        $BUSINESS_ID,
        $lessonId,
        $amount,
        $payidReference,
        $currency
    ]);
    
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    return $result['id'];
}

/**
 * Get instructor schedule
 */
function getInstructorSchedule($instructorId, $startDate, $endDate) {
    global $BUSINESS_ID;
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT 
            l.id,
            l.scheduled_at,
            l.status,
            l.notes,
            s.name as student_name,
            s.phone as student_phone,
            lt.name as lesson_type,
            lt.duration_minutes,
            lt.price,
            pd.status as payment_status
        FROM lessons l
        JOIN students s ON l.student_id = s.id
        JOIN lesson_types lt ON l.lesson_type_id = lt.id
        LEFT JOIN payment_deposits pd ON pd.lesson_id = l.id
        WHERE l.business_id = ?
          AND l.instructor_id = ?
          AND l.scheduled_at >= ?
          AND l.scheduled_at < ?
          AND l.status != 'cancelled'
        ORDER BY l.scheduled_at
    ");
    
    $stmt->execute([$BUSINESS_ID, $instructorId, $startDate, $endDate]);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

/**
 * Check business limits before creating resources
 */
function checkBusinessLimit($limitType) {
    global $BUSINESS_ID, $BUSINESS;
    $db = getDbConnection();
    
    $limits = [
        'instructors' => $BUSINESS['max_instructors'],
        'students' => $BUSINESS['max_students'],
        'monthly_bookings' => $BUSINESS['max_monthly_bookings']
    ];
    
    if (!isset($limits[$limitType])) {
        return true;
    }
    
    $maxLimit = $limits[$limitType];
    
    if ($limitType === 'instructors') {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM instructors 
            WHERE business_id = ? AND is_active = true
        ");
    } elseif ($limitType === 'students') {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM students 
            WHERE business_id = ?
        ");
    } elseif ($limitType === 'monthly_bookings') {
        $stmt = $db->prepare("
            SELECT COUNT(*) as count FROM lessons 
            WHERE business_id = ? 
              AND created_at >= date_trunc('month', CURRENT_DATE)
        ");
    }
    
    $stmt->execute([$BUSINESS_ID]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    return $result['count'] < $maxLimit;
}

// ============================================
// ROUTING
// ============================================

$method = $_SERVER['REQUEST_METHOD'];
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// ============================================
// PUBLIC ENDPOINTS (Student-facing)
// ============================================

/**
 * GET /{business}/api/config
 * Get public business configuration
 */
if ($route === 'api/config' && $method === 'GET') {
    sendResponse([
        'success' => true,
        'business' => [
            'name' => $BUSINESS['business_name'],
            'slug' => $BUSINESS['subdomain']
        ],
        'config' => [
            'booking_buffer_minutes' => (int)getBusinessConfig('booking_buffer_minutes', '30'),
            'cancellation_hours' => (int)getBusinessConfig('cancellation_hours', '24'),
            'deposit_percentage' => (int)getBusinessConfig('deposit_percentage', '50'),
            'timezone' => getBusinessConfig('timezone', 'Australia/Sydney'),
            'currency' => getBusinessConfig('currency', 'AUD'),
            'online_payment_enabled' => getBusinessConfig('online_payment_enabled', 'true') === 'true'
        ]
    ]);
}

/**
 * GET /{business}/api/lesson-types
 * Get available lesson types
 */
if ($route === 'api/lesson-types' && $method === 'GET') {
    $lessonTypes = getLessonTypes(true);
    
    sendResponse([
        'success' => true,
        'lesson_types' => $lessonTypes
    ]);
}

/**
 * GET /{business}/api/instructors
 * Get available instructors
 */
if ($route === 'api/instructors' && $method === 'GET') {
    $instructors = getInstructors(true);
    
    sendResponse([
        'success' => true,
        'instructors' => $instructors
    ]);
}

/**
 * POST /{business}/api/register
 * Register/login student with phone number
 */
if ($route === 'api/register' && $method === 'POST') {
    $phone = $input['phone'] ?? '';
    
    if (empty($phone)) {
        sendError('Phone number is required');
    }
    
    // Validate phone format (basic)
    if (!preg_match('/^\+?[0-9]{10,15}$/', $phone)) {
        sendError('Invalid phone number format');
    }
    
    // Check business limit
    if (!checkBusinessLimit('students')) {
        sendError('Maximum student limit reached. Please contact support.', 403);
    }
    
    $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
    $student = getStudentByPhone($phone);
    
    if (!$student) {
        $studentId = createStudent($phone, '');
    } else {
        $studentId = $student['id'];
    }
    
    // Update OTP
    updateStudent($studentId, [
        'otp_code' => $otp,
        'otp_expires_at' => date('Y-m-d H:i:s', strtotime('+10 minutes'))
    ]);
    
    // In production, send SMS here via SMS provider
    // For now, return OTP in response (development only)
    sendResponse([
        'success' => true,
        'message' => 'OTP generated',
        'student_id' => $studentId,
        'otp' => $otp // REMOVE IN PRODUCTION!
    ]);
}

/**
 * POST /{business}/api/verify-otp
 * Verify OTP and authenticate student
 */
if ($route === 'api/verify-otp' && $method === 'POST') {
    $phone = $input['phone'] ?? '';
    $otp = $input['otp'] ?? '';
    
    if (empty($phone) || empty($otp)) {
        sendError('Phone and OTP are required');
    }
    
    $student = getStudentByPhone($phone);
    if (!$student) {
        sendError('Student not found');
    }
    
    // Check OTP
    $otpExpires = strtotime($student['otp_expires_at']);
    
    if ($student['otp_code'] === $otp && $otpExpires > time()) {
        // OTP valid - mark as verified
        updateStudent($student['id'], [
            'otp_verified' => true,
            'otp_code' => null,
            'otp_expires_at' => null
        ]);
        
        sendResponse([
            'success' => true,
            'message' => 'OTP verified successfully',
            'student_id' => $student['id'],
            'student_name' => $student['name']
        ]);
    } else {
        sendError('Invalid or expired OTP');
    }
}

/**
 * GET /{business}/api/availability
 * Get available time slots for instructor
 */
if ($route === 'api/availability' && $method === 'GET') {
    $db = getDbConnection();
    
    $instructorId = $_GET['instructor_id'] ?? '';
    $date = $_GET['date'] ?? '';
    $lessonTypeId = $_GET['lesson_type_id'] ?? '';
    
    if (empty($instructorId) || empty($date)) {
        sendError('Instructor ID and date are required');
    }
    
    // Get lesson duration
    $duration = 60;
    if ($lessonTypeId) {
        $stmt = $db->prepare("SELECT duration_minutes FROM lesson_types WHERE id = ? AND business_id = ?");
        $stmt->execute([$lessonTypeId, $BUSINESS_ID]);
        $lessonType = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($lessonType) {
            $duration = $lessonType['duration_minutes'];
        }
    }
    
    $slots = getAvailableSlots($instructorId, $date, $duration);
    
    sendResponse([
        'success' => true,
        'date' => $date,
        'instructor_id' => $instructorId,
        'slots' => $slots
    ]);
}

/**
 * POST /{business}/api/book-lesson
 * Book a lesson
 */
if ($route === 'api/book-lesson' && $method === 'POST') {
    $db = getDbConnection();
    
    $phone = $input['phone'] ?? '';
    $lessonTypeId = $input['lesson_type_id'] ?? '';
    $instructorId = $input['instructor_id'] ?? '';
    $scheduledAt = $input['scheduled_at'] ?? '';
    
    if (empty($phone) || empty($lessonTypeId) || empty($instructorId) || empty($scheduledAt)) {
        sendError('All fields are required');
    }
    
    $student = getStudentByPhone($phone);
    if (!$student || !$student['otp_verified']) {
        sendError('Student not verified. Please verify OTP first.');
    }
    
    // Check business limit
    if (!checkBusinessLimit('monthly_bookings')) {
        sendError('Monthly booking limit reached. Please contact support.', 403);
    }
    
    // Check availability
    if (!checkInstructorAvailability($instructorId, $scheduledAt)) {
        sendError('This time slot is no longer available', 409);
    }
    
    // Get lesson type details
    $stmt = $db->prepare("SELECT price FROM lesson_types WHERE id = ? AND business_id = ?");
    $stmt->execute([$lessonTypeId, $BUSINESS_ID]);
    $lessonType = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lessonType) {
        sendError('Invalid lesson type');
    }
    
    // Calculate deposit
    $depositPercentage = (int)getBusinessConfig('deposit_percentage', '50');
    $depositAmount = ($lessonType['price'] * $depositPercentage) / 100;
    
    // Create lesson
    $lessonId = createLesson([
        'lesson_type_id' => $lessonTypeId,
        'instructor_id' => $instructorId,
        'student_id' => $student['id'],
        'scheduled_at' => $scheduledAt,
        'status' => 'pending_deposit',
        'deposit_paid' => false
    ]);
    
    // Emit booking created event (non-blocking)
    emitBookingCreatedEvent($lessonId, $BUSINESS_ID);
    
    sendResponse([
        'success' => true,
        'message' => 'Lesson booked successfully',
        'lesson_id' => $lessonId,
        'deposit_required' => $depositAmount,
        'currency' => getBusinessConfig('currency', 'AUD')
    ]);
}

/**
 * POST /{business}/api/submit-deposit
 * Submit payment deposit reference
 */
if ($route === 'api/submit-deposit' && $method === 'POST') {
    $db = getDbConnection();
    
    $lessonId = $input['lesson_id'] ?? '';
    $payidReference = $input['payid_reference'] ?? '';
    
    if (empty($lessonId) || empty($payidReference)) {
        sendError('Lesson ID and PayID reference are required');
    }
    
    // Verify lesson exists and belongs to this business
    $stmt = $db->prepare("
        SELECT l.*, lt.price 
        FROM lessons l
        JOIN lesson_types lt ON l.lesson_type_id = lt.id
        WHERE l.id = ? AND l.business_id = ?
    ");
    $stmt->execute([$lessonId, $BUSINESS_ID]);
    $lesson = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$lesson) {
        sendError('Lesson not found');
    }
    
    // Calculate deposit
    $depositPercentage = (int)getBusinessConfig('deposit_percentage', '50');
    $depositAmount = ($lesson['price'] * $depositPercentage) / 100;
    
    // Create deposit record
    $depositId = createDeposit($lessonId, $payidReference, $depositAmount);
    
    sendResponse([
        'success' => true,
        'message' => 'Deposit reference submitted. Awaiting verification.',
        'deposit_id' => $depositId,
        'payid_reference' => $payidReference
    ]);
}

/**
 * GET /{business}/api/my-lessons
 * Get student's lessons
 */
if ($route === 'api/my-lessons' && $method === 'GET') {
    $phone = $_GET['phone'] ?? '';
    
    if (empty($phone)) {
        sendError('Phone number is required');
    }
    
    $student = getStudentByPhone($phone);
    if (!$student) {
        sendError('Student not found');
    }
    
    $lessons = getStudentLessons($student['id']);
    
    sendResponse([
        'success' => true,
        'lessons' => $lessons
    ]);
}

// ============================================
// ADMIN/INSTRUCTOR ENDPOINTS
// ============================================

/**
 * GET /{business}/api/admin/schedule
 * Get instructor schedule
 */
if ($route === 'api/admin/schedule' && $method === 'GET') {
    $instructorId = $_GET['instructor_id'] ?? '';
    $startDate = $_GET['start_date'] ?? date('Y-m-d');
    $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+7 days'));
    
    if (empty($instructorId)) {
        sendError('Instructor ID is required');
    }
    
    $schedule = getInstructorSchedule($instructorId, $startDate, $endDate);
    
    sendResponse([
        'success' => true,
        'instructor_id' => $instructorId,
        'start_date' => $startDate,
        'end_date' => $endDate,
        'lessons' => $schedule
    ]);
}

/**
 * GET /{business}/api/admin/pending-deposits
 * Get pending payment deposits
 */
if ($route === 'api/admin/pending-deposits' && $method === 'GET') {
    $db = getDbConnection();
    
    $stmt = $db->prepare("
        SELECT 
            pd.id,
            pd.lesson_id,
            pd.amount,
            pd.payid_reference,
            pd.created_at,
            l.scheduled_at,
            s.name as student_name,
            s.phone as student_phone,
            lt.name as lesson_type
        FROM payment_deposits pd
        JOIN lessons l ON pd.lesson_id = l.id
        JOIN students s ON l.student_id = s.id
        JOIN lesson_types lt ON l.lesson_type_id = lt.id
        WHERE pd.business_id = ?
          AND pd.status = 'pending'
        ORDER BY pd.created_at DESC
    ");
    $stmt->execute([$BUSINESS_ID]);
    $deposits = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse([
        'success' => true,
        'pending_deposits' => $deposits
    ]);
}

/**
 * POST /{business}/api/admin/verify-deposit
 * Verify/confirm payment deposit
 */
if ($route === 'api/admin/verify-deposit' && $method === 'POST') {
    $db = getDbConnection();
    
    $depositId = $input['deposit_id'] ?? '';
    $status = $input['status'] ?? ''; // 'confirmed' or 'failed'
    
    if (empty($depositId) || empty($status)) {
        sendError('Deposit ID and status are required');
    }
    
    if (!in_array($status, ['confirmed', 'failed'])) {
        sendError('Invalid status. Must be confirmed or failed');
    }
    
    // Update deposit
    $stmt = $db->prepare("
        UPDATE payment_deposits 
        SET status = ?, verified_at = CURRENT_TIMESTAMP
        WHERE id = ? AND business_id = ?
        RETURNING lesson_id
    ");
    $stmt->execute([$status, $depositId, $BUSINESS_ID]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$result) {
        sendError('Deposit not found');
    }
    
    // Update lesson status if deposit confirmed
    if ($status === 'confirmed') {
        $stmt = $db->prepare("
            UPDATE lessons 
            SET status = 'confirmed', deposit_paid = true
            WHERE id = ? AND business_id = ?
        ");
        $stmt->execute([$result['lesson_id'], $BUSINESS_ID]);
        
        // Emit deposit confirmed event (non-blocking)
        emitDepositConfirmedEvent($result['lesson_id'], $BUSINESS_ID);
    } else if ($status === 'failed') {
        // Emit deposit failed event (non-blocking)
        emitDepositFailedEvent($result['lesson_id'], $BUSINESS_ID);
    }
    
    sendResponse([
        'success' => true,
        'message' => "Deposit $status successfully"
    ]);
}

/**
 * GET /{business}/api/admin/dashboard
 * Get admin dashboard statistics
 */
if ($route === 'api/admin/dashboard' && $method === 'GET') {
    $db = getDbConnection();
    
    // Today's lessons
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM lessons 
        WHERE business_id = ? 
          AND DATE(scheduled_at) = CURRENT_DATE
          AND status != 'cancelled'
    ");
    $stmt->execute([$BUSINESS_ID]);
    $todayLessons = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Pending deposits
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM payment_deposits 
        WHERE business_id = ? AND status = 'pending'
    ");
    $stmt->execute([$BUSINESS_ID]);
    $pendingDeposits = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    // Monthly bookings
    $stmt = $db->prepare("
        SELECT COUNT(*) as count FROM lessons 
        WHERE business_id = ? 
          AND created_at >= date_trunc('month', CURRENT_DATE)
    ");
    $stmt->execute([$BUSINESS_ID]);
    $monthlyBookings = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    sendResponse([
        'success' => true,
        'dashboard' => [
            'today_lessons' => (int)$todayLessons,
            'pending_deposits' => (int)$pendingDeposits,
            'monthly_bookings' => (int)$monthlyBookings,
            'business_name' => $BUSINESS['business_name']
        ]
    ]);
}

// ============================================
// PORTAL ENDPOINTS (Authentication & Management)
// ============================================

/**
 * POST /{business}/api/portal/login
 * Portal login with phone + password (for instructors and students)
 */
if ($route === 'api/portal/login' && $method === 'POST') {
    $db = getDbConnection();
    
    $phone = $input['phone'] ?? '';
    $password = $input['password'] ?? '';
    
    if (empty($phone) || empty($password)) {
        sendError('Phone and password are required');
    }
    
    // Check instructors table first
    $stmt = $db->prepare("
        SELECT id, name, email, phone, password_hash, 'instructor' as role, is_active
        FROM instructors 
        WHERE business_id = ? AND phone = ? AND is_active = true
        LIMIT 1
    ");
    $stmt->execute([$BUSINESS_ID, $phone]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // If not found in instructors, check students table
    if (!$user) {
        // Check if students table has password_hash column
        // Try to select it, will fail gracefully if column doesn't exist
        try {
            $stmt = $db->prepare("
                SELECT id, name, phone, password_hash, 'student' as role, 
                       COALESCE(otp_verified, false) as is_active
                FROM students 
                WHERE business_id = ? AND phone = ?
                LIMIT 1
            ");
            $stmt->execute([$BUSINESS_ID, $phone]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            
            // If student found but no password_hash, they need to set password first
            if ($user && (!isset($user['password_hash']) || empty($user['password_hash']))) {
                sendError('Student account found but no password set. Please register first or use OTP booking.');
            }
        } catch (PDOException $e) {
            // password_hash column might not exist in students table
            // In this case, students can't login with password (use OTP instead)
            sendError('Invalid credentials');
        }
    }
    
    if (!$user) {
        sendError('Invalid credentials');
    }
    
    // Verify password
    if (!isset($user['password_hash']) || empty($user['password_hash']) || !password_verify($password, $user['password_hash'])) {
        sendError('Invalid credentials');
    }
    
    // Remove password hash from response
    unset($user['password_hash']);
    
    sendResponse([
        'success' => true,
        'message' => 'Login successful',
        'user' => $user
    ]);
}

/**
 * POST /{business}/api/portal/register
 * Register a new portal user (instructor/admin)
 * Note: In production, this should be admin-only or removed
 */
if ($route === 'api/portal/register' && $method === 'POST') {
    $db = getDbConnection();
    
    $name = $input['name'] ?? '';
    $phone = $input['phone'] ?? '';
    $email = $input['email'] ?? '';
    $password = $input['password'] ?? '';
    $role = $input['role'] ?? 'instructor'; // Default to instructor
    
    if (empty($name) || empty($phone) || empty($password)) {
        sendError('Name, phone, and password are required');
    }
    
    if (!in_array($role, ['student', 'instructor'])) {
        sendError('Invalid role. Must be student or instructor');
    }
    
    // Check if phone already exists in either table
    if ($role === 'instructor') {
        $stmt = $db->prepare("
            SELECT id FROM instructors 
            WHERE business_id = ? AND phone = ?
            LIMIT 1
        ");
        $stmt->execute([$BUSINESS_ID, $phone]);
        if ($stmt->fetch()) {
            sendError('Phone number already registered as instructor');
        }
    } else {
        // Check students table
        $stmt = $db->prepare("
            SELECT id FROM students 
            WHERE business_id = ? AND phone = ?
            LIMIT 1
        ");
        $stmt->execute([$BUSINESS_ID, $phone]);
        if ($stmt->fetch()) {
            sendError('Phone number already registered as student');
        }
    }
    
    // Hash password
    $passwordHash = password_hash($password, PASSWORD_DEFAULT);
    
    if ($role === 'student') {
        // Create student with password (for portal access)
        // Note: Students table might need password_hash column added
        // For now, we'll create the student and store password_hash if column exists
        $stmt = $db->prepare("
            INSERT INTO students (
                business_id, name, phone, otp_verified
            ) VALUES (?, ?, ?, true)
            RETURNING id
        ");
        $stmt->execute([
            $BUSINESS_ID,
            $name,
            $phone
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $result['id'];
        
        // Try to update with password_hash if column exists (optional)
        // This will fail silently if column doesn't exist
        try {
            $stmt = $db->prepare("
                UPDATE students 
                SET password_hash = ? 
                WHERE id = ? AND business_id = ?
            ");
            $stmt->execute([$passwordHash, $userId, $BUSINESS_ID]);
        } catch (PDOException $e) {
            // password_hash column might not exist - that's okay
            // Students can still use OTP for booking
        }
    } else {
        // Create instructor (portal user)
        $stmt = $db->prepare("
            INSERT INTO instructors (
                business_id, name, email, phone, password_hash, 
                max_hours_per_week, is_active
            ) VALUES (?, ?, ?, ?, ?, 40, true)
            RETURNING id
        ");
        $stmt->execute([
            $BUSINESS_ID,
            $name,
            $email ?: null,
            $phone,
            $passwordHash
        ]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $userId = $result['id'];
    }
    
    sendResponse([
        'success' => true,
        'message' => 'Registration successful',
        'user' => [
            'id' => $userId,
            'name' => $name,
            'phone' => $phone,
            'email' => $email,
            'role' => $role
        ]
    ]);
}

/**
 * GET /{business}/api/portal/students
 * Get all students for this business (instructor/admin view)
 */
if ($route === 'api/portal/students' && $method === 'GET') {
    $db = getDbConnection();
    
    $search = $_GET['search'] ?? '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $sql = "
        SELECT 
            s.id,
            s.name,
            s.phone,
            s.otp_verified,
            s.created_at,
            COUNT(l.id) as total_lessons,
            COUNT(CASE WHEN l.status = 'completed' THEN 1 END) as completed_lessons,
            MAX(l.scheduled_at) as last_lesson_date
        FROM students s
        LEFT JOIN lessons l ON l.student_id = s.id AND l.business_id = ?
        WHERE s.business_id = ?
    ";
    
    $params = [$BUSINESS_ID, $BUSINESS_ID];
    
    if (!empty($search)) {
        $sql .= " AND (s.name ILIKE ? OR s.phone ILIKE ?)";
        $searchParam = '%' . $search . '%';
        $params[] = $searchParam;
        $params[] = $searchParam;
    }
    
    $sql .= "
        GROUP BY s.id, s.name, s.phone, s.otp_verified, s.created_at
        ORDER BY s.created_at DESC
        LIMIT ? OFFSET ?
    ";
    
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get total count
    $countSql = "SELECT COUNT(*) as count FROM students WHERE business_id = ?";
    $countParams = [$BUSINESS_ID];
    
    if (!empty($search)) {
        $countSql .= " AND (name ILIKE ? OR phone ILIKE ?)";
        $countParams[] = $searchParam;
        $countParams[] = $searchParam;
    }
    
    $stmt = $db->prepare($countSql);
    $stmt->execute($countParams);
    $totalCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    sendResponse([
        'success' => true,
        'students' => $students,
        'total' => (int)$totalCount,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

/**
 * GET /{business}/api/portal/bookings
 * Get all bookings/lessons for this business (admin view)
 */
if ($route === 'api/portal/bookings' && $method === 'GET') {
    $db = getDbConnection();
    
    $status = $_GET['status'] ?? '';
    $instructorId = $_GET['instructor_id'] ?? '';
    $studentId = $_GET['student_id'] ?? '';
    $startDate = $_GET['start_date'] ?? '';
    $endDate = $_GET['end_date'] ?? '';
    $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 100;
    $offset = isset($_GET['offset']) ? (int)$_GET['offset'] : 0;
    
    $sql = "
        SELECT 
            l.id,
            l.scheduled_at,
            l.status,
            l.notes,
            l.deposit_paid,
            s.id as student_id,
            s.name as student_name,
            s.phone as student_phone,
            i.id as instructor_id,
            i.name as instructor_name,
            i.phone as instructor_phone,
            lt.name as lesson_type,
            lt.duration_minutes,
            lt.price,
            pd.id as deposit_id,
            pd.status as payment_status,
            pd.payid_reference,
            l.created_at
        FROM lessons l
        JOIN students s ON l.student_id = s.id
        JOIN instructors i ON l.instructor_id = i.id
        JOIN lesson_types lt ON l.lesson_type_id = lt.id
        LEFT JOIN payment_deposits pd ON pd.lesson_id = l.id
        WHERE l.business_id = ?
    ";
    
    $params = [$BUSINESS_ID];
    
    if (!empty($status)) {
        $sql .= " AND l.status = ?";
        $params[] = $status;
    }
    
    if (!empty($instructorId)) {
        $sql .= " AND l.instructor_id = ?";
        $params[] = $instructorId;
    }
    
    if (!empty($studentId)) {
        $sql .= " AND l.student_id = ?";
        $params[] = $studentId;
    }
    
    if (!empty($startDate)) {
        $sql .= " AND l.scheduled_at >= ?";
        $params[] = $startDate;
    }
    
    if (!empty($endDate)) {
        $sql .= " AND l.scheduled_at <= ?";
        $params[] = $endDate . ' 23:59:59';
    }
    
    $sql .= " ORDER BY l.scheduled_at DESC LIMIT ? OFFSET ?";
    $params[] = $limit;
    $params[] = $offset;
    
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $bookings = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    sendResponse([
        'success' => true,
        'bookings' => $bookings,
        'limit' => $limit,
        'offset' => $offset
    ]);
}

/**
 * POST /{business}/api/booking/cancel
 * Cancel a booking/lesson
 */
if ($route === 'api/booking/cancel' && $method === 'POST') {
    $db = getDbConnection();
    
    $bookingId = $input['booking_id'] ?? '';
    $reason = $input['reason'] ?? '';
    
    if (empty($bookingId)) {
        sendError('Booking ID is required');
    }
    
    // Verify booking exists and belongs to this business
    $stmt = $db->prepare("
        SELECT id, status, scheduled_at 
        FROM lessons 
        WHERE id = ? AND business_id = ?
        LIMIT 1
    ");
    $stmt->execute([$bookingId, $BUSINESS_ID]);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        sendError('Booking not found');
    }
    
    if ($booking['status'] === 'cancelled') {
        sendError('Booking is already cancelled');
    }
    
    if ($booking['status'] === 'completed') {
        sendError('Cannot cancel a completed lesson');
    }
    
    // Check cancellation policy (e.g., 24 hours notice)
    $cancellationHours = (int)getBusinessConfig('cancellation_hours', '24');
    $scheduledTime = strtotime($booking['scheduled_at']);
    $hoursUntilLesson = ($scheduledTime - time()) / 3600;
    
    $cancellationNote = '';
    if ($hoursUntilLesson < $cancellationHours) {
        $cancellationNote = " (Late cancellation - less than {$cancellationHours}h notice)";
    }
    
    // Update lesson status
    $stmt = $db->prepare("
        UPDATE lessons 
        SET status = 'cancelled',
            notes = CONCAT(COALESCE(notes, ''), ?, ?)
        WHERE id = ? AND business_id = ?
    ");
    $stmt->execute([
        "\n[Cancelled: " . date('Y-m-d H:i:s') . "]",
        $cancellationNote . ($reason ? " Reason: $reason" : ''),
        $bookingId,
        $BUSINESS_ID
    ]);
    
    sendResponse([
        'success' => true,
        'message' => 'Booking cancelled successfully' . $cancellationNote
    ]);
}

// ============================================
// NOTIFICATION TIMELINE ENDPOINT
// ============================================

/**
 * GET /{business}/api/booking/{bookingId}/notifications
 * Get notification timeline for a booking
 */
if (preg_match('#^api/booking/([^/]+)/notifications$#', $route, $matches) && $method === 'GET') {
    $bookingId = $matches[1];
    
    if (empty($bookingId)) {
        sendError('Booking ID is required');
    }
    
    $timeline = getNotificationTimeline($bookingId, $BUSINESS_ID);
    
    sendResponse([
        'success' => true,
        'timeline' => $timeline
    ]);
}

// ============================================
// 404 - Route Not Found
// ============================================

sendError('Endpoint not found: ' . $route, 404);