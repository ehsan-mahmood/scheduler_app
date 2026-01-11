# Mobile Device Access Setup

## ✅ **Fixed: Auto-Detection of API URL**

Both `driving_school_app.html` and `portal.html` now **auto-detect** the API base URL from the current hostname.

### **How It Works:**

- **Desktop (localhost):** `http://localhost:8000` → API: `http://localhost:8001` ✅
- **Mobile (IP):** `http://192.168.1.7:8000` → API: `http://192.168.1.7:8001` ✅
- **Domain:** `http://example.com` → API: `http://example.com:8001` ✅

---

## 🚀 **Backend Server Setup**

### **Important: Backend Must Listen on All Interfaces**

The backend PHP server must be accessible from your network, not just localhost.

### **Option 1: PHP Built-in Server (Recommended for Testing)**

```bash
# ❌ WRONG - Only accessible from localhost
php -S localhost:8001

# ✅ CORRECT - Accessible from network
php -S 0.0.0.0:8001
```

**Or use your IP:**
```bash
php -S 192.168.1.7:8001
```

### **Option 2: Check Your Current Server**

If you're using a different server setup, make sure it's listening on:
- `0.0.0.0:8001` (all interfaces)
- OR `192.168.1.7:8001` (your specific IP)

---

## 🧪 **Testing Steps**

### **Step 1: Start Backend on Network Interface**

```bash
cd backend
php -S 0.0.0.0:8001
```

**Expected output:**
```
PHP 8.x.x Development Server (http://0.0.0.0:8001) started
```

### **Step 2: Start Frontend Server**

```bash
cd frontend
python server.py
# Or use: php -S 0.0.0.0:8000
```

### **Step 3: Test on Desktop**

```
http://localhost:8000/acme-driving/driving_school_app.html
```

Should work ✅

### **Step 4: Test on Mobile**

1. Make sure mobile is on the **same WiFi network**
2. Open browser on mobile
3. Go to: `http://192.168.1.7:8000/acme-driving/driving_school_app.html`

**Should now work!** ✅

---

## 🔍 **Troubleshooting**

### **Problem: Still Can't Connect from Mobile**

#### **Check 1: Backend Server Interface**

```bash
# Check if backend is listening on all interfaces
netstat -an | findstr :8001
# Should show: 0.0.0.0:8001 or 192.168.1.7:8001
```

#### **Check 2: Firewall**

Windows Firewall might be blocking port 8001:

1. Open Windows Defender Firewall
2. Allow port 8001 for inbound connections
3. Or temporarily disable firewall for testing

#### **Check 3: Network Connection**

- Mobile and computer must be on **same WiFi network**
- Check mobile can ping computer: `ping 192.168.1.7` (if ping app available)

#### **Check 4: Browser Console**

On mobile, open browser developer tools (if available) or use remote debugging:

**Chrome Remote Debugging:**
1. Connect mobile via USB
2. Enable USB debugging
3. Open `chrome://inspect` on desktop
4. Check console for errors

**Look for:**
- CORS errors
- Network errors
- Connection refused errors

---

## 📱 **Mobile Browser Testing**

### **Chrome on Android:**

1. Open Chrome
2. Go to: `http://192.168.1.7:8000/acme-driving/driving_school_app.html`
3. Open Developer Tools (if available)
4. Check Network tab for failed requests

### **Safari on iOS:**

1. Open Safari
2. Go to: `http://192.168.1.7:8000/acme-driving/driving_school_app.html`
3. Enable Web Inspector (Settings → Safari → Advanced → Web Inspector)
4. Connect to Mac and use Safari Web Inspector

---

## 🔧 **Quick Fix Script**

Create a batch file to start backend on network interface:

**`start-backend-network.bat`:**
```batch
@echo off
echo Starting backend server on network interface...
cd backend
php -S 0.0.0.0:8001
```

**`start-frontend-network.bat`:**
```batch
@echo off
echo Starting frontend server on network interface...
cd frontend
python server.py
# Or: php -S 0.0.0.0:8000
```

---

## ✅ **Verification Checklist**

- [ ] Backend running on `0.0.0.0:8001` (not `localhost:8001`)
- [ ] Frontend accessible from mobile: `http://192.168.1.7:8000/...`
- [ ] Mobile and computer on same WiFi network
- [ ] Firewall allows port 8001
- [ ] Browser console shows API calls to `http://192.168.1.7:8001/...`
- [ ] No CORS errors in console
- [ ] Data loads successfully

---

## 🎯 **What Was Fixed**

### **Before:**
```javascript
API_BASE_URL: 'http://localhost:8001'  // ❌ Doesn't work on mobile
```

### **After:**
```javascript
API_BASE_URL: detectApiBaseUrl()  // ✅ Auto-detects from hostname
// http://192.168.1.7:8001 on mobile
// http://localhost:8001 on desktop
```

---

## 📝 **Manual Override (If Needed)**

If auto-detection doesn't work, you can manually set the API URL:

**In `driving_school_app.html` or `portal.html`:**

```javascript
const CONFIG = {
    // ...
    API_BASE_URL: 'http://192.168.1.7:8001',  // Manual override
    // ...
};
```

---

## 🚨 **Important Notes**

1. **Security:** Using `0.0.0.0` makes the server accessible to anyone on your network. Only use for development!

2. **Production:** In production, use proper web server (Apache/Nginx) with SSL and proper security.

3. **Port Forwarding:** If mobile is on different network, you'll need port forwarding or VPN.

---

## 🎉 **Ready to Test!**

1. Start backend: `php -S 0.0.0.0:8001` (in backend folder)
2. Start frontend: `python server.py` (in frontend folder)
3. Open on mobile: `http://192.168.1.7:8000/acme-driving/driving_school_app.html`
4. Should work! ✅

---

**If it still doesn't work, check:**
- Backend is running on `0.0.0.0:8001`
- Firewall allows port 8001
- Mobile and computer on same network
- Browser console for specific errors

