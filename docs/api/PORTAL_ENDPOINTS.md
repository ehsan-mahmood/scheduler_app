# Portal API Endpoints

Complete API documentation for the Portal (instructor/admin interface) with multi-tenant support.

## 🎯 Overview

All endpoints require the business slug in the URL: `/{business_slug}/api/...`

**Base URL:** `http://localhost:8001/{business}/api/...`

**Example:** `http://localhost:8001/acme-driving/api/portal/login`

---

## 🔐 Authentication Endpoints

### 1. Portal Login

**Endpoint:** `POST /{business}/api/portal/login`

**Description:** Login for instructors/admins with phone + password

**Request Body:**
```json
{
  "phone": "+61400333444",
  "password": "demo123"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Login successful",
  "user": {
    "id": "uuid",
    "name": "John Smith",
    "email": "john@example.com",
    "phone": "+61400333444",
    "role": "instructor",
    "is_active": true
  }
}
```

**Error Response (400):**
```json
{
  "success": false,
  "message": "Invalid credentials"
}
```

**Notes:**
- Password verified using `password_verify()` against `password_hash` column
- Only active instructors can login
- Password hash never returned in response

---

### 2. Portal Registration

**Endpoint:** `POST /{business}/api/portal/register`

**Description:** Register a new portal user (instructor/admin)

**⚠️ Security Note:** In production, this should be admin-only or removed entirely.

**Request Body:**
```json
{
  "name": "Jane Doe",
  "phone": "+61400777888",
  "email": "jane@example.com",
  "password": "securePassword123",
  "role": "instructor"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Registration successful",
  "user": {
    "id": "uuid",
    "name": "Jane Doe",
    "phone": "+61400777888",
    "email": "jane@example.com",
    "role": "instructor"
  }
}
```

**Notes:**
- Password hashed using `password_hash()` with `PASSWORD_DEFAULT`
- Phone number must be unique per business
- Email is optional
- Default role is "instructor"

---

## 👥 Student Management

### 3. Get Students List

**Endpoint:** `GET /{business}/api/portal/students`

**Description:** Get all students for this business with lesson statistics

**Query Parameters:**
- `search` (optional) - Search by name or phone (case-insensitive)
- `limit` (optional, default: 100) - Number of results per page
- `offset` (optional, default: 0) - Pagination offset

**Example Request:**
```
GET /acme-driving/api/portal/students?search=alice&limit=20&offset=0
```

**Success Response (200):**
```json
{
  "success": true,
  "students": [
    {
      "id": "uuid",
      "name": "Alice Johnson",
      "phone": "+61400111222",
      "otp_verified": true,
      "created_at": "2026-01-01 10:00:00",
      "total_lessons": 15,
      "completed_lessons": 12,
      "last_lesson_date": "2026-01-15 14:00:00"
    }
  ],
  "total": 42,
  "limit": 20,
  "offset": 0
}
```

**Notes:**
- Returns aggregated lesson statistics for each student
- Search is case-insensitive and matches partial names/phones
- Useful for instructor's "Students" page

---

## 📅 Booking Management

### 4. Get All Bookings

**Endpoint:** `GET /{business}/api/portal/bookings`

**Description:** Get all bookings/lessons for this business with filters

**Query Parameters:**
- `status` (optional) - Filter by status: `pending_deposit`, `confirmed`, `in_progress`, `completed`, `cancelled`
- `instructor_id` (optional) - Filter by instructor UUID
- `student_id` (optional) - Filter by student UUID
- `start_date` (optional) - Filter from date (YYYY-MM-DD)
- `end_date` (optional) - Filter to date (YYYY-MM-DD)
- `limit` (optional, default: 100) - Number of results
- `offset` (optional, default: 0) - Pagination offset

**Example Request:**
```
GET /acme-driving/api/portal/bookings?status=confirmed&start_date=2026-01-01&limit=50
```

**Success Response (200):**
```json
{
  "success": true,
  "bookings": [
    {
      "id": "lesson-uuid",
      "scheduled_at": "2026-01-15 10:00:00",
      "status": "confirmed",
      "notes": "Student is progressing well",
      "deposit_paid": true,
      "student_id": "student-uuid",
      "student_name": "Alice Johnson",
      "student_phone": "+61400111222",
      "instructor_id": "instructor-uuid",
      "instructor_name": "John Smith",
      "instructor_phone": "+61400333444",
      "lesson_type": "Basic Driving",
      "duration_minutes": 60,
      "price": 80,
      "deposit_id": "deposit-uuid",
      "payment_status": "confirmed",
      "payid_reference": "PAY123456",
      "created_at": "2026-01-01 12:00:00"
    }
  ],
  "limit": 50,
  "offset": 0
}
```

**Notes:**
- Returns comprehensive booking information with student, instructor, and payment details
- Supports multiple filters that can be combined
- Results ordered by `scheduled_at` DESC (most recent first)

---

### 5. Cancel Booking

**Endpoint:** `POST /{business}/api/booking/cancel`

**Description:** Cancel a booking/lesson

**Request Body:**
```json
{
  "booking_id": "lesson-uuid",
  "reason": "Student requested reschedule"
}
```

**Success Response (200):**
```json
{
  "success": true,
  "message": "Booking cancelled successfully"
}
```

**Error Responses:**
- `400` - Booking not found, already cancelled, or cannot cancel completed lesson

**Notes:**
- Updates lesson status to `cancelled`
- Checks cancellation policy (default: 24 hours notice)
- Appends cancellation note to lesson notes with timestamp
- Reason is optional but recommended

---

## 🔧 Database Setup

### Required Migration

Run this SQL to add password support to instructors:

```bash
psql -U your_user -d driving_school < backend/migrations/003_add_password_to_instructors.sql
```

Or manually:

```sql
-- Add password_hash column
ALTER TABLE instructors 
ADD COLUMN IF NOT EXISTS password_hash VARCHAR(255) DEFAULT NULL;

-- Add index for faster lookups
CREATE INDEX IF NOT EXISTS idx_instructors_phone_business 
ON instructors(business_id, phone) 
WHERE is_active = true;
```

---

## 🧪 Testing with cURL

### Test Portal Login
```bash
curl -X POST http://localhost:8001/acme-driving/api/portal/login \
  -H "Content-Type: application/json" \
  -d '{"phone": "+61400333444", "password": "demo123"}'
```

### Test Get Students
```bash
curl http://localhost:8001/acme-driving/api/portal/students?limit=10
```

### Test Get All Bookings
```bash
curl "http://localhost:8001/acme-driving/api/portal/bookings?status=confirmed&limit=20"
```

### Test Cancel Booking
```bash
curl -X POST http://localhost:8001/acme-driving/api/booking/cancel \
  -H "Content-Type: application/json" \
  -d '{"booking_id": "your-lesson-uuid", "reason": "Student requested reschedule"}'
```

---

## 🔗 Related Endpoints

### Public Booking Endpoints
- `GET /api/instructors` - Get instructors list
- `GET /api/lesson-types` - Get lesson types
- `GET /api/availability` - Check time slot availability
- `POST /api/book-lesson` - Book a lesson (student)

### Admin Endpoints
- `GET /api/admin/dashboard` - Get admin dashboard stats
- `GET /api/admin/pending-deposits` - Get pending deposits
- `POST /api/admin/verify-deposit` - Approve/reject deposit

---

## ✅ Summary

**5 Portal Endpoints:**
1. ✅ `POST /api/portal/login` - Portal authentication
2. ✅ `POST /api/portal/register` - Portal registration
3. ✅ `GET /api/portal/students` - Students list with stats
4. ✅ `GET /api/portal/bookings` - All bookings with filters
5. ✅ `POST /api/booking/cancel` - Cancel booking

**All endpoints:**
- ✅ Support multi-tenant (business slug in URL)
- ✅ Include proper error handling
- ✅ Return consistent response format
- ✅ Use prepared statements (SQL injection safe)
- ✅ Respect business-specific configurations

