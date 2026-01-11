<?php
/**
 * Configuration file for Driving School API
 */

// Storage mode: 'json' or 'database'
define('STORAGE_MODE', 'database'); // Change to 'database' to use PostgreSQL

// Database configuration (only used when STORAGE_MODE is 'database')
define('DB_HOST', 'localhost');
define('DB_PORT', '5433');
define('DB_NAME', 'driving_school');
define('DB_USER', 'postgres');
define('DB_PASS', 'postgres'); // Set your PostgreSQL password here

// OTP and Payment settings
define('OTP_DISPLAY_ONLY', true); // OTP is only displayed in response, not sent via SMS
define('PAYMENT_DISPLAY_ONLY', true); // Payment info is only displayed, not processed

