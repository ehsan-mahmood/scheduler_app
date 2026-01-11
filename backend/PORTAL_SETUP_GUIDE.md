# Portal Endpoints - Quick Setup Guide

## 🚀 Quick Start (5 Minutes)

### Step 1: Run Database Migration

```bash
cd backend
psql -U postgres -d driving_school < migrations/003_add_password_to_instructors.sql
```

Or manually in psql:

```sql
ALTER TABLE instructors ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255);
CREATE INDEX IF NOT EXISTS idx_instructors_phone_business ON instructors(business_id, phone) WHERE is_active = true;
```

---

### Step 2: Create Demo Instructor Account

```sql
-- Password: "demo123"
-- This updates the existing instructor to have a password
UPDATE instructors 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE phone = '+61400333444' 
  AND business_id = (SELECT id FROM businesses WHERE subdomain = 'acme-driving');
```

---

### Step 3: Test the Endpoints

```bash
# Start backend (if not already running)
cd backend
php -S localhost:8001

# Test login
curl -X POST http://localhost:8001/acme-driving/api/portal/login \
  -H "Content-Type: application/json" \
  -d '{"phone": "+61400333444", "password": "demo123"}'

# Should return:
# {
#   "success": true,
#   "message": "Login successful",
#   "user": { ... }
# }
```

---

### Step 4: Test All New Endpoints

```bash
# Get students list
curl http://localhost:8001/acme-driving/api/portal/students

# Get all bookings
curl http://localhost:8001/acme-driving/api/portal/bookings

# Cancel a booking (replace with real lesson ID)
curl -X POST http://localhost:8001/acme-driving/api/booking/cancel \
  -H "Content-Type: application/json" \
  -d '{"booking_id": "your-lesson-uuid", "reason": "Test cancellation"}'
```

---

## 📋 What Was Added

### New API Endpoints (5 total)

1. **POST /api/portal/login** - Instructor/admin login with password
2. **POST /api/portal/register** - Create new portal user
3. **GET /api/portal/students** - List all students with stats
4. **GET /api/portal/bookings** - List all bookings with filters
5. **POST /api/booking/cancel** - Cancel a booking

### Database Changes

- Added `password_hash` column to `instructors` table
- Added index on `(business_id, phone)` for faster lookups

---

## 🔐 Create More Instructor Accounts

### Option 1: Using SQL

```sql
-- Create new instructor with password
INSERT INTO instructors (
    business_id, 
    name, 
    email, 
    phone, 
    password_hash, 
    max_hours_per_week, 
    is_active
) VALUES (
    (SELECT id FROM businesses WHERE subdomain = 'acme-driving'),
    'Jane Instructor',
    'jane@example.com',
    '+61400999888',
    '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', -- password: demo123
    40,
    true
);
```

### Option 2: Using API (if enabled)

```bash
curl -X POST http://localhost:8001/acme-driving/api/portal/register \
  -H "Content-Type: application/json" \
  -d '{
    "name": "Jane Instructor",
    "phone": "+61400999888",
    "email": "jane@example.com",
    "password": "demo123"
  }'
```

### Option 3: Using PHP Script

```php
<?php
// generate_password.php
$password = 'your_password_here';
$hash = password_hash($password, PASSWORD_DEFAULT);
echo "Password hash: $hash\n";
```

Run:
```bash
php generate_password.php
```

Then use the hash in SQL INSERT.

---

## 🧪 Testing Checklist

### ✅ Authentication
- [ ] Login with correct credentials → Success
- [ ] Login with wrong password → Error
- [ ] Login with non-existent phone → Error
- [ ] Register new instructor → Success
- [ ] Register duplicate phone → Error

### ✅ Students List
- [ ] Get all students → Returns list
- [ ] Search by name → Filters correctly
- [ ] Search by phone → Filters correctly
- [ ] Pagination works → limit/offset

### ✅ Bookings List
- [ ] Get all bookings → Returns list
- [ ] Filter by status → Works
- [ ] Filter by instructor → Works
- [ ] Filter by date range → Works
- [ ] Multiple filters combined → Works

### ✅ Cancel Booking
- [ ] Cancel pending booking → Success
- [ ] Cancel confirmed booking → Success
- [ ] Cancel already cancelled → Error
- [ ] Cancel completed lesson → Error
- [ ] Late cancellation → Shows warning

---

## 🔧 Troubleshooting

### Error: "Invalid credentials"

**Check:**
1. Password hash exists in database:
   ```sql
   SELECT id, name, phone, password_hash FROM instructors WHERE phone = '+61400333444';
   ```
2. Phone number matches exactly (including +)
3. Instructor is active (`is_active = true`)

**Fix:**
```sql
UPDATE instructors 
SET password_hash = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'
WHERE phone = '+61400333444';
```

---

### Error: "Endpoint not found"

**Check:**
1. Backend is running: `curl http://localhost:8001/acme-driving/api/config`
2. Business slug is correct: `acme-driving` (not `acme_driving`)
3. URL format: `/{business}/api/portal/login` (not `/api/{business}/portal/login`)

---

### Error: "Phone number already registered"

This is expected when trying to register a duplicate phone.

**Check existing:**
```sql
SELECT phone, name FROM instructors WHERE business_id = (SELECT id FROM businesses WHERE subdomain = 'acme-driving');
```

---

### Students List Returns Empty

**Check:**
1. Students exist in database:
   ```sql
   SELECT COUNT(*) FROM students WHERE business_id = (SELECT id FROM businesses WHERE subdomain = 'acme-driving');
   ```
2. Business ID is correct

**Add test student:**
```sql
INSERT INTO students (business_id, phone, name, otp_verified)
VALUES (
    (SELECT id FROM businesses WHERE subdomain = 'acme-driving'),
    '+61400111222',
    'Test Student',
    true
);
```

---

## 📊 Demo Data Setup

### Complete Demo Setup Script

```sql
-- Get business ID
DO $$
DECLARE
    biz_id UUID;
    inst_id UUID;
    student_id UUID;
    lesson_type_id UUID;
BEGIN
    -- Get or create business
    SELECT id INTO biz_id FROM businesses WHERE subdomain = 'acme-driving';
    
    -- Create instructor with password (password: demo123)
    INSERT INTO instructors (business_id, name, email, phone, password_hash, max_hours_per_week, is_active)
    VALUES (biz_id, 'John Smith', 'john@acme.com', '+61400333444', 
            '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 40, true)
    ON CONFLICT (business_id, phone) DO UPDATE SET password_hash = EXCLUDED.password_hash
    RETURNING id INTO inst_id;
    
    -- Create test student
    INSERT INTO students (business_id, phone, name, otp_verified)
    VALUES (biz_id, '+61400111222', 'Alice Johnson', true)
    ON CONFLICT (business_id, phone) DO NOTHING
    RETURNING id INTO student_id;
    
    -- Get lesson type
    SELECT id INTO lesson_type_id FROM lesson_types WHERE business_id = biz_id LIMIT 1;
    
    -- Create test booking
    INSERT INTO lessons (business_id, lesson_type_id, instructor_id, student_id, scheduled_at, status, deposit_paid)
    VALUES (biz_id, lesson_type_id, inst_id, student_id, CURRENT_DATE + INTERVAL '2 days' + TIME '10:00:00', 'confirmed', true);
    
    RAISE NOTICE 'Demo data created successfully!';
END $$;
```

Save as `backend/migrations/demo_data.sql` and run:

```bash
psql -U postgres -d driving_school < backend/migrations/demo_data.sql
```

---

## 🎯 Next Steps

### 1. Update Frontend (portal.html)

The endpoints are ready! Now update `portal.html` to use them:

```javascript
// Remove demo mode override
const CONFIG = {
    FORCE_BUSINESS_SLUG: null,  // Let it auto-detect
    // ...
};

// Update login function to use real API
async function handleLogin() {
    const response = await apiCall('/api/portal/login', 'POST', {
        phone: phone,
        password: password
    });
    // ... handle response
}
```

### 2. Test End-to-End

1. Start backend: `php -S localhost:8001`
2. Start frontend: `python server.py` (in frontend folder)
3. Open: `http://localhost:8000/acme-driving/portal.html`
4. Login with: `+61400333444` / `demo123`
5. Test all features!

### 3. Security Hardening

For production:
- [ ] Disable `/api/portal/register` or add admin-only check
- [ ] Add JWT token authentication
- [ ] Add rate limiting
- [ ] Use HTTPS only
- [ ] Add audit logging

---

## 📚 Documentation

- **Full API Docs:** `backend/PORTAL_ENDPOINTS.md`
- **Frontend Update:** `frontend/PORTAL_API_UPDATE_SUMMARY.md`
- **Testing:** Use `backend/tests/api_test_page.html`

---

## ✅ Verification

Run this to verify everything is set up:

```bash
# Check database
psql -U postgres -d driving_school -c "SELECT column_name FROM information_schema.columns WHERE table_name='instructors' AND column_name='password_hash';"

# Should return: password_hash

# Check instructor with password
psql -U postgres -d driving_school -c "SELECT name, phone, (password_hash IS NOT NULL) as has_password FROM instructors WHERE phone='+61400333444';"

# Should show: John Smith | +61400333444 | t

# Test API
curl -X POST http://localhost:8001/acme-driving/api/portal/login \
  -H "Content-Type: application/json" \
  -d '{"phone": "+61400333444", "password": "demo123"}'

# Should return success with user object
```

---

**All set!** 🎉 Your portal endpoints are ready to use!

