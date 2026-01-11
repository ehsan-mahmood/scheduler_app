# Driving School Scheduler

A complete driving school scheduling system with student, instructor, and admin portals.

## 🚀 Quick Start

### Easiest Way: Start Both Servers at Once

**Windows:**
Double-click `START_BOTH_SERVERS.bat` - this will start both frontend and backend in separate windows.

**Manual Start:**

1. **Start Backend API:**
   ```bash
   cd backend
   php -S localhost:8001 router.php
   ```

2. **Start Frontend (in a new terminal):**
   ```bash
   cd frontend
   python scripts/server.py 8000
   ```
   
   Or use the batch script:
   ```bash
   cd frontend\scripts
   start-multitenant-server.bat
   ```

3. **Open in Browser:**
   - Frontend: http://localhost:8000/driving_school_app.html
   - Backend API: http://localhost:8001

## 📁 Project Structure

```
schedule_dr/
├── frontend/
│   ├── driving_school_app.html  # Main frontend application
│   ├── start-server.bat          # Frontend server script
│   └── README.md                 # Frontend documentation
├── backend/
│   ├── api.php                   # Main API file
│   ├── config.php                # Configuration file
│   ├── db.php                    # Database functions
│   ├── router.php                # PHP server router
│   ├── data/                     # JSON data storage (auto-created)
│   ├── docs/                     # Documentation files
│   ├── scripts/                  # Batch scripts and utilities
│   ├── sql/                      # SQL schema files
│   ├── tests/                    # Test files
│   └── legacy/                   # Legacy/unused files
└── START_BOTH_SERVERS.bat        # Start both servers at once
```

## 🔧 Requirements

- **PHP 7.4+** (check with `php --version`)
- **Python 3.x** (check with `python --version`)

Both are usually pre-installed on Windows 10/11.

## 📱 Features

### Student Portal
- Phone-based registration with OTP
- Book lessons
- Submit PayID deposit
- View lesson status
- Reschedule/Cancel lessons

### Instructor Portal
- View calendar/schedule
- Start lessons with OTP verification

### Admin Portal
- Dashboard overview
- Verify deposits
- Manage instructors
- Manage lesson types
- Analytics

## 🔌 API Configuration

The frontend is configured to connect to `http://localhost:8001` by default.

To change the API URL, edit `frontend/driving_school_app.html` and update:
```javascript
const API_BASE_URL = 'http://localhost:8001';
```

## 📝 Data Storage

Currently uses file-based JSON storage (perfect for MVP/testing):
- `backend/data/students.json`
- `backend/data/lessons.json`
- `backend/data/deposits.json`
- `backend/data/instructors.json`

## 🛠️ Development

### Demo Mode
The frontend has a demo mode that simulates API responses. It's currently **disabled** to use the real backend API.

To enable demo mode (no backend required), edit `frontend/driving_school_app.html`:
```javascript
const DEMO_MODE = true; // Enable demo mode
```

### Production Deployment

For production, you'll want to:
1. Set up a proper database (MySQL/PostgreSQL)
2. Add authentication/authorization
3. Implement real SMS sending
4. Use a production web server (Apache/Nginx)
5. Configure CORS properly
6. Add input validation and security measures

## 📚 Documentation

### Main Documentation

- **[Documentation Index](docs/README.md)** - Complete documentation index
- [Frontend README](frontend/README.md) - Frontend guide
- [Backend API README](backend/docs/README.md) - Backend guide
- [Full Specifications](driving_school_scheduler_specs.md) - Complete specs

### Quick Links

- **[Getting Started](docs/setup/GETTING_STARTED.md)** - Setup guide
- **[Multi-Tenant Routing](docs/features/MULTI_TENANT_ROUTING.md)** - URL routing
- **[Demo Mode](docs/features/DEMO_MODE.md)** - Demo mode guide
- **[Portal API](docs/api/PORTAL_ENDPOINTS.md)** - Portal endpoints
- **[Mobile Access](docs/setup/MOBILE_ACCESS.md)** - Mobile setup
- **[Bug Fixes](docs/troubleshooting/BUGFIXES.md)** - Troubleshooting

## 🐛 Troubleshooting

**Port already in use:**
- Change ports in the server commands (e.g., `8002`, `8003`)
- Update `API_BASE_URL` in frontend if you change backend port

**PHP not found:**
- Make sure PHP is in your system PATH
- Or use full path: `C:\php\php.exe -S localhost:8001 router.php`

**CORS errors:**
- The backend has CORS enabled for development
- If issues persist, check browser console for specific errors

## 📄 License

This is an MVP/demo project.

