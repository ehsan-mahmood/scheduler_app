# DriveScheduler Frontend

Complete booking and management system for a driving school.

## 📁 Project Structure

```
frontend/
├── driving_school_app.html  # Main booking app
├── portal.html              # Admin/instructor portal
├── index.html               # Landing page
├── README.md                # This file
├── scripts/                 # Utility scripts and servers
│   ├── server.py            # Python multi-tenant server
│   ├── router.php           # PHP routing script
│   └── *.bat                # Windows batch scripts
├── tests/                   # Test files
│   └── test-detection.html  # Business detection test
├── themes/                  # Themed variations
│   ├── theme1-trust-professional/
│   ├── theme2-modern-friendly/
│   └── theme3-premium/
└── legacy/                  # Old folders (reference only)
    ├── acme-driving/
    └── city-school/
```

## 📄 Main Files

### `driving_school_app.html`
**Public Booking Page** - Calendly-style booking interface

- ✅ No login required to browse
- ✅ 60-day booking window
- ✅ Multi-slot booking (book multiple lessons at once)
- ✅ Month/Week calendar views
- ✅ Color-coded availability
- ✅ OTP confirmation (only at booking)
- ✅ Demo mode included

**Use for:** New customers booking their first lessons

### `portal.html`
**Authenticated Portal** - Dashboard for Students, Instructors, and Admins

- ✅ Phone + Password login (no OTP)
- ✅ Role-based dashboards
- ✅ Booking management
- ✅ Statistics and analytics
- ✅ Demo mode included

**Use for:** Existing users managing their bookings

## 🚀 Quick Start

### Demo Mode (No Backend Required)

1. **Try Public Booking:**
   ```
   Open: driving_school_app.html
   - Select instructor and lesson type
   - Browse calendar (weekends are unavailable)
   - Click multiple time slots
   - Use OTP: 123456
   ```

2. **Try Portal:**
   ```
   Open: portal.html
   
   Student Login:
   Phone: +61400111222
   Password: demo123
   
   Instructor Login:
   Phone: +61400333444
   Password: demo123
   
   Admin Login:
   Phone: +61400555666
   Password: demo123
   ```

### Production Mode

1. **Configure API:**
   ```javascript
   // In both HTML files, change:
   const DEMO_MODE = false;
   const API_BASE_URL = 'http://your-backend-url';
   ```

2. **Implement Backend Endpoints:**
   - See `REDESIGN_SPEC.md` for public booking API
   - See `PORTAL_GUIDE.md` for portal API

## 📚 Documentation

### Main Documentation

- **[Documentation Index](../../docs/README.md)** - Complete documentation index
- **[Getting Started](../../docs/setup/GETTING_STARTED.md)** - Setup guide
- **[Multi-Tenant Routing](../../docs/features/MULTI_TENANT_ROUTING.md)** - URL routing
- **[Demo Mode](../../docs/features/DEMO_MODE.md)** - Demo mode guide
- **[Portal API](../../docs/api/PORTAL_ENDPOINTS.md)** - Portal endpoints

### Scripts & Utilities

- **[Scripts README](scripts/README.md)** - Server scripts and utilities
- **[Tests README](tests/README.md)** - Test files

### Local Documentation

- **[Frontend Documentation](docs/README.md)** - Frontend-specific documentation
  - **[Multi-Slot Booking](docs/features/MULTI_SLOT_BOOKING.md)** - Feature guide
  - **[Themes](docs/features/THEMES.md)** - Theme system
  - **[Portal Guide](docs/guides/PORTAL_GUIDE.md)** - Portal guide
  - **[Design Specs](docs/specs/)** - Design specifications

## ✨ Key Features

### Public Booking Page

1. **Calendar Views**
   - Month view: Traditional calendar grid
   - Week view: Detailed list view
   - Toggle between views

2. **Availability Status**
   - 🟢 Free: >20% slots available
   - 🟠 Almost Booked: ≤20% slots available
   - 🔴 Fully Booked: No slots
   - ⚪ Unavailable: Outside working hours

3. **Multi-Slot Selection**
   - Select multiple time slots
   - Browse different days
   - Remove individual slots
   - See total count and price
   - Book all at once

4. **Smart Booking Flow**
   - Browse without login
   - Select slots freely
   - OTP only at confirmation
   - Holds slots during OTP (5-10 min)

### Portal Dashboards

#### Student Dashboard
- View all bookings
- Check status
- Cancel/reschedule
- Book new lessons

#### Instructor Dashboard
- Today's schedule
- Weekly statistics
- Start lessons
- View student details

#### Admin Dashboard
- System-wide statistics
- All bookings overview
- Approve pending bookings
- Manage instructors
- Full system control

## 🎨 Design

- **Modern gradient background**
- **Glassmorphism effects**
- **Smooth animations**
- **Responsive mobile design**
- **Color-coded status indicators**
- **Toast notifications**
- **Sticky summary sidebar**

## 🔧 Configuration

### Demo Mode Toggle
```javascript
const DEMO_MODE = true; // Set to false for production
```

### API Base URL
```javascript
const API_BASE_URL = 'http://localhost:8001';
```

### Booking Window
```javascript
const BOOKING_WINDOW_DAYS = 60; // Days into the future
```

## 🔐 Authentication

### Public Booking
- **OTP-based** (phone number only)
- Triggered at booking confirmation
- Creates account automatically
- Demo OTP: `123456`

### Portal
- **Password-based** (phone + password)
- Role selection (Student/Instructor/Admin)
- Session stored in localStorage
- Demo password: `demo123`

## 📱 Responsive Design

Both pages are fully responsive:
- **Desktop**: Full layout with sidebar
- **Tablet**: Stacked layout
- **Mobile**: Touch-optimized, bottom sheets

## 🌐 Browser Support

- Chrome/Edge: ✅
- Firefox: ✅
- Safari: ✅
- Mobile browsers: ✅

## 🔄 User Flow

### New Customer Journey
```
1. Visit driving_school_app.html
2. Browse availability (no login)
3. Select instructor + lesson type
4. Pick time slots (can select multiple)
5. Click "Book X Lessons"
6. Enter phone number
7. Receive OTP → Enter code
8. Booking confirmed!
9. Can now login to portal.html
```

### Existing Customer Journey
```
1. Visit portal.html
2. Login with phone + password
3. View bookings dashboard
4. Click "Book New Lesson"
5. Redirected to driving_school_app.html
6. Select slots → OTP → Confirmed
7. New booking appears in portal
```

## 🎯 Use Cases

### For Driving Schools
- Accept online bookings 24/7
- Reduce phone call volume
- Manage instructor schedules
- Track all bookings centrally
- Approve/reject bookings

### For Students
- Book lessons anytime
- See real-time availability
- Book multiple lessons at once
- Manage bookings online
- Receive confirmations

### For Instructors
- View daily schedule
- Track upcoming lessons
- Start lessons with one click
- See student information

## 🚧 Future Enhancements

- [ ] Payment integration (Stripe/PayPal)
- [ ] Email notifications
- [ ] SMS reminders
- [ ] Calendar export (iCal/Google)
- [ ] Recurring bookings
- [ ] Package deals/discounts
- [ ] Student progress tracking
- [ ] Instructor notes
- [ ] Mobile app
- [ ] Multi-language support

## 🐛 Troubleshooting

### Calendar not loading
- Check instructor and lesson type are selected
- Open browser console for errors
- Verify DEMO_MODE or backend connection

### OTP not working
- In demo mode, use `123456`
- Check phone number format
- Verify backend SMS service (production)

### Portal login fails
- Check role selection
- Verify credentials
- Clear localStorage
- Check backend authentication

## 📞 Support

For issues or questions:
1. Check documentation files
2. Review browser console
3. Test in demo mode first
4. Verify backend API responses

---

Built with ❤️ for modern driving schools. No frameworks, just vanilla JavaScript! 🚗✨
