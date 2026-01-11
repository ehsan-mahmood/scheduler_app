# Frontend Folder Organization

This document describes the frontend directory structure and organization.

## 📁 Current Structure

```
frontend/
├── driving_school_app.html  # Main booking application
├── portal.html              # Admin/instructor portal
├── index.html               # Landing page
├── README.md                # Main frontend documentation
│
├── scripts/                 # Utility scripts and servers
│   ├── server.py            # Python multi-tenant server
│   ├── router.php           # PHP routing script
│   ├── start-multitenant-server.bat
│   ├── start-server.bat
│   └── README.md
│
├── tests/                   # Test files
│   ├── test-detection.html  # Business detection test
│   └── README.md
│
├── themes/                  # Themed variations
│   ├── theme1-trust-professional/
│   ├── theme2-modern-friendly/
│   ├── theme3-premium/
│   └── README.md
│
├── legacy/                  # Old folders (reference only)
│   ├── acme-driving/        # Old folder-based business
│   ├── city-school/         # Old folder-based business
│   └── README.md
│
└── docs/                    # Local documentation (if any)
```

## 📋 File Organization

### Root Level Files

**Main Applications:**
- `driving_school_app.html` - Public booking page
- `portal.html` - Admin/instructor portal
- `index.html` - Landing page

**Documentation:**
- `README.md` - Main frontend documentation
- `PORTAL_GUIDE.md` - Portal guide
- `MULTI_SLOT_BOOKING.md` - Feature documentation
- `REDESIGN_SPEC.md` - Design specifications
- `PORTAL_REDESIGN_SPEC.md` - Portal design specs

**Note:** Most documentation has been consolidated into `docs/` at project root.

### Scripts Directory

**Purpose:** Server scripts and utility files

**Files:**
- `server.py` - Python development server with multi-tenant routing
- `router.php` - PHP routing script for Apache/PHP server
- `*.bat` - Windows batch scripts for easy server startup

**Usage:** See [Scripts README](scripts/README.md)

### Tests Directory

**Purpose:** Test files for development/debugging

**Files:**
- `test-detection.html` - Test business slug detection

**Usage:** See [Tests README](tests/README.md)

### Themes Directory

**Purpose:** Themed variations of the main applications

**Themes:**
- `theme1-trust-professional/` - Trust & Professional theme
- `theme2-modern-friendly/` - Modern Friendly theme
- `theme3-premium/` - Premium theme

**Usage:** See [Themes README](themes/README.md)

### Legacy Directory

**Purpose:** Old folders kept for reference

**Contents:**
- `acme-driving/` - Old folder-based business implementation
- `city-school/` - Old folder-based business implementation

**Note:** These are no longer needed - URL-based routing replaces folder-based approach.

**Usage:** See [Legacy README](legacy/README.md)

## 🎯 Organization Principles

1. **Main files in root** - Core applications stay in root for easy access
2. **Scripts organized** - Server scripts moved to `scripts/` folder
3. **Tests separated** - Test files in `tests/` folder
4. **Themes preserved** - Theme variations stay in `themes/` folder
5. **Legacy archived** - Old folders moved to `legacy/` for reference

## 📝 Notes

- Similar structure to `backend/` directory for consistency
- Scripts folder mirrors backend's scripts organization
- Tests folder mirrors backend's tests organization
- Legacy folder similar to backend's legacy folder
- Documentation consolidated into project root `docs/` folder

## 🔄 Migration Notes

**Moved from root:**
- `server.py` → `scripts/server.py`
- `router.php` → `scripts/router.php`
- `*.bat` files → `scripts/*.bat`
- `test-detection.html` → `tests/test-detection.html`
- `acme-driving/` → `legacy/acme-driving/`
- `city-school/` → `legacy/city-school/`

**Removed (consolidated into docs/):**
- Portal endpoint docs → `docs/api/PORTAL_ENDPOINTS.md`
- URL routing docs → `docs/features/MULTI_TENANT_ROUTING.md`
- Demo mode docs → `docs/features/DEMO_MODE.md`
- Bug fix docs → `docs/troubleshooting/BUGFIXES.md`

**Kept in root:**
- Main HTML files (for easy access)
- Main README.md
- Feature documentation (MULTI_SLOT_BOOKING.md, etc.)
- Design specifications

---

**Last Updated:** 2026-01-12

