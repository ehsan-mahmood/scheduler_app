# Driving School Backend API

Simple PHP API for the Driving School Scheduler application.

## Quick Start

### Option 1: Using the Batch Script (Windows)

Run the start script from the `scripts/` directory:
```bash
scripts\start-server.bat
```

Or from the backend root directory:
```bash
.\scripts\start-server.bat
```

### Option 2: Using Command Line

Open Command Prompt in this directory and run:

```bash
php -S localhost:8001 router.php
```

The API will be available at: **http://localhost:8001**

## API Endpoints

### Student Endpoints

- `POST /register` - Register student and send OTP
  - Body: `{ "phone": "1234567890" }`

- `POST /verify-otp` - Verify OTP
  - Body: `{ "phone": "1234567890", "otp": "123456" }`

- `POST /book-lesson` - Book a lesson
  - Body: `{ "lesson_type": "1", "instructor": "1", "date": "2024-01-15", "time": "10:00" }`

- `POST /submit-deposit` - Submit PayID deposit reference
  - Body: `{ "payid_reference": "REF123456" }`

### Admin Endpoints

- `GET /dashboard` - Get dashboard statistics

- `POST /verify-deposit` - Verify a deposit
  - Body: `{ "deposit_id": "xxx", "status": "approved" }`

- `POST /manage-instructor` - Add instructor
  - Body: `{ "name": "John Doe", "phone": "1234567890", "max_hours": 40 }`

## Data Storage

Currently uses file-based JSON storage in the `data/` directory. This is perfect for MVP/testing.

Files created:
- `data/students.json`
- `data/lessons.json`
- `data/deposits.json`
- `data/instructors.json`

## Notes

- CORS is enabled for all origins (for development)
- All responses include `messageId` and `receivingId` for consistency
- OTP is returned in response for testing (remove in production)
- For production, you'll want to:
  - Add database (MySQL/PostgreSQL)
  - Add authentication/authorization
  - Implement real SMS sending
  - Add input validation and sanitization
  - Remove test OTP from responses

## Stopping the Server

Press `Ctrl+C` in the terminal window where the server is running.

