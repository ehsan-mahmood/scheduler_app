<?php
/**
 * Demo Email Provider
 * Logs email messages instead of actually sending them
 * Can be replaced with SMTP, SendGrid, etc.
 */

class DemoEmailProvider {
    /**
     * Send email message
     * 
     * @param string $to Email address
     * @param string $subject Email subject
     * @param string $body Email body
     * @param string $eventType Event type (for logging)
     * @param array $payload Event payload (for logging)
     * @return bool Success status
     */
    public function sendEmail($to, $subject, $body, $eventType = '', $payload = []) {
        // In production, this would call SMTP/SendGrid API
        // For now, just log the message
        error_log(sprintf(
            "[DEMO EMAIL] To: %s | Type: %s | Subject: %s | Body: %s",
            $to,
            $eventType,
            $subject,
            substr($body, 0, 100) // Truncate long messages in log
        ));
        
        // Simulate success
        return true;
    }
    
    /**
     * Check if email provider is available
     * @return bool
     */
    public function isAvailable() {
        return true; // Demo provider is always available
    }
}

