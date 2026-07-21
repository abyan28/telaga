<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    {{-- Template PDF laporan transaksi (dompdf) — PRD §7.12 --}}
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #1e293b; }
        h1 { font-size: 16px; margin: 0 0 4px; }
        .sub { color: #64748b; font-size: 10px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; }
        th { background: #e0f2fe; }
        td.num { text-align: right; }
        tfoot td { font-weight: bold; background: #f1f5f9; }
    </style>
</head>
<body>
    <h1>Laporan Transaksi — TELAGA AL KAUTSAR</h1>
    <div class="sub">Dicetak: {{ now()->format('d/m/Y H:i') }} · Total baris: {{ $transactions->count() }}</div>

    <table>
        <thead>
            <tr><th>Tanggal</th><th>Murid</th><th>Jenis</th><th>Jumlah (Rp)</th><th>Status</th></tr>
        </thead>
        <tbody>
            @forelse ($transactions as $t)
                <tr>
                    <td>{{ $t->tanggal_bayar?->format('d/m/Y') }}</td>
                    <td>{{ $t->student?->nama_lengkap ?? '-' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $t->jenis)) }}</td>
                    <td class="num">{{ number_format((int) $t->jumlah, 0, ',', '.') }}</td>
                    <td>{{ ucfirst($t->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">Tidak ada data transaksi.</td></tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="3">Total Terverifikasi</td>
                <td class="num">{{ number_format((int) $total, 0, ',', '.') }}</td>
                <td></td>
            </tr>
        </tfoot>
    </table>
</body>
</html>
