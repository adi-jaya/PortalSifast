param(
    [string]$Version = "0.2.1",
    [string]$Server = "",
    [string]$EnrollmentKey = "",
    [switch]$SkipBuildAgent
)

$ErrorActionPreference = "Stop"
$root = Split-Path -Parent $PSScriptRoot
$laravelRoot = Split-Path -Parent $root
Set-Location $root

$env:Path = [System.Environment]::GetEnvironmentVariable("Path", "Machine") + ";" +
    [System.Environment]::GetEnvironmentVariable("Path", "User")

function Get-DotEnvValue {
    param(
        [string]$Path,
        [string]$Name
    )
    if (-not (Test-Path $Path)) {
        return $null
    }
    foreach ($line in Get-Content -Path $Path) {
        $trim = $line.Trim()
        if ($trim -eq "" -or $trim.StartsWith("#")) {
            continue
        }
        $parts = $trim.Split("=", 2)
        if ($parts.Count -ne 2) {
            continue
        }
        if ($parts[0].Trim() -ne $Name) {
            continue
        }
        $value = $parts[1].Trim()
        if (($value.StartsWith('"') -and $value.EndsWith('"')) -or ($value.StartsWith("'") -and $value.EndsWith("'"))) {
            $value = $value.Substring(1, $value.Length - 2)
        }
        return $value
    }
    return $null
}

if (-not $SkipBuildAgent) {
    Write-Host "== build agent v$Version =="
    & "$PSScriptRoot\build.ps1" -Version $Version
    if ($LASTEXITCODE -ne 0) {
        exit $LASTEXITCODE
    }
}

$dotenv = Join-Path $laravelRoot ".env"

if ([string]::IsNullOrWhiteSpace($EnrollmentKey)) {
    $EnrollmentKey = $env:AGENT_ENROLLMENT_KEY
}
if ([string]::IsNullOrWhiteSpace($EnrollmentKey)) {
    $EnrollmentKey = Get-DotEnvValue -Path $dotenv -Name "AGENT_ENROLLMENT_KEY"
}

# Prefer dedicated agent URL — do NOT fall back to APP_URL (often http://localhost in .env).
if ([string]::IsNullOrWhiteSpace($Server)) {
    $Server = $env:AGENT_SERVER_URL
}
if ([string]::IsNullOrWhiteSpace($Server)) {
    $Server = Get-DotEnvValue -Path $dotenv -Name "AGENT_SERVER_URL"
}
if ([string]::IsNullOrWhiteSpace($Server)) {
    $Server = "https://portalsifast.rsaisyiyahsitifatimah.com"
}
$Server = $Server.TrimEnd("/")
if ($Server -match '^https?://(localhost|127\.0\.0\.1)(:|$)') {
    Write-Warning "Server URL looks local ($Server). PC clients will not reach production. Pass -Server https://..."
}

$isccCandidates = @(
    "${env:ProgramFiles(x86)}\Inno Setup 6\ISCC.exe",
    "${env:ProgramFiles}\Inno Setup 6\ISCC.exe",
    "${env:LocalAppData}\Programs\Inno Setup 6\ISCC.exe"
)
$iscc = $null
foreach ($c in $isccCandidates) {
    if (Test-Path $c) {
        $iscc = $c
        break
    }
}
if (-not $iscc) {
    Write-Error "ISCC.exe tidak ditemukan. Install Inno Setup 6 dulu."
}

$iss = Join-Path $root "installer\PortalSifast-Agent.iss"
$defines = @(
    "/DMyAppVersion=$Version",
    "/DMyServerURL=$Server"
)

$oneClick = $false
if (-not [string]::IsNullOrWhiteSpace($EnrollmentKey)) {
    $safeKey = $EnrollmentKey.Replace('"', '')
    if ($safeKey -eq "change-me-agent-enrollment" -or $safeKey -eq "PASTE_AGENT_ENROLLMENT_KEY_FROM_SERVER_ENV") {
        Write-Warning "Enrollment key masih placeholder. Set AGENT_ENROLLMENT_KEY di .env production."
    }
    $defines += "/DMyEnrollmentKey=$safeKey"
    $oneClick = $true
    Write-Host "Mode 1-klik: key tertanam, wizard key di-skip."
} else {
    Write-Host "WARNING: EnrollmentKey kosong - wizard akan meminta key saat install."
}

Write-Host "Server: $Server"
Write-Host "== compile installer =="
& $iscc @defines $iss
if ($LASTEXITCODE -ne 0) {
    exit $LASTEXITCODE
}

$setup = Join-Path $root "dist\PortalSifast-Agent-Setup.exe"
if (-not (Test-Path $setup)) {
    Write-Error "Setup.exe tidak ditemukan di $setup"
}

# Zip for GitHub Releases / USB share
$zipPath = Join-Path $root "dist\PortalSifast-Agent-Setup-$Version.zip"
if (Test-Path $zipPath) {
    Remove-Item -Force $zipPath
}
Compress-Archive -Path $setup -DestinationPath $zipPath -Force

Write-Host ""
Write-Host "Siap:"
Write-Host "  $setup"
Write-Host "  $zipPath"
if ($oneClick) {
    Write-Host "PC client: double-click Setup.exe -> izinkan UAC -> selesai (tanpa ketik key)."
} else {
    Write-Host "PC client: double-click Setup.exe -> izinkan UAC -> isi enrollment key."
}
Write-Host "Agent jalan sebagai Windows Service - TIDAK ada jendela terminal."
