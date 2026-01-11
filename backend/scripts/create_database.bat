@echo off
setlocal enabledelayedexpansion

REM ============================================
REM PostgreSQL Database Creation Script (Windows)
REM ============================================

echo ============================================
echo Driving School Database Setup
echo ============================================
echo.

REM Check if PostgreSQL is available
psql --version >nul 2>&1
if errorlevel 1 (
    echo ERROR: PostgreSQL psql is not found in PATH.
    echo Please install PostgreSQL or add it to your system PATH.
    echo.
    pause
    exit /b 1
)

echo PostgreSQL found.
echo.

REM Prompt for database name
set DB_NAME=driving_school
set /p "DB_NAME=Enter database name [driving_school]: "
if "!DB_NAME!"=="" set DB_NAME=driving_school

REM Prompt for username
set DB_USER=postgres
set /p "DB_USER=Enter PostgreSQL username [postgres]: "
if "!DB_USER!"=="" set DB_USER=postgres

REM Prompt for host
set DB_HOST=localhost
set /p "DB_HOST=Enter PostgreSQL host [localhost]: "
if "!DB_HOST!"=="" set DB_HOST=localhost

echo.
echo ============================================
echo Creating database: !DB_NAME!
echo User: !DB_USER!
echo Host: !DB_HOST!
echo ============================================
echo.

REM Create the database
echo Creating database...
psql -U !DB_USER! -h !DB_HOST! -c "CREATE DATABASE !DB_NAME!;" postgres
if errorlevel 1 (
    echo.
    echo ERROR: Failed to create database.
    echo The database might already exist, or there was a connection error.
    echo.
    pause
    exit /b 1
)

echo Database created successfully!
echo.

REM Run the SQL script
echo Creating tables and schema...
psql -U !DB_USER! -h !DB_HOST! -d !DB_NAME! -f "%~dp0..\sql\create_database.sql"
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
echo Database: !DB_NAME!
echo You can now connect to the database using:
echo   psql -U !DB_USER! -h !DB_HOST! -d !DB_NAME!
echo.
pause
