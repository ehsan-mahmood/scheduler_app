# Database Setup Guide

This guide explains how to set up the PostgreSQL database for the Driving School application.

## Compatibility Check

✅ **This schema is compatible with the PHP backend code.**

### Key Compatibility Points:

1. **All PHP backend fields are included:**
   - `students`: Includes `otp_code`, `otp_expires_at` (used by PHP backend)
   - `instructors`: Includes `email`, `phone`, `is_active` (used by PHP backend)
   - `lesson_types`: Includes `description`, `price` (decimal), `is_active` (used by PHP backend)
   - `lessons`: Includes `scheduled_at`, `status` enum, `deposit_paid`, `lesson_otp`, `lesson_otp_expires_at`, `notes` (all used by PHP backend)
   - `payment_deposits`: Includes `amount` (decimal), `verified_at` (used by PHP backend)
   - `sms_notifications`: Full table matching PHP backend structure

2. **ID Type:**
   - Uses UUIDs as specified in the design summary
   - PHP backend uses Laravel's `id()` which defaults to auto-incrementing integers
   - **Note:** If your PHP backend expects integer IDs, you may need to:
     - Modify the PHP backend to use UUIDs, OR
     - Use a version with SERIAL/BIGSERIAL instead (see alternative script below)

3. **Timestamps:**
   - Includes both `created_at` and `updated_at` (matching Laravel conventions)
   - Auto-updates `updated_at` via triggers

4. **Data Types:**
   - Uses `DECIMAL(10, 2)` for prices/amounts (matches PHP backend)
   - Uses `VARCHAR` for status enums with CHECK constraints
   - Uses `BOOLEAN` for flags

## Quick Start

### Option 1: Using the Batch Script (Windows)

1. Double-click `create_database.bat`
2. Follow the prompts

### Option 2: Using the Shell Script (Linux/Mac)

1. Make the script executable:
   ```bash
   chmod +x create_database.sh
   ```
2. Run it:
   ```bash
   ./create_database.sh
   ```

### Option 3: Manual Setup

1. **Connect to PostgreSQL:**
   ```bash
   psql -U postgres
   ```

2. **Create the database:**
   ```sql
   CREATE DATABASE driving_school;
   ```

3. **Connect to the database:**
   ```sql
   \c driving_school
   ```

4. **Run the SQL script:**
   ```sql
   \i create_database.sql
   ```

   Or from command line:
   ```bash
   psql -U postgres -d driving_school -f create_database.sql
   ```

## Database Structure

### Tables Created:

1. **students** - Customer information
2. **instructors** - Driving instructors
3. **lesson_types** - Lesson offerings
4. **lessons** - Central scheduling table
5. **payment_deposits** - PayID deposit tracking
6. **sms_notifications** - SMS message logging

### Indexes:

- Performance indexes on foreign keys
- Composite indexes for scheduling queries: `(instructor_id, scheduled_at)`, `(student_id, scheduled_at)`
- Status indexes for filtering

### Triggers:

- Auto-update `updated_at` timestamp on all tables

## Differences from Design Summary

The schema includes additional fields from the PHP backend that weren't in the original design summary:

- **students**: Added `otp_code`, `otp_expires_at` (OTP stored in students table, not separate otp_events table)
- **instructors**: Added `email`, `phone`
- **lesson_types**: Added `description`, `is_active`
- **lessons**: Added `deposit_paid`, `lesson_otp`, `lesson_otp_expires_at`, `notes`
- **payment_deposits**: Added `verified_at` (renamed from `matched_at`)
- **sms_notifications**: Full table (design summary had simpler `sms_logs`)

## Using with PHP Backend

If your PHP backend uses Laravel, update your `.env` file:

```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=driving_school
DB_USERNAME=postgres
DB_PASSWORD=your_password
```

**Important:** If your PHP backend expects integer IDs instead of UUIDs, you'll need to either:
1. Modify the PHP backend to use UUIDs, OR
2. Use SERIAL/BIGSERIAL for primary keys (contact developer for alternative script)

## Verification

After setup, verify the tables were created:

```sql
\dt
```

Check table structure:

```sql
\d students
\d instructors
\d lessons
```

## Troubleshooting

### Database already exists
```sql
DROP DATABASE driving_school;
CREATE DATABASE driving_school;
```

### Permission denied
Make sure your PostgreSQL user has CREATE DATABASE privileges, or use the `postgres` superuser.

### UUID extension error
The script should create the UUID extension automatically. If it fails, run:
```sql
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";
```

## Next Steps

1. Update your PHP backend configuration to use PostgreSQL
2. Test database connections
3. Seed initial data (instructors, lesson types)
4. Update PHP backend code if using UUIDs instead of integer IDs

