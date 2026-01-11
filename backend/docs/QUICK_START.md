# Quick Start Guide - PostgreSQL Setup

## Problem: "Connection refused" Error

This error means PostgreSQL service is not running.

## Solution: Start PostgreSQL Service

### Method 1: Using Services (Easiest - No Admin Needed)

1. Press **Windows + R**
2. Type: `services.msc`
3. Press Enter
4. Scroll down and find **`postgresql-x64-16`**
5. Right-click it
6. Click **"Start"**

That's it! The service should start.

### Method 2: Using Batch File (Requires Admin)

1. Right-click `start_postgres.bat`
2. Select **"Run as administrator"**
3. Follow the prompts

### Method 3: Command Line (Requires Admin)

1. Open Command Prompt as Administrator
   - Press Windows key
   - Type "cmd"
   - Right-click "Command Prompt"
   - Select "Run as administrator"

2. Run:
   ```cmd
   net start postgresql-x64-16
   ```

## After Starting the Service

Once the service is running, you can create the database:

### Option 1: Use the Batch File (Recommended)

```cmd
create_database.bat
```

### Option 2: Manual Commands

1. **Create the database:**
   ```cmd
   "C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -c "CREATE DATABASE driving_school;"
   ```

2. **Run the SQL script:**
   ```cmd
   "C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -d driving_school -f create_database.sql
   ```

You'll be prompted for the PostgreSQL password.

## Set Service to Start Automatically

To avoid this in the future:

1. Open Services (`services.msc`)
2. Find `postgresql-x64-16`
3. Right-click → Properties
4. Set "Startup type" to **"Automatic"**
5. Click OK

Now PostgreSQL will start automatically when Windows boots.

## Verify Service is Running

Check service status:
```cmd
sc query postgresql-x64-16
```

Look for `STATE: 4 RUNNING`

Or test connection:
```cmd
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -c "SELECT version();"
```

