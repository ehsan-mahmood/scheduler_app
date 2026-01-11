# Portal Login System Guide

## Overview
The portal system (`portal.html`) provides authenticated access for **Students**, **Instructors**, and **Admins** to manage bookings and view their dashboards.

## Key Differences from Public Booking

| Feature | Public Booking (`driving_school_app.html`) | Portal (`portal.html`) |
|---------|-------------------------------------------|------------------------|
| **Purpose** | Book new lessons (Calendly-style) | Manage existing bookings |
| **Authentication** | OTP only (at confirmation) | Phone + Password |
| **Access** | Anyone can browse | Registered users only |
| **Multi-booking** | Yes (select multiple slots) | View only |

## User Roles

### 👨‍🎓 Student
**Can:**
- View all their bookings
- See booking status (confirmed/pending/canceled)
- Cancel bookings
- Reschedule lessons
- Book new lessons (redirects to public booking page)

**Dashboard Shows:**
- List of all bookings with date, time, instructor, lesson type
- Status badges
- Action buttons (Reschedule, Cancel)

### 👨‍🏫 Instructor
**Can:**
- View their schedule
- See all assigned lessons
- Start lessons
- View student details
- Track statistics

**Dashboard Shows:**
- Stats: Today's lessons, This week, Upcoming, Completed
- Schedule table with all bookings
- Student names and contact info
- Action buttons (Start, Details)

### 👔 Admin
**Can:**
- View all bookings across all instructors
- Approve/reject pending bookings
- Manage instructors (edit, deactivate)
- View system statistics
- Cancel any booking

**Dashboard Shows:**
- Stats: Total bookings, Pending approvals, Active instructors, Total students
- All bookings table
- Instructor management table
- Full system overview

## Demo Mode Credentials

### Quick Login (Demo Mode)
When demo mode is enabled, the login page shows a **Quick Login** panel with one-click access to all roles:

- **👨‍🎓 Student** - Alice Johnson
- **👨‍🏫 Instructor** - John Smith  
- **👔 Admin** - Admin User

Just click any button to instantly login as that role!

### Manual Login Credentials (Demo Mode)
```
Student:
Phone: +61400111222
Password: demo123

Instructor:
Phone: +61400333444
Password: demo123

Admin:
Phone: +61400555666
Password: demo123
```

**Note:** In demo mode, you can also use ANY phone number with password `demo123` for any role.

## How to Use

### For Students

1. **Register** (First time)
   - Open `portal.html`
   - Click "Register"
   - Enter: Name, Phone, Password
   - Account created automatically as Student

2. **Login** (Returning users)
   - Open `portal.html`
   - Select "Student" role
   - Enter phone + password
   - Click "Login"

3. **View Bookings**
   - See all your lessons in a table
   - Check status (confirmed/pending/canceled)
   - Click actions to manage

4. **Book New Lessons**
   - Click "Book New Lesson" button
   - Redirects to public booking page
   - Complete booking with OTP

### For Instructors

1. **Login**
   - Open `portal.html`
   - Select "Instructor" role
   - Enter credentials
   - View your schedule

2. **Manage Schedule**
   - See today's lessons at a glance
   - View weekly statistics
   - Start lessons when students arrive
   - View student details

### For Admins

1. **Login**
   - Open `portal.html`
   - Select "Admin" role
   - Enter credentials
   - Access full system

2. **Manage Bookings**
   - View all bookings system-wide
   - Approve pending bookings
   - Cancel problematic bookings
   - View detailed statistics

3. **Manage Instructors**
   - See all instructors
   - View their active bookings
   - Edit instructor details
   - Deactivate if needed

## Authentication Flow

### Registration (Students Only)
```
1. User enters: Name, Phone, Password
2. Backend creates account
3. Auto-login after registration
4. Redirect to student dashboard
```

### Login (All Roles)
```
1. Select role (Student/Instructor/Admin)
2. Enter phone + password
3. Backend validates credentials + role
4. Session stored in localStorage
5. Redirect to appropriate dashboard
```

### Session Management
- Sessions stored in browser localStorage
- Persists across page refreshes
- Logout clears session
- No expiration (manual logout required)

## API Endpoints Required

### Authentication
```
POST /auth/login
{
  "phone": "+61400111222",
  "password": "demo123",
  "role": "student"
}
Response: { "success": true, "user": {...} }

POST /auth/register
{
  "name": "John Doe",
  "phone": "+61400111222",
  "password": "securepass"
}
Response: { "success": true, "user": {...} }
```

### Bookings
```
GET /bookings/student
Response: { "bookings": [...] }

GET /bookings/instructor
Response: { "bookings": [...] }

GET /bookings/all (Admin only)
Response: { "bookings": [...] }

POST /bookings/{id}/cancel
POST /bookings/{id}/approve (Admin only)
POST /bookings/{id}/reschedule
```

### Instructors (Admin only)
```
GET /instructors
Response: { "instructors": [...] }

PUT /instructors/{id}
DELETE /instructors/{id}
```

## Security Considerations

### Password Requirements (Production)
- Minimum 8 characters
- At least 1 uppercase letter
- At least 1 number
- Hash passwords with bcrypt

### Session Security
- Use JWT tokens instead of localStorage
- Implement token expiration (e.g., 24 hours)
- Add refresh token mechanism
- Validate role on every API call

### Role-Based Access Control
- Backend must verify user role
- Students can only see their own bookings
- Instructors can only see their assigned lessons
- Admins have full access

## Integration with Public Booking

The portal and public booking pages work together:

1. **New Users**: Book via public page (OTP) → Auto-create account → Can login to portal
2. **Existing Users**: Login to portal → Click "Book New" → Redirects to public booking
3. **Booking Confirmation**: OTP on public page → Creates booking → Visible in portal

### Linking Between Pages

Add to `driving_school_app.html` header:
```html
<a href="portal.html" style="color: var(--primary);">
  Already have an account? Login to Portal →
</a>
```

Add to `portal.html` (already included):
```html
<a href="driving_school_app.html">
  ← Back to Public Booking
</a>
```

## Testing in Demo Mode

### Test Student Flow
1. Open `portal.html`
2. Login as student (credentials above)
3. View bookings (2 demo bookings shown)
4. Try canceling a booking
5. Click "Book New Lesson" → redirects to public booking

### Test Instructor Flow
1. Login as instructor
2. View dashboard stats
3. See assigned lessons (3 demo bookings)
4. Click "Start" on a lesson
5. View student details

### Test Admin Flow
1. Login as admin
2. View all bookings (3 total)
3. Approve a pending booking
4. View instructor management
5. Check system statistics

## Future Enhancements

- **Email notifications** on booking confirmation
- **SMS reminders** before lessons
- **Calendar integration** (Google Calendar, iCal)
- **Payment tracking** and invoicing
- **Instructor availability** management
- **Student progress tracking** and notes
- **Multi-language support**
- **Mobile app** (React Native/Flutter)

## Troubleshooting

### Can't login
- Check role selection matches your account
- Verify phone number format (+61...)
- In demo mode, use password `demo123`
- Clear localStorage and try again

### Bookings not showing
- Verify you're logged in
- Check browser console for errors
- Ensure backend is running (if not demo mode)
- Try refreshing the page

### Session lost after refresh
- Check browser localStorage
- Ensure JavaScript is enabled
- Try logging in again

---

This portal provides a complete management system for all user types while keeping the public booking experience simple and accessible! 🚗✨

