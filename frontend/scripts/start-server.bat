@echo off
echo Starting Driving School App Server...
echo.
echo Choose a server option:
echo 1. Python HTTP Server (port 8000)
echo 2. Node.js HTTP Server (port 8080)
echo 3. PHP Built-in Server (port 8000)
echo.
set /p choice="Enter choice (1-3): "

if "%choice%"=="1" (
    echo.
    echo Starting Python HTTP Server on http://localhost:8000
    echo Press Ctrl+C to stop the server
    echo.
    cd /d "%~dp0\.."
    python scripts\server.py 8000
) else if "%choice%"=="2" (
    echo.
    echo Starting Node.js HTTP Server on http://localhost:8080
    echo Press Ctrl+C to stop the server
    echo.
    cd /d "%~dp0"
    npx --yes http-server -p 8080 -o
) else if "%choice%"=="3" (
    echo.
    echo Starting PHP Server on http://localhost:8000
    echo Press Ctrl+C to stop the server
    echo.
    cd /d "%~dp0"
    php -S localhost:8000
) else (
    echo Invalid choice!
    pause
)


