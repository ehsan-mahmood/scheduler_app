# Troubleshooting PostgreSQL Connection Issues

## Problem: Service Running But "Connection Refused"

If the service shows as running but you get "Connection refused", PostgreSQL may not be configured to accept TCP/IP connections.

## Solution 1: Use Named Pipes (Windows Default)

On Windows, PostgreSQL uses named pipes by default. Try connecting **without** `-h localhost`:

```cmd
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres
```

Or to create database:
```cmd
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -c "CREATE DATABASE driving_school;"
```

Then run the SQL script:
```cmd
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -d driving_school -f create_database.sql
```

## Solution 2: Enable TCP/IP in PostgreSQL Config

If Solution 1 doesn't work, PostgreSQL needs TCP/IP enabled:

1. **Stop PostgreSQL service:**
   - Open Services (`services.msc`)
   - Find `postgresql-x64-16`
   - Right-click → Stop

2. **Edit postgresql.conf:**
   - Location: `C:\Program Files\PostgreSQL\16\data\postgresql.conf`
   - Find line: `#listen_addresses = 'localhost'`
   - Change to: `listen_addresses = '*'` (or `'localhost'`)
   - Uncomment the line (remove the `#`)

3. **Edit pg_hba.conf (if needed):**
   - Location: `C:\Program Files\PostgreSQL\16\data\pg_hba.conf`
   - Ensure this line exists:
   ```
   host    all             all             127.0.0.1/32            md5
   ```

4. **Start PostgreSQL service again:**
   - In Services, right-click `postgresql-x64-16` → Start

5. **Test connection:**
   ```cmd
   "C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -h localhost -c "SELECT version();"
   ```

## Solution 3: Reinstall/Repair PostgreSQL

If the above doesn't work, PostgreSQL installation might be corrupted:

1. **Stop the service**
2. **Run PostgreSQL installer**
3. **Choose "Repair" or "Reinstall"**
4. **During setup, ensure TCP/IP is enabled**

## Quick Test Commands

**Test 1 - Named pipes (no -h):**
```cmd
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -c "SELECT version();"
```

**Test 2 - TCP/IP localhost:**
```cmd
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -h localhost -c "SELECT version();"
```

**Test 3 - Check if port is listening:**
```cmd
netstat -an | findstr ":5432"
```

## Most Likely Fix

**Try Solution 1 first** - just remove `-h localhost` from your commands. Windows PostgreSQL often uses named pipes by default, not TCP/IP.

