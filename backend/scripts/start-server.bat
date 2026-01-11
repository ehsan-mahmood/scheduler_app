@echo off
echo Starting Driving School Backend API Server...
echo.
echo Backend API will be available at: http://localhost:8001
echo.
echo Press Ctrl+C to stop the server
echo.
cd /d "%~dp0.."
php -S 0.0.0.0:8001 router.php

