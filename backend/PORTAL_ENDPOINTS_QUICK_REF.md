# Portal Endpoints - Quick Reference Card

## 🚀 5 New Endpoints Added

| # | Method | Endpoint | Purpose |
|---|--------|----------|---------|
| 1 | POST | `/api/portal/login` | Login with phone + password |
| 2 | POST | `/api/portal/register` | Register new portal user |
| 3 | GET | `/api/portal/students` | List all students + stats |
| 4 | GET | `/api/portal/bookings` | List all bookings + filters |
| 5 | POST | `/api/booking/cancel` | Cancel a booking |

---

## ⚡ Quick Setup (30 seconds)

```bash
# 1. Run migration
psql -U postgres -d driving_school < backend/migrations/003_add_password_to_instructors.sql

# 2. Test login
curl -X POST http://localhost:8001/acme-driving/api/portal/login \
  -H "Content-Type: application/json" \
  -d '{"phone": "+61400333444", "password": "demo123"}'

# Done! ✅
```

---

## 📖 Usage Examples

### Login
```bash
curl -X POST http://localhost:8001/acme-driving/api/portal/login \
  -H "Content-Type: application/json" \
  -d '{"phone": "+61400333444", "password": "demo123"}'
```

### Get Students
```bash
curl http://localhost:8001/acme-driving/api/portal/students?search=alice&limit=20
```

### Get Bookings
```bash
curl "http://localhost:8001/acme-driving/api/portal/bookings?status=confirmed&start_date=2026-01-01"
```

### Cancel Booking
```bash
curl -X POST http://localhost:8001/acme-driving/api/booking/cancel \
  -H "Content-Type: application/json" \
  -d '{"booking_id": "uuid-here", "reason": "Student requested"}'
```

### Register User
```bash
curl -X POST http://localhost:8001/acme-driving/api/portal/register \
  -H "Content-Type: application/json" \
  -d '{"name": "Jane Doe", "phone": "+61400777888", "password": "secure123"}'
```

---

## 🔑 Demo Credentials

- **Phone:** `+61400333444`
- **Password:** `demo123`

---

## 📚 Full Documentation

- **Complete API Docs:** `backend/PORTAL_ENDPOINTS.md`
- **Setup Guide:** `backend/PORTAL_SETUP_GUIDE.md`
- **Summary:** `backend/PORTAL_ENDPOINTS_SUMMARY.md`

---

## ✅ Status

All endpoints are:
- ✅ Implemented
- ✅ Multi-tenant
- ✅ Documented
- ✅ Ready to use

**Just run the migration and test!** 🎉

