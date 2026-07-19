@echo off
setlocal
:: Stop + uninstall PortalSifast RS Agent Windows Service (Administrator).

set "ROOT=%~dp0.."
for %%I in ("%ROOT%") do set "ROOT=%%~fI"
set "EXE=%ROOT%\rs-agent.exe"
set "CFG=%ROOT%\configs\config.json"

if not exist "%EXE%" (
  echo ERROR: %EXE% not found.
  exit /b 1
)

"%EXE%" -config "%CFG%" -service stop 2>nul
"%EXE%" -config "%CFG%" -service uninstall
echo Uninstall attempted.
endlocal
