# Demo Mode Guide

Complete guide to using demo mode in both the booking app and portal.

## 🎯 Overview

Demo mode simulates all API responses, allowing you to test the application **without a backend server**. Demo mode is **automatically enabled** when no business slug is detected in the URL.

---

## 🔍 How Demo Mode Works

### Auto-Detection

Demo mode is **automatically enabled** when:
- No business slug in URL: `http://localhost:8000/driving_school_app.html`
- File opened directly: `file:///path/to/driving_school_app.html`
- Accessing root path: `http://localhost:8000/`

### Production Mode

Production mode is **automatically enabled** when:
- Business slug in URL: `http://localhost:8000/acme-driving/`
- Subdomain detected: `http://acme-driving.example.com/`

### Manual Override

Force demo mode OFF by setting `FORCE_BUSINESS_SLUG`:

```javascript
const CONFIG = {
    FORCE_BUSINESS_SLUG: 'acme-driving', // Demo mode OFF
    // or
    FORCE_BUSINESS_SLUG: null, // Auto-detect (demo if no business)
};
```

---

## ✨ Demo Mode Features

### What Works

1. **Instructor Selection**
   - 3 demo instructors: John Smith, Sarah Johnson, Mike Davis

2. **Lesson Type Selection**
   - 5 demo lesson types with different durations and prices
   - Basic Driving (60 min - $80)
   - Highway Driving (90 min - $120)
   - Parking Practice (45 min - $60)
   - Manual Transmission (60 min - $85)
   - Test Preparation (120 min - $150)

3. **Calendar Availability**
   - Auto-generated 60-day availability
   - Weekends marked as unavailable
   - Color-coded availability status
   - Month and Week views fully functional

4. **Time Slot Selection**
   - Generated slots from 9 AM to 5 PM (30-minute intervals)
   - 70% availability rate (random)

5. **OTP Flow**
   - Enter any phone number
   - **Demo OTP Code**: `123456` (displayed in toast notification)
   - Any 6-digit code will work in demo mode

6. **Booking Confirmation**
   - Creates demo booking ID
   - Simulates successful booking
   - Shows confirmation message

---

## 🎮 Demo Mode Indicators

### Visual Indicators

**Booking App (`driving_school_app.html`):**
- Purple banner: "🎮 DEMO MODE - All data is simulated"
- Banner stays visible throughout session

**Portal (`portal.html`):**
- Purple banner: "🎓 Portal - Demo Mode | Business: acme-driving"
- Quick Login buttons visible

### Console Logs

Look for these in browser console (F12):

```
🎮 DEMO MODE ACTIVE - Using simulated data
[DEMO] API Call: GET /api/instructors
[DEMO] Returning demo instructors: [...]
```

---

## 🧪 Testing Flow

### Booking App Demo Flow

1. Open `driving_school_app.html` (without business slug in URL)
2. Select an instructor (e.g., "John Smith")
3. Select a lesson type (e.g., "Basic Driving")
4. View the calendar - notice color-coded days
5. Click on a green day to see time slots
6. Select time slots
7. Click "Continue to Confirm"
8. Enter any phone number
9. Click "Send Code"
10. Enter OTP: `123456` (or any 6-digit number)
11. Click "Verify & Book"
12. Success! Booking confirmed

### Portal Demo Flow

1. Open `portal.html` (without business slug in URL)
2. See demo mode banner
3. Use Quick Login buttons:
   - **Student:** Phone: `+61400111222`, Password: `demo123`
   - **Instructor:** Phone: `+61400333444`, Password: `demo123`
   - **Admin:** Phone: `+61400555666`, Password: `demo123`
4. View dashboard with demo data
5. Test booking management features

---

## 📱 Mobile Support

Demo mode works on mobile devices! 

**Access:**
- `http://192.168.1.7:8000/driving_school_app.html` (without business slug)

**Requirements:**
- No backend server needed
- Works offline (after initial page load)
- All features functional

**Mobile Fixes Applied:**
- Enhanced endpoint matching (`/api/` prefix support)
- Better error handling with fallbacks
- Direct demo data loading if API simulation fails
- Improved logging for debugging

---

## 🔧 Turning Demo Mode Off

### Method 1: Use URL with Business Slug (Recommended)

**Production Mode URLs:**
```
http://localhost:8000/acme-driving/driving_school_app.html
http://localhost:8000/city-school/portal.html
```

### Method 2: Force Business Slug in Code

Edit `driving_school_app.html` or `portal.html`:

```javascript
const CONFIG = {
    FORCE_BUSINESS_SLUG: 'acme-driving', // Demo mode OFF
};
```

---

## 🐛 Troubleshooting

### Issue: Demo banner not showing

**Solutions:**
- Check URL format (should not have business slug)
- Check browser console for errors
- Clear browser cache and reload

### Issue: Calendar not loading

**Solutions:**
- Open browser console (F12) and check for errors
- Verify instructor and lesson type are selected
- Check for `[DEMO]` logs in console

### Issue: OTP not working

**Solutions:**
- In demo mode, use `123456` or any 6-digit number
- Check console for `[DEMO]` messages
- Verify phone number format

### Issue: Demo mode not working on mobile

**Solutions:**
- Access without business slug in URL
- Check browser console for errors
- Verify page loaded completely
- Look for `[DEMO]` logs in console

---

## ✅ Summary

**Key Points:**
- Demo mode auto-activates when no business detected
- Works without backend server
- All features functional in demo mode
- Mobile-friendly with recent fixes
- Use OTP: `123456` for bookings
- Use demo credentials for portal

**When to Use:**
- UI testing and development
- Feature demonstrations
- Offline testing
- Mobile testing without backend

**When to Use Production Mode:**
- Testing with real backend
- Integration testing
- Production deployment

