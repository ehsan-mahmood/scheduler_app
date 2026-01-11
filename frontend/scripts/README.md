# Frontend Scripts

Utility scripts and server files for the frontend.

## Files

### `server.py`
Python development server with multi-tenant URL routing support.

**Usage:**
```bash
python server.py 8000
```

Routes all URLs to `driving_school_app.html` and `portal.html` to enable URL-based business detection.

### `router.php`
PHP routing script for PHP built-in server and Apache.

**Usage with PHP server:**
```bash
php -S localhost:8000 router.php
```

**Usage with Apache:**
Configure `.htaccess` to use this router for clean URLs.

### `start-multitenant-server.bat`
Windows batch script to start the Python multi-tenant server.

**Usage:**
Double-click or run from command line:
```bash
start-multitenant-server.bat
```

### `start-server.bat`
Windows batch script to start the basic Python server.

**Usage:**
Double-click or run from command line:
```bash
start-server.bat
```

---

## Notes

- `server.py` is the recommended development server (supports multi-tenant routing)
- `router.php` is for PHP/Apache production deployment
- Batch scripts are for Windows convenience

For more information, see the main [Frontend README](../README.md).

