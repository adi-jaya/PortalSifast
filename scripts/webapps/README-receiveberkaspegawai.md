# Deploy checklist — `receiveberkaspegawai.php`

HTTP file receiver for **Berkas Kepegawaian** (Portal → webapps2). Source: `scripts/webapps/receiveberkaspegawai.php`.

Do **not** treat this checklist as an automatic production deploy. Copy and configure on the webapps host manually.

## 1. Copy receiver to webapps host

```bash
# Example destination
scp scripts/webapps/receiveberkaspegawai.php user@webapps-host:/var/www/html/uplodtes/receiveberkaspegawai.php
```

Typical URL after deploy:

`http://<webapps-host>/uplodtes/receiveberkaspegawai.php`

## 2. Shared secret

On the **webapps** host, set:

```bash
WEBAPPS_RECEIVER_SECRET=<same-value-as-portal-token>
```

On **Portal**, set `.env`:

```env
WEBAPPS_BERKAS_PEGAWAI_RECEIVER_TOKEN=<same-value-as-webapps-secret>
```

The receiver checks header `X-Webapps-Token` when `WEBAPPS_RECEIVER_SECRET` is non-empty.

Optional on webapps:

```bash
WEBAPPS_RECEIVER_LOG=/var/log/receiveberkaspegawai.log
WEBAPPS_RECEIVER_CORS_ORIGIN=*
```

## 3. Writable storage folder

Ensure the destination directory exists and is writable by the PHP/web server user:

```text
/var/www/html/webapps2/penggajian/pages/berkaspegawai/berkas
```

```bash
sudo mkdir -p /var/www/html/webapps2/penggajian/pages/berkaspegawai/berkas
sudo chown www-data:www-data /var/www/html/webapps2/penggajian/pages/berkaspegawai/berkas
sudo chmod 755 /var/www/html/webapps2/penggajian/pages/berkaspegawai/berkas
```

(Adjust owner to match the host’s Apache/Nginx PHP user.)

Allowed upload extensions: `pdf`, `jpg`, `jpeg`.

## 4. Portal `.env` keys

```env
WEBAPPS_BERKAS_PEGAWAI_RECEIVER_URL=http://192.168.10.3/uplodtes/receiveberkaspegawai.php
WEBAPPS_BERKAS_PEGAWAI_RECEIVER_TOKEN=
WEBAPPS_BERKAS_PEGAWAI_PUBLIC_BASE_URL=http://192.168.10.3/webapps2/penggajian
WEBAPPS_BERKAS_PEGAWAI_TIMEOUT=30
```

| Key | Purpose |
| --- | --- |
| `WEBAPPS_BERKAS_PEGAWAI_RECEIVER_URL` | Absolute URL of `receiveberkaspegawai.php` |
| `WEBAPPS_BERKAS_PEGAWAI_RECEIVER_TOKEN` | Must match webapps `WEBAPPS_RECEIVER_SECRET` |
| `WEBAPPS_BERKAS_PEGAWAI_PUBLIC_BASE_URL` | Base used to build Khanza/webapps preview URLs |
| `WEBAPPS_BERKAS_PEGAWAI_TIMEOUT` | HTTP timeout (seconds) for upload/delete |

After changing `.env`, clear config cache if used: `php artisan config:clear`.

## 5. MySQL GRANT note (`dbsimrs`)

Portal writes to `master_berkas_pegawai` and `berkas_pegawai` on the SIMRS DB connection. If INSERT/UPDATE/DELETE fail with permission errors, grant the Portal DB user as needed, for example:

```sql
GRANT SELECT, INSERT, UPDATE, DELETE ON <simrs_db>.master_berkas_pegawai TO '<portal_user>'@'<host>';
GRANT SELECT, INSERT, UPDATE, DELETE ON <simrs_db>.berkas_pegawai TO '<portal_user>'@'<host>';
FLUSH PRIVILEGES;
```

Feature tests skip write paths when the DB user cannot INSERT; that is expected until GRANTs are applied.

## 6. Curl smoke — upload

```bash
curl -sS -X POST \
  -H "X-Webapps-Token: YOUR_SECRET" \
  -F "target=berkaspegawai" \
  -F "filename=smoke_test.pdf" \
  -F "dokumen=@/path/to/sample.pdf" \
  "http://192.168.10.3/uplodtes/receiveberkaspegawai.php"
```

Expect JSON roughly:

```json
{"success":true,"filename":"smoke_test.pdf","target":"berkaspegawai","path":".../berkas/smoke_test.pdf","message":"Uploaded"}
```

Confirm the file exists under `.../penggajian/pages/berkaspegawai/berkas/`.

## 7. Curl smoke — delete

```bash
curl -sS -X POST \
  -H "X-Webapps-Token: YOUR_SECRET" \
  -F "target=berkaspegawai" \
  -F "action=delete" \
  -F "filename=smoke_test.pdf" \
  "http://192.168.10.3/uplodtes/receiveberkaspegawai.php"
```

Expect `success: true` (file deleted or already absent).

## 8. Manual UI smoke (Portal + Khanza)

1. **Grant access** — As a user who can manage access, enable `can_access_berkas_kepegawaian` for a test user (Users admin).
2. **Master** — Open `/berkas-kepegawaian/master`, create/edit a jenis berkas (`kode` + `nama_berkas`).
3. **Upload** — Open `/berkas-kepegawaian`, pick a pegawai, upload PDF/JPG for that jenis; confirm success toast and row appears.
4. **Preview in Khanza** — Open the same pegawai’s berkas in Khanza/webapps penggajian; file should resolve under `pages/berkaspegawai/berkas/...` via `WEBAPPS_BERKAS_PEGAWAI_PUBLIC_BASE_URL`.
5. **Replace / delete** — Replace the file, then delete; confirm file gone on disk and row removed in Portal.

## Contract (quick reference)

| Action | Method / fields |
| --- | --- |
| Upload | `POST` multipart: `target=berkaspegawai` + `dokumen`\|`file`\|`image` (+ optional `filename`) |
| Delete | `POST`: `target=berkaspegawai` + `action=delete` + `filename` |
| Auth | Header `X-Webapps-Token` when secret is set |
| Response | JSON `{ success, filename, target, path, message? }` |
