@echo off
setlocal
:: Jalankan sebagai Administrator.
:: Opsional: set AGENT_ENROLLMENT_KEY=... sebelum menjalankan, atau edit baris di bawah.

cd /d "%~dp0"
powershell -NoProfile -ExecutionPolicy Bypass -File "%~dp0install-service.ps1" %*
if errorlevel 1 exit /b 1
endlocal
