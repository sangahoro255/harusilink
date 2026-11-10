@echo off
cd /d "%~dp0"
set PHPBIN=php
if exist "C:\xampp\php\php.exe" set PHPBIN=C:\xampp\php\php.exe
echo.
echo HarusiLink inaendeshwa: http://localhost:8000
echo Hakikisha .env ina: APP_URL=http://localhost:8000
echo Bonyeza Ctrl+C kusimamisha.
echo.
"%PHPBIN%" -S localhost:8000 -t public public/router.php
