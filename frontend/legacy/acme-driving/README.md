# Acme Driving Academy - Booking App

This folder contains the booking application for **Acme Driving Academy**.

## Access URLs

- **With filename**: `http://localhost:8000/acme-driving/driving_school_app.html`
- **Clean URL**: `http://localhost:8000/acme-driving/` (uses index.html)
- **Alternative**: `http://localhost:8000/acme-driving/index.html`

All these URLs will automatically detect the business as `acme-driving`.

## How It Works

The app automatically detects the business slug from the URL:
- URL path: `/acme-driving/...` → Business: `acme-driving`
- This business slug is used for all API calls: `/acme-driving/api/...`

## Configuration

The detection happens automatically. Check browser console (F12) to see:

```javascript
🚗 Driving School Booking Configuration: {
  detectedBusiness: "acme-driving",
  demoMode: false,
  ...
}
```

## Backend Requirement

Ensure your backend has a business record with:
- `subdomain`: `acme-driving`
- `status`: `active`

## Testing

1. Start backend: `cd backend && php -S localhost:8001 router.php`
2. Start frontend: `cd frontend && python -m http.server 8000`
3. Visit: `http://localhost:8000/acme-driving/`

## Demo Mode

If backend is not available, the app will try to connect and show errors. To use demo mode, access the file without a business path: `http://localhost:8000/driving_school_app.html`

