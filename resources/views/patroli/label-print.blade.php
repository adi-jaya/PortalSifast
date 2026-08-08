<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }}</title>
    <style>
        body { font-family: Arial, Helvetica, sans-serif; margin: 0; background: #eef2f7; color: #111; }
        .toolbar { display: flex; justify-content: space-between; gap: 12px; padding: 12px 16px; background: #0f766e; color: #fff; }
        .toolbar a, .toolbar button { background: #fff; color: #0f766e; border: 0; border-radius: 8px; padding: 8px 12px; text-decoration: none; cursor: pointer; }
        .sheet { max-width: 420px; margin: 24px auto; background: #fff; border-radius: 12px; padding: 20px; text-align: center; box-shadow: 0 8px 24px rgba(0,0,0,.08); }
        .qr { display: inline-block; width: 180px; height: 180px; }
        .qr svg { width: 100%; height: 100%; }
        h1 { font-size: 18px; margin: 0 0 8px; }
        p { margin: 4px 0; font-size: 14px; }
        .muted { color: #64748b; font-size: 12px; word-break: break-all; }
        @media print {
            .toolbar { display: none; }
            body { background: #fff; }
            .sheet { box-shadow: none; margin: 0; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <div>Cetak label QR patroli — {{ $area->nama }} / {{ $ruang->nama }}</div>
        <div>
            <button type="button" onclick="window.print()">Cetak</button>
            <a href="{{ url()->previous() }}">Kembali</a>
        </div>
    </div>
    <div class="sheet">
        <h1>Patroli Security</h1>
        <p class="muted">{{ $area->nama }}</p>
        @if($ruang->kode)
            <p><strong>{{ $ruang->kode }}</strong></p>
        @endif
        <p>{{ $ruang->nama }}</p>
        <div class="qr">{!! $qrSvg !!}</div>
        <p class="muted">{{ $scanUrl }}</p>
    </div>
</body>
</html>
