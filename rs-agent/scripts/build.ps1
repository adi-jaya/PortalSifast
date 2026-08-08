param(
    [string]$Version = "0.2.0"
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
Set-Location $root

$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" +
    [System.Environment]::GetEnvironmentVariable("Path", "User")

Write-Host "== gofmt =="
gofmt -w ./cmd ./internal

Write-Host "== go test =="
go test ./...
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Write-Host "== go vet =="
go vet ./...
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

New-Item -ItemType Directory -Force -Path "dist\PortalSifast-Agent\scripts" | Out-Null
New-Item -ItemType Directory -Force -Path "dist\PortalSifast-Agent\configs" | Out-Null

Write-Host "== go build v$Version =="
go build -ldflags "-X main.version=$Version" -o "dist\rs-agent.exe" ./cmd/rs-agent
if ($LASTEXITCODE -ne 0) { exit $LASTEXITCODE }

Copy-Item -Force "dist\rs-agent.exe" "dist\PortalSifast-Agent\rs-agent.exe"
Copy-Item -Force "configs\config.example.json" "dist\PortalSifast-Agent\configs\config.example.json"
Copy-Item -Force "scripts\install-service.ps1" "dist\PortalSifast-Agent\scripts\install-service.ps1"
Copy-Item -Force "scripts\uninstall-service.ps1" "dist\PortalSifast-Agent\scripts\uninstall-service.ps1"
Copy-Item -Force "scripts\install-service.bat" "dist\PortalSifast-Agent\scripts\install-service.bat"
Copy-Item -Force "scripts\uninstall-service.bat" "dist\PortalSifast-Agent\scripts\uninstall-service.bat"
Copy-Item -Force "README.md" "dist\PortalSifast-Agent\README.md"

Write-Host "Paket siap: dist\PortalSifast-Agent\"
Write-Host "JANGAN salin configs\config.json development ke paket ini."
