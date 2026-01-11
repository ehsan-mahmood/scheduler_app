<?php
/**
 * Database connection and helper functions for PostgreSQL
 * Note: Functions are prefixed with 'Db' to avoid conflicts
 */

$dbConnection = null;

function getDbConnection() {
    global $dbConnection;
    
    if ($dbConnection !== null) {
        return $dbConnection;
    }
    
    try {
        $dsn = "pgsql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME;
        $dbConnection = new PDO($dsn, DB_USER, DB_PASS);
        $dbConnection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $dbConnection;
    } catch (PDOException $e) {
        error_log("Database connection error: " . $e->getMessage());
        throw new Exception("Database connection failed");
    }
}

function generateUuid() {
    $conn = getDbConnection();
    $stmt = $conn->query("SELECT uuid_generate_v4()");
    return $stmt->fetchColumn();
}

// Student functions
function getStudentByPhoneDb($phone) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM students WHERE phone = ?");
    $stmt->execute([$phone]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($result) {
        // Ensure otp_verified is a proper boolean
        $result['otp_verified'] = ($result['otp_verified'] === true || $result['otp_verified'] === 't' || $result['otp_verified'] === '1' || $result['otp_verified'] === 1);
    }
    return $result ?: null;
}

function createStudentDb($phone, $name = '') {
    $conn = getDbConnection();
    $id = generateUuid();
    $stmt = $conn->prepare("
        INSERT INTO students (id, phone, name, otp_verified, created_at, updated_at)
        VALUES (?, ?, ?, false, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$id, $phone, $name]);
    return $id;
}

function updateStudentDb($id, $data) {
    $conn = getDbConnection();
    $allowedFields = ['phone', 'name', 'otp_verified', 'otp_code', 'otp_expires_at'];
    $fields = [];
    $values = [];
    foreach ($data as $key => $value) {
        // Only process allowed fields
        if (!in_array($key, $allowedFields)) {
            continue;
        }
        
        // Handle boolean fields - convert to proper boolean FIRST (before any other processing)
        if ($key === 'otp_verified') {
            // Explicitly convert to boolean - handle ALL cases including empty string
            if ($value === true || $value === 'true' || $value === 't' || $value === '1' || $value === 1) {
                $value = true;
            } else {
                // Everything else becomes false (including '', false, null, 'false', '0', etc.)
                $value = false;
            }
        }
        // Convert empty strings to null for nullable TEXT fields (but NOT for boolean which we already handled)
        elseif ($value === '') {
            $value = null;
        }
        
        $fields[] = "$key = ?";
        $values[] = $value;
    }
    if (empty($fields)) {
        return;
    }
    $values[] = $id;
    $sql = "UPDATE students SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    try {
        $stmt = $conn->prepare($sql);
        if ($stmt === false) {
            $errorInfo = $conn->errorInfo();
            throw new Exception("Failed to prepare statement: " . ($errorInfo[2] ?? 'Unknown error'));
        }
        $result = $stmt->execute($values);
        if ($result === false) {
            $errorInfo = $stmt->errorInfo();
            throw new Exception("Failed to execute statement: " . ($errorInfo[2] ?? 'Unknown error'));
        }
    } catch (PDOException $e) {
        // Enhanced error logging
        $errorInfo = [
            'SQL' => $sql,
            'Values' => $values,
            'ValueCount' => count($values),
            'PlaceholderCount' => substr_count($sql, '?'),
            'PDOError' => $e->getMessage(),
            'ErrorCode' => $e->getCode()
        ];
        error_log("UPDATE students error: " . json_encode($errorInfo, JSON_PRETTY_PRINT));
        throw new Exception("Database update failed: " . $e->getMessage() . " (SQL: $sql)");
    } catch (Exception $e) {
        error_log("UPDATE students error: " . $e->getMessage() . " (SQL: $sql)");
        throw $e;
    }
}

// Lesson functions
function createLessonDb($data) {
    $conn = getDbConnection();
    $id = generateUuid();
    $stmt = $conn->prepare("
        INSERT INTO lessons (id, student_id, instructor_id, lesson_type_id, scheduled_at, status, deposit_paid, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, 'pending_deposit', false, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([
        $id,
        $data['student_id'],
        $data['instructor_id'],
        $data['lesson_type_id'],
        $data['scheduled_at']
    ]);
    return $id;
}

function getLessonDb($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM lessons WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function getAllLessonsDb() {
    $conn = getDbConnection();
    $stmt = $conn->query("SELECT * FROM lessons ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Deposit functions
function createDepositDb($lessonId, $payidReference) {
    $conn = getDbConnection();
    $id = generateUuid();
    // Get lesson price for amount (try to get from lesson_type if available)
    try {
        $lessonStmt = $conn->prepare("SELECT lt.price FROM lessons l JOIN lesson_types lt ON l.lesson_type_id = lt.id WHERE l.id = ?");
        $lessonStmt->execute([$lessonId]);
        $amount = $lessonStmt->fetchColumn() ?: 0;
    } catch (Exception $e) {
        $amount = 0; // Default if join fails
    }
    
    $stmt = $conn->prepare("
        INSERT INTO payment_deposits (id, lesson_id, amount, payid_reference, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$id, $lessonId, $amount, $payidReference]);
    return $id;
}

function getDepositDb($id) {
    $conn = getDbConnection();
    $stmt = $conn->prepare("SELECT * FROM payment_deposits WHERE id = ?");
    $stmt->execute([$id]);
    return $stmt->fetch(PDO::FETCH_ASSOC);
}

function updateDepositDb($id, $data) {
    $conn = getDbConnection();
    $allowedFields = ['status', 'verified_at', 'payid_reference'];
    $fields = [];
    $values = [];
    foreach ($data as $key => $value) {
        if (in_array($key, $allowedFields)) {
            $fields[] = "$key = ?";
            $values[] = $value;
        }
    }
    if (empty($fields)) {
        return;
    }
    $values[] = $id;
    $stmt = $conn->prepare("UPDATE payment_deposits SET " . implode(', ', $fields) . ", updated_at = CURRENT_TIMESTAMP WHERE id = ?");
    $stmt->execute($values);
}

function getAllDepositsDb() {
    $conn = getDbConnection();
    $stmt = $conn->query("SELECT * FROM payment_deposits ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Instructor functions
function createInstructorDb($name, $phone, $maxHours = 40) {
    $conn = getDbConnection();
    $id = generateUuid();
    $stmt = $conn->prepare("
        INSERT INTO instructors (id, name, phone, max_hours_per_week, is_active, created_at, updated_at)
        VALUES (?, ?, ?, ?, true, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $stmt->execute([$id, $name, $phone, $maxHours]);
    return $id;
}

function getAllInstructorsDb() {
    $conn = getDbConnection();
    $stmt = $conn->query("SELECT * FROM instructors ORDER BY created_at DESC");
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}
