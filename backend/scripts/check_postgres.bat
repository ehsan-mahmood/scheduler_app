@echo off
REM ============================================
REM Check PostgreSQL Configuration
REM ============================================

echo ============================================
echo PostgreSQL Diagnostic Check
echo ============================================
echo.

echo 1. Checking service status...
sc query postgresql-x64-16 | findstr /i "STATE"
echo.

echo 2. Checking if port 5432 is listening...
netstat -an | findstr ":5432"
if errorlevel 1 (
    echo    Port 5432 is NOT listening
) else (
    echo    Port 5432 is listening
)
echo.

echo 3. Checking PostgreSQL data directory...
if exist "C:\Program Files\PostgreSQL\16\data" (
    echo    Data directory exists: C:\Program Files\PostgreSQL\16\data
) else (
    echo    Data directory NOT found
)
echo.

echo 4. Checking postgresql.conf location...
if exist "C:\Program Files\PostgreSQL\16\data\postgresql.conf" (
    echo    Found: C:\Program Files\PostgreSQL\16\data\postgresql.conf
    echo.
    echo    Checking port setting...
    findstr /i "^port" "C:\Program Files\PostgreSQL\16\data\postgresql.conf" 2>nul
    if errorlevel 1 (
        echo    Port setting not found in config (using default 5432)
    )
) else (
    echo    postgresql.conf NOT found
)
echo.

echo 5. Checking pg_hba.conf location...
if exist "C:\Program Files\PostgreSQL\16\data\pg_hba.conf" (
    echo    Found: C:\Program Files\PostgreSQL\16\data\pg_hba.conf
    echo    (TCP/IP connection settings are in this file)
) else (
    echo    pg_hba.conf NOT found
)
echo.

echo 6. Trying to connect with psql...
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -c "SELECT version();" 2>&1 | findstr /i /v "password"
echo.

echo ============================================
echo Diagnostic Complete
echo ============================================
echo.
echo If port 5432 is not listening, PostgreSQL may not be configured
echo to accept TCP/IP connections, or it may be using a different port.
echo.
pause

