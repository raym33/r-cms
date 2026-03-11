@echo off
cd /d %~dp0
@echo off
setlocal
cd /d "%~dp0"
if not exist ".linuxcms-runtime" mkdir ".linuxcms-runtime"
set HOST=127.0.0.1
set PORT=8088
set URL=http://%HOST%:%PORT%/r-admin/
start "LinuxCMS" /B php -S %HOST%:%PORT% "%cd%\router.php" >> ".linuxcms-runtime\server.log" 2>&1
echo LinuxCMS started at %URL%
echo Open this in your browser: %URL%
endlocal
