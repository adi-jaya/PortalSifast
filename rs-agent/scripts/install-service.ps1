param(
    [string]$SourceExe = "",
    [string]$EnrollmentKey = "",
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

if (-not (Test-IsAdministrator)) {
    Write-Error "Jalankan skrip ini sebagai Administrator."
}

$ProgramFilesDir = Join-Path ${env:ProgramFiles} "PortalSifast Agent"
$ProgramDataDir = Join-Path $env:ProgramData "PortalSifast Agent"
$TargetExe = Join-Path $ProgramFilesDir "rs-agent.exe"
$TargetConfig = Join-Path $ProgramDataDir "config.json"
$ServiceName = "PortalSifastAgent"

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
    Write-Error "Binary tidak ditemukan. Build dulu: .\scripts\build.ps1"
}

New-Item -ItemType Directory -Force -Path $ProgramFilesDir | Out-Null
New-Item -ItemType Directory -Force -Path $ProgramDataDir | Out-Null

$existing = Get-Service -Name $ServiceName -ErrorAction SilentlyContinue
if ($existing -and $existing.Status -ne "Stopped") {
    Write-Host "Menghentikan service $ServiceName..."
    & $TargetExe -config $TargetConfig -service stop 2>$null
    Start-Sleep -Seconds 2
    Stop-Service -Name $ServiceName -Force -ErrorAction SilentlyContinue
}

Copy-Item -Force -Path $SourceExe -Destination $TargetExe

$needEnrollment = $true
if (Test-Path $TargetConfig) {
    try {
        $cfg = Get-Content -Raw -Path $TargetConfig | ConvertFrom-Json
        if ($cfg.api_key -and $cfg.api_key.ToString().Trim() -ne "") {
            $needEnrollment = $false
            Write-Host "Config ProgramData sudah terdaftar; mempertahankan uuid/api_key."
        }
    } catch {
        Write-Warning "Config ProgramData tidak valid; akan dibuat ulang."
    }
}

if (-not (Test-Path $TargetConfig) -or $needEnrollment) {
    if (-not [string]::IsNullOrWhiteSpace($MigrateFrom) -and (Test-Path $MigrateFrom)) {
        Copy-Item -Force -Path $MigrateFrom -Destination $TargetConfig
        Write-Host "Config dimigrasikan dari $MigrateFrom"
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
        $EnrollmentKey = $env:AGENT_ENROLLMENT_KEY
    }
    if ([string]::IsNullOrWhiteSpace($EnrollmentKey)) {
        Write-Error "Enrollment key wajib untuk instalasi baru. Set -EnrollmentKey atau env AGENT_ENROLLMENT_KEY."
    }

    $fresh = [ordered]@{
        server          = $Server
        enrollment_key  = $EnrollmentKey
        api_key         = ""
        interval        = 30
        log_level       = "info"
        uuid            = ""
    }
    ($fresh | ConvertTo-Json) | Set-Content -Path $TargetConfig -Encoding utf8
    Write-Host "Config baru ditulis ke $TargetConfig"
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

Write-Host "Menginstal service..."
& $TargetExe -config $TargetConfig -service uninstall 2>$null
& $TargetExe -config $TargetConfig -service install
if ($LASTEXITCODE -ne 0) {
    Write-Error "Gagal install service (exit $LASTEXITCODE)."
}

# Restart on failure via SCM.
sc.exe failure $ServiceName reset= 86400 actions= restart/60000/restart/60000/restart/60000 | Out-Null
sc.exe failureflag $ServiceName 1 | Out-Null

if (-not $SkipStart) {
    & $TargetExe -config $TargetConfig -service start
    if ($LASTEXITCODE -ne 0) {
        Write-Error "Gagal start service (exit $LASTEXITCODE)."
    }
}

& $TargetExe -config $TargetConfig -service status
Write-Host "Selesai."
Write-Host "  Binary : $TargetExe"
Write-Host "  Config : $TargetConfig"
Write-Host "  Logs   : $(Join-Path $ProgramDataDir 'logs')"
Write-Host "Cek UI monitoring portal setelah beberapa detik."
