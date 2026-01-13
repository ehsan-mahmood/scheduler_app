<?php
/**
 * Demo Payment Provider
 * Simulates payment processing - always succeeds
 * Can be replaced with StripeProvider, PayIDProvider, etc.
 */

require_once __DIR__ . '/../PaymentProviderInterface.php';

class DemoPaymentProvider implements PaymentProviderInterface {
    /**
     * Process a payment (simulated)
     * 
     * @param array $paymentData Payment data
     * @return array Payment result
     */
    public function processPayment($paymentData) {
        $amount = $paymentData['amount'] ?? 0;
        $currency = $paymentData['currency'] ?? 'AUD';
        $reference = $paymentData['reference'] ?? '';
        $metadata = $paymentData['metadata'] ?? [];
        
        // Log the payment attempt
        error_log(sprintf(
            "[DEMO PAYMENT] Amount: %s %s | Reference: %s | Metadata: %s",
            $currency,
            number_format($amount, 2),
            $reference,
            json_encode($metadata)
        ));
        
        // Simulate payment processing delay
        usleep(100000); // 100ms delay
        
        // Always succeed in demo mode
        $transactionId = 'DEMO-' . strtoupper(substr(md5(uniqid()), 0, 12));
        
        // Generate receipt
        $receipt = [
            'transaction_id' => $transactionId,
            'amount' => $amount,
            'currency' => $currency,
            'reference' => $reference,
            'status' => 'CONFIRMED',
            'payment_method' => 'simulated',
            'processed_at' => date('Y-m-d H:i:s'),
            'simulated' => true
        ];
        
        return [
            'success' => true,
            'status' => 'CONFIRMED',
            'transaction_id' => $transactionId,
            'receipt' => $receipt,
            'error' => null
        ];
    }
    
    /**
     * Check if payment provider is available
     * @return bool
     */
    public function isAvailable() {
        return true; // Demo provider is always available
    }
}


