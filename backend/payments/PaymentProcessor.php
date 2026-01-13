<?php
/**
 * Payment Processor
 * Processes payments through registered payment providers
 * Similar to NotificationDispatcher but for payments
 */

require_once __DIR__ . '/PaymentProviderInterface.php';

class PaymentProcessor {
    private static $provider = null;
    private static $initialized = false;
    
    /**
     * Initialize processor with payment provider
     */
    private static function initialize() {
        if (self::$initialized) {
            return;
        }
        
        // Register DemoPaymentProvider (default)
        require_once __DIR__ . '/providers/DemoPaymentProvider.php';
        self::$provider = new DemoPaymentProvider();
        
        self::$initialized = true;
    }
    
    /**
     * Process a payment
     * 
     * @param array $paymentData Payment data
     * @return array Payment result
     */
    public static function process($paymentData) {
        self::initialize();
        
        try {
            if (!self::$provider || !self::$provider->isAvailable()) {
                return [
                    'success' => false,
                    'status' => 'FAILED',
                    'transaction_id' => null,
                    'receipt' => null,
                    'error' => 'Payment provider is not available'
                ];
            }
            
            return self::$provider->processPayment($paymentData);
        } catch (Exception $e) {
            error_log("Payment processing failed: " . $e->getMessage());
            return [
                'success' => false,
                'status' => 'FAILED',
                'transaction_id' => null,
                'receipt' => null,
                'error' => 'Payment processing error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Register a custom payment provider (for future extensibility)
     * @param PaymentProviderInterface $provider Payment provider instance
     */
    public static function registerProvider($provider) {
        self::initialize();
        if ($provider instanceof PaymentProviderInterface) {
            self::$provider = $provider;
        }
    }
    
    /**
     * Get the current payment provider
     * @return PaymentProviderInterface|null
     */
    public static function getProvider() {
        self::initialize();
        return self::$provider;
    }
}


