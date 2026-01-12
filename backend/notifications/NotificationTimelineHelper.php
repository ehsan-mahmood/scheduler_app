<?php
/**
 * Notification Timeline Helper
 * Generates notification timeline data for demo purposes (generic terminology)
 */

require_once __DIR__ . '/../db.php';

/**
 * Get notification timeline for a booking
 * @param string $bookingId Booking ID
 * @param string|null $businessId Business ID
 * @return array Array of notification timeline items
 */
function getNotificationTimeline($bookingId, $businessId = null) {
    try {
        $db = getDbConnection();
        
        // Fetch booking with related data
        $stmt = $db->prepare("
            SELECT 
                l.*,
                s.id as customer_id, s.phone as customer_phone, s.name as customer_name, s.email as customer_email,
                i.id as provider_id, i.phone as provider_phone, i.name as provider_name, i.email as provider_email,
                lt.id as service_type_id, lt.name as service_type_name, lt.price as service_type_price
            FROM lessons l
            LEFT JOIN students s ON l.student_id = s.id
            LEFT JOIN instructors i ON l.instructor_id = i.id
            LEFT JOIN lesson_types lt ON l.lesson_type_id = lt.id
            WHERE l.id = ?
            LIMIT 1
        ");
        $stmt->execute([$bookingId]);
        $data = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$data) {
            return [];
        }
        
        $timeline = [];
        $now = time();
        
        // Format scheduled time
        $scheduledAt = $data['scheduled_at'] ?? 'N/A';
        $serviceName = $data['service_type_name'] ?? 'appointment';
        $customerName = $data['customer_name'] ?? 'Customer';
        $providerName = $data['provider_name'] ?? 'Provider';
        
        // Notification 1: SMS to customer
        if (!empty($data['customer_phone'])) {
            $timeline[] = [
                'id' => uniqid('notif_', true),
                'type' => 'sms',
                'channel' => 'SMS',
                'recipient' => $customerName,
                'recipient_contact' => $data['customer_phone'],
                'message' => "Your {$serviceName} is booked for {$scheduledAt}. Please submit your deposit to confirm.",
                'status' => 'sent',
                'timestamp' => date('Y-m-d H:i:s', $now - 2),
                'simulated' => true
            ];
        }
        
        // Notification 2: Email to customer
        if (!empty($data['customer_email'])) {
            $timeline[] = [
                'id' => uniqid('notif_', true),
                'type' => 'email',
                'channel' => 'Email',
                'recipient' => $customerName,
                'recipient_contact' => $data['customer_email'],
                'subject' => "Booking Confirmation - {$serviceName}",
                'message' => "Your {$serviceName} has been booked for {$scheduledAt}. Please submit your deposit to confirm the booking.",
                'status' => 'sent',
                'timestamp' => date('Y-m-d H:i:s', $now - 1),
                'simulated' => true
            ];
        }
        
        // Notification 3: Email to provider
        if (!empty($data['provider_email'])) {
            $timeline[] = [
                'id' => uniqid('notif_', true),
                'type' => 'email',
                'channel' => 'Email',
                'recipient' => $providerName,
                'recipient_contact' => $data['provider_email'],
                'subject' => "New Booking - {$serviceName}",
                'message' => "You have a new booking with {$customerName} scheduled for {$scheduledAt}.",
                'status' => 'sent',
                'timestamp' => date('Y-m-d H:i:s', $now),
                'simulated' => true
            ];
        }
        
        // Sort by timestamp
        usort($timeline, function($a, $b) {
            return strtotime($a['timestamp']) <=> strtotime($b['timestamp']);
        });
        
        return $timeline;
    } catch (Exception $e) {
        error_log("Failed to get notification timeline: " . $e->getMessage());
        return [];
    }
}

