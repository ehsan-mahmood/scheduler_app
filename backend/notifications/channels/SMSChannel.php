<?php
/**
 * SMS Notification Channel Interface
 * Abstraction for SMS notification providers
 */

require_once __DIR__ . '/NotificationChannelInterface.php';
require_once __DIR__ . '/../NotificationEvent.php';

class SMSChannel implements NotificationChannelInterface {
    private $provider;
    
    /**
     * @param object $provider SMS provider (must implement sendSMS method)
     */
    public function __construct($provider) {
        $this->provider = $provider;
    }
    
    /**
     * Handle notification event and send SMS if applicable
     * @param NotificationEvent $event
     */
    public function handle($event) {
        $recipients = $event->getRecipients();
        
        foreach ($recipients as $recipient) {
            if (empty($recipient['phone'])) {
                continue; // Skip if no phone number
            }
            
            $message = $this->buildMessage($event, $recipient);
            
            if ($message) {
                try {
                    $this->provider->sendSMS(
                        $recipient['phone'],
                        $message,
                        $event->type,
                        $event->payload
                    );
                } catch (Exception $e) {
                    // Log but don't throw - failures must not block operations
                    error_log("SMS send failed for {$recipient['phone']}: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Build SMS message based on event type and recipient
     * @param NotificationEvent $event
     * @param array $recipient
     * @return string|null Message text or null if event doesn't need SMS
     */
    private function buildMessage($event, $recipient) {
        switch ($event->type) {
            case NotificationEvent::BOOKING_CREATED:
                $booking = $event->payload['lesson'] ?? [];
                $scheduledAt = $booking['scheduled_at'] ?? 'N/A';
                $serviceType = $event->payload['lesson_type']['name'] ?? 'appointment';
                return "Your {$serviceType} is booked for {$scheduledAt}. Please submit your deposit to confirm.";
                
            case NotificationEvent::DEPOSIT_CONFIRMED:
                $booking = $event->payload['lesson'] ?? [];
                $scheduledAt = $booking['scheduled_at'] ?? 'N/A';
                return "Deposit confirmed! Your appointment on {$scheduledAt} is now confirmed.";
                
            case NotificationEvent::DEPOSIT_FAILED:
                return "Your deposit verification failed. Please contact support.";
                
            case NotificationEvent::LESSON_RESCHEDULED:
                $booking = $event->payload['lesson'] ?? [];
                $scheduledAt = $booking['scheduled_at'] ?? 'N/A';
                return "Your appointment has been rescheduled to {$scheduledAt}.";
                
            case NotificationEvent::LESSON_CANCELLED:
                return "Your appointment has been cancelled.";
                
            case NotificationEvent::OTP_GENERATED:
                // OTP messages are typically handled separately, but can be sent via SMS
                $otp = $event->payload['otp'] ?? null;
                if ($otp) {
                    return "Your verification code is: {$otp}. Valid for 10 minutes.";
                }
                return null;
                
            default:
                return null; // Unknown event type
        }
    }
}

