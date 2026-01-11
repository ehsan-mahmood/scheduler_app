# Multi-Tenant URL Routing

Complete guide to the URL-based routing system that enables multiple businesses to be served from a single codebase.

## 🎯 Overview

The system uses **URL-based routing** to automatically detect which business a user is accessing, eliminating the need for separate folders or files per business.

**Key Benefits:**
- ✅ One file serves all businesses
- ✅ No folder creation needed when adding businesses
- ✅ Auto-detects business from URL
- ✅ Automatic demo mode when no business detected

---

## 🔍 How It Works

### 1. URL Detection

The system checks the URL in this order:

1. **Path-based detection** (recommended)
   - `http://example.com/acme-driving/` → Business: `acme-driving`
   - `http://example.com/acme-driving/driving_school_app.html` → Business: `acme-driving`

2. **Subdomain-based detection**
   - `http://acme-driving.example.com/` → Business: `acme-driving`

3. **No business detected**
   - `http://example.com/driving_school_app.html` → **Demo Mode**

### 2. Detection Logic

```javascript
// JavaScript detects business from URL
const pathname = window.location.pathname; // "/acme-driving/"
const business = pathname.split('/')[1];   // "acme-driving"

// If no business detected:
if (!business || business.includes('.')) {
    DEMO_MODE = true; // Enter demo mode
}
```

### 3. API Calls

Once business is detected, API calls include the business slug:

```
GET /acme-driving/api/instructors
GET /acme-driving/api/lesson-types
```

---

## 🚀 Setup

### Development (Python Server)

**Start the server:**
```bash
cd frontend
python server.py 8000
```

Or use the batch file:
```bash
cd frontend
start-multitenant-server.bat
```

**Access:**
- `http://localhost:8000/acme-driving/` → Production mode
- `http://localhost:8000/city-school/` → Production mode
- `http://localhost:8000/driving_school_app.html` → Demo mode

### Production (Apache)

**Create `.htaccess`:**
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ router.php [QSA,L]
```

**Requirements:**
- `mod_rewrite` enabled
- `AllowOverride All` in VirtualHost config

### Production (Nginx)

**Add to nginx.conf:**
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html/frontend;
    
    location / {
        try_files $uri $uri/ /driving_school_app.html;
    }
    
    location ~ ^/([a-z0-9-]+)/api/ {
        proxy_pass http://localhost:8001;
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
    }
}
```

---

## 📋 URL Examples

### Production Mode

| URL | Business Detected | Mode |
|-----|------------------|------|
| `http://localhost:8000/acme-driving/` | `acme-driving` | Production ✅ |
| `http://localhost:8000/city-school/` | `city-school` | Production ✅ |
| `http://acme-driving.example.com/` | `acme-driving` | Production ✅ |

### Demo Mode

| URL | Business Detected | Mode |
|-----|------------------|------|
| `http://localhost:8000/driving_school_app.html` | `null` | Demo 🎮 |
| `http://localhost:8000/` | `null` | Demo 🎮 |
| `file:///path/to/driving_school_app.html` | `null` | Demo 🎮 |

---

## ➕ Adding a New Business

### Step 1: Add to Database

```sql
INSERT INTO businesses (business_name, subdomain, status)
VALUES ('New Business', 'new-business', 'active');
```

### Step 2: Access Immediately

```
http://localhost:8000/new-business/
```

**That's it!** No file operations needed.

---

## 🔧 Configuration

### Force Business Slug (Development)

In `driving_school_app.html` or `portal.html`:

```javascript
const CONFIG = {
    // Force a specific business (demo mode OFF)
    FORCE_BUSINESS_SLUG: 'acme-driving',
    
    // Or auto-detect from URL (demo mode if no business)
    FORCE_BUSINESS_SLUG: null,
    
    // Fallback for demo mode
    FALLBACK_BUSINESS_SLUG: 'acme-driving'
};
```

---

## 🐛 Troubleshooting

### Issue: "Business not found"

**Solution:** Check database
```sql
SELECT * FROM businesses WHERE subdomain = 'acme-driving';
```

### Issue: Demo mode instead of production

**Check:**
1. URL format: Should be `/business-slug/`
2. Business slug should be lowercase with hyphens
3. Check browser console for `detectedBusiness` value

### Issue: Server not routing

**Python:**
```bash
python server.py 8000  # ✅ Correct
python -m http.server 8000  # ❌ Wrong (no routing)
```

**PHP:**
```bash
php -S localhost:8000 router.php  # ✅ Correct
php -S localhost:8000  # ❌ Wrong (no routing)
```

---

## ✅ Summary

**Key Points:**
- One file serves all businesses
- Business detected from URL automatically
- No folders needed when adding businesses
- Demo mode activated when no business detected

**Files:**
- `frontend/server.py` - Python development server
- `frontend/router.php` - PHP routing script
- `frontend/.htaccess` - Apache routing rules

**Usage:**
1. Add business to database
2. Access via URL: `/business-slug/`
3. Done! ✅

