# Payment System Documentation

## Overview

The payment system is a provider-based architecture that processes payments through configurable payment providers. In demo mode, it uses a `DemoPaymentProvider` that simulates payments without processing real money. The system can be extended with real payment providers (Stripe, PayID, etc.) in the future.

## Architecture

```
┌─────────────────┐
│  API Endpoint   │
│  (api_v2.php)   │
└────────┬────────┘
         │
         │ processPaymentForLesson()
         ▼
┌─────────────────┐
│ PaymentHelper   │
│  (Helper Func)  │
└────────┬────────┘
         │
         │ PaymentProcessor::process()
         ▼
┌─────────────────┐
│PaymentProcessor │
│  (Routes to)    │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│PaymentProvider  │
│  (Demo/Stripe)  │
└─────────────────┘
```

## Payment Status Machine

The payment system uses a status machine with three states:

- **CREATED**: Payment has been initiated but not yet processed
- **CONFIRMED**: Payment has been successfully processed
- **FAILED**: Payment processing failed

These are mapped to database statuses:
- `CREATED` → `pending`
- `CONFIRMED` → `confirmed`
- `FAILED` → `failed`

## Key Principles

1. **Provider Abstraction**: Payment providers are swappable (Demo → Stripe, PayID, etc.)
2. **Status Machine**: Payments follow a clear state flow (CREATED → CONFIRMED/FAILED)
3. **Demo Mode**: Default provider simulates payments (always succeeds)
4. **Non-Blocking**: Payment processing errors are handled gracefully
5. **Receipt Generation**: All successful payments generate receipts

## File Structure

```
backend/payments/
├── README.md (this file)
├── PaymentProviderInterface.php   # Provider interface
├── PaymentProcessor.php            # Payment processor/router
├── PaymentHelper.php               # Helper functions
└── providers/
    └── DemoPaymentProvider.php     # Demo provider (always succeeds)
```

## Usage

### 1. Basic Usage (Already Integrated)

The payment system is integrated into `api_v2.php`. Payments are processed when:
- A deposit is submitted via `/api/submit-deposit`

### 2. Processing Payments Manually

If you need to process payments from other parts of your code:

```php
require_once __DIR__ . '/payments/PaymentHelper.php';

$result = processPaymentForLesson(
    $lessonId,
    $amount,
    $currency,
    $reference,
    $businessId
);

// Result contains:
// - deposit_id: Database ID of the deposit record
// - status: Database status ('pending', 'confirmed', 'failed')
// - processor_status: Processor status ('CREATED', 'CONFIRMED', 'FAILED')
// - transaction_id: Transaction ID from provider
// - receipt: Receipt data (if payment succeeded)
// - success: Boolean success flag
```

### 3. Getting Payment Receipt

```php
// Via API endpoint
GET /{business}/api/payment-receipt/{deposit_id}

// Or programmatically
require_once __DIR__ . '/payments/PaymentHelper.php';

$receipt = generatePaymentReceipt($deposit, $lesson, $student, $lessonType);
```

## Current Providers

### DemoPaymentProvider (Default)

The demo provider simulates payment processing:
- **Always succeeds**: All payments are automatically confirmed
- **No real money**: No actual payment processing occurs
- **Simulated receipts**: Generates receipts with "simulated" flag
- **Transaction IDs**: Generates demo transaction IDs (format: `DEMO-XXXXXXXXXXXX`)

**Demo behavior**:
- All payments immediately return `CONFIRMED` status
- Receipts are marked with `simulated: true`
- Transaction IDs start with `DEMO-`
- Payment method is marked as `simulated`

**Log format**:
```
[DEMO PAYMENT] Amount: AUD 50.00 | Reference: PAYID123 | Metadata: {"lesson_id":"..."}
```

## Replacing Demo Provider with Real Provider

### Step 1: Create a Real Provider

#### For Stripe:

Create `backend/payments/providers/StripePaymentProvider.php`:

```php
<?php
require_once __DIR__ . '/../PaymentProviderInterface.php';

class StripePaymentProvider implements PaymentProviderInterface {
    private $secretKey;
    
    public function __construct() {
        $this->secretKey = getenv('STRIPE_SECRET_KEY');
    }
    
    public function processPayment($paymentData) {
        \Stripe\Stripe::setApiKey($this->secretKey);
        
        try {
            $charge = \Stripe\Charge::create([
                'amount' => $paymentData['amount'] * 100, // Convert to cents
                'currency' => strtolower($paymentData['currency']),
                'source' => $paymentData['metadata']['stripe_token'],
                'description' => 'Lesson deposit'
            ]);
            
            return [
                'success' => true,
                'status' => 'CONFIRMED',
                'transaction_id' => $charge->id,
                'receipt' => [
                    'transaction_id' => $charge->id,
                    'amount' => $paymentData['amount'],
                    'currency' => $paymentData['currency'],
                    'status' => 'CONFIRMED',
                    'payment_method' => 'card',
                    'processed_at' => date('Y-m-d H:i:s'),
                    'simulated' => false
                ],
                'error' => null
            ];
        } catch (\Stripe\Exception\CardException $e) {
            return [
                'success' => false,
                'status' => 'FAILED',
                'transaction_id' => null,
                'receipt' => null,
                'error' => $e->getMessage()
            ];
        }
    }
    
    public function isAvailable() {
        return !empty($this->secretKey);
    }
}
```

#### For PayID (Manual):

Create `backend/payments/providers/PayIDPaymentProvider.php`:

```php
<?php
require_once __DIR__ . '/../PaymentProviderInterface.php';

class PayIDPaymentProvider implements PaymentProviderInterface {
    public function processPayment($paymentData) {
        // PayID payments require manual verification
        // Return CREATED status - admin will verify later
        return [
            'success' => true,
            'status' => 'CREATED', // Requires manual verification
            'transaction_id' => null,
            'receipt' => null,
            'error' => null
        ];
    }
    
    public function isAvailable() {
        return true;
    }
}
```

### Step 2: Update PaymentProcessor

Modify `backend/payments/PaymentProcessor.php`:

```php
private static function initialize() {
    if (self::$initialized) {
        return;
    }
    
    // Try to use Stripe if configured
    require_once __DIR__ . '/providers/StripePaymentProvider.php';
    $stripeProvider = new StripePaymentProvider();
    if ($stripeProvider->isAvailable()) {
        self::$provider = $stripeProvider;
    } else {
        // Fallback to demo
        require_once __DIR__ . '/providers/DemoPaymentProvider.php';
        self::$provider = new DemoPaymentProvider();
    }
    
    self::$initialized = true;
}
```

## API Integration Points

Payments are processed from these API endpoints:

| Endpoint | Description | When Payment is Processed |
|----------|-------------|---------------------------|
| `POST /{business}/api/submit-deposit` | Submit payment deposit | Immediately processes through payment provider |
| `GET /{business}/api/payment-receipt/{deposit_id}` | Get payment receipt | Returns receipt data for a processed payment |

## Receipt Format

Receipts include the following information:

```json
{
  "receipt_id": "uuid",
  "transaction_id": "DEMO-XXXXXXXXXXXX",
  "amount": 50.00,
  "currency": "AUD",
  "status": "confirmed",
  "payment_method": "simulated",
  "processed_at": "2024-01-15 10:00:00",
  "simulated": true,
  "customer": {
    "name": "John Doe",
    "phone": "+61400123456"
  },
  "booking": {
    "lesson_id": "uuid",
    "lesson_type": "Standard Session",
    "scheduled_at": "2024-01-20 10:00:00"
  }
}
```

**Key fields**:
- `simulated`: Boolean indicating if payment was simulated (demo mode)
- `transaction_id`: Transaction ID from payment provider (starts with `DEMO-` for demo)
- `payment_method`: Payment method used (`simulated`, `card`, `payid`, etc.)

## Demo Mode Behavior

In demo mode (default):
- ✅ **Always succeeds**: All payments are automatically confirmed
- ✅ **Shows receipt**: Receipts are generated for all successful payments
- ✅ **Clearly marked "Simulated"**: Receipts include `simulated: true` flag
- ✅ **Transaction IDs**: Demo transaction IDs are generated
- ❌ **NO Stripe**: No Stripe integration
- ❌ **NO bank APIs**: No bank API integration
- ❌ **NO real money**: No actual money is processed

## Error Handling

Payment processing errors are handled gracefully:
- Provider errors are caught and logged
- Failed payments are stored with `FAILED` status
- Errors never block the booking process
- All errors are logged to PHP error log

## Testing

### Testing with Demo Provider

1. Submit a deposit via API:
```bash
curl -X POST http://localhost:8001/acme-driving/api/submit-deposit \
  -H "Content-Type: application/json" \
  -d '{
    "lesson_id": "uuid-here",
    "payid_reference": "PAYID123"
  }'
```

2. Check PHP error log for payment processing:
```
[DEMO PAYMENT] Amount: AUD 50.00 | Reference: PAYID123 | Metadata: {...}
```

3. Payment will be immediately confirmed in demo mode

4. Get receipt:
```bash
curl http://localhost:8001/acme-driving/api/payment-receipt/{deposit_id}
```

### Testing with Real Providers

1. Configure environment variables for your provider
2. Update `PaymentProcessor` to use real provider
3. Submit a payment and verify it's processed correctly

## Future Enhancements

- Stripe integration for credit card payments
- PayID provider with automatic verification
- Refund processing
- Payment history endpoint
- Webhook support for payment notifications
- Payment retry logic for failed payments
- Multiple payment methods per business


