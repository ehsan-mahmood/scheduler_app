# Getting Started Guide

Complete guide to setting up and running the Driving School Scheduler system.

## 🚀 Quick Start

### Easiest Way: Start Both Servers at Once

**Windows:**
Double-click `START_BOTH_SERVERS.bat` - this will start both frontend and backend in separate windows.

### Manual Start

1. **Start Backend API:**
   ```bash
   cd backend
   php -S 0.0.0.0:8001 router.php
   ```

2. **Start Frontend (in a new terminal):**
   ```bash
   cd frontend
   python server.py 8000
   ```

3. **Open in Browser:**
   - Booking App: `http://localhost:8000/acme-driving/driving_school_app.html`
   - Portal: `http://localhost:8000/acme-driving/portal.html`
   - Demo Mode: `http://localhost:8000/driving_school_app.html`

---

## 📋 Requirements

- **PHP 7.4+** (check with `php --version`)
- **Python 3.x** (check with `python --version`)
- **PostgreSQL** (for production database)
- **Web Server** (Apache/Nginx for production)

Both PHP and Python are usually pre-installed on Windows 10/11.

---

## 🔧 Setup Steps

### 1. Database Setup (Production)

See [Database Setup Guide](../backend/docs/DATABASE_SETUP.md) for complete instructions.

**Quick Setup:**
```bash
# Create database
psql -U postgres
CREATE DATABASE driving_school;

# Run migrations
psql -U postgres -d driving_school < backend/sql/multi_tenant_db_schema.sql
```

### 2. Backend Configuration

**Update `backend/config.php`:**
```php
define('STORAGE_MODE', 'database'); // or 'json'
define('DB_HOST', 'localhost');
define('DB_NAME', 'driving_school');
define('DB_USER', 'your_user');
define('DB_PASS', 'your_password');
```

### 3. Frontend Configuration

**Demo Mode (No Backend Required):**
- Access without business slug: `http://localhost:8000/driving_school_app.html`
- Demo mode activates automatically

**Production Mode:**
- Access with business slug: `http://localhost:8000/acme-driving/driving_school_app.html`
- Requires backend server running

### 4. Mobile Access Setup

See [Mobile Access Guide](MOBILE_ACCESS.md) for complete instructions.

**Quick Setup:**
- Backend must listen on all interfaces: `php -S 0.0.0.0:8001`
- Frontend auto-detects API URL from hostname
- Access from mobile: `http://192.168.1.7:8000/acme-driving/driving_school_app.html`

---

## 🧪 Testing

### Test Booking App (Demo Mode)

1. Open: `http://localhost:8000/driving_school_app.html`
2. Should see: Purple "DEMO MODE" banner
3. Select instructor and lesson type
4. Browse calendar and book lessons
5. Use OTP: `123456`

### Test Portal (Demo Mode)

1. Open: `http://localhost:8000/portal.html`
2. Should see: Purple "DEMO MODE" banner
3. Use Quick Login buttons:
   - **Student:** Phone: `+61400111222`, Password: `demo123`
   - **Instructor:** Phone: `+61400333444`, Password: `demo123`
   - **Admin:** Phone: `+61400555666`, Password: `demo123`

### Test Production Mode

1. Start backend: `php -S 0.0.0.0:8001 router.php`
2. Open: `http://localhost:8000/acme-driving/driving_school_app.html`
3. Should see: Blue "Production Mode" banner
4. Should connect to backend API

---

## 📱 Features

### Booking App (`driving_school_app.html`)

- Phone-based registration with OTP
- Multi-slot booking
- Calendar views (Month/Week)
- Color-coded availability
- 60-day booking window

### Portal (`portal.html`)

- Role-based dashboards (Student/Instructor/Admin)
- Booking management
- Statistics and analytics
- Deposit verification (Admin)

---

## 🐛 Troubleshooting

### Port Already in Use

**Solution:** Change ports in server commands:
```bash
php -S localhost:8002 router.php  # Backend
python server.py 8001             # Frontend
```

### PHP Not Found

**Solution:** Make sure PHP is in your system PATH, or use full path:
```bash
C:\php\php.exe -S localhost:8001 router.php
```

### CORS Errors

**Solution:** Backend has CORS enabled for development. Check browser console for specific errors.

### Database Connection Errors

**Solution:** See [Troubleshooting Guide](../troubleshooting/BUGFIXES.md)

---

## 📚 Next Steps

1. **Explore Features:**
   - [Multi-Tenant Routing](../features/MULTI_TENANT_ROUTING.md)
   - [Demo Mode](../features/DEMO_MODE.md)

2. **API Documentation:**
   - [Portal Endpoints](../api/PORTAL_ENDPOINTS.md)

3. **Troubleshooting:**
   - [Bug Fixes](../troubleshooting/BUGFIXES.md)

4. **Deployment:**
   - See production deployment guides in documentation

---

## ✅ Setup Checklist

- [ ] PHP installed and in PATH
- [ ] Python installed and in PATH
- [ ] PostgreSQL installed (for production)
- [ ] Database created and configured
- [ ] Backend server starts successfully
- [ ] Frontend server starts successfully
- [ ] Demo mode works
- [ ] Production mode works
- [ ] Mobile access works (optional)

---

**Ready to go!** 🚀

For more detailed information, see the [Documentation Index](../README.md).

