# RS Agent — PortalSifast

Agent ringan (Go) untuk monitoring perangkat. Mengirim register + heartbeat (CPU/RAM/Disk) ke portal Laravel.

## Alur singkat

1. Build agent di PC develop.
2. Install sebagai **Windows Service** di PC client (Administrator).
3. Agent otomatis daftar ke server dan muncul di UI `/monitoring`.

> Enrollment key bersama hanya untuk **pilot**. Sebelum distribusi ke 200+ PC, ganti ke sistem token rollout yang bisa dibatasi/dicabut.

## Build (PC develop)

```powershell
cd c:\laragon\www\PortalSifast\rs-agent
.\scripts\build.ps1
```

Hasil:
- `dist\rs-agent.exe`
- paket `dist\PortalSifast-Agent\` (binary + skrip + contoh config)

## Install di PC client (Administrator)

```powershell
cd ...\PortalSifast-Agent
$env:AGENT_ENROLLMENT_KEY = "ISI_KEY_DARI_SERVER_PRODUCTION"
.\scripts\install-service.ps1
```

Atau:

```powershell
.\scripts\install-service.ps1 -EnrollmentKey "ISI_KEY_DARI_SERVER_PRODUCTION"
```

Lokasi production:
- Binary: `C:\Program Files\PortalSifast Agent\rs-agent.exe`
- Config: `C:\ProgramData\PortalSifast Agent\config.json`
- Log: `C:\ProgramData\PortalSifast Agent\logs\`

Service name: `PortalSifastAgent` (auto-start + restart jika crash).

### Migrasi config mesin develop (opsional)

```powershell
.\scripts\install-service.ps1 -MigrateFrom "C:\laragon\www\PortalSifast\rs-agent\configs\config.json"
```

Jangan bagikan `configs\config.json` development ke banyak PC — UUID/API key-nya unik per mesin.

## Cek status

```powershell
Get-Service PortalSifastAgent
& "C:\Program Files\PortalSifast Agent\rs-agent.exe" -config "C:\ProgramData\PortalSifast Agent\config.json" -service status
Get-Content "C:\ProgramData\PortalSifast Agent\logs\$(Get-Date -Format yyyy-MM-dd).log" -Tail 20
```

Lalu buka portal → **Monitoring**.

## Upgrade

Jalankan lagi `install-service.ps1` dengan binary baru. Config ProgramData (uuid/api_key) dipertahankan.

## Uninstall

```powershell
.\scripts\uninstall-service.ps1
```

Hapus total data:

```powershell
.\scripts\uninstall-service.ps1 -PurgeData
```

## Mode console (debug)

```powershell
.\dist\rs-agent.exe -config configs\config.json
```

Stop: `Ctrl+C`.

## Flag

| Flag | Keterangan |
|------|------------|
| `-config` | Path `config.json` |
| `-service install\|start\|stop\|restart\|status\|uninstall` | Kontrol Windows Service |

## Catatan keamanan

- `enrollment_key` dihapus dari config setelah registrasi berhasil.
- Jangan commit `configs/config.json` (sudah di-gitignore).
- Pastikan `AGENT_ENROLLMENT_KEY` di server production **bukan** placeholder `change-me-agent-enrollment`.
