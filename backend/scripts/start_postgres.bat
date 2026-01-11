@echo off
setlocal enabledelayedexpansion

REM ============================================
REM Start PostgreSQL Service
REM ============================================
REM Run this as Administrator
REM ============================================

echo ============================================
echo Start PostgreSQL Service
echo ============================================
echo.

REM Service name found: postgresql-x64-16
set SERVICE_NAME=postgresql-x64-16

echo Checking service status...
sc query %SERVICE_NAME% | findstr /i "STATE" >nul
if errorlevel 1 (
    echo ERROR: Service %SERVICE_NAME% not found.
    echo.
    pause
    exit /b 1
)

sc query %SERVICE_NAME% | findstr /i "RUNNING" >nul
if not errorlevel 1 (
    echo PostgreSQL service is already running!
    echo.
    pause
    exit /b 0
)

echo Service found but not running.
echo Attempting to start service...
echo.
echo NOTE: You need Administrator privileges to start services.
echo If this fails, right-click this file and select "Run as administrator"
echo.

net start %SERVICE_NAME%
if errorlevel 1 (
    echo.
    echo ============================================
    echo ERROR: Failed to start service.
    echo ============================================
    echo.
    echo You need to run this script as Administrator:
    echo 1. Right-click on this file
    echo 2. Select "Run as administrator"
    echo.
    echo OR manually start it:
    echo 1. Press Windows + R
    echo 2. Type: services.msc
    echo 3. Find: %SERVICE_NAME%
    echo 4. Right-click and select "Start"
    echo.
    pause
    exit /b 1
) else (
    echo.
    echo ============================================
    echo PostgreSQL service started successfully!
    echo ============================================
    echo.
    echo You can now run create_database.bat or connect to PostgreSQL.
    echo.
)

pause

