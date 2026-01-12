<?php
/**
 * Notification Event Abstraction
 * Represents a notification event that can be handled by notification channels
 */

class NotificationEvent {
    // Event types
    const BOOKING_CREATED = 'booking_created';
    const DEPOSIT_CONFIRMED = 'deposit_confirmed';
    const DEPOSIT_FAILED = 'deposit_failed';
    const LESSON_RESCHEDULED = 'lesson_rescheduled';
    const LESSON_CANCELLED = 'lesson_cancelled';
    const OTP_GENERATED = 'otp_generated';
    const LESSON_REMINDER = 'lesson_reminder';
    
    public $type;
    public $payload;
    public $businessId;
    public $createdAt;
    
    /**
     * @param string $type Event type (e.g., NotificationEvent::BOOKING_CREATED)
     * @param array $payload Event data (lesson, student, instructor, etc.)
     * @param string|null $businessId Business ID (optional, may be in payload)
     */
    public function __construct($type, $payload = [], $businessId = null) {
        $this->type = $type;
        $this->payload = $payload;
        $this->businessId = $businessId;
        $this->createdAt = date('Y-m-d H:i:s');
    }
    
    /**
     * Get recipient data (students, instructors) for this event
     * @return array Array of recipient data with 'phone' and/or 'email'
     */
    public function getRecipients() {
        $recipients = [];
        
        switch ($this->type) {
            case self::BOOKING_CREATED:
            case self::DEPOSIT_CONFIRMED:
            case self::DEPOSIT_FAILED:
            case self::LESSON_RESCHEDULED:
            case self::LESSON_CANCELLED:
                // These events involve students and instructors
                if (isset($this->payload['student'])) {
                    $recipients[] = [
                        'phone' => $this->payload['student']['phone'] ?? null,
                        'email' => $this->payload['student']['email'] ?? null,
                        'name' => $this->payload['student']['name'] ?? null,
                        'role' => 'student'
                    ];
                }
                if (isset($this->payload['instructor'])) {
                    $recipients[] = [
                        'phone' => $this->payload['instructor']['phone'] ?? null,
                        'email' => $this->payload['instructor']['email'] ?? null,
                        'name' => $this->payload['instructor']['name'] ?? null,
                        'role' => 'instructor'
                    ];
                }
                break;
                
            case self::OTP_GENERATED:
                if (isset($this->payload['student'])) {
                    $recipients[] = [
                        'phone' => $this->payload['student']['phone'] ?? null,
                        'email' => $this->payload['student']['email'] ?? null,
                        'name' => $this->payload['student']['name'] ?? null,
                        'role' => 'student'
                    ];
                }
                break;
        }
        
        return $recipients;
    }
}

