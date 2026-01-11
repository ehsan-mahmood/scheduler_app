# How to Start PostgreSQL Service on Windows

## The Issue

You're getting "Connection refused" because the PostgreSQL server service is not running.

## Solution: Start PostgreSQL Service

### Option 1: Using Services (GUI - Easiest)

1. Press `Windows + R`
2. Type `services.msc` and press Enter
3. Find the PostgreSQL service (usually named "postgresql-x64-16" or "PostgreSQL 16")
4. Right-click and select "Start"

### Option 2: Using Command Line (Run as Administrator)

1. Open Command Prompt as Administrator (Right-click Command Prompt → Run as administrator)
2. Run one of these commands (try the one that matches your PostgreSQL version):

```cmd
net start postgresql-x64-16
```

Or:

```cmd
net start "PostgreSQL 16"
```

Or if you have version 15:

```cmd
net start postgresql-x64-15
```

Or:

```cmd
net start "PostgreSQL 15"
```

### Option 3: Using PowerShell (Run as Administrator)

```powershell
Start-Service postgresql-x64-16
```

Or:

```powershell
Start-Service "PostgreSQL 16"
```

## Find Your Service Name

To find the exact service name, run:

```cmd
sc query | findstr /i postgres
```

Or check Services GUI and look for the PostgreSQL service name.

## After Starting the Service

Once the service is running, you can:

1. **Create the database first:**
   ```cmd
   "C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -c "CREATE DATABASE driving_school;"
   ```

2. **Then run the SQL script:**
   ```cmd
   "C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -d driving_school -f create_database.sql
   ```

   **Note:** Fix the typo - use `-U postgres` (not `postges`)

3. **Or use the batch file:**
   ```cmd
   create_database.bat
   ```

## Set Service to Start Automatically

To make PostgreSQL start automatically on Windows boot:

1. Open Services (`services.msc`)
2. Find PostgreSQL service
3. Right-click → Properties
4. Set "Startup type" to "Automatic"
5. Click OK

## Verify Connection

Test if PostgreSQL is running:

```cmd
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -c "SELECT version();"
```

You'll be prompted for the password you set during PostgreSQL installation.

