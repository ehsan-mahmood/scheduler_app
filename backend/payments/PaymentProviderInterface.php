<?php
/**
 * Payment Provider Interface
 * All payment providers must implement the processPayment method
 */

interface PaymentProviderInterface {
    /**
     * Process a payment
     * 
     * @param array $paymentData Payment data including:
     *   - amount: Decimal amount
     *   - currency: Currency code (e.g., 'AUD')
     *   - reference: Payment reference (e.g., PayID reference)
     *   - metadata: Additional metadata (lesson_id, student_id, etc.)
     * @return array Payment result with:
     *   - success: bool
     *   - status: 'CREATED' | 'CONFIRMED' | 'FAILED'
     *   - transaction_id: string (if available)
     *   - receipt: array (if payment succeeded)
     *   - error: string (if payment failed)
     */
    public function processPayment($paymentData);
    
    /**
     * Check if payment provider is available
     * @return bool
     */
    public function isAvailable();
}


