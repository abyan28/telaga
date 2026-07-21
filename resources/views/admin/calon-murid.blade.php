@extends('layouts.dashboard')

@section('title', 'Calon Murid — RA Al Kautsar')
@section('header_title', 'PPDB — Calon Murid')

@section('content')
@php
    $dendaPct = $persen; // langsung persen denda (settings key persen_refund = denda, perbaiki L2.1)
    $stColor  = ['lunas' => 'bg-emerald-100 text-emerald-700', 'kurang' => 'bg-amber-100 text-amber-700', 'belum_lunas' => 'bg-rose-100 text-rose-700'];
@endphp
<div class="space-y-6">

    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    {{-- Header + tombol Generate NIS --}}
    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <h3 class="text-base font-bold text-slate-950">Calon Murid Baru</h3>
            <p class="text-xs text-slate-400 mt-0.5">Murid yang sudah lulus seleksi &amp; membayar sebagian/lunas daftar ulang, belum mendapat NIS.</p>
        </div>
        <div class="flex items-center gap-3">
            @if (! $ppdbTutup)
                <span class="text-xs text-amber-600 font-semibold bg-amber-50 border border-amber-100 rounded-xl px-3 py-2">
                    PPDB masih buka — tutup PPDB dulu untuk generate NIS
                </span>
            @elseif (strlen($nsmSekolah) !== 12)
                <span class="text-xs text-rose-600 font-semibold bg-rose-50 border border-rose-100 rounded-xl px-3 py-2">
                    NSM belum diisi — isi di Pengaturan Sistem → NSM
                </span>
            @endif
            <form action="{{ route('admin.calon-murid.generate-nis') }}" method="POST"
                  onsubmit="return confirm('Generate NIS untuk {{ $calons->count() }} calon murid? Urutan berdasarkan abjad nama. Tindakan ini tidak dapat dibatalkan.')">
                @csrf
                <button type="submit"
                        @if (! $ppdbTutup || strlen($nsmSekolah) !== 12 || $calons->isEmpty()) disabled @endif
                        class="inline-flex items-center gap-2 px-4 py-2.5 bg-indigo-600 hover:bg-indigo-500 disabled:opacity-40 disabled:cursor-not-allowed text-white font-bold rounded-xl text-xs shadow-md transition-colors">
                    <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M7 20l4-16m2 16l4-16M6 9h14M4 15h14"/></svg>
                    Generate NIS ({{ $calons->count() }})
                </button>
            </form>
        </div>
    </div>

    {{-- Info denda --}}
    <div class="bg-slate-50 border border-slate-100 rounded-2xl px-5 py-3 text-xs text-slate-500">
        Aturan pembatalan: denda <span class="font-bold text-slate-700">{{ $dendaPct }}%</span> dari total biaya DU.
        Refund = terbayar &minus; {{ $dendaPct }}% total. Bila terbayar &lt; {{ $dendaPct }}% → belum bisa dibatalkan penuh (wajib lunasi sisa denda).
    </div>

    {{-- Tabel calon murid --}}
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3">No</th>
                        <th class="py-3">Nama Murid</th>
                        <th class="py-3">Orang Tua</th>
                        <th class="py-3">NIS</th>
                        <th class="py-3">Tgl Lahir</th>
                        <th class="py-3 text-right">Tagihan DU</th>
                        <th class="py-3 text-right">Terbayar</th>
                        <th class="py-3 text-right">Sisa</th>
                        <th class="py-3 text-right">Status DU</th>
                        <th class="py-3 text-right">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($calons as $i => $s)
                        @php
                            $bill     = $s->reRegistrationPayments->first();
                            $total    = (float) ($bill?->total_biaya ?? 0);
                            $bayar    = (float) ($bill?->jumlah_terbayar ?? 0);
                            $denda    = round($total * $dendaPct / 100, 0);
                            $refund   = max(0, $bayar - $denda);
                            $kurang   = max(0, $denda - $bayar); // sisa wajib lunasi
                            $stClass  = $stColor[$bill?->status ?? ''] ?? 'bg-slate-100 text-slate-600';
                        @endphp
                        <tr class="text-slate-600 hover:bg-slate-50 transition-colors">
                            <td class="py-3 text-slate-400">{{ $i + 1 }}</td>
                            <td class="py-3 font-bold text-slate-900">{{ $s->nama_lengkap }}</td>
                            <td class="py-3 text-slate-500">{{ $s->ortu?->namaWali() ?? '-' }}</td>
                            <td class="py-3 text-slate-300 italic">— belum —</td>
                            <td class="py-3 text-slate-400">{{ $s->tanggal_lahir?->translatedFormat('d M Y') ?? '-' }}</td>
                            <td class="py-3 text-right font-semibold">Rp {{ number_format((int) $total, 0, ',', '.') }}</td>
                            <td class="py-3 text-right text-emerald-700 font-bold">Rp {{ number_format((int) $bayar, 0, ',', '.') }}</td>
                            <td class="py-3 text-right font-bold text-slate-900">Rp {{ number_format((int) ($bill?->sisa() ?? 0), 0, ',', '.') }}</td>
                            <td class="py-3 text-right">
                                <span class="inline-flex px-2 py-0.5 rounded text-4xs font-bold uppercase {{ $stClass }}">{{ str_replace('_', ' ', $bill?->status ?? '-') }}</span>
                            </td>
                            <td class="py-3 text-right">
                                @if ($kurang > 0)
                                    {{-- Terbayar < denda: tampilkan keterangan, bukan tombol batal aktif --}}
                                    <span class="text-4xs text-amber-600 font-semibold">
                                        Lunasi Rp {{ number_format((int) $kurang, 0, ',', '.') }} dulu
                                    </span>
                                @else
                                    <form action="{{ route('admin.calon-murid.cancel', $s) }}" method="POST"
                                          onsubmit="return confirm('Batalkan kelulusan {{ addslashes($s->nama_lengkap) }}?{{ $refund > 0 ? ' Refund Rp '.number_format((int)$refund,0,',','.').'' : ' Tidak ada refund.' }}')">
                                        @csrf
                                        <button type="submit" class="px-3 py-1.5 border border-rose-200 hover:bg-rose-50 text-rose-600 font-bold rounded-lg text-4xs transition-colors">
                                            Batalkan{{ $refund > 0 ? ' (Refund Rp '.number_format((int)$refund,0,',','.').')' : '' }}
                                        </button>
                                    </form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="py-8 text-center text-slate-400">
                                @if ($ppdbTutup)
                                    Semua calon murid sudah mendapat NIS atau belum ada yang membayar daftar ulang.
                                @else
                                    Belum ada calon murid yang membayar daftar ulang.
                                @endif
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
