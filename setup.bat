@echo off
rem Wrapper that bypasses the PowerShell execution policy and runs setup.ps1.
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0setup.ps1"
echo.
pause
