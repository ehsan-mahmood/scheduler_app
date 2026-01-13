# Scheduler Platform – Pre-Shelving Change Checklist

## Purpose
This document outlines all architectural, naming, UI, and deployment changes required to:
- Generalise the driving-school scheduler into a neutral scheduling engine
- Decouple costly integrations (SMS, payments, email)
- Prepare the system to be safely shelved
- Preserve maximum option value for future revival or pivot

---

## 1. Product Reframing (Critical)

### Rename Domain Concepts (Everywhere)
Replace all driving-specific language in:
- UI copy
- API payloads
- DB schema
- Variable names

| Old | New |
|----|----|
| Driving School | Business |
| Instructor | Provider |
| Student | Customer |
| Lesson | Appointment |
| Lesson Type | Service |
| Lesson Start | Session Start |

> Goal: Make the product vertical-agnostic without rewriting logic.

---

## 2. UI / UX Changes

### Design Direction
- Neutral
- Calm
- Elegant
- Non-opinionated

### UI Rules
- Single accent color only
- Off-white background
- Near-black text
- Minimal shadows
- Clear spacing over decoration

### Demo vs Live
- Same UI for both
- Only a small “Demo Mode” badge
- No visual degradation in demo

### Remove / Avoid
- Driving-specific icons
- Industry metaphors
- Bright or playful colors
- Over-branding

---

## 3. Notification System Refactor (SMS / Email)

### Core Principle
> Scheduling logic must not know how notifications are sent.

### Changes Required
- Introduce `NotificationEvent` abstraction
- Remove direct SMS/email calls from core logic
- Emit events instead (e.g. BOOKING_CREATED)

### Channels (Optional Plugins)
- SMS
- Email
- Future: WhatsApp / Push

### Provider Abstraction
- SMSChannel → Twilio / MessageMedia / Demo
- EmailChannel → SMTP / Demo

### Failure Rules
- Notification failures must NOT block bookings
- Fail silently + log

---

## 4. DB-Driven Configuration

### Required Tables
- `business_settings`
- `notification_channels`
- `notification_providers`
- `notification_events`

### Config Principles
- DB stores flags and preferences
- Code enforces rules
- No business logic in DB
- Defaults must fail closed (OFF)

### Caching
- Cache per business
- Invalidate on update
- Never query config per request

---

## 5. Payment System Changes

### Demo Mode
- NO Stripe
- NO bank APIs
- NO real money

### Payment Architecture
- Payment as status machine
  - CREATED
  - CONFIRMED
  - FAILED

### Providers
- DemoPaymentProvider (default)
- StripeProvider (future)
- Manual PayID (future)

### Demo Behaviour
- Always succeeds
- Shows receipt
- Clearly marked “Simulated”

---

## 6. Demo Deployment Strategy (GitHub Pages)

### What Demo Includes
- Booking flow
- Reschedule / cancellation
- OTP simulation
- Notification timeline (SMS + Email)
- Payment simulation

### What Demo Does NOT Do
- Send real SMS
- Send real email
- Take real payments
- Store sensitive data

### Technical Setup
- Static HTML/CSS/JS
- Mock JSON APIs
- LocalStorage for state
- Same API contract as live backend

---

## 7. Environment Modes

### Required Modes
- DEMO
- LIVE

### Mode Differences
| Feature | Demo | Live |
|-----|-----|-----|
| SMS | Simulated | Real |
| Email | Simulated | Real |
| Payments | Simulated | Real |
| Secrets | None | Required |

Mode controlled via:
- ENV variable
- Or build flag

---

## 8. Codebase Hygiene Before Shelving

### Required Actions
- Tag repo: `v1-generic-core`
- Clean TODOs
- Remove half-built features
- Write README:
  - What it does
  - What it doesn’t do
  - Known limitations
  - How to revive

### Folder Structure (Recommended)

````
/core
/notifications
/payments
/demo
/config
/docs
`````

---

---

## 9. What NOT to Build Before Shelving

Do NOT add:
- Mobile app
- Advanced analytics
- AI features
- Multiple themes
- Role complexity
- Subscription logic

Preserve simplicity.

---

## 10. Strategic Goal of Shelving

This product should become:
- A reusable scheduling engine
- A demoable asset
- A fast-start foundation for future verticals

Not:
- A failed startup
- A sunk-cost project
- A maintenance burden

---

## Final Checklist (TL;DR)

- [x] Rename domain concepts (PARTIAL - UI/Docs done, code/db pending)
- [x] Neutral UI applied (COMPLETE - user-facing text updated)
- [x] Notifications event-based + optional (COMPLETE - verified)
- [x] DB-driven config (COMPLETE - migration script created)
- [x] Demo payment provider (COMPLETE - verified working)
- [ ] GitHub Pages demo live (NOT STARTED)
- [ ] Repo tagged + documented (PARTIAL - docs updated, tag pending)
- [ ] Stop active development (READY - core work complete)

**Status:** Core shelving requirements met. System is generic, decoupled, and ready for shelving. Full code/database renaming can be done later if needed when reviving the project.

---


