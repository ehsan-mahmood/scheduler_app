@echo off
REM Multi-Tenant Frontend Server Starter
REM This starts the Python server that routes all business URLs to the same file

echo ============================================================
echo  Starting Multi-Tenant Frontend Server
echo ============================================================
echo.

REM Check if Python is available
python --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ERROR: Python is not installed or not in PATH
    echo Please install Python from https://www.python.org/
    pause
    exit /b 1
)

REM Navigate to frontend directory (parent of scripts folder)
cd /d "%~dp0\.."

echo Starting server on port 8000...
echo.
echo After server starts, visit:
echo   http://localhost:8000/
echo.
echo Then try any business URL like:
echo   http://localhost:8000/acme-driving/
echo   http://localhost:8000/city-school/
echo   http://localhost:8000/your-business-name/
echo.
echo Press Ctrl+C to stop the server
echo ============================================================
echo.

python scripts\server.py 8000

pause

