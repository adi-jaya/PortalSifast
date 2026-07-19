@echo off
setlocal
:: Install + start PortalSifast RS Agent as Windows Service.
:: Run this file as Administrator from the rs-agent folder (or any folder).

set "ROOT=%~dp0.."
for %%I in ("%ROOT%") do set "ROOT=%%~fI"
set "EXE=%ROOT%\rs-agent.exe"
set "CFG=%ROOT%\configs\config.json"

if not exist "%EXE%" (
  echo ERROR: %EXE% not found. Build first: go build -o rs-agent.exe .\cmd\rs-agent
  exit /b 1
)
if not exist "%CFG%" (
  echo ERROR: %CFG% not found. Copy configs\config.example.json to configs\config.json and edit it.
  exit /b 1
)

echo Installing service with:
echo   exe: %EXE%
echo   cfg: %CFG%
"%EXE%" -config "%CFG%" -service install
if errorlevel 1 exit /b 1
"%EXE%" -config "%CFG%" -service start
if errorlevel 1 exit /b 1
"%EXE%" -config "%CFG%" -service status
echo Done. Check services.msc for "PortalSifast RS Agent".
endlocal
