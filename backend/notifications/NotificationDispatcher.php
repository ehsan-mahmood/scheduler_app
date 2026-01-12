<?php
/**
 * Notification Dispatcher
 * Emits events to registered notification channels without blocking
 */

require_once __DIR__ . '/NotificationEvent.php';

class NotificationDispatcher {
    private static $channels = [];
    private static $initialized = false;
    
    /**
     * Initialize dispatcher with channels
     */
    private static function initialize() {
        if (self::$initialized) {
            return;
        }
        
        // Register SMS channel (always available)
        require_once __DIR__ . '/channels/providers/DemoSMSProvider.php';
        require_once __DIR__ . '/channels/SMSChannel.php';
        $smsProvider = new DemoSMSProvider();
        self::$channels['sms'] = new SMSChannel($smsProvider);
        
        // Register Email channel (always available)
        require_once __DIR__ . '/channels/providers/DemoEmailProvider.php';
        require_once __DIR__ . '/channels/EmailChannel.php';
        $emailProvider = new DemoEmailProvider();
        self::$channels['email'] = new EmailChannel($emailProvider);
        
        self::$initialized = true;
    }
    
    /**
     * Emit a notification event
     * This method NEVER throws exceptions - failures are logged silently
     * 
     * @param NotificationEvent $event The event to emit
     */
    public static function emit($event) {
        self::initialize();
        
        try {
            // Dispatch to all registered channels
            foreach (self::$channels as $channelName => $channel) {
                try {
                    $channel->handle($event);
                } catch (Exception $e) {
                    // Log error but don't fail - notifications must not block operations
                    error_log("Notification channel '$channelName' failed: " . $e->getMessage());
                }
            }
        } catch (Exception $e) {
            // Log error but don't fail - notifications must not block operations
            error_log("Notification dispatch failed: " . $e->getMessage());
        }
    }
    
    /**
     * Register a custom channel (for future extensibility)
     * @param string $name Channel name
     * @param object $channel Channel instance (must implement handle method)
     */
    public static function registerChannel($name, $channel) {
        self::initialize();
        self::$channels[$name] = $channel;
    }
}

