@echo off
setlocal
:: Jalankan sebagai Administrator.
:: Hapus total data: uninstall-service.bat -PurgeData

cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0uninstall-service.ps1" %*
if errorlevel 1 exit /b 1
endlocal
