# Multi-Slot Booking Feature

## Overview
The booking system now supports **selecting and booking multiple time slots** in a single transaction. This allows customers to book multiple lessons at once across different days.

## How It Works

### For Customers

1. **Select Instructor & Lesson Type** (as usual)
2. **Browse the calendar** and click on an available day
3. **Click multiple time slots** - each click toggles selection:
   - Click once = slot selected (purple highlight)
   - Click again = slot deselected
4. **Browse different days** and keep adding more slots
   - Your previously selected slots remain selected
   - A counter badge shows total selected slots
5. **Review your selections** in the Summary sidebar:
   - Single slot: Shows date and time
   - Multiple slots: Shows list with individual dates/times
   - Each slot has a remove button (×)
6. **Click "Book X Lessons"** to proceed with OTP confirmation
7. **All lessons are booked together** after OTP verification

### Visual Indicators

#### Time Slot Counter Badge
- Appears in the slots panel header
- Shows: "X selected" in purple badge
- Updates in real-time as you add/remove slots

#### Summary Sidebar
- **Single slot**: Shows date and time normally
- **Multiple slots**: 
  - Date field: "X lessons"
  - Time field: "See below"
  - Expandable list showing all selected slots
  - Each slot has a remove button (×)

#### Button Text
- Single slot: "Continue to Confirm"
- Multiple slots: "Book X Lessons"

#### Total Price
- Automatically calculates: `price per lesson × number of slots`
- Example: 3 lessons @ $80 each = $240 total

### User Flow Example

```
1. Select: John Smith (Instructor)
2. Select: Basic Driving ($80, 60 min)
3. Click: January 5 → Select 9:00 AM ✓
4. Click: January 7 → Select 2:00 PM ✓
5. Click: January 12 → Select 10:30 AM ✓
6. Summary shows: "3 lessons" - Total: $240
7. Click: "Book 3 Lessons"
8. Enter phone & OTP
9. Success: All 3 lessons confirmed!
```

## Technical Details

### State Management
```javascript
state.selectedSlots = [
  { startISO: "2026-01-05T09:00:00Z", endISO: "2026-01-05T10:00:00Z", available: true },
  { startISO: "2026-01-07T14:00:00Z", endISO: "2026-01-07T15:00:00Z", available: true },
  { startISO: "2026-01-12T10:30:00Z", endISO: "2026-01-12T11:30:00Z", available: true }
]
```

### API Calls

#### Hold Multiple Slots
```javascript
// Creates individual holds for each selected slot
const holdPromises = selectedSlots.map(slot => 
  POST /booking/hold {
    instructorId, 
    lessonTypeId, 
    startISO: slot.startISO
  }
);
// Returns: [{ holdId: "H1" }, { holdId: "H2" }, { holdId: "H3" }]
```

#### Confirm Multiple Bookings
```javascript
// After OTP verification, confirms all holds
const bookingPromises = holdIds.map(holdId =>
  POST /booking/confirm {
    holdId,
    customerId
  }
);
// Returns: [{ bookingId: "B1" }, { bookingId: "B2" }, { bookingId: "B3" }]
```

### Error Handling

#### Slot Becomes Unavailable
- If any slot is no longer available when creating holds
- Shows error: "One or more slots are no longer available"
- User must reselect slots

#### Hold Expiration
- If OTP takes too long (>10 minutes)
- Shows error: "Time expired. Please select your slots again"
- User returns to slot selection

#### Partial Failures
- Currently uses `Promise.all()` - all or nothing approach
- If one fails, all fail (to prevent partial bookings)
- Could be enhanced to support partial success with user confirmation

## UX Benefits

✅ **Saves Time**: Book multiple lessons in one go
✅ **Convenience**: Plan weeks ahead in single session  
✅ **Clear Feedback**: See all selections before confirming
✅ **Easy Editing**: Add/remove individual slots anytime
✅ **Single Payment**: One OTP, one confirmation for everything

## Backend Requirements

The backend should support:

1. **Multiple Hold Creation**: Handle rapid sequential hold requests
2. **Hold Management**: Track multiple holds per session
3. **Batch Confirmation**: Convert multiple holds to bookings atomically
4. **Transaction Safety**: Ensure all-or-nothing booking (avoid partial failures)

### Recommended Backend Enhancement

Consider adding a **batch booking endpoint**:

```php
POST /booking/batch-hold
{
  "instructorId": 1,
  "lessonTypeId": 2,
  "slots": [
    { "startISO": "..." },
    { "startISO": "..." },
    { "startISO": "..." }
  ]
}
// Returns: { "holdIds": ["H1", "H2", "H3"], "expiresAt": "..." }

POST /booking/batch-confirm
{
  "holdIds": ["H1", "H2", "H3"],
  "customerId": 123
}
// Returns: { "bookingIds": ["B1", "B2", "B3"], "status": "confirmed" }
```

This would be more efficient than multiple individual API calls.

## Testing in Demo Mode

Demo mode fully supports multi-slot booking:

1. Open the app (with `DEMO_MODE = true`)
2. Select instructor and lesson type
3. Browse calendar and click multiple green days
4. Select multiple time slots from each day
5. Watch the counter badge update
6. Review the list in the summary sidebar
7. Use OTP `123456` to confirm all bookings

Try booking 5+ lessons to see the full experience!

## Future Enhancements

- **Package Deals**: Discount for booking X lessons at once
- **Smart Suggestions**: "Book the same time next 4 weeks"
- **Recurring Bookings**: "Every Tuesday at 2 PM for 8 weeks"
- **Calendar Export**: Download all booked lessons to Google/Apple Calendar
- **Drag to Select**: Select multiple consecutive slots with click-and-drag

---

This feature makes the booking system significantly more powerful while maintaining simplicity for single bookings! 🎉

