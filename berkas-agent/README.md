# Berkas Agent — PortalSifast

Agent Go di PC HR untuk baca hasil scan Plustek (OCR Tesseract), tebak jenis berkas (STR/SIP/ijazah/…), lalu kirim ke **Inbox Portal**.

HR konfirmasi di Portal (pilih pegawai) → upload ke Khanza lewat modul yang sudah ada.

## Prasyarat

1. [Tesseract OCR](https://github.com/UB-Mannheim/tesseract/wiki) terpasang di Windows
2. Language data `ind` (+ `eng`)
3. Folder scan Plustek mengarah ke `inbox_dir` (disarankan output **JPG**; PDF tergantung build Tesseract)
4. Portal endpoint `POST /api/berkas-scan/inbox` (fase Portal inbox — menyusul jika belum ada)

## Config

Salin contoh:

```powershell
copy configs\config.example.json configs\config.json
```

Edit `portal_url`, `api_token`, path folder.

## Build & jalankan

```powershell
cd berkas-agent
go test ./...
go build -o dist/berkas-agent.exe ./cmd/berkas-agent

# sekali proses
.\dist\berkas-agent.exe -config configs\config.json -once

# loop terus
.\dist\berkas-agent.exe -config configs\config.json
```

## Alur folder

- `inbox/` — file baru dari Plustek
- `processed/` — berhasil dikirim ke Portal
- `error/` — gagal OCR/kirim (cek log)

## Catatan

- Agent **tidak** menulis DB Khanza langsung.
- Token agent khusus; jangan pakai password user HR.
- Klasifikasi awal berbasis kata kunci (bukan ML).
