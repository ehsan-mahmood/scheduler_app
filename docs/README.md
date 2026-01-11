# Documentation Index

Complete documentation for the Driving School Scheduler system.

## 📚 Documentation Structure

```
docs/
├── README.md (this file)
├── setup/          # Setup and installation guides
├── api/            # API documentation
├── features/       # Feature guides
└── troubleshooting/ # Bug fixes and troubleshooting
```

---

## 🚀 Quick Start

### New to the Project?

1. Start here: [Getting Started Guide](setup/GETTING_STARTED.md)
2. Review: [Main README](../../README.md)
3. Explore: [Frontend README](../../frontend/README.md)
4. Explore: [Backend README](../../backend/docs/README.md)

---

## 📖 Setup & Installation

### Setup Guides

- **[Getting Started](setup/GETTING_STARTED.md)** - Complete setup guide
- **[Mobile Access Setup](setup/MOBILE_ACCESS.md)** - Accessing from mobile devices
- **[Database Setup](../../backend/docs/DATABASE_SETUP.md)** - Database configuration

### Requirements

- PHP 7.4+
- Python 3.x
- PostgreSQL (for production)
- Web server (Apache/Nginx for production)

---

## 🔌 API Documentation

### Portal API

- **[Portal Endpoints](api/PORTAL_ENDPOINTS.md)** - Complete Portal API documentation
  - Authentication endpoints
  - Student management
  - Booking management
  - Testing examples

### Public Booking API

See `backend/api_v2.php` for complete API documentation.

---

## ✨ Features

### Core Features

- **[Multi-Tenant Routing](features/MULTI_TENANT_ROUTING.md)** - URL-based business routing
  - How it works
  - Setup instructions
  - Adding new businesses
  - Troubleshooting

- **[Demo Mode](features/DEMO_MODE.md)** - Testing without backend
  - How demo mode works
  - Features available in demo mode
  - Mobile support
  - Turning demo mode off

### Other Features

- Multi-slot booking
- Role-based portals (Student/Instructor/Admin)
- Calendar views (Month/Week)
- OTP authentication
- Deposit management

---

## 🐛 Troubleshooting

### Bug Fixes & Solutions

- **[Bug Fixes](troubleshooting/BUGFIXES.md)** - Consolidated bug fixes
  - Frontend fixes
  - Backend fixes
  - Common issues

### Common Issues

- **Port already in use:** Change ports in server commands
- **PHP not found:** Check PHP is in system PATH
- **CORS errors:** Check backend CORS configuration
- **Database connection:** Verify PostgreSQL is running
- **Demo mode not working:** Check URL format and console logs

---

## 📋 Quick Reference

### Starting Servers

**Both servers:**
```bash
START_BOTH_SERVERS.bat  # Windows
```

**Separately:**
```bash
# Backend
cd backend
php -S 0.0.0.0:8001 router.php

# Frontend
cd frontend
python server.py 8000
```

### Access URLs

**Production:**
- Booking: `http://localhost:8000/acme-driving/driving_school_app.html`
- Portal: `http://localhost:8000/acme-driving/portal.html`

**Demo:**
- Booking: `http://localhost:8000/driving_school_app.html`
- Portal: `http://localhost:8000/portal.html`

---

## 🔗 Related Documentation

### Main Documentation

- [Project README](../../README.md) - Project overview
- [Frontend README](../../frontend/README.md) - Frontend guide
- [Backend README](../../backend/docs/README.md) - Backend guide
- [Full Specifications](../../driving_school_scheduler_specs.md) - Complete specs

### Configuration

- [Multi-Tenant Routing](features/MULTI_TENANT_ROUTING.md) - URL routing
- [Demo Mode](features/DEMO_MODE.md) - Demo mode configuration
- [Mobile Access](setup/MOBILE_ACCESS.md) - Mobile setup

---

## 📝 Documentation Updates

This documentation is organized to reduce redundancy and improve findability. If you find outdated or missing information, please update the relevant documentation files.

### Documentation Structure

- **Setup:** Installation and configuration guides
- **API:** API endpoint documentation
- **Features:** Feature guides and how-tos
- **Troubleshooting:** Bug fixes and problem-solving guides

---

## ✅ Documentation Checklist

- [x] Setup guides consolidated
- [x] API documentation organized
- [x] Feature guides created
- [x] Bug fixes documented
- [x] Main README updated
- [x] Redundant docs removed

---

**Last Updated:** 2026-01-12

