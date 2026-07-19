# RS Agent — PortalSifast

Agent ringan (Go) untuk monitoring perangkat. Mengirim register + heartbeat ke portal Laravel.

## Alur singkat (1 klik)

1. Download `PortalSifast-Agent-Setup.exe` dari **GitHub Releases** (repo private), atau build lokal.
2. Di PC client: double-click → izinkan **UAC**.
3. Selesai. Agent jalan sebagai Windows Service (tanpa terminal, tanpa icon tray).
4. Cek di portal `/monitoring`.

> Enrollment key tertanam saat build (dari `.env` / GitHub Secret). Repo harus **private**.

## Terminal / tray?

- **Tidak ada terminal** yang tetap terbuka.
- **Tidak ada icon tray** (bukan seperti AnyDesk/Radmin).
- Status dicek di portal atau `services.msc` → PortalSifast RS Agent.

## Build lokal (otomatis baca key)

Pastikan di `.env` Laravel (folder project) ada:

```env
AGENT_ENROLLMENT_KEY=isi-key-production-yang-kuat
APP_URL=https://portalsifast.rsaisyiyahsitifatimah.com
```

Lalu:

```powershell
cd c:\laragon\www\PortalSifast\rs-agent
.\scripts\build-installer.ps1
```

Hasil:
- `dist\PortalSifast-Agent-Setup.exe` — 1x klik (UAC saja, tanpa ketik key)
- `dist\PortalSifast-Agent-Setup-0.2.1.zip` — untuk dibagikan / di-upload ke Releases

## GitHub Releases (repo private)

1. Di GitHub → **Settings → Secrets and variables → Actions**, buat:
   - `AGENT_ENROLLMENT_KEY` = key production
   - `AGENT_SERVER_URL` = `https://portalsifast.rsaisyiyahsitifatimah.com` (opsional)
2. Push tag:

```bash
git tag rs-agent-v0.2.1
git push origin rs-agent-v0.2.1
```

3. Buka repo → **Releases** → download `PortalSifast-Agent-Setup.exe` atau `.zip`

Atau jalankan manual: **Actions → release-rs-agent → Run workflow**.

## Install manual (tanpa Setup.exe)

```powershell
.\scripts\build.ps1
.\scripts\install-service.ps1 -EnrollmentKey "KEY_PRODUCTION"
```

Lokasi production:
- Binary: `C:\Program Files\PortalSifast Agent\rs-agent.exe`
- Config: `C:\ProgramData\PortalSifast Agent\config.json`
- Log: `C:\ProgramData\PortalSifast Agent\logs\`

Service: `PortalSifastAgent` (auto-start + restart jika crash).

## Uninstall

Control Panel → Uninstall "PortalSifast Agent", atau:

```powershell
.\scripts\uninstall-service.ps1
# hapus total data:
.\scripts\uninstall-service.ps1 -PurgeData
```

## Mode console (debug)

```powershell
.\dist\rs-agent.exe -config configs\config.json
```

Stop: `Ctrl+C`.
