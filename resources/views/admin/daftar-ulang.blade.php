@extends('layouts.dashboard')

@section('title', 'Daftar Ulang — RA Al Kautsar')
@section('header_title', 'PPDB — Daftar Ulang')

@section('content')
@php
    $stColor = ['lunas' => 'bg-emerald-100 text-emerald-700', 'kurang' => 'bg-amber-100 text-amber-700', 'belum_lunas' => 'bg-rose-100 text-rose-700'];
@endphp

{{-- K5.3: pisah tab antara Bukti Pembayaran (perlu verifikasi) vs List Murid (rekap tagihan). --}}
<div class="space-y-8" x-data="hashTabs('siswa')">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    {{-- Tab bar --}}
    <div class="flex gap-2 border-b border-slate-100">
        <button @click="setTab('siswa')" :class="tab === 'siswa' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="px-4 py-3 border-b-2 font-bold text-xs transition-colors">List Murid</button>
        <button @click="setTab('bukti')" :class="tab === 'bukti' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                class="inline-flex items-center gap-2 px-4 py-3 border-b-2 font-bold text-xs transition-colors">
            Bukti Pembayaran
            @if ($pending->isNotEmpty())
                <span class="inline-flex items-center justify-center min-w-5 h-5 px-1.5 rounded-full bg-rose-500 text-white text-4xs font-black">{{ $pending->count() }}</span>
            @endif
        </button>
    </div>

    {{-- Tab: Bukti daftar ulang menunggu verifikasi (T5.6b) --}}
    <div x-show="tab === 'bukti'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <h3 class="text-base font-bold text-slate-950">Bukti Daftar Ulang Menunggu Verifikasi</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3">No. Ref</th>
                        <th class="py-3">Murid</th>
                        <th class="py-3">Bank Asal</th>
                        <th class="py-3">Tanggal &amp; Waktu</th>
                        <th class="py-3 text-right">Jumlah</th>
                        <th class="py-3 text-right">Bukti</th>
                        <th class="py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($pending as $tx)
                        <tr class="text-slate-600 hover:bg-slate-50 transition-colors">
                            <td class="py-3 font-bold text-slate-800">TX-{{ str_pad($tx->id_payment_transactions, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-3 font-semibold text-slate-700">{{ $tx->student?->nama_lengkap ?? '-' }}</td>
                            <td class="py-3 text-slate-500">{{ $tx->bank_asal ?: '-' }}</td>
                            <td class="py-3 text-slate-400">
                                {{ $tx->tanggal_bayar?->translatedFormat('d M Y') ?? '-' }}
                                <span class="block text-4xs text-slate-300">{{ $tx->created_at?->format('H:i') }} WIB</span>
                            </td>
                            <td class="py-3 text-right font-bold text-slate-900">Rp {{ number_format((int) $tx->jumlah, 0, ',', '.') }}</td>
                            <td class="py-3 text-right">
                                @if ($tx->bukti_path)
                                    <a href="{{ asset('storage/'.$tx->bukti_path) }}" target="_blank" class="text-4xs font-bold text-indigo-600 hover:underline">Lihat Bukti</a>
                                @else <span class="text-4xs text-slate-300">-</span> @endif
                            </td>
                            <td class="py-3 text-right">
                                <form action="{{ route('admin.payments.verify', $tx->id_payment_transactions) }}" method="POST" class="inline-flex gap-1">
                                    @csrf
                                    <button type="submit" name="status" value="ditolak" class="px-2.5 py-1.5 border border-rose-200 hover:bg-rose-50 text-rose-600 font-bold rounded-lg">Tolak</button>
                                    <button type="submit" name="status" value="diverifikasi" class="px-2.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white font-bold rounded-lg">Verifikasi</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="py-6 text-center text-slate-400">Tidak ada bukti daftar ulang menunggu verifikasi.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Tab: Rekap tagihan daftar ulang semua murid lolos (lunas tetap tampil) --}}
    <div x-show="tab === 'siswa'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <h3 class="text-base font-bold text-slate-950">Histori Pembayaran Daftar Ulang</h3>

            {{-- Filter T.A. + status + search. GET + tab=murid agar posisi tab bertahan (rules §6.1). --}}
            <form method="GET" action="{{ route('admin.daftar-ulang') }}" class="flex flex-wrap items-center gap-2">
                <input type="hidden" name="tab" value="siswa">
                <input type="text" name="cari" value="{{ $cari }}" placeholder="Cari nama / NISN / No. HP…"
                       class="border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-600 w-48">
                <select name="status" onchange="this.form.submit()"
                        class="border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-600">
                    <option value="">Semua Status</option>
                    <option value="lunas" @selected($status === 'lunas')>Lunas</option>
                    <option value="kurang" @selected($status === 'kurang')>Kurang</option>
                    <option value="belum_lunas" @selected($status === 'belum_lunas')>Belum Bayar</option>
                </select>
                <select name="tahun" onchange="this.form.submit()"
                        class="border border-slate-200 rounded-lg px-3 py-2 text-xs font-semibold text-slate-600">
                    <option value="">Semua Tahun Ajaran</option>
                    @foreach ($tahunOpsi as $ta)
                        <option value="{{ $ta->id_academic_years }}" @selected((string) $tahun === (string) $ta->id_academic_years)>{{ $ta->tahun }}</option>
                    @endforeach
                </select>
                <button type="submit" class="px-3 py-2 bg-indigo-600 hover:bg-indigo-500 text-white text-xs font-bold rounded-lg">Cari</button>
                @if ($tahun || $status || $cari)
                    <a href="{{ route('admin.daftar-ulang', ['tab' => 'siswa']) }}" class="text-4xs font-bold text-slate-400 hover:text-rose-500 px-2">Reset</a>
                @endif
            </form>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3">Murid</th>
                        <th class="py-3">Orang Tua</th>
                        <th class="py-3">Tahun Ajaran</th>
                        <th class="py-3">Tanggal</th>
                        <th class="py-3">Waktu</th>
                        <th class="py-3">Bank</th>
                        <th class="py-3 text-right">Tagihan</th>
                        <th class="py-3 text-right">Terbayar</th>
                        <th class="py-3 text-right">Sisa</th>
                        <th class="py-3 text-center">Bukti</th>
                        <th class="py-3 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($bills as $b)
                        @php $tx = $b->transactions->firstWhere('bukti_path'); @endphp
                        <tr class="text-slate-600 hover:bg-slate-50 transition-colors">
                            <td class="py-3 font-bold text-slate-900">{{ $b->student?->nama_lengkap ?? '-' }}</td>
                            <td class="py-3 text-slate-500">{{ $b->student?->ortu?->namaWali() ?? '-' }}</td>
                            <td class="py-3 text-slate-400">{{ $b->academicYear?->tahun ?? '-' }}</td>
                            <td class="py-3 text-slate-400">{{ $tx?->tanggal_bayar?->translatedFormat('d M Y') ?? '-' }}</td>
                            <td class="py-3 text-slate-400">{{ $tx?->created_at?->format('H:i') ? $tx->created_at->format('H:i').' WIB' : '-' }}</td>
                            <td class="py-3 text-slate-500">{{ $tx?->bank_asal ?: '-' }}</td>
                            <td class="py-3 text-right">Rp {{ number_format((int) $b->total_biaya, 0, ',', '.') }}</td>
                            <td class="py-3 text-right">Rp {{ number_format((int) $b->jumlah_terbayar, 0, ',', '.') }}</td>
                            <td class="py-3 text-right font-bold text-slate-900">Rp {{ number_format((int) $b->sisa(), 0, ',', '.') }}</td>
                            <td class="py-3 text-center">
                                @if ($tx)
                                    <button type="button" onclick="openBerkas('{{ asset('storage/'.$tx->bukti_path) }}')"
                                            class="inline-flex w-9 h-9 rounded border border-slate-200 overflow-hidden hover:border-indigo-400 transition-colors items-center justify-center bg-slate-50">
                                        @if (preg_match('/\.(jpe?g|png|gif|webp)$/i', $tx->bukti_path))
                                            <img src="{{ asset('storage/'.$tx->bukti_path) }}" class="w-full h-full object-cover" alt="Bukti">
                                        @else
                                            <span class="text-3xs font-black text-rose-500">PDF</span>
                                        @endif
                                    </button>
                                @else <span class="text-4xs text-slate-300">-</span> @endif
                            </td>
                            <td class="py-3 text-right"><span class="inline-flex px-2 py-0.5 rounded text-4xs font-bold uppercase {{ $stColor[$b->status] ?? 'bg-slate-100 text-slate-600' }}">{{ str_replace('_', ' ', $b->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="11" class="py-6 text-center text-slate-400">Tidak ada tagihan daftar ulang.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginate :paginator="$bills" />
    </div>

</div>
@endsection
