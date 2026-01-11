# Frontend Redesign Spec — `portal.html` (Role-based Login + Dashboards)

## Goal
Redesign `frontend/portal.html` into a **role-based portal** where **customers (students)**, **instructors**, and **admin/owner** log in (non-OTP) and see a **dashboard + features** specific to their role.

This portal is separate from the **public booking** experience in `driving_school_app.html`.

---

## Relationship to Public Booking
- **Public Booking (`driving_school_app.html`)**:
  - Browse availability without login
  - Select slot without login
  - Confirm booking triggers identity step (OTP or portal login)
- **Portal (`portal.html`)**:
  - Always behind login
  - Used for: managing bookings, schedules, approvals, settings

### Booking → Portal handoff (recommended)
When a customer clicks “Confirm booking” on the public booking page:
- If already logged in (portal session exists): proceed to confirm booking
- If not logged in:
  - Offer **Login** or **Create Account**
  - After success, return to booking confirmation (preserve selected slot/hold via URL or storage)

---

## Roles & Top-level Capabilities
### Customer / Student
- View upcoming/past lessons
- Pay deposit / upload reference (if required)
- Reschedule / cancel (policy-dependent)
- Profile (name, phone, notes)

### Instructor
- Weekly schedule (calendar/list)
- Confirm lesson start (OTP entry if still used for in-lesson verification)
- Mark lesson complete + notes
- Availability management (working hours / blocks) — if you want instructors to manage their own hours

### Admin / Owner
- Dashboard metrics
- Approve deposits
- Manage instructors (CRUD)
- Manage lesson types (CRUD)
- View all bookings, overrides, reporting

---

## Authentication Model (Non-OTP)
### Login identifiers
Choose one primary identifier (recommendation):
- **Phone + password** (consistent with public booking identity)
- Or **Email + password** (common portal pattern)

### Registration rules
- **Customers** can self-register.
- **Instructors/Admin** should be created by admin (invite flow) or seeded; they can set password via “Set password” link (optional).

### Sessions
- Store session token in **httpOnly cookie** (best) or **localStorage** (MVP).
- Portal should read session on load and route to the correct dashboard automatically.

### Role-based access
- The **role selector on login UI is optional**:
  - If backend determines role from credentials, you can remove role selector.
  - Keep role selector only if needed for demo/testing.
- Hard requirement: after login, **UI and API calls must enforce role**.

### Forgot password
- Non-OTP requirement means you need some recovery:
  - Email reset link, or
  - Admin-assisted reset (MVP), or
  - SMS reset (OTP-like) (only if you allow it later)

---

## Information Architecture (IA)
### Global structure
Portal layout for authenticated users:
- Top header: logo, role badge, user menu
- Left sidebar (desktop) / bottom nav (mobile): role-specific navigation
- Main content: page content

### Routes / Pages (recommended)
#### Shared (all roles)
- `/portal/login`
- `/portal/register` (customers only)
- `/portal/forgot-password`
- `/portal/logout`
- `/portal/profile`

#### Customer
- `/portal/student/dashboard`
- `/portal/student/lessons` (upcoming + history)
- `/portal/student/payments` (deposit status + reference submit)

#### Instructor
- `/portal/instructor/dashboard`
- `/portal/instructor/schedule` (week view + day list)
- `/portal/instructor/students` (student list + search)
- `/portal/instructor/student/:id` (detail drawer/page: history + notes)
- `/portal/instructor/availability` (working hours + blocks) (optional for v1)
- `/portal/instructor/lesson/:id` (notes, status transitions)

#### Admin
- `/portal/admin/dashboard`
- `/portal/admin/bookings`
- `/portal/admin/deposits`
- `/portal/admin/instructors`
- `/portal/admin/lesson-types`
- `/portal/admin/settings` (optional)

---

## Screen Specs
### 1) Login screen
- Inputs:
  - phone/email
  - password
- CTA:
  - Login
- Secondary:
  - Register (customers)
  - Forgot password

### 2) Customer registration screen
- Inputs:
  - full name
  - phone/email
  - password + confirm
- CTA:
  - Create account

### 3) Dashboard patterns (all roles)
Each dashboard should have:
- **Summary cards** (counts, next lesson, pending actions)
- A **primary table/list** (most important items)
- A clear **primary action** button

#### Customer dashboard (minimum)
Summary cards:
- Next lesson (date/time)
- Pending deposit count
Primary table:
- Upcoming lessons
Primary actions:
- “Book a lesson” (links to public booking page but preserves login)
- “Submit deposit”

#### Instructor dashboard (minimum)
Summary cards:
- Today’s lessons
- This week
Primary table:
- Upcoming schedule
Primary actions:
- “Open weekly schedule”

#### Instructor “Today” execution view (recommended)
To support day-of-lesson workflow:
- A “Today’s Lessons” list/table with per-row actions:
  - **Call** (`tel:` link)
  - **SMS** (`sms:` link)
  - **Start lesson** → opens OTP modal (verify before marking in-progress)
- A sticky **Current Lesson dock** (Design B):
  - Student name + phone + Call/SMS
  - Current status (scheduled / otp_pending / in_progress / completed)
  - OTP modal entry point (when starting)
  - **Notes** editor (see below) and optional “End lesson”

#### Instructor notes (per lesson)
Notes are primarily **per lesson** (most accurate), editable:
- During lesson (live notes)
- After lesson (finalize)
UX requirements:
- Autosave on blur and periodic autosave (e.g., every 2–5 seconds while typing)
- “Saving…” / “Saved” indicator (simple)
UI placement (recommended):
- Notes are editable in the **Current Lesson dock** (or a lesson detail drawer opened from the dock)

#### Admin dashboard (minimum)
Summary cards:
- Pending deposits
- Total bookings (7 days)
- Utilization snapshot (optional)
Primary table:
- Pending deposits / bookings needing attention
Primary actions:
- “Review deposits”
- “Add instructor”

---

## UI Components (Recommended)
### Navigation
- Sidebar items render based on `currentUser.role`.
- Active route highlighted.

### Tables
Reusable table component style:
- filters (date range, status, instructor)
- sortable columns
- empty states (“No bookings yet”)

### Status badges
Reuse the same status color semantics across portal + public:
- Confirmed = green
- Pending deposit = yellow
- Cancelled = gray/red (pick one)

### Toasts + modals
Standard:
- Success/error toasts
- Confirm modals for destructive actions (cancel lesson, deactivate instructor)

---

## Data & API Needs (Portal)
Keep endpoints aligned with backend capabilities; names can be adjusted.

### Auth
- `POST /auth/login` → returns user + session token
- `POST /auth/register` (customer only)
- `POST /auth/logout`
- `GET /auth/me` (session introspection)

### Customer
- `GET /portal/student/lessons`
- `POST /portal/student/cancel`
- `POST /portal/student/reschedule`
- `POST /portal/student/submit-deposit`

### Instructor
- `GET /portal/instructor/schedule?from=...&to=...`
- `POST /portal/instructor/lesson/start`
- `POST /portal/instructor/lesson/complete`
- `POST /portal/instructor/lesson/note`
- `GET/POST /portal/instructor/availability` (optional)

#### Instructor “Start lesson + OTP” (API expectation)
To implement “Start lesson requires student OTP”:
- `POST /portal/instructor/lesson/request-start`
  - Body: lessonId
  - Response: { challengeId, expiresAt } (optional)
- `POST /portal/instructor/lesson/verify-otp`
  - Body: lessonId, otp (or challengeId + otp)
  - Response: lesson status updated to `in_progress`

> The customer OTP can be generated earlier (e.g., on booking confirm) and delivered by SMS/app. Instructor verifies it at start time.

#### Instructor students
- `GET /portal/instructor/students?query=...&limit=...&cursor=...`
  - Returns students the instructor has taught (or is assigned to), with summary fields:
    - id, name, phone
    - totalLessons
    - lastLessonAt
    - lastNotePreview (optional)
    - status (optional; see below)
- `GET /portal/instructor/students/:id`
  - Returns student detail + history:
    - student profile fields
    - lesson history list (date, type, status, notes)
- `POST /portal/instructor/students/:id/note` (optional)
  - Student-level note for preferences/context (not tied to one lesson)

### Admin
- `GET /portal/admin/bookings`
- `GET /portal/admin/deposits`
- `POST /portal/admin/deposit/approve`
- `POST /portal/admin/deposit/reject`
- `GET/POST/PUT/DELETE /portal/admin/instructors`
- `GET/POST/PUT/DELETE /portal/admin/lesson-types`

---

## Integration With Public Booking (Deep-linking)
To preserve a selected slot/hold when redirecting to portal login:
- Store context as:
  - URL params (preferred): `portal.html?next=/confirm&holdId=...`
  - or `sessionStorage` key: `pendingBookingContext`

After login:
- If `pendingBookingContext` exists, redirect user back to public flow to finish confirm,
  or confirm inside portal (your choice).

---

## MVP Decisions (Pick now)
1) **Identifier**: phone+password vs email+password
2) **Role selection on login**: keep for demo vs remove for production
3) **Instructor availability editing**: v1 yes/no
4) **Forgot password**: admin-reset vs email reset

---

## Acceptance Criteria
- Portal requires login to access any dashboard.
- After login, user only sees navigation and pages for their role.
- Customer can view lessons and manage booking-related actions.
- Instructor can view schedule and manage lesson workflow.
- Instructor has a **Students** page with searchable list and per-student history (lessons + notes).
- Admin can review deposits and manage instructors/lesson types.
- Portal can receive a “booking context” from public booking and route back after login.

---

## Instructor “Students” UX Spec (Detailed)
### Purpose
Give instructors fast access to **historical records per student**:
- lessons taken
- lesson notes
- lesson counts
- (optional) student status

### Navigation
Add sidebar item for instructors:
- **Students**

### Students list (table)
Must be:
- Search-first (name/phone)
- Fast to scan
- Mobile-friendly (rows collapse to cards)

Recommended columns:
- **Student** (name + small phone)
- **Call/SMS** quick actions
- **Lessons** (count)
- **Last lesson** (date)
- **Last note** (1-line preview, optional)
- **Status** (optional)

Row click:
- Opens Student detail (drawer on desktop, full page on mobile)

### Student detail (drawer/page)
Header:
- Student name
- Phone + Call/SMS
Summary chips:
- Total lessons
- Last lesson date
- Next booked lesson (if any)
Tabs/sections:
- **Lesson history** table:
  - date/time
  - lesson type
  - status
  - notes (preview + expand)
- Optional: **Student note** (general context not tied to a lesson)

### Student Status (optional)
Only include status if it’s meaningful and low-maintenance.
Suggested status values:
- **Active** (has upcoming lesson OR last lesson within X days)
- **Needs follow-up** (manual flag)
- **At risk** (computed: repeated cancellations/no-shows)
- **Completed** (manual/computed milestone)

If status is included, define:
- Who can edit it (instructor vs admin)
- Whether it’s computed or manual


