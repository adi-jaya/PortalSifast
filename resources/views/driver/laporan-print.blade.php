<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Rekap Checklist Kendaraan</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 12px; color: #111; margin: 24px; }
        h1 { font-size: 18px; margin: 0 0 4px; }
        h2 { font-size: 14px; margin: 0 0 16px; font-weight: normal; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #ccc; padding: 6px 8px; text-align: left; }
        th { background: #f3f4f6; }
        .meta { margin-bottom: 12px; color: #444; }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>
    <button class="no-print" onclick="window.print()">Cetak</button>

    <h1>RSU Aisyiyah Siti Fatimah</h1>
    <h2>Rekap Checklist Kendaraan — {{ $mode === 'mingguan' ? 'Mingguan' : 'Harian' }}</h2>

    <div class="meta">
        @if ($mode === 'mingguan')
            Periode: {{ $data['periode']['mulai'] }} s/d {{ $data['periode']['selesai'] }}
        @else
            Tanggal: {{ $data['tanggal'] }}
        @endif
        <br>
        Dicetak: {{ $printedAt->format('d/m/Y H:i') }} oleh {{ $printedBy }}
    </div>

    <p>
        @php
            $summaryLabels = [
                'total_kendaraan' => 'Total kendaraan',
                'belum' => 'Belum',
                'sudah' => 'Sudah',
                'ada_temuan' => 'Ada temuan',
                'target_minimal' => 'Target minimal',
                'pemeriksaan_aktual' => 'Pemeriksaan aktual',
                'hari_terpenuhi' => 'Hari terpenuhi',
                'kepatuhan' => 'Kepatuhan',
                'pemeriksaan_ke_2' => 'Pemeriksaan ke-2',
                'hari_layak' => 'Hari baik',
                'temuan_tidak_layak' => 'Temuan tidak baik',
            ];
        @endphp
        @foreach ($data['summary'] as $key => $value)
            <strong>{{ $summaryLabels[$key] ?? str_replace('_', ' ', $key) }}:</strong> {{ $value }}
            @if (! $loop->last) · @endif
        @endforeach
    </p>

    <table>
        <thead>
            @if ($mode === 'mingguan')
                <tr>
                    <th>Kendaraan</th>
                    <th>Target</th>
                    <th>Aktual</th>
                    <th>Hari terpenuhi</th>
                    <th>Ke-2</th>
                    <th>Kepatuhan</th>
                </tr>
            @else
                <tr>
                    <th>Kendaraan</th>
                    <th>Check 1</th>
                    <th>Check 2</th>
                    <th>Temuan</th>
                    <th>Status</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @foreach ($data['rows'] as $row)
                <tr>
                    @if ($mode === 'mingguan')
                        <td>{{ $row['nama'] }}</td>
                        <td>{{ $row['target'] }}</td>
                        <td>{{ $row['aktual'] }}</td>
                        <td>{{ $row['hari_terpenuhi'] }}</td>
                        <td>{{ $row['pemeriksaan_ke_2'] }}</td>
                        <td>{{ $row['kepatuhan'] }}%</td>
                    @else
                        <td>{{ $row['nama'] }}</td>
                        <td>{{ $row['check_1'] ? '✓' : '-' }}</td>
                        <td>{{ $row['check_2'] ? '✓' : '-' }}</td>
                        <td>{{ $row['temuan'] }}</td>
                        <td>{{ $row['status'] }}</td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
