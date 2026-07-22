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
    </style>
</head>
<body>
    <h1>Laporan Data Murid — TELAGA AL KAUTSAR</h1>
    <div class="sub">Dicetak: {{ now()->format('d/m/Y H:i') }} · Total: {{ $students->count() }} murid</div>

    <table>
        <thead>
            <tr>
                <th>Nama</th><th>NISN</th><th>NIS</th><th>Kelas</th><th>Wali Kelas</th>
                <th>JK</th><th>TTL</th><th>Ayah</th><th>Ibu</th><th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($students as $s)
                <tr>
                    <td>{{ $s->nama_lengkap }}</td>
                    <td>{{ $s->nisn ?? '-' }}</td>
                    <td>{{ $s->nis ?? '-' }}</td>
                    <td>{{ $s->schoolClass?->nama_kelas ?? '-' }}</td>
                    <td>{{ $s->schoolClass?->homeroomTeacher?->nama ?? '-' }}</td>
                    <td>{{ $s->jenis_kelamin }}</td>
                    <td>{{ $s->tempat_lahir }}, {{ $s->tanggal_lahir?->format('d/m/Y') }}</td>
                    <td>{{ $s->ortu?->ayah_nama ?? '-' }}</td>
                    <td>{{ $s->ortu?->ibu_nama ?? '-' }}</td>
                    <td>{{ ucfirst($s->status) }}</td>
                </tr>
            @empty
                <tr><td colspan="10">Tidak ada data.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
