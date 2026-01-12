<?php
/**
 * Demo SMS Provider
 * Logs SMS messages instead of actually sending them
 * Can be replaced with Twilio, MessageMedia, etc.
 */

class DemoSMSProvider {
    /**
     * Send SMS message
     * 
     * @param string $to Phone number
     * @param string $message Message text
     * @param string $eventType Event type (for logging)
     * @param array $payload Event payload (for logging)
     * @return bool Success status
     */
    public function sendSMS($to, $message, $eventType = '', $payload = []) {
        // In production, this would call Twilio/MessageMedia API
        // For now, just log the message
        error_log(sprintf(
            "[DEMO SMS] To: %s | Type: %s | Message: %s",
            $to,
            $eventType,
            substr($message, 0, 100) // Truncate long messages in log
        ));
        
        // Simulate success
        return true;
    }
    
    /**
     * Check if SMS provider is available
     * @return bool
     */
    public function isAvailable() {
        return true; // Demo provider is always available
    }
}

