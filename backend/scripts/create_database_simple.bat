@echo off
setlocal enabledelayedexpansion

REM ============================================
REM Simple Database Creation (No TCP/IP)
REM Uses Windows named pipes (default)
REM ============================================

echo ============================================
echo Driving School Database Setup
echo ============================================
echo.

REM Check if PostgreSQL is available
"C:\Program Files\PostgreSQL\16\bin\psql.exe" --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PostgreSQL psql is not found.
    echo Please ensure PostgreSQL 16 is installed.
    echo.
    pause
    exit /b 1
)

echo PostgreSQL found.
echo.

REM Create the database (without -h, uses named pipes)
echo Creating database...
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -c "CREATE DATABASE driving_school;"
if errorlevel 1 (
    echo.
    echo ERROR: Failed to create database.
    echo The database might already exist, or you entered the wrong password.
    echo.
    pause
    exit /b 1
)

echo Database created successfully!
echo.

REM Run the SQL script
echo Creating tables and schema...
cd /d "%~dp0"
"C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -d driving_school -f "..\sql\create_database.sql"
if errorlevel 1 (
    echo.
    echo ERROR: Failed to create tables.
    echo Please check the error messages above.
    echo.
    pause
    exit /b 1
)

echo.
echo ============================================
echo Database setup complete!
echo ============================================
echo.
echo Database: driving_school
echo You can now connect using:
echo   "C:\Program Files\PostgreSQL\16\bin\psql.exe" -U postgres -d driving_school
echo.
pause

