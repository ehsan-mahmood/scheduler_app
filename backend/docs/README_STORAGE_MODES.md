# Storage Modes - JSON vs Database

The API supports two storage modes that can be switched via configuration.

## Configuration

Edit `backend/config.php` to switch between modes:

```php
// For JSON file storage (default)
define('STORAGE_MODE', 'json');

// For PostgreSQL database
define('STORAGE_MODE', 'database');
```

When using database mode, also configure your database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'driving_school');
define('DB_USER', 'postgres');
define('DB_PASS', 'your_password_here');
```

## JSON Mode (Default)

- **Storage**: Files in `backend/data/` directory
- **Files**: `students.json`, `lessons.json`, `deposits.json`, `instructors.json`
- **Pros**: 
  - No database setup needed
  - Easy to inspect/edit data
  - Good for development/testing
- **Cons**:
  - Not suitable for production
  - No concurrent access protection
  - Limited querying capabilities

## Database Mode

- **Storage**: PostgreSQL database
- **Tables**: `students`, `instructors`, `lesson_types`, `lessons`, `payment_deposits`, `sms_notifications`
- **Pros**:
  - Production-ready
  - Supports concurrent access
  - Better performance
  - Advanced querying
- **Cons**:
  - Requires PostgreSQL setup
  - Need to configure database connection

## Switching Modes

1. **To switch from JSON to Database:**
   - Set up PostgreSQL database (see `SETUP_WITH_PGADMIN.md`)
   - Update `config.php`: Change `STORAGE_MODE` to `'database'`
   - Set database credentials in `config.php`
   - Restart the API server

2. **To switch from Database to JSON:**
   - Update `config.php`: Change `STORAGE_MODE` to `'json'`
   - Restart the API server

## Data Migration

Data is **not automatically migrated** between modes. If you switch modes:

- **JSON → Database**: You'll start with empty database
- **Database → JSON**: You'll start with empty JSON files

To migrate data, you'll need to write a migration script or manually transfer data.

## OTP and Payment Behavior

Both modes work the same way:
- **OTP**: Only displayed in response, not sent via SMS
- **Payment**: Only stored/displayed, not actually processed

This is controlled by constants in `config.php` (currently always true for display-only mode).

