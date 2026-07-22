<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <style>
        body { font-family: sans-serif; font-size: 8px; color: #1e293b; }
        h1 { font-size: 13px; margin: 0 0 2px; }
        .sub { font-size: 8px; color: #64748b; margin-bottom: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 3px 4px; text-align: left; }
        th { background: #e0f2fe; }
        td.num { text-align: right; }
    </style>
</head>
<body>
    <h1>Laporan SPP Bulanan — TELAGA AL KAUTSAR</h1>
    <div class="sub">Dicetak: {{ now()->format('d/m/Y H:i') }} · Total: {{ $bills->count() }} tagihan</div>

    <table>
        <thead>
            <tr><th>Murid</th><th>Ortu (Ibu)</th><th>Kelas</th><th>TA</th><th>Bulan</th><th class="num">Nominal</th><th class="num">Terbayar</th><th class="num">Sisa</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse ($bills as $b)
                <tr>
                    <td>{{ $b->student?->nama_lengkap ?? '-' }}</td>
                    <td>{{ $b->student?->ortu?->ibu_nama ?? $b->student?->ortu?->ayah_nama ?? '-' }}</td>
                    <td>{{ $b->student?->schoolClass?->nama_kelas ?? '-' }}</td>
                    <td>{{ $b->academicYear?->tahun ?? '-' }}</td>
                    <td>{{ $b->bulan }}</td>
                    <td class="num">{{ number_format((float) $b->nominal, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $b->jumlah_terbayar, 0, ',', '.') }}</td>
                    <td class="num">{{ number_format((float) $b->sisa(), 0, ',', '.') }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $b->status)) }}</td>
                </tr>
            @empty
                <tr><td colspan="9">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
