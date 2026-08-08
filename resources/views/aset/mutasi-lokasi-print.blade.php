<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Bukti Mutasi {{ $mutasi->nomor }}</title>
    <style>
        body { font-family: ui-sans-serif, system-ui, sans-serif; margin: 2rem; color: #111; }
        h1 { font-size: 1.25rem; margin-bottom: .25rem; }
        .muted { color: #666; font-size: .875rem; }
        table { width: 100%; border-collapse: collapse; margin-top: 1.5rem; }
        th, td { text-align: left; padding: .5rem .25rem; border-bottom: 1px solid #ddd; vertical-align: top; }
        th { width: 35%; color: #555; font-weight: 600; }
        .actions { margin-top: 2rem; }
        @media print { .actions { display: none; } }
    </style>
</head>
<body>
    <h1>Bukti Mutasi Lokasi Aset</h1>
    <p class="muted">{{ $mutasi->nomor }}</p>

    <table>
        <tr><th>Kode aset</th><td>{{ $mutasi->aset?->kode_aset }}</td></tr>
        <tr><th>Nama barang</th><td>{{ $mutasi->aset?->barang?->nama_barang }}</td></tr>
        <tr><th>Ruang asal</th><td>{{ $mutasi->ruangAsal?->nama_ruang }}</td></tr>
        <tr><th>Ruang tujuan</th><td>{{ $mutasi->ruangTujuan?->nama_ruang }}</td></tr>
        <tr><th>Penerima</th><td>{{ $penerimaLabel }}</td></tr>
        <tr><th>Dicatat oleh</th><td>{{ $mutasi->dicatatOleh?->name }}</td></tr>
        <tr><th>Tanggal mutasi</th><td>{{ $mutasi->tanggal_mutasi }}</td></tr>
        <tr><th>Catatan</th><td>{{ $mutasi->catatan ?? '–' }}</td></tr>
    </table>

    <div class="actions">
        <button type="button" onclick="window.print()">Cetak</button>
    </div>
</body>
</html>
