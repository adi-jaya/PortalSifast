param(
    [switch]$PurgeData
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

if (Test-Path $TargetExe) {
    & $TargetExe -config $TargetConfig -service stop 2>$null
    & $TargetExe -config $TargetConfig -service uninstall 2>$null
} else {
    Stop-Service -Name $ServiceName -Force -ErrorAction SilentlyContinue
    sc.exe delete $ServiceName 2>$null | Out-Null
}

if (Test-Path $ProgramFilesDir) {
    Remove-Item -Recurse -Force $ProgramFilesDir
    Write-Host "Dihapus: $ProgramFilesDir"
}

if ($PurgeData) {
    if (Test-Path $ProgramDataDir) {
        Remove-Item -Recurse -Force $ProgramDataDir
        Write-Host "Data ProgramData dihapus (purge)."
    }
} else {
    Write-Host "Config/log ProgramData dipertahankan di $ProgramDataDir"
    Write-Host "Untuk hapus total: .\uninstall-service.ps1 -PurgeData"
}

Write-Host "Uninstall selesai."
