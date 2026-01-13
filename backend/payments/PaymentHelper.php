<?php
/**
 * Payment Helper Functions
 * Helper functions for payment operations
 */

require_once __DIR__ . '/PaymentProcessor.php';

/**
 * Map payment status from processor format to database format
 * 
 * @param string $processorStatus Status from payment processor ('CREATED', 'CONFIRMED', 'FAILED')
 * @return string Database status ('pending', 'confirmed', 'failed')
 */
function mapPaymentStatus($processorStatus) {
    $statusMap = [
        'CREATED' => 'pending',
        'CONFIRMED' => 'confirmed',
        'FAILED' => 'failed'
    ];
    
    return $statusMap[$processorStatus] ?? 'pending';
}

/**
 * Process a payment through the payment system
 * 
 * @param string $lessonId Lesson ID
 * @param float $amount Payment amount
 * @param string $currency Currency code
 * @param string $reference Payment reference (e.g., PayID reference)
 * @param string $businessId Business ID
 * @return array Payment result with deposit_id and payment status
 */
function processPaymentForLesson($lessonId, $amount, $currency, $reference, $businessId) {
    require_once __DIR__ . '/../db.php';
    $db = getDbConnection();
    
    // Build payment data
    $paymentData = [
        'amount' => $amount,
        'currency' => $currency,
        'reference' => $reference,
        'metadata' => [
            'lesson_id' => $lessonId,
            'business_id' => $businessId
        ]
    ];
    
    // Process payment through provider
    $result = PaymentProcessor::process($paymentData);
    
    // Map status from processor format to database format
    $dbStatus = mapPaymentStatus($result['status']);
    
    // Create deposit record in database
    $stmt = $db->prepare("
        INSERT INTO payment_deposits (
            business_id, lesson_id, amount, currency,
            payid_reference, transaction_id, status, created_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        RETURNING id
    ");
    
    $stmt->execute([
        $businessId,
        $lessonId,
        $amount,
        $currency,
        $reference,
        $result['transaction_id'] ?? null,
        $dbStatus
    ]);
    
    $deposit = $stmt->fetch(PDO::FETCH_ASSOC);
    $depositId = $deposit['id'];
    
    // If payment was confirmed immediately (demo mode), update verified_at
    if ($dbStatus === 'confirmed') {
        $stmt = $db->prepare("
            UPDATE payment_deposits 
            SET verified_at = CURRENT_TIMESTAMP
            WHERE id = ?
        ");
        $stmt->execute([$depositId]);
        
        // Update lesson status
        $stmt = $db->prepare("
            UPDATE lessons 
            SET status = 'confirmed', deposit_paid = true
            WHERE id = ? AND business_id = ?
        ");
        $stmt->execute([$lessonId, $businessId]);
        
        // Emit deposit confirmed event (non-blocking)
        require_once __DIR__ . '/../notifications/NotificationHelper.php';
        emitDepositConfirmedEvent($lessonId, $businessId);
    } else if ($dbStatus === 'failed') {
        // Emit deposit failed event (non-blocking)
        require_once __DIR__ . '/../notifications/NotificationHelper.php';
        emitDepositFailedEvent($lessonId, $businessId);
    }
    
    return [
        'deposit_id' => $depositId,
        'status' => $dbStatus,
        'processor_status' => $result['status'],
        'transaction_id' => $result['transaction_id'] ?? null,
        'receipt' => $result['receipt'] ?? null,
        'success' => $result['success']
    ];
}

/**
 * Generate payment receipt data
 * 
 * @param array $deposit Deposit record from database
 * @param array $lesson Lesson record from database
 * @param array $student Student record from database
 * @param array $lessonType Lesson type record from database
 * @return array Receipt data
 */
function generatePaymentReceipt($deposit, $lesson, $student, $lessonType) {
    $isSimulated = ($deposit['transaction_id'] ?? '') !== '' && 
                   strpos($deposit['transaction_id'], 'DEMO-') === 0;
    
    return [
        'receipt_id' => $deposit['id'],
        'transaction_id' => $deposit['transaction_id'] ?? null,
        'amount' => floatval($deposit['amount']),
        'currency' => $deposit['currency'] ?? 'AUD',
        'status' => $deposit['status'],
        'payment_method' => $deposit['payment_method'] ?? 'simulated',
        'processed_at' => $deposit['verified_at'] ?? $deposit['created_at'],
        'simulated' => $isSimulated,
        'customer' => [
            'name' => $student['name'] ?? '',
            'phone' => $student['phone'] ?? ''
        ],
        'booking' => [
            'lesson_id' => $lesson['id'],
            'lesson_type' => $lessonType['name'] ?? '',
            'scheduled_at' => $lesson['scheduled_at'] ?? ''
        ]
    ];
}

