<?php
/**
 * Email Notification Channel Interface
 * Abstraction for email notification providers
 */

require_once __DIR__ . '/NotificationChannelInterface.php';
require_once __DIR__ . '/../NotificationEvent.php';

class EmailChannel implements NotificationChannelInterface {
    private $provider;
    
    /**
     * @param object $provider Email provider (must implement sendEmail method)
     */
    public function __construct($provider) {
        $this->provider = $provider;
    }
    
    /**
     * Handle notification event and send email if applicable
     * @param NotificationEvent $event
     */
    public function handle($event) {
        $recipients = $event->getRecipients();
        
        foreach ($recipients as $recipient) {
            if (empty($recipient['email'])) {
                continue; // Skip if no email address
            }
            
            $emailData = $this->buildEmail($event, $recipient);
            
            if ($emailData) {
                try {
                    $this->provider->sendEmail(
                        $recipient['email'],
                        $emailData['subject'],
                        $emailData['body'],
                        $event->type,
                        $event->payload
                    );
                } catch (Exception $e) {
                    // Log but don't throw - failures must not block operations
                    error_log("Email send failed for {$recipient['email']}: " . $e->getMessage());
                }
            }
        }
    }
    
    /**
     * Build email content based on event type and recipient
     * @param NotificationEvent $event
     * @param array $recipient
     * @return array|null Array with 'subject' and 'body', or null if event doesn't need email
     */
    private function buildEmail($event, $recipient) {
        $name = $recipient['name'] ?? 'Valued Customer';
        $booking = $event->payload['lesson'] ?? [];
        $scheduledAt = $booking['scheduled_at'] ?? 'N/A';
        $serviceType = $event->payload['lesson_type']['name'] ?? 'appointment';
        
        switch ($event->type) {
            case NotificationEvent::BOOKING_CREATED:
                return [
                    'subject' => "Booking Confirmation - {$serviceType}",
                    'body' => "Dear {$name},\n\nYour {$serviceType} has been booked for {$scheduledAt}.\n\nPlease submit your deposit to confirm the booking.\n\nThank you!"
                ];
                
            case NotificationEvent::DEPOSIT_CONFIRMED:
                return [
                    'subject' => "Appointment Confirmed",
                    'body' => "Dear {$name},\n\nYour deposit has been confirmed. Your appointment on {$scheduledAt} is now confirmed.\n\nWe look forward to seeing you!"
                ];
                
            case NotificationEvent::DEPOSIT_FAILED:
                return [
                    'subject' => "Deposit Verification Failed",
                    'body' => "Dear {$name},\n\nYour deposit verification has failed. Please contact support for assistance.\n\nThank you!"
                ];
                
            case NotificationEvent::LESSON_RESCHEDULED:
                return [
                    'subject' => "Appointment Rescheduled",
                    'body' => "Dear {$name},\n\nYour appointment has been rescheduled to {$scheduledAt}.\n\nThank you!"
                ];
                
            case NotificationEvent::LESSON_CANCELLED:
                return [
                    'subject' => "Appointment Cancelled",
                    'body' => "Dear {$name},\n\nYour appointment has been cancelled.\n\nIf you have any questions, please contact us.\n\nThank you!"
                ];
                
            case NotificationEvent::OTP_GENERATED:
                $otp = $event->payload['otp'] ?? null;
                if ($otp) {
                    return [
                        'subject' => "Verification Code",
                        'body' => "Dear {$name},\n\nYour verification code is: {$otp}\n\nThis code is valid for 10 minutes.\n\nThank you!"
                    ];
                }
                return null;
                
            default:
                return null; // Unknown event type
        }
    }
}

