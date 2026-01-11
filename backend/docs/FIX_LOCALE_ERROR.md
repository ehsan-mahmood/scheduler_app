# Fix "unrecognised lc_locale" Error in pgAdmin

## The Problem

When creating a database in pgAdmin, you might see an error like:
- `unrecognised lc_locale`
- `locale "xxx" does not exist`
- Locale-related errors

This happens because PostgreSQL is trying to use a locale (language/country settings) that doesn't exist on your Windows system.

## Quick Fix

When creating the database in pgAdmin:

### Step-by-Step:

1. Right-click **"Databases"** → **Create** → **Database...**
2. Enter database name: `driving_school`
3. **Click the "Definition" tab** (at the top of the dialog)
4. Set these values:
   - **Template:** `template0` (not `template1`)
   - **Collation:** `C` (or leave empty)
   - **Character type:** `C` (or leave empty)
5. Click **"Save"**

### Why This Works

- `template0` is a clean template without locale dependencies
- `C` locale always exists on all systems (it's the default system locale)
- This avoids Windows locale compatibility issues

## Alternative: SQL Command

If you prefer, you can also create the database using SQL in pgAdmin Query Tool:

```sql
CREATE DATABASE driving_school 
    WITH TEMPLATE = template0 
    ENCODING = 'UTF8' 
    LC_COLLATE = 'C' 
    LC_CTYPE = 'C';
```

This does the same thing - creates the database using the `C` locale which always works.

## After Creating the Database

Once the database is created with the correct locale settings, you can:
1. Run the `create_database.sql` script normally
2. The script will create all tables without any locale issues
3. Your application will work fine - the locale only affects sorting/character handling, which `C` handles adequately for most applications

## Don't Worry About Performance

Using the `C` locale won't affect your application's performance or functionality. It's just the default system locale that ensures compatibility across all systems.

