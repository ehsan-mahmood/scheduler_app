<?php
/**
 * Notification Channel Interface
 * All notification channels must implement the handle method
 */

interface NotificationChannelInterface {
    /**
     * Handle a notification event
     * @param NotificationEvent $event The event to handle
     */
    public function handle($event);
}

