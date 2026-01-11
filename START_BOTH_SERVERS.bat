@echo off
echo ========================================
echo Driving School Scheduler - Start Servers
echo ========================================
echo.
echo Starting both Frontend and Backend servers...
echo.
echo Frontend: http://localhost:8000/driving_school_app.html
echo Backend API: http://localhost:8001
echo.
echo Press Ctrl+C to stop all servers
echo.

start "Driving School Backend" cmd /k "cd backend && php -S localhost:8001 router.php"
timeout /t 2 /nobreak >nul
start "Driving School Frontend" cmd /k "cd frontend && python scripts\server.py 8000"

echo.
echo Both servers started in separate windows!
echo Close the windows or press Ctrl+C in each to stop them.
pause

