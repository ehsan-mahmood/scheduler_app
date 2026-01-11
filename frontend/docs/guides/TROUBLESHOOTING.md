# Troubleshooting Business Detection

## Issue: `http://localhost:8000/acme-driving/driving_school_app.html` doesn't work

### Quick Diagnosis

1. **Open the page** in your browser
2. **Press F12** to open Developer Console
3. **Look for** the configuration log:

```javascript
🚗 Driving School Booking Configuration: {
  pathname: "/acme-driving/driving_school_app.html",
  pathParts: ["acme-driving", "driving_school_app.html"],
  detectedBusiness: "acme-driving",  // ← Should show this
  demoMode: false,  // ← Should be false
  ...
}
```

---

## Expected Behavior

For URL: `http://localhost:8000/acme-driving/driving_school_app.html`

✅ **Should detect**: `acme-driving`  
✅ **Should be**: Production mode  
✅ **API calls**: `http://localhost:8001/acme-driving/api/...`

---

## Common Issues & Solutions

### Issue 1: Shows Demo Mode Instead of Production

**Console shows:**
```javascript
detectedBusiness: null
demoMode: true
```

**Possible causes:**

1. **Business slug has uppercase or special characters**
   - ❌ `Acme-Driving` (uppercase)
   - ❌ `acme_driving` (underscore)
   - ✅ `acme-driving` (lowercase with hyphens)

2. **Path has extra characters**
   - ❌ `/Acme-Driving/` (uppercase A)
   - ✅ `/acme-driving/`

3. **Check console log** - Look at `pathParts` array

**Solution:**
```javascript
// In console, check:
window.location.pathname.split('/').filter(p => p)
// Should return: ["acme-driving", "driving_school_app.html"]
```

---

### Issue 2: API Calls Return 404

**Console shows:**
```
GET http://localhost:8001/acme-driving/api/instructors 404 (Not Found)
```

**Possible causes:**

1. **Backend not running on port 8001**
   ```bash
   # Check if backend is running
   curl http://localhost:8001/acme-driving/api/config
   ```

2. **Business doesn't exist in database**
   ```sql
   SELECT * FROM businesses WHERE subdomain = 'acme-driving';
   ```

3. **Wrong API_BASE_URL**
   ```javascript
   // In driving_school_app.html, check:
   API_BASE_URL: 'http://localhost:8001'  // Make sure port matches
   ```

**Solution:**

Check backend is running:
```bash
cd backend
php -S localhost:8001 router.php
```

Then test:
```bash
curl http://localhost:8001/acme-driving/api/config
```

---

### Issue 3: CORS Errors

**Console shows:**
```
Access to fetch at 'http://localhost:8001/...' from origin 'http://localhost:8000' 
has been blocked by CORS policy
```

**Solution:**

Backend needs CORS headers. Check `backend/api_v2.php`:

```php
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');
```

---

### Issue 4: Business Detected but Wrong One

**Console shows:**
```javascript
detectedBusiness: "something-else"  // Not what you expected
```

**Solution:**

Check your URL structure:
- First path segment is always used as business
- `/acme-driving/file.html` → `acme-driving` ✅
- `/wrong/acme-driving/file.html` → `wrong` ❌

---

## Testing Tools

### Tool 1: Test Detection Page

Open `frontend/test-detection.html` in your browser:

```bash
# Serve the frontend folder
cd frontend
python -m http.server 8000

# Then visit:
http://localhost:8000/acme-driving/test-detection.html
```

This page shows:
- Current URL breakdown
- Path parts analysis
- Detected business
- Current mode

### Tool 2: Console Commands

Open any page and run in console:

```javascript
// Check URL parsing
window.location.pathname.split('/').filter(p => p)

// Check first segment
window.location.pathname.split('/').filter(p => p)[0]

// Check if it has extension
window.location.pathname.split('/').filter(p => p)[0].includes('.')

// Manual detection test
function testDetection() {
    const parts = window.location.pathname.split('/').filter(p => p);
    const first = parts[0];
    console.log('Path parts:', parts);
    console.log('First segment:', first);
    console.log('Has extension:', first?.includes('.') || false);
    console.log('Valid slug:', /^[a-z0-9-]+$/.test(first));
    return first && !first.includes('.') && /^[a-z0-9-]+$/.test(first) ? first : null;
}
testDetection();
```

### Tool 3: API Test Page

Use the backend test page:

```bash
# Open in browser
backend/tests/api_test_page.html
```

- Select your business from dropdown
- Test each endpoint individually
- Verify business is working in backend

---

## Step-by-Step Debugging

### Step 1: Verify URL Structure

Your URL should look like:
```
http://localhost:8000/acme-driving/driving_school_app.html
                       └──────────┘ └───────────────────┘
                          business        filename
```

### Step 2: Open Developer Console (F12)

Look for the configuration log:

```javascript
🚗 Driving School Booking Configuration: {
  pathname: "/acme-driving/driving_school_app.html",
  pathParts: ["acme-driving", "driving_school_app.html"],  // ← Check this
  hostname: "localhost",
  detectedBusiness: "acme-driving",  // ← Should match folder name
  forcedBusiness: null,
  effectiveBusinessSlug: "acme-driving",
  demoMode: false,  // ← Should be false for production
  apiBaseUrl: "http://localhost:8001",
  currentUrl: "http://localhost:8000/acme-driving/driving_school_app.html"
}
```

### Step 3: Check the Banner

At the top of the page, you should see:

**If working (Production Mode):**
```
┌──────────────────────────────────────────────────┐
│ ✅ Production Mode | Business: acme-driving     │
│ URL: /acme-driving/driving_school_app.html      │
└──────────────────────────────────────────────────┘
```
(Disappears after 3 seconds)

**If not working (Demo Mode):**
```
┌──────────────────────────────────────────────────┐
│ 🎮 DEMO MODE - All data is simulated           │
└──────────────────────────────────────────────────┘
```
(Stays visible)

### Step 4: Check Network Tab

1. Open Network tab (F12 → Network)
2. Refresh page
3. Look for API calls

**If working:** Should see requests to:
```
http://localhost:8001/acme-driving/api/instructors
http://localhost:8001/acme-driving/api/lesson-types
```

**If not working (demo mode):** No network requests (all simulated)

### Step 5: Test Backend Separately

```bash
# Start backend
cd backend
php -S localhost:8001 router.php

# In another terminal, test
curl http://localhost:8001/acme-driving/api/config

# Should return JSON with business config
```

---

## Force Production Mode (Workaround)

If detection isn't working, you can force a specific business:

Edit `driving_school_app.html`:

```javascript
const CONFIG = {
    FORCE_BUSINESS_SLUG: 'acme-driving',  // ← Add this
    // ... rest of config
};
```

This will:
- Override URL detection
- Always use `acme-driving`
- Enable production mode
- Show warning banner

---

## Checklist

- [ ] URL format: `/business-slug/filename.html`
- [ ] Business slug is lowercase with hyphens only
- [ ] Browser console shows correct `detectedBusiness`
- [ ] Banner shows "Production Mode" (or demo mode as expected)
- [ ] Backend is running on correct port
- [ ] Business exists in database
- [ ] CORS headers are set in backend
- [ ] Network tab shows API requests (if production mode)

---

## Still Not Working?

### Collect Debug Info

Run in console:

```javascript
console.log({
    url: window.location.href,
    pathname: window.location.pathname,
    parts: window.location.pathname.split('/').filter(p => p),
    first: window.location.pathname.split('/').filter(p => p)[0],
    detected: (function() {
        const parts = window.location.pathname.split('/').filter(p => p);
        return parts[0] && !parts[0].includes('.') && /^[a-z0-9-]+$/.test(parts[0]) 
            ? parts[0] 
            : null;
    })()
});
```

Share this output to help diagnose the issue.

### Common "Gotchas"

1. **Port mismatch**: Frontend on 8000, backend on 8001 ✅
2. **Trailing slash**: `/acme-driving/` vs `/acme-driving` (both work)
3. **Filename in path**: Should work with `/acme-driving/file.html`
4. **Cache**: Try hard refresh (Ctrl+Shift+R or Cmd+Shift+R)
5. **Multiple businesses**: First path segment is always used

---

## Contact

If you've tried everything and it's still not working:

1. Check console for errors
2. Check Network tab for failed requests  
3. Verify backend is accessible
4. Try the test detection page
5. Use force override as temporary solution

