<?php
/**
 * Notification Helper
 * Helper functions for emitting notification events with proper data
 */

require_once __DIR__ . '/NotificationEvent.php';
require_once __DIR__ . '/NotificationDispatcher.php';
require_once __DIR__ . '/../db.php';

/**
 * Emit booking created event
 * @param string $lessonId Lesson ID
 * @param string|null $businessId Business ID
 */
function emitBookingCreatedEvent($lessonId, $businessId = null) {
    try {
        $db = getDbConnection();
        
        // Fetch lesson with related data
        $stmt = $db->prepare("
            SELECT 
                l.*,
                s.id as student_id, s.phone as student_phone, s.name as student_name, s.email as student_email,
                i.id as instructor_id, i.phone as instructor_phone, i.name as instructor_name, i.email as instructor_email,
                lt.id as lesson_type_id, lt.name as lesson_type_name, lt.price as lesson_type_price
            FROM lessons l
            LEFT JOIN students s ON l.student_id = s.id
            LEFT JOIN instructors i ON l.instructor_id = i.id
            LEFT JOIN lesson_types lt ON l.lesson_type_id = lt.id
            WHERE l.id = ?
            LIMIT 1
        ");
        $stmt->execute([$lessonId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return; // Lesson not found, skip notification
        }
        
        // Build event payload
        $payload = [
            'lesson' => [
                'id' => $data['id'],
                'scheduled_at' => $data['scheduled_at'],
                'status' => $data['status']
            ],
            'student' => [
                'id' => $data['student_id'],
                'phone' => $data['student_phone'],
                'name' => $data['student_name'],
                'email' => $data['student_email']
            ],
            'instructor' => [
                'id' => $data['instructor_id'],
                'phone' => $data['instructor_phone'],
                'name' => $data['instructor_name'],
                'email' => $data['instructor_email']
            ],
            'lesson_type' => [
                'id' => $data['lesson_type_id'],
                'name' => $data['lesson_type_name'],
                'price' => $data['lesson_type_price']
            ]
        ];
        
        $event = new NotificationEvent(NotificationEvent::BOOKING_CREATED, $payload, $businessId);
        NotificationDispatcher::emit($event);
    } catch (Exception $e) {
        // Log but don't throw - notifications must not block operations
        error_log("Failed to emit booking created event: " . $e->getMessage());
    }
}

/**
 * Emit deposit confirmed event
 * @param string $lessonId Lesson ID
 * @param string|null $businessId Business ID
 */
function emitDepositConfirmedEvent($lessonId, $businessId = null) {
    try {
        $db = getDbConnection();
        
        // Fetch lesson with related data
        $stmt = $db->prepare("
            SELECT 
                l.*,
                s.id as student_id, s.phone as student_phone, s.name as student_name, s.email as student_email,
                i.id as instructor_id, i.phone as instructor_phone, i.name as instructor_name, i.email as instructor_email,
                lt.id as lesson_type_id, lt.name as lesson_type_name, lt.price as lesson_type_price
            FROM lessons l
            LEFT JOIN students s ON l.student_id = s.id
            LEFT JOIN instructors i ON l.instructor_id = i.id
            LEFT JOIN lesson_types lt ON l.lesson_type_id = lt.id
            WHERE l.id = ?
            LIMIT 1
        ");
        $stmt->execute([$lessonId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return; // Lesson not found, skip notification
        }
        
        // Build event payload
        $payload = [
            'lesson' => [
                'id' => $data['id'],
                'scheduled_at' => $data['scheduled_at'],
                'status' => $data['status']
            ],
            'student' => [
                'id' => $data['student_id'],
                'phone' => $data['student_phone'],
                'name' => $data['student_name'],
                'email' => $data['student_email']
            ],
            'instructor' => [
                'id' => $data['instructor_id'],
                'phone' => $data['instructor_phone'],
                'name' => $data['instructor_name'],
                'email' => $data['instructor_email']
            ],
            'lesson_type' => [
                'id' => $data['lesson_type_id'],
                'name' => $data['lesson_type_name'],
                'price' => $data['lesson_type_price']
            ]
        ];
        
        $event = new NotificationEvent(NotificationEvent::DEPOSIT_CONFIRMED, $payload, $businessId);
        NotificationDispatcher::emit($event);
    } catch (Exception $e) {
        // Log but don't throw - notifications must not block operations
        error_log("Failed to emit deposit confirmed event: " . $e->getMessage());
    }
}

/**
 * Emit deposit failed event
 * @param string $lessonId Lesson ID
 * @param string|null $businessId Business ID
 */
function emitDepositFailedEvent($lessonId, $businessId = null) {
    try {
        $db = getDbConnection();
        
        // Fetch lesson with related data
        $stmt = $db->prepare("
            SELECT 
                l.*,
                s.id as student_id, s.phone as student_phone, s.name as student_name, s.email as student_email,
                i.id as instructor_id, i.phone as instructor_phone, i.name as instructor_name, i.email as instructor_email,
                lt.id as lesson_type_id, lt.name as lesson_type_name, lt.price as lesson_type_price
            FROM lessons l
            LEFT JOIN students s ON l.student_id = s.id
            LEFT JOIN instructors i ON l.instructor_id = i.id
            LEFT JOIN lesson_types lt ON l.lesson_type_id = lt.id
            WHERE l.id = ?
            LIMIT 1
        ");
        $stmt->execute([$lessonId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return; // Lesson not found, skip notification
        }
        
        // Build event payload
        $payload = [
            'lesson' => [
                'id' => $data['id'],
                'scheduled_at' => $data['scheduled_at'],
                'status' => $data['status']
            ],
            'student' => [
                'id' => $data['student_id'],
                'phone' => $data['student_phone'],
                'name' => $data['student_name'],
                'email' => $data['student_email']
            ],
            'instructor' => [
                'id' => $data['instructor_id'],
                'phone' => $data['instructor_phone'],
                'name' => $data['instructor_name'],
                'email' => $data['instructor_email']
            ],
            'lesson_type' => [
                'id' => $data['lesson_type_id'],
                'name' => $data['lesson_type_name'],
                'price' => $data['lesson_type_price']
            ]
        ];
        
        $event = new NotificationEvent(NotificationEvent::DEPOSIT_FAILED, $payload, $businessId);
        NotificationDispatcher::emit($event);
    } catch (Exception $e) {
        // Log but don't throw - notifications must not block operations
        error_log("Failed to emit deposit failed event: " . $e->getMessage());
    }
}

