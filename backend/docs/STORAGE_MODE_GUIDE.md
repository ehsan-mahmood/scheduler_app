# Storage Mode Guide

The API now supports **two storage modes**: JSON files or PostgreSQL database.

## Quick Start

### JSON Mode (Default - No Setup Needed)

The API starts in JSON mode by default. Just run:

```bash
php -S localhost:8001 router.php
```

Data is stored in `backend/data/*.json` files.

### Database Mode (Requires PostgreSQL)

1. Set up PostgreSQL database (see `SETUP_WITH_PGADMIN.md`)
2. Edit `backend/config.php`:
   ```php
   define('STORAGE_MODE', 'database');
   define('DB_PASS', 'your_password');
   ```
3. Restart the server

## Configuration

Edit `backend/config.php`:

```php
// Storage mode: 'json' or 'database'
define('STORAGE_MODE', 'json'); // or 'database'

// Database settings (only used in database mode)
define('DB_HOST', 'localhost');
define('DB_PORT', '5432');
define('DB_NAME', 'driving_school');
define('DB_USER', 'postgres');
define('DB_PASS', ''); // Set your password here
```

## Features

✅ **Both modes support:**
- Student registration and OTP verification
- Lesson booking
- Deposit submission
- Dashboard statistics
- Instructor management

✅ **OTP and Payment:**
- OTP is **only displayed** in API response (not sent via SMS)
- Payment info is **only stored/displayed** (not processed)

## Switching Modes

You can switch modes anytime by changing `STORAGE_MODE` in `config.php` and restarting the server.

**Note:** Data is not automatically migrated between modes. Switching modes starts with empty storage.

## Testing

Use the same test endpoints regardless of mode:
- `POST /register` - Register student
- `POST /verify-otp` - Verify OTP
- `POST /book-lesson` - Book lesson
- `GET /dashboard` - View dashboard

The API behaves the same way in both modes!

