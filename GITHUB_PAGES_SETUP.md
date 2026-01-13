# GitHub Pages Setup Guide

## Quick Setup

1. **Enable GitHub Pages:**
   - Go to repository Settings → Pages
   - Source: Deploy from a branch
   - Branch: `main` → `/docs` folder
   - Click Save

2. **Your demo will be live at:**
   ```
   https://[your-username].github.io/[repo-name]/
   ```

## What's Included

- `docs/index.html` - Full scheduler demo (forced demo mode)
- `docs/.nojekyll` - Disables Jekyll processing
- `docs/README.md` - Demo documentation

## Features

✅ **Fully Functional Demo**
- No backend required
- Complete booking flow
- Payment simulation
- Notification simulation
- LocalStorage persistence

✅ **Auto-Detection**
- Automatically detects GitHub Pages hostname
- Forces demo mode
- Shows demo banner

## Testing Locally

Before pushing, test locally:

```bash
cd docs
python -m http.server 8000
# Open http://localhost:8000
```

## After Deployment

1. Wait 1-2 minutes for GitHub Pages to build
2. Visit your GitHub Pages URL
3. The demo should work immediately (no backend needed)

## Troubleshooting

**404 Error:**
- Make sure `/docs` folder is selected as source
- Check that `index.html` exists in `/docs`
- Wait a few minutes for GitHub to build

**Demo not working:**
- Check browser console for errors
- Verify `.nojekyll` file exists
- Clear browser cache

**Styles not loading:**
- All styles are inline in `index.html` (no external CSS)
- Should work without any additional files

## Notes

- The demo uses LocalStorage (data persists in browser)
- All API calls are simulated
- No real payments, SMS, or emails are sent
- Perfect for showcasing the scheduler functionality

