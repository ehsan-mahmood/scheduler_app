# Frontend Redesign Spec — Public Booking Calendar (60-day window)

## Goal
Redesign the frontend so **anyone can view availability and select a slot without logging in**, but **booking confirmation requires login/register (OTP)**.

This is a “public booking link” flow (Calendly-style), supporting **multiple instructors** and **variable lesson durations** (based on lesson type).

## Non-goals (for this redesign doc)
- No payment/deposit UI changes (can remain after booking confirmation or as current flow).
- No full auth/roles implementation beyond “OTP gate to confirm booking”.
- No backend rewrite implied; this is a frontend UX + API contract spec.

---

## Primary User Journey (Customer)
1. **Landing / Booking page (public)**
   - Select **Lesson Type** (defines duration).
   - Select **Instructor** (required).
   - See **Month/Week calendar** for the next **60 days** showing day-level availability status.

2. **Day view (public)**
   - Click a day → show available **time slots** for the selected instructor + lesson type duration.
   - Unavailable days are disabled.

3. **Slot selection (public)**
   - Clicking a slot selects it and updates a “Selected slot” summary panel.
   - No OTP/login yet.

4. **Confirm booking (requires OTP)**
   - Clicking “Continue / Confirm” triggers:
     - (Recommended) backend “HOLD” of the slot for 5–10 minutes.
     - OTP login/register modal.
   - After OTP success → finalize booking (convert HOLD → BOOKING).

---

## Owner/Instructor Perspective
- Availability shown is **computed per instructor** based on:
  - Working hours / availability blocks
  - Breaks / blocked times
  - Existing bookings
  - Holds (temporary locks)

Customers must choose instructor, so availability should reflect that selection.

---

## Key UX Rules
### 60-day booking window
- Calendar only allows selection from **today → today + 60 days** (inclusive).
- Dates outside range should be **disabled**.

### Selection prerequisites
- Calendar availability is computed for the selected:
  - **Instructor**
  - **Lesson Type** (duration)
- If either is missing:
  - Calendar shows a placeholder state: “Select instructor and lesson type to view availability.”

### Login/register gating
- **Viewing and selecting** does not require login.
- Only **Confirm** requires OTP.
- If user cancels OTP modal:
  - Keep slot selection in UI, but release HOLD if it was created.

---

## Calendar Day Statuses (Month/Week overview)
Status is computed for the selected (Instructor, LessonType duration) for each day.

### Definitions
- **Unavailable**
  - No working windows, or day is blocked (holiday/admin block), or outside booking window.
  - Day cell is disabled (not clickable).

- **Fully booked**
  - Has working windows but **0 valid slots** for the selected duration.
  - Day cell can be clickable to show “No slots available” (optional) or disabled.

- **Almost booked**
  - Availability is low based on **% remaining**:
    - \( remainingPercent = availableCapacity / totalCapacity \)
  - Default threshold:
    - **Almost booked** if \( remainingPercent > 0 \) and \( remainingPercent \le 20\% \)

- **Free**
  - \( remainingPercent > 20\% \)

### Capacity calculation (per day)
- **totalCapacity**: number of valid start times *if there were no bookings/holds*, using the instructor’s working windows and selected duration (+ buffer rules).
- **availableCapacity**: number of valid start times after subtracting bookings/holds/blocks.

### UI treatment
- Month/Week view shows:
  - Color-coded day cell by status
  - Optional small indicator: “Low” / “Full” / “Off”
  - Optional tooltip: “3 slots available”

---

## Slot Generation Rules (Day view)
Given:
- Day D
- Instructor I
- LessonType T (durationMinutes)
- Optional bufferMinutes (default 0 for now; add later)

Compute free windows by subtracting:
- Existing bookings
- Holds (not expired)
- Breaks/blocked periods

Generate candidate start times:
- Step size: 15 minutes (recommended) OR 30 minutes (simpler)
- A slot is valid if:
  - start + durationMinutes (+ buffer if needed) fits fully in a free window

---

## UI Components / Sections
### Booking Page (public)
- **Header**: business name + optional “Contact”
- **Selection panel**:
  - Instructor dropdown (required)
  - Lesson type dropdown (required) + display duration/price
- **Calendar view**:
  - Toggle: Month | Week
  - Day cells colored by status
- **Day slots panel**:
  - Appears after selecting a day
  - Shows “Available slots” list/grid
  - Disabled slots if they become invalid (due to a refresh)
- **Selection summary (sticky)**:
  - Instructor, lesson type, date, time, duration, price
  - CTA: “Continue / Confirm”

### OTP Modal (on Confirm)
- Phone input → send OTP
- OTP input → verify
- Success → finalize booking and show confirmation state

---

## API Contract (frontend needs)
The frontend should not calculate availability purely from static HTML; it should fetch computed availability.

### 1) Get instructors
- `GET /instructors`
- Response: list of instructors

### 2) Get lesson types
- `GET /lesson-types`
- Response: list including durationMinutes, price

### 3) Get calendar day statuses (next 60 days)
- `GET /availability/days?instructorId=...&lessonTypeId=...&from=YYYY-MM-DD&to=YYYY-MM-DD`
- Response per day:
  - date
  - status: unavailable | full | almost | free
  - availableCapacity, totalCapacity (optional but useful)

### 4) Get slots for a day
- `GET /availability/slots?instructorId=...&lessonTypeId=...&date=YYYY-MM-DD`
- Response:
  - slots: [{ startISO, endISO, available: true }]

### 5) Hold a slot (created on Confirm click, before OTP)
- `POST /booking/hold`
- Body: instructorId, lessonTypeId, startISO
- Response:
  - holdId
  - expiresAt

### 6) OTP register/login
- `POST /auth/register` or `POST /auth/send-otp`
- `POST /auth/verify-otp`

### 7) Confirm booking (convert hold → booking)
- `POST /booking/confirm`
- Body: holdId, customer info (if needed)
- Response: bookingId, status (pending_deposit/confirmed, etc.)

> Note: If the current backend only supports a subset, the frontend can implement the UX first and map to existing endpoints later. But “hold/confirm” is strongly recommended to avoid double booking.

---

## State Management (frontend)
Store:
- selectedInstructorId
- selectedLessonTypeId (+ duration)
- selectedDate
- selectedSlotStartISO
- holdId (if created)
- isLoggedIn + customerId (after OTP)

Refresh rules:
- Changing instructor or lesson type should:
  - Clear selected date/slot
  - Refetch day statuses for the next 60 days

---

## Edge Cases
- **Slot disappears**: If slot becomes unavailable between selection and confirm:
  - On Confirm: backend rejects hold → show toast “Slot no longer available” and refresh day slots.
- **Hold expired**: If OTP takes too long and hold expires:
  - Booking confirm fails → show prompt to reselect slot.
- **Time zone**: Display in business local time; use ISO in API.
- **Mobile**: Day slots panel should be a bottom sheet or stacked under calendar.

---

## Acceptance Criteria
- User can open booking page and view next **60 days** availability without logging in.
- User must pick **Instructor + Lesson Type** before calendar becomes interactive.
- Calendar indicates: **Unavailable, Fully booked, Almost booked, Free**.
- Clicking a day shows valid slots that fit lesson duration.
- Selecting a slot does not require login.
- Clicking **Confirm** triggers OTP flow; booking is only created after OTP verification.


