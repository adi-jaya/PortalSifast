<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Peminjaman {{ $peminjaman->nomor }}</title>
    <style>
        body { font-family: Georgia, 'Times New Roman', serif; margin: 0; color: #1a1a1a; background: #f6f4f0; }
        .sheet { max-width: 720px; margin: 1.5rem auto; background: #fff; border: 1px solid #d6d0c6; padding: 2rem 2.25rem; }
        .eyebrow { font-family: ui-sans-serif, system-ui, sans-serif; font-size: 11px; letter-spacing: .14em; text-transform: uppercase; color: #6b645c; }
        h1 { font-size: 1.5rem; margin: .35rem 0 .15rem; font-weight: 600; }
        .nomor { font-family: ui-monospace, monospace; font-size: .95rem; color: #0f766e; }
        table { width: 100%; border-collapse: collapse; margin-top: 1.75rem; font-family: ui-sans-serif, system-ui, sans-serif; font-size: .9rem; }
        th, td { text-align: left; padding: .65rem .2rem; border-bottom: 1px solid #e8e2d9; vertical-align: top; }
        th { width: 38%; color: #6b645c; font-weight: 600; font-size: .8rem; }
        .actions { margin-top: 1.75rem; font-family: ui-sans-serif, system-ui, sans-serif; }
        button { background: #0f766e; color: #fff; border: 0; padding: .55rem 1rem; border-radius: 6px; cursor: pointer; }
        @media print {
            body { background: #fff; }
            .sheet { border: 0; margin: 0; max-width: none; }
            .actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="sheet">
        <p class="eyebrow">PortalSifast · Inventaris</p>
        <h1>Bukti Peminjaman Aset</h1>
        <p class="nomor">{{ $peminjaman->nomor }}</p>

        <table>
            <tr><th>Kode aset</th><td>{{ $peminjaman->aset?->kode_aset }}</td></tr>
            <tr><th>Nama barang</th><td>{{ $peminjaman->aset?->barang?->nama_barang }}</td></tr>
            <tr><th>Peminjam</th><td>{{ $peminjamLabel }}</td></tr>
            <tr><th>Diserahkan oleh</th><td>{{ $peminjaman->diserahkanOleh?->name }}</td></tr>
            <tr><th>Tanggal pinjam</th><td>{{ $peminjaman->tanggal_pinjam }}</td></tr>
            <tr><th>Rencana kembali</th><td>{{ $peminjaman->tanggal_kembali_rencana ?? '–' }}</td></tr>
            <tr><th>Status</th><td>{{ $peminjaman->status }}</td></tr>
            <tr><th>Diterima kembali oleh</th><td>{{ $peminjaman->diterimaKembaliOleh?->name ?? '–' }}</td></tr>
            <tr><th>Tanggal kembali</th><td>{{ $peminjaman->tanggal_kembali_aktual ?? '–' }}</td></tr>
            <tr><th>Catatan</th><td>{{ $peminjaman->catatan ?? '–' }}</td></tr>
        </table>

        <div class="actions">
            <button type="button" onclick="window.print()">Cetak</button>
        </div>
    </div>
</body>
</html>
