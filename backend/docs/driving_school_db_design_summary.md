# Driving School Scheduler – Database Design Summary (PostgreSQL)

## Why PostgreSQL?
PostgreSQL is **not heavy** for this system.

For your scale (≈80 students, 2 instructors, ~500 lessons/month):
- CPU usage: very low
- Memory: < 200MB
- Storage: few MBs

**Why Postgres fits well:**
- Strong relational integrity (important for bookings & payments)
- Excellent time/date handling for schedules
- JSON support for future flexibility
- Easy analytics with SQL
- Cheap managed hosting

---

## Core Design Principles
- Relational-first (avoid NoSQL for scheduling logic)
- Clear ownership of data (student, instructor, lesson)
- Event-driven fields (status-based, not duplicated logic)
- Easy to extend later (packs, progress, multi-schools)

---

## Core Tables (MVP)

### 1. students
Stores customer information.
```sql
students (
  id UUID PRIMARY KEY,
  phone VARCHAR(20) UNIQUE NOT NULL,
  name TEXT,
  otp_verified BOOLEAN DEFAULT false,
  created_at TIMESTAMP
)
```

---

### 2. instructors
Driving instructors.
```sql
instructors (
  id UUID PRIMARY KEY,
  name TEXT NOT NULL,
  max_hours_per_week INT DEFAULT 60,
  active BOOLEAN DEFAULT true,
  created_at TIMESTAMP
)
```

---

### 3. lesson_types
Different lesson offerings.
```sql
lesson_types (
  id UUID PRIMARY KEY,
  name TEXT,
  duration_minutes INT,
  price_cents INT,
  deposit_cents INT
)
```

---

### 4. lessons
Central scheduling table.
```sql
lessons (
  id UUID PRIMARY KEY,
  student_id UUID REFERENCES students(id),
  instructor_id UUID REFERENCES instructors(id),
  lesson_type_id UUID REFERENCES lesson_types(id),
  start_time TIMESTAMP,
  end_time TIMESTAMP,
  status VARCHAR(20), -- pending, confirmed, cancelled, in_progress, completed
  created_at TIMESTAMP
)
```

**Indexes (important):**
- `(instructor_id, start_time)`
- `(student_id, start_time)`

---

### 5. payment_deposits
Tracks PayID deposits.
```sql
payment_deposits (
  id UUID PRIMARY KEY,
  lesson_id UUID REFERENCES lessons(id),
  amount_cents INT,
  payid_reference TEXT,
  status VARCHAR(20), -- pending, confirmed, failed
  matched_at TIMESTAMP
)
```

---

### 6. otp_events
Used for lesson start verification.
```sql
otp_events (
  id UUID PRIMARY KEY,
  lesson_id UUID REFERENCES lessons(id),
  otp_code VARCHAR(10),
  expires_at TIMESTAMP,
  verified BOOLEAN DEFAULT false
)
```

---

### 7. sms_logs (optional but recommended)
For auditing and retries.
```sql
sms_logs (
  id UUID PRIMARY KEY,
  lesson_id UUID,
  phone VARCHAR(20),
  message_type TEXT,
  sent_at TIMESTAMP,
  status TEXT
)
```

---

## Status-Driven Logic (Important)

Avoid extra tables. Use **status fields** instead:
- lesson.status controls lifecycle
- payment_deposits.status controls confirmation
- otp_events.verified controls lesson start

This keeps DB simple and fast.

---

## Analytics (Lean)

Use SQL views or daily aggregate tables:
```sql
daily_stats (
  stat_date DATE,
  lessons_completed INT,
  revenue_cents INT,
  cancellations INT
)
```

Updated via cron job.

---

## Scalability Notes
- This schema supports **10×–20× growth** without changes
- Multi-school support later = add `school_id` column
- No migration needed for packs, progress, or subscriptions

---

## Bottom Line
- PostgreSQL is **lightweight** for this use case
- Strong consistency beats NoSQL for scheduling
- Cheapest long-term option
- Perfect for analytics + payments + bookings

**Recommended choice: PostgreSQL**

