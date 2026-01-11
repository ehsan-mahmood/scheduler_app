# Bug Fixes and Technical Solutions

This document consolidates all bug fixes and technical solutions applied to the codebase.

---

## 🔧 Frontend Bug Fixes

### 1. Availability API Endpoint Mismatch

**Issue:** Frontend was calling `/availability/days` endpoint which doesn't exist in `api_v2.php`.

**Error:**
```
API Error: Error: Endpoint not found: api/availability/days
```

**Solution:**
- Changed to on-demand loading: Show all days as potentially available
- Load slots only when user clicks a day
- Simpler, faster initial load

**Status:** ✅ Fixed

---

### 2. BOOKING_WINDOW_DAYS Not Defined

**Issue:** After refactoring configuration into `CONFIG` object, code was still referencing `BOOKING_WINDOW_DAYS` directly.

**Error:**
```
BOOKING_WINDOW_DAYS is not defined
```

**Solution:**
- Updated all references to use `CONFIG.BOOKING_WINDOW_DAYS`
- Fixed in 4 locations: `generateDemoDayStatuses()`, `loadAvailability()`, `renderCalendar()` (2x)

**Status:** ✅ Fixed

---

### 3. Demo Mode Mobile Issues

**Issue:** Demo mode wasn't working on mobile browsers when accessing without business slug.

**Root Causes:**
1. Endpoint matching didn't handle `/api/` prefix
2. Missing error handling with fallbacks
3. Insufficient logging for debugging

**Solution:**
- Enhanced endpoint matching to handle multiple formats (`/api/instructors`, `/instructors`, `instructors`)
- Added try-catch blocks with fallback to direct demo data
- Improved logging and error messages
- Better initialization with error recovery

**Status:** ✅ Fixed

---

### 4. Registration Role Selection

**Issue:** Portal registration didn't differentiate between student and instructor, always defaulting to instructor.

**Solution:**
- Added role selection screen (Student or Instructor)
- Updated `handleRegister` to send selected role to backend
- Backend creates user in correct table based on role

**Status:** ✅ Fixed

---

## 🔧 Backend Bug Fixes

### 1. Database Connection Errors

**Issue:** Multiple "Call to a member function prepare() on null" errors across `api_v2.php`.

**Error:**
```
PHP Fatal error: Call to a member function prepare() on null
```

**Solution:**
- Replaced all `global $db;` with `$db = getDbConnection();`
- Fixed in all affected functions and route handlers
- Systematic search and replace across entire file

**Status:** ✅ Fixed

---

### 2. Boolean Parameter Binding

**Issue:** PostgreSQL doesn't accept empty strings for boolean fields.

**Error:**
```
SQLSTATE[22P02]: Invalid text representation: invalid input syntax for type boolean: ""
```

**Solution:**
- In `createLesson()`: Explicitly cast `deposit_paid` to `(bool)`
- In `updateStudent()`: Added robust boolean conversion logic
- Used `PDO::PARAM_BOOL` for explicit type binding

**Status:** ✅ Fixed

---

### 3. Slot Time Format Mismatch

**Issue:** Backend was returning `slot_time` but frontend expected `startISO` and `endISO`.

**Error:** Time slots showed as "Invalid Date"

**Solution:**
- Updated `getAvailableSlots()` to transform data
- Returns `startISO` and `endISO` in ISO 8601 format
- Matches frontend expectations

**Status:** ✅ Fixed

---

## 📋 Summary of All Fixes

| Issue | Component | Status |
|-------|-----------|--------|
| Availability API endpoint mismatch | Frontend | ✅ Fixed |
| BOOKING_WINDOW_DAYS not defined | Frontend | ✅ Fixed |
| Demo mode mobile issues | Frontend | ✅ Fixed |
| Registration role selection | Frontend | ✅ Fixed |
| Database connection errors | Backend | ✅ Fixed |
| Boolean parameter binding | Backend | ✅ Fixed |
| Slot time format mismatch | Backend | ✅ Fixed |

---

## 🐛 Troubleshooting Common Issues

### Issue: API calls failing

**Check:**
1. Backend server is running (`php -S 0.0.0.0:8001`)
2. CORS headers are set correctly
3. Business slug exists in database
4. Browser console for specific errors

### Issue: Demo mode not working

**Check:**
1. URL format (should not have business slug)
2. Browser console for `[DEMO]` logs
3. Check `CONFIG.FORCE_BUSINESS_SLUG` value
4. Clear browser cache

### Issue: Calendar not loading

**Check:**
1. Instructor and lesson type selected
2. Browser console for errors
3. API endpoints responding correctly
4. Demo mode vs production mode

### Issue: Database connection errors

**Check:**
1. PostgreSQL is running
2. Database connection settings in `backend/db.php`
3. Database exists and is accessible
4. All migrations are applied

---

## 📝 Notes

- All fixes have been tested and verified
- Backend fixes use prepared statements for security
- Frontend fixes include proper error handling
- All fixes maintain backward compatibility where possible

For more details on specific fixes, see individual bugfix documentation files in the codebase history.

