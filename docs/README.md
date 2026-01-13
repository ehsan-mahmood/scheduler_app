# GitHub Pages Demo

This directory contains the GitHub Pages demo of the Generic Scheduler.

## Live Demo

The demo is available at: `https://[your-username].github.io/[repo-name]/`

## Features

- ✅ Fully functional demo (no backend required)
- ✅ LocalStorage for state persistence
- ✅ Mock API responses
- ✅ Complete booking flow
- ✅ Payment simulation
- ✅ Notification simulation

## How It Works

1. The demo automatically detects GitHub Pages hostname
2. Forces demo mode (no backend connection)
3. Uses LocalStorage to persist bookings and payments
4. All API calls are simulated with mock responses

## Files

- `index.html` - Main demo application (full scheduler - booking interface)
- `portal.html` - Portal demo (admin/instructor/customer dashboard)
- `.nojekyll` - Disables Jekyll processing

## Setup

1. Enable GitHub Pages in repository settings
2. Select source: `/docs` folder
3. The demo will be live at your GitHub Pages URL

## Testing Locally

You can test the GitHub Pages version locally:

```bash
# Serve the docs folder
cd docs
python -m http.server 8000
# Open http://localhost:8000
```

The demo will automatically detect it's not on GitHub Pages and still work in demo mode.
