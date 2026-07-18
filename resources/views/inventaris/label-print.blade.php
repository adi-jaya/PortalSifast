<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title }} — Epson LW-600P 24mm</title>
    <style>
        :root {
            --tape-h: 24mm;
            --print-h: 18mm;
            --label-w: 50mm;
            --qr: 17mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }

        html, body {
            margin: 0;
            padding: 0;
            background: #e8ecef;
            color: #000;
            font-family: Arial, Helvetica, sans-serif;
        }

        .screen-toolbar {
            position: sticky;
            top: 0;
            z-index: 10;
            display: flex;
            flex-wrap: wrap;
            gap: 8px;
            align-items: center;
            justify-content: space-between;
            padding: 12px 16px;
            background: #0f766e;
            color: #fff;
        }

        .screen-toolbar p {
            margin: 0;
            font-size: 13px;
            line-height: 1.35;
            max-width: 52rem;
        }

        .screen-toolbar .actions {
            display: flex;
            gap: 8px;
        }

        .screen-toolbar button,
        .screen-toolbar a {
            border: 0;
            border-radius: 8px;
            padding: 8px 12px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            color: #0f766e;
            background: #fff;
        }

        .preview-board {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 10px;
            padding: 16px;
        }

        .hint {
            max-width: 40rem;
            text-align: center;
            font-size: 12px;
            color: #475569;
            line-height: 1.45;
            margin: 4px 0 0;
        }

        .label {
            width: var(--label-w);
            height: var(--tape-h);
            background: #fff;
            border: 1px dashed #94a3b8;
            display: flex;
            align-items: center;
            justify-content: center;
            overflow: hidden;
            page-break-after: always;
            break-after: page;
        }

        .label:last-child {
            page-break-after: auto;
            break-after: auto;
        }

        .label-inner {
            width: 100%;
            height: var(--print-h);
            display: flex;
            flex-direction: row;
            align-items: center;
            gap: 0.6mm;
            padding: 0 0.4mm;
            margin: 0;
        }

        .qr {
            width: var(--qr);
            height: var(--qr);
            flex: 0 0 var(--qr);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qr svg {
            width: var(--qr);
            height: var(--qr);
            display: block;
        }

        .meta {
            min-width: 0;
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 0.15mm;
            overflow: hidden;
        }

        .meta .name {
            margin: 0;
            font-size: 6pt;
            font-weight: 700;
            line-height: 1.05;
            max-height: 7.5mm;
            overflow: hidden;
            word-break: break-word;
        }

        .meta .line {
            margin: 0;
            font-size: 4.7pt;
            line-height: 1.05;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .meta .code {
            font-family: "Courier New", Courier, monospace;
            font-weight: 700;
            letter-spacing: 0;
        }

        @media print {
            @page {
                size: 50mm 24mm;
                margin: 0 !important;
            }

            html, body {
                background: #fff !important;
                margin: 0 !important;
                padding: 0 !important;
                width: 50mm;
            }

            .screen-toolbar,
            .hint {
                display: none !important;
            }

            .preview-board {
                display: block !important;
                padding: 0 !important;
                gap: 0 !important;
            }

            .label {
                border: 0 !important;
                margin: 0 !important;
                width: 50mm;
                height: 24mm;
            }

            .label-inner {
                padding: 0 0.2mm !important;
                gap: 0.4mm !important;
            }
        }
    </style>
</head>
<body>
    <div class="screen-toolbar">
        <p>
            {{ $subtitle }}
            — pita <strong>24mm</strong>, margin minimal (area cetak ±18mm).
            Pilih <strong>Epson LW-600P</strong>, skala 100%, margin printer nol.
        </p>
        <div class="actions">
            <a href="{{ $backUrl }}">Kembali</a>
            <button type="button" onclick="window.print()">Cetak {{ count($labels) > 1 ? count($labels).' Label' : 'Label' }}</button>
        </div>
    </div>

    <div class="preview-board">
        @forelse ($labels as $label)
            <div class="label">
                <div class="label-inner">
                    <div class="qr">{!! $label['qrSvg'] !!}</div>
                    <div class="meta">
                        <p class="name">{{ $label['nama_barang'] }}</p>
                        <p class="line code">{{ $label['no_inventaris'] }}</p>
                        <p class="line">{{ $label['kode_barang'] }}</p>
                        <p class="line">{{ $label['nama_ruang'] }}</p>
                    </div>
                </div>
            </div>
        @empty
            <p class="hint">Tidak ada inventaris untuk dicetak.</p>
        @endforelse

        @if (count($labels) > 0)
            <p class="hint">
                Tiap label = 1 halaman cetak (50×24mm). Batch ruang akan mencetak berurutan — biarkan printer memotong tiap label.
            </p>
        @endif
    </div>

    @if ($autoPrint && count($labels) > 0)
        <script>
            window.addEventListener('load', function () {
                setTimeout(function () { window.print(); }, 300);
            });
        </script>
    @endif
</body>
</html>
