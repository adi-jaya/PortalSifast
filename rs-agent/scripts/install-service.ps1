param(
    [string]$SourceExe = "",
    [string]$EnrollmentKey = "",
    [string]$EnrollmentKeyFile = "",
    [string]$Server = "https://portalsifast.rsaisyiyahsitifatimah.com",
    [string]$MigrateFrom = "",
    [switch]$SkipStart
)

$ErrorActionPreference = "Stop"

function Test-IsAdministrator {
    $identity = [Security.Principal.WindowsIdentity]::GetCurrent()
    $principal = New-Object Security.Principal.WindowsPrincipal($identity)
    return $principal.IsInRole([Security.Principal.WindowsBuiltInRole]::Administrator)
}

function Write-InstallLog {
    param([string]$Message)
    $line = "[{0}] {1}" -f (Get-Date -Format "yyyy-MM-dd HH:mm:ss"), $Message
    Write-Host $line
    try {
        $logDir = Join-Path $env:ProgramData "PortalSifast Agent\logs"
        New-Item -ItemType Directory -Force -Path $logDir | Out-Null
        Add-Content -Path (Join-Path $logDir "install.log") -Value $line -Encoding UTF8
    } catch {
        # Logging must never abort install.
    }
}

if (-not (Test-IsAdministrator)) {
    Write-Error "Jalankan skrip ini sebagai Administrator."
}

# Optional enroll file from Inno Setup (avoids CLI quoting breakage).
# Format: lines "server=..." and/or "enrollment_key=..."
if (-not [string]::IsNullOrWhiteSpace($EnrollmentKeyFile) -and (Test-Path -LiteralPath $EnrollmentKeyFile)) {
    Write-InstallLog "Membaca enrollment file: $EnrollmentKeyFile"
    Get-Content -LiteralPath $EnrollmentKeyFile -Encoding UTF8 | ForEach-Object {
        $trim = $_.Trim()
        if ($trim -eq "" -or $trim.StartsWith("#")) {
            return
        }
        $parts = $trim.Split("=", 2)
        if ($parts.Count -ne 2) {
            return
        }
        $name = $parts[0].Trim().ToLowerInvariant()
        $value = $parts[1].Trim()
        if ($name -eq "server" -and -not [string]::IsNullOrWhiteSpace($value)) {
            $Server = $value.TrimEnd("/")
        }
        if ($name -eq "enrollment_key" -and -not [string]::IsNullOrWhiteSpace($value)) {
            $EnrollmentKey = $value
        }
    }
}

if (-not [string]::IsNullOrWhiteSpace($env:AGENT_ENROLLMENT_KEY) -and [string]::IsNullOrWhiteSpace($EnrollmentKey)) {
    $EnrollmentKey = $env:AGENT_ENROLLMENT_KEY
}
if (-not [string]::IsNullOrWhiteSpace($env:AGENT_SERVER_URL) -and $Server -eq "https://portalsifast.rsaisyiyahsitifatimah.com") {
    $Server = $env:AGENT_SERVER_URL.TrimEnd("/")
}

$ProgramFilesDir = Join-Path ${env:ProgramFiles} "PortalSifast Agent"
$ProgramDataDir = Join-Path $env:ProgramData "PortalSifast Agent"
$TargetExe = Join-Path $ProgramFilesDir "rs-agent.exe"
$TargetConfig = Join-Path $ProgramDataDir "config.json"
$ServiceName = "PortalSifastAgent"

Write-InstallLog "Mulai install-service.ps1"
Write-InstallLog "SourceExe=$SourceExe Server=$Server SkipStart=$SkipStart"

if ([string]::IsNullOrWhiteSpace($SourceExe)) {
    $repoRoot = Split-Path -Parent $PSScriptRoot
    $candidates = @(
        (Join-Path $repoRoot "dist\rs-agent.exe"),
        (Join-Path $repoRoot "rs-agent.exe")
    )
    foreach ($candidate in $candidates) {
        if (Test-Path $candidate) {
            $SourceExe = $candidate
            break
        }
    }
}

if (-not (Test-Path $SourceExe)) {
    Write-InstallLog "ERROR: binary tidak ditemukan"
    Write-Error "Binary tidak ditemukan. Build dulu: .\scripts\build.ps1"
}

New-Item -ItemType Directory -Force -Path $ProgramFilesDir | Out-Null
New-Item -ItemType Directory -Force -Path $ProgramDataDir | Out-Null

$existing = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if ($existing -and $existing.Status -ne "Stopped") {
    Write-InstallLog "Menghentikan service $ServiceName..."
    if (Test-Path $TargetExe) {
        & $TargetExe -config $TargetConfig -service stop 2>$null
    }
    Start-Sleep -Seconds 2
    Stop-Service -Name $ServiceName -Force -ErrorAction SilentlyContinue
}

$sourceFull = (Resolve-Path -LiteralPath $SourceExe).Path
$targetFull = $TargetExe
if ($sourceFull -ne $targetFull) {
    Copy-Item -Force -Path $SourceExe -Destination $TargetExe
    Write-InstallLog "Binary disalin ke $TargetExe"
} else {
    Write-InstallLog "Binary sudah di $TargetExe; skip copy."
}

$needEnrollment = $true
if (Test-Path $TargetConfig) {
    try {
        $cfg = Get-Content -Raw -Path $TargetConfig | ConvertFrom-Json
        if ($cfg.api_key -and $cfg.api_key.ToString().Trim() -ne "") {
            $needEnrollment = $false
            Write-InstallLog "Config ProgramData sudah terdaftar; mempertahankan uuid/api_key."
        }
    } catch {
        Write-InstallLog "WARNING: Config ProgramData tidak valid; akan dibuat ulang."
    }
}

if (-not (Test-Path $TargetConfig) -or $needEnrollment) {
    if (-not [string]::IsNullOrWhiteSpace($MigrateFrom) -and (Test-Path $MigrateFrom)) {
        Copy-Item -Force -Path $MigrateFrom -Destination $TargetConfig
        Write-InstallLog "Config dimigrasikan dari $MigrateFrom"
        $needEnrollment = $false
        try {
            $cfg = Get-Content -Raw -Path $TargetConfig | ConvertFrom-Json
            if (-not $cfg.api_key -or $cfg.api_key.ToString().Trim() -eq "") {
                $needEnrollment = $true
            }
        } catch {
            $needEnrollment = $true
        }
    }
}

if ($needEnrollment) {
    if ([string]::IsNullOrWhiteSpace($EnrollmentKey)) {
        Write-InstallLog "ERROR: enrollment key kosong"
        Write-Error "Enrollment key wajib untuk instalasi baru. Set -EnrollmentKey, -EnrollmentKeyFile, atau env AGENT_ENROLLMENT_KEY."
    }

    $fresh = [ordered]@{
        server         = $Server
        enrollment_key = $EnrollmentKey
        api_key        = ""
        interval       = 30
        log_level      = "info"
        uuid           = ""
    }
    $json = ($fresh | ConvertTo-Json)
    $utf8NoBom = New-Object System.Text.UTF8Encoding $false
    [System.IO.File]::WriteAllText($TargetConfig, $json, $utf8NoBom)
    Write-InstallLog "Config baru ditulis ke $TargetConfig"
}

# Restrict ProgramData ACL to SYSTEM + Administrators.
$acl = Get-Acl $ProgramDataDir
$acl.SetAccessRuleProtection($true, $false)
$acl.Access | ForEach-Object { [void]$acl.RemoveAccessRule($_) }
$systemRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
    "NT AUTHORITY\SYSTEM", "FullControl", "ContainerInherit,ObjectInherit", "None", "Allow"
)
$adminRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
    "BUILTIN\Administrators", "FullControl", "ContainerInherit,ObjectInherit", "None", "Allow"
)
$acl.AddAccessRule($systemRule)
$acl.AddAccessRule($adminRule)
Set-Acl -Path $ProgramDataDir -AclObject $acl

if (Test-Path $TargetConfig) {
    $fileAcl = Get-Acl $TargetConfig
    $fileAcl.SetAccessRuleProtection($true, $false)
    $fileAcl.Access | ForEach-Object { [void]$fileAcl.RemoveAccessRule($_) }
    $fileSystemRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        "NT AUTHORITY\SYSTEM", "FullControl", "Allow"
    )
    $fileAdminRule = New-Object System.Security.AccessControl.FileSystemAccessRule(
        "BUILTIN\Administrators", "FullControl", "Allow"
    )
    $fileAcl.AddAccessRule($fileSystemRule)
    $fileAcl.AddAccessRule($fileAdminRule)
    Set-Acl -Path $TargetConfig -AclObject $fileAcl
}

Write-InstallLog "Menginstal service..."
$existingForUninstall = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if ($existingForUninstall) {
    $prevEap = $ErrorActionPreference
    $ErrorActionPreference = "Continue"
    & $TargetExe -config $TargetConfig -service uninstall 2>$null | Out-Null
    $ErrorActionPreference = $prevEap
    Start-Sleep -Seconds 1
}

& $TargetExe -config $TargetConfig -service install
$installExit = $LASTEXITCODE
if ($null -eq $installExit) {
    $installExit = 0
}
if ($installExit -ne 0) {
    Write-InstallLog "ERROR: install service exit=$installExit"
    Write-Error "Gagal install service (exit $installExit). Lihat logs\install.log"
}

$svc = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if (-not $svc) {
    Write-InstallLog "ERROR: service $ServiceName tidak terdaftar setelah install"
    Write-Error "Service $ServiceName tidak terdaftar. Lihat %ProgramData%\PortalSifast Agent\logs\install.log"
}

sc.exe failure $ServiceName reset= 86400 actions= restart/60000/restart/60000/restart/60000 | Out-Null
sc.exe failureflag $ServiceName 1 | Out-Null

if (-not $SkipStart) {
    Write-InstallLog "Menjalankan service..."
    & $TargetExe -config $TargetConfig -service start
    $startExit = $LASTEXITCODE
    if ($null -eq $startExit) {
        $startExit = 0
    }
    if ($startExit -ne 0) {
        Write-InstallLog "WARNING: service start exit=$startExit - mencoba Start-Service"
        Start-Service -Name $ServiceName -ErrorAction SilentlyContinue
    }

    Start-Sleep -Seconds 3
    $svc.Refresh()
    if ($svc.Status -ne "Running") {
        # One more try via SCM
        Start-Service -Name $ServiceName -ErrorAction SilentlyContinue
        Start-Sleep -Seconds 2
        $svc.Refresh()
    }

    if ($svc.Status -ne "Running") {
        Write-InstallLog "ERROR: service status=$($svc.Status) setelah start"
        Write-Error "Service terpasang tapi tidak RUNNING (status=$($svc.Status)). Cek Event Viewer / logs di %ProgramData%\PortalSifast Agent\logs\"
    }
    Write-InstallLog "Service RUNNING."
}

& $TargetExe -config $TargetConfig -service status
Write-InstallLog "Selesai. Binary=$TargetExe Config=$TargetConfig"
Write-Host "Cek UI monitoring portal setelah beberapa detik."
exit 0
