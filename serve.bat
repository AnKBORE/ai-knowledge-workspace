@echo off
rem Start the local development server with the correct PHP (8.4.27).
cd /d "%~dp0"
set "PHP=C:\wamp64\bin\php\php8.4.27\php.exe"
if not exist "%PHP%" set "PHP=php"
echo Using PHP: %PHP%
echo Workspace: http://127.0.0.1:8080/
echo Press Ctrl+C to stop.
"%PHP%" -d upload_max_filesize=20M -d post_max_size=25M -S 127.0.0.1:8080 -t public
pause
