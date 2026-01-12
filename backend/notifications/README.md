# Notification System Documentation

## Overview

The notification system is an event-driven architecture that allows the scheduling logic to emit events without knowing how notifications are sent. Notifications are handled through channels (SMS, Email) with provider abstraction, ensuring that notification failures never block core operations like bookings.

## Architecture

```
┌─────────────────┐
│  API Endpoint   │
│  (api_v2.php)   │
└────────┬────────┘
         │
         │ emitBookingCreatedEvent()
         ▼
┌─────────────────┐
│NotificationEvent│ (Event Type + Payload)
└────────┬────────┘
         │
         │ NotificationDispatcher::emit()
         ▼
┌─────────────────┐
│   Dispatcher    │
│  (Routes Event) │
└────────┬────────┘
         │
    ┌────┴────┐
    ▼         ▼
┌────────┐ ┌────────┐
│SMSChannel│ │EmailChannel│
└────┬───┘ └────┬───┘
     │          │
     ▼          ▼
┌─────────┐ ┌──────────┐
│Provider │ │ Provider │
│(Demo/   │ │(Demo/    │
│Twilio)  │ │SMTP)     │
└─────────┘ └──────────┘
```

## Key Principles

1. **Event-Driven**: Scheduling logic emits events, not direct notification calls
2. **Provider Abstraction**: Easy to swap providers (Demo → Twilio, SMTP, etc.)
3. **Non-Blocking**: Notification failures never block bookings or core operations
4. **Silent Failure**: All errors are logged but never thrown
5. **Extensible**: Easy to add new channels (WhatsApp, Push) or providers

## File Structure

```
backend/notifications/
├── README.md (this file)
├── NotificationEvent.php          # Event abstraction
├── NotificationDispatcher.php     # Event dispatcher
├── NotificationHelper.php         # Helper functions for emitting events
└── channels/
    ├── NotificationChannelInterface.php  # Channel interface
    ├── SMSChannel.php                    # SMS channel implementation
    ├── EmailChannel.php                  # Email channel implementation
    └── providers/
        ├── DemoSMSProvider.php          # Demo SMS provider (logs messages)
        └── DemoEmailProvider.php        # Demo Email provider (logs messages)
```

## Usage

### 1. Basic Usage (Already Integrated)

The notification system is already integrated into `api_v2.php`. Events are automatically emitted when:

- **Booking Created**: When a lesson is booked via `/api/book-lesson`
- **Deposit Confirmed**: When a deposit is verified via `/api/admin/verify-deposit` with status 'confirmed'
- **Deposit Failed**: When a deposit is verified via `/api/admin/verify-deposit` with status 'failed'

### 2. Emitting Events Manually

If you need to emit events from other parts of your code:

```php
// Include the notification helper
require_once __DIR__ . '/notifications/NotificationHelper.php';

// Emit booking created event
emitBookingCreatedEvent($lessonId, $businessId);

// Emit deposit confirmed event
emitDepositConfirmedEvent($lessonId, $businessId);

// Emit deposit failed event
emitDepositFailedEvent($lessonId, $businessId);
```

### 3. Creating Custom Events

To create a custom event (e.g., for lesson cancellation):

```php
require_once __DIR__ . '/notifications/NotificationEvent.php';
require_once __DIR__ . '/notifications/NotificationDispatcher.php';

// Build event payload
$payload = [
    'lesson' => $lessonData,
    'student' => $studentData,
    'instructor' => $instructorData
];

// Create and emit event
$event = new NotificationEvent(
    NotificationEvent::LESSON_CANCELLED,
    $payload,
    $businessId
);

NotificationDispatcher::emit($event);
```

### 4. Available Event Types

Event types are defined in `NotificationEvent.php`:

- `NotificationEvent::BOOKING_CREATED` - When a lesson is booked
- `NotificationEvent::DEPOSIT_CONFIRMED` - When a deposit is confirmed
- `NotificationEvent::DEPOSIT_FAILED` - When a deposit verification fails
- `NotificationEvent::LESSON_RESCHEDULED` - When a lesson is rescheduled
- `NotificationEvent::LESSON_CANCELLED` - When a lesson is cancelled
- `NotificationEvent::OTP_GENERATED` - When an OTP is generated
- `NotificationEvent::LESSON_REMINDER` - For lesson reminders

## Current Providers

### Demo Providers (Default)

Currently, the system uses **demo providers** that log messages instead of actually sending them:

- **DemoSMSProvider**: Logs SMS messages to PHP error log
- **DemoEmailProvider**: Logs email messages to PHP error log

**Check logs**: PHP error log (usually in `php_error.log` or configured in `php.ini`)

**Log format**:
```
[DEMO SMS] To: +61400123456 | Type: booking_created | Message: Your Standard Session is booked for 2024-01-15 10:00:00...
[DEMO EMAIL] To: student@example.com | Type: deposit_confirmed | Subject: Lesson Confirmed | Body: Dear John...
```

## Replacing Demo Providers with Real Providers

### Step 1: Create a Real Provider

#### For SMS (e.g., Twilio):

Create `backend/notifications/channels/providers/TwilioSMSProvider.php`:

```php
<?php
class TwilioSMSProvider {
    private $accountSid;
    private $authToken;
    private $fromNumber;
    
    public function __construct() {
        $this->accountSid = getenv('TWILIO_ACCOUNT_SID');
        $this->authToken = getenv('TWILIO_AUTH_TOKEN');
        $this->fromNumber = getenv('TWILIO_FROM_NUMBER');
    }
    
    public function sendSMS($to, $message, $eventType = '', $payload = []) {
        $client = new \Twilio\Rest\Client($this->accountSid, $this->authToken);
        $client->messages->create(
            $to,
            [
                'from' => $this->fromNumber,
                'body' => $message
            ]
        );
        return true;
    }
    
    public function isAvailable() {
        return !empty($this->accountSid) && !empty($this->authToken);
    }
}
```

#### For Email (e.g., SMTP):

Create `backend/notifications/channels/providers/SMTPEmailProvider.php`:

```php
<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

class SMTPEmailProvider {
    private $host;
    private $port;
    private $username;
    private $password;
    
    public function __construct() {
        $this->host = getenv('SMTP_HOST');
        $this->port = getenv('SMTP_PORT');
        $this->username = getenv('SMTP_USERNAME');
        $this->password = getenv('SMTP_PASSWORD');
    }
    
    public function sendEmail($to, $subject, $body, $eventType = '', $payload = []) {
        $mail = new PHPMailer(true);
        
        $mail->isSMTP();
        $mail->Host = $this->host;
        $mail->SMTPAuth = true;
        $mail->Username = $this->username;
        $mail->Password = $this->password;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port = $this->port;
        
        $mail->setFrom('noreply@drivingschool.com', 'Driving School');
        $mail->addAddress($to);
        $mail->Subject = $subject;
        $mail->Body = $body;
        
        $mail->send();
        return true;
    }
    
    public function isAvailable() {
        return !empty($this->host) && !empty($this->username);
    }
}
```

### Step 2: Update NotificationDispatcher

Modify `backend/notifications/NotificationDispatcher.php`:

```php
private static function initialize() {
    if (self::$initialized) {
        return;
    }
    
    // Register SMS channel with real provider
    require_once __DIR__ . '/channels/providers/TwilioSMSProvider.php';
    require_once __DIR__ . '/channels/SMSChannel.php';
    $smsProvider = new TwilioSMSProvider();
    if ($smsProvider->isAvailable()) {
        self::$channels['sms'] = new SMSChannel($smsProvider);
    } else {
        // Fallback to demo if not configured
        require_once __DIR__ . '/channels/providers/DemoSMSProvider.php';
        self::$channels['sms'] = new SMSChannel(new DemoSMSProvider());
    }
    
    // Register Email channel with real provider
    require_once __DIR__ . '/channels/providers/SMTPEmailProvider.php';
    require_once __DIR__ . '/channels/EmailChannel.php';
    $emailProvider = new SMTPEmailProvider();
    if ($emailProvider->isAvailable()) {
        self::$channels['email'] = new EmailChannel($emailProvider);
    } else {
        // Fallback to demo if not configured
        require_once __DIR__ . '/channels/providers/DemoEmailProvider.php';
        self::$channels['email'] = new EmailChannel(new DemoEmailProvider());
    }
    
    self::$initialized = true;
}
```

## Adding New Channels

To add a new channel (e.g., WhatsApp or Push Notifications):

1. Create the channel class in `backend/notifications/channels/WhatsAppChannel.php`:

```php
<?php
require_once __DIR__ . '/NotificationChannelInterface.php';
require_once __DIR__ . '/../NotificationEvent.php';

class WhatsAppChannel implements NotificationChannelInterface {
    private $provider;
    
    public function __construct($provider) {
        $this->provider = $provider;
    }
    
    public function handle($event) {
        $recipients = $event->getRecipients();
        foreach ($recipients as $recipient) {
            if (empty($recipient['phone'])) {
                continue;
            }
            $message = $this->buildMessage($event, $recipient);
            if ($message) {
                $this->provider->sendWhatsApp($recipient['phone'], $message);
            }
        }
    }
    
    private function buildMessage($event, $recipient) {
        // Build WhatsApp message based on event type
        // ...
    }
}
```

2. Create a provider for the channel
3. Register it in `NotificationDispatcher::initialize()`

## Error Handling

**Important**: All notification errors are caught and logged, but never thrown. This ensures that notification failures never block core operations like bookings.

Errors are logged to PHP error log with messages like:
```
Notification channel 'sms' failed: Connection timeout
Email send failed for student@example.com: SMTP authentication failed
```

## Testing

### Testing with Demo Providers

1. Make a booking via API:
```bash
curl -X POST http://localhost:8001/acme-driving/api/book-lesson \
  -H "Content-Type: application/json" \
  -d '{
    "phone": "+61400123456",
    "lesson_type_id": "uuid-here",
    "instructor_id": "uuid-here",
    "scheduled_at": "2024-01-15 10:00:00"
  }'
```

2. Check PHP error log for notification messages:
```
[DEMO SMS] To: +61400123456 | Type: booking_created | Message: Your Standard Session is booked...
[DEMO EMAIL] To: student@example.com | Type: booking_created | Subject: Booking Confirmation...
```

### Testing with Real Providers

1. Configure environment variables for your provider
2. Update `NotificationDispatcher` to use real providers
3. Make a booking and verify notifications are sent

## Troubleshooting

### Notifications Not Appearing

1. **Check PHP error log** - All notifications are logged there
2. **Verify event is emitted** - Check that `emitBookingCreatedEvent()` is called
3. **Check database** - Ensure lesson data exists and has student/instructor info
4. **Check provider configuration** - Verify provider credentials if using real providers

### Common Issues

- **No phone/email in recipient**: Ensure students and instructors have phone/email in database
- **Provider not available**: Check provider `isAvailable()` method
- **Silent failures**: Check PHP error log for error messages

## API Integration Points

Events are automatically emitted from these API endpoints:

| Endpoint | Event Type | When |
|----------|-----------|------|
| `POST /{business}/api/book-lesson` | `BOOKING_CREATED` | After lesson is created |
| `POST /{business}/api/admin/verify-deposit` (status: confirmed) | `DEPOSIT_CONFIRMED` | After deposit is confirmed |
| `POST /{business}/api/admin/verify-deposit` (status: failed) | `DEPOSIT_FAILED` | After deposit verification fails |

## Future Enhancements

- Database logging of notifications (optional)
- Notification preferences per user
- Retry logic for failed notifications
- Webhook support for external integrations
- Queue system for high-volume notifications

