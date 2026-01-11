# Testing the Backend API

This guide shows you how to test all the backend API endpoints.

## Step 1: Start the Backend Server

### Option 1: Using Batch File (Windows)
Double-click `start-server.bat` in the backend folder.

### Option 2: Using Command Line
```bash
cd backend
php -S localhost:8001 router.php
```

The server will start and you should see:
```
PHP 8.x.x Development Server (http://localhost:8001) started
```

**Keep this window open** - the server runs in this terminal.

## Step 2: Test the API

You can test the API using:
- **curl** (command line)
- **Postman** (GUI tool)
- **Browser** (for GET requests)
- **HTML Test Page** (see below)

## API Endpoints

Base URL: `http://localhost:8001`

### 1. Register Student (POST /register)

**Request:**
```bash
curl -X POST http://localhost:8001/register \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"0412345678\"}"
```

**Expected Response:**
```json
{
    "success": true,
    "message": "OTP sent to phone",
    "student_id": "67890abcdef12345",
    "otp": "123456",
    "messageId": "xxx",
    "receivingId": "xxx"
}
```

**Note:** OTP is returned in response for testing (remove in production).

---

### 2. Verify OTP (POST /verify-otp)

**Request:**
```bash
curl -X POST http://localhost:8001/verify-otp \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"0412345678\",\"otp\":\"123456\"}"
```

Use the OTP from the register response.

**Expected Response:**
```json
{
    "success": true,
    "message": "OTP verified successfully",
    "messageId": "xxx",
    "receivingId": "xxx"
}
```

---

---

### 4. Book Lesson (POST /book-lesson)

**Request:**
```bash
curl -X POST http://localhost:8001/book-lesson \
  -H "Content-Type: application/json" \
  -d "{\"phone\":\"0412345678\",\"lesson_type\":\"1\",\"instructor\":\"1\",\"date\":\"2024-01-15\",\"time\":\"10:00\"}"
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Lesson booked successfully",
    "lesson_id": "xxx",
    "messageId": "xxx",
    "receivingId": "xxx"
}
```

---

### 5. Submit Deposit (POST /submit-deposit)

**Request:**
```bash
curl -X POST http://localhost:8001/submit-deposit \
  -H "Content-Type: application/json" \
  -d "{\"lesson_id\":\"xxx\",\"payid_reference\":\"REF123456\"}"
```

Use the lesson_id from the book-lesson response.

**Expected Response:**
```json
{
    "success": true,
    "message": "Deposit reference submitted",
    "messageId": "xxx",
    "receivingId": "xxx"
}
```

---

### 6. Get Dashboard (GET /dashboard)

**Request:**
```bash
curl http://localhost:8001/dashboard
```

Or open in browser:
```
http://localhost:8001/dashboard
```

**Expected Response:**
```json
{
    "success": true,
    "stats": {
        "total_lessons": 5,
        "pending_deposits": 2,
        "confirmed_lessons": 3
    },
    "messageId": "xxx",
    "receivingId": "xxx"
}
```

---

### 7. Verify Deposit (POST /verify-deposit)

**Request:**
```bash
curl -X POST http://localhost:8001/verify-deposit \
  -H "Content-Type: application/json" \
  -d "{\"deposit_id\":\"xxx\",\"status\":\"approved\"}"
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Deposit verified",
    "messageId": "xxx",
    "receivingId": "xxx"
}
```

---

### 8. Manage Instructor (POST /manage-instructor)

**Add Instructor:**
```bash
curl -X POST http://localhost:8001/manage-instructor \
  -H "Content-Type: application/json" \
  -d "{\"name\":\"John Doe\",\"phone\":\"0412345678\",\"max_hours\":40}"
```

**Expected Response:**
```json
{
    "success": true,
    "message": "Instructor added",
    "instructor_id": "xxx",
    "messageId": "xxx",
    "receivingId": "xxx"
}
```

---

## Quick Test Sequence

Here's a complete test flow:

```bash
# 1. Register a student
curl -X POST http://localhost:8001/register -H "Content-Type: application/json" -d "{\"phone\":\"0412345678\"}"

# 2. Verify OTP (use the OTP from step 1)
curl -X POST http://localhost:8001/verify-otp -H "Content-Type: application/json" -d "{\"phone\":\"0412345678\",\"otp\":\"123456\"}"

# 3. Book a lesson
curl -X POST http://localhost:8001/book-lesson -H "Content-Type: application/json" -d "{\"phone\":\"0412345678\",\"lesson_type\":\"1\",\"instructor\":\"1\",\"date\":\"2024-01-15\",\"time\":\"10:00\"}"

# 4. Check dashboard
curl http://localhost:8001/dashboard
```

## Using Postman

1. **Install Postman** (if not already installed): https://www.postman.com/downloads/

2. **Create a new request:**
   - Method: POST (or GET)
   - URL: `http://localhost:8001/register`
   - Headers: `Content-Type: application/json`
   - Body: Select "raw" and "JSON", then paste your JSON

3. **Save requests** in a collection for easy testing

## Troubleshooting

**Server won't start:**
- Make sure PHP is installed: `php -v`
- Check if port 8001 is already in use
- Try a different port: `php -S localhost:8002 router.php`

**Connection refused:**
- Make sure the server is running
- Check the URL: `http://localhost:8001` (not `https://`)

**404 Not Found:**
- Make sure you're using the correct endpoint paths
- Check that `router.php` exists in the backend folder

**500 Internal Server Error:**
- Check the server terminal for error messages
- Make sure the `data/` folder exists and is writable

## Viewing Data

The backend stores data in JSON files in the `data/` folder:
- `data/students.json` - Student records
- `data/lessons.json` - Lesson bookings
- `data/deposits.json` - Deposit records
- `data/instructors.json` - Instructor data

You can view these files to see what data is being stored.

