@extends('layouts.dashboard')

@section('title', 'Pembayaran SPP & Daftar Ulang — TELAGA AL KAUTSAR')
@section('header_title', 'Pembayaran & SPP')

@section('content')
@php
    // Kumpulkan seluruh tagihan yang masih ada sisa, dari semua anak orang tua.
    $items = collect();
    foreach ($students as $s) {
        foreach ($s->reRegistrationPayments as $r) {
            if ($r->sisa() > 0) {
                $items->push([
                    'jenis' => 'daftar_ulang', 'ref' => $r->id_re_registration_payments,
                    'label' => 'Daftar Ulang', 'anak' => $s->nama_lengkap,
                    'total' => (int) $r->total_biaya, 'bayar' => (int) $r->jumlah_terbayar,
                    'sisa' => (int) $r->sisa(), 'status' => $r->status,
                    'pending' => $r->transactions->contains('status', 'pending'),
                    // L2.2: DU terkunci bila PPDB ditutup & belum bayar sama sekali.
                    'terkunci' => ! $ppdbDibuka && (int) $r->jumlah_terbayar <= 0,
                    'blob' => 'bg-indigo-50', 'text' => 'text-indigo-600', 'bar' => 'bg-indigo-500', 'btn' => 'bg-indigo-600 hover:bg-indigo-500',
                ]);
            }
        }
        foreach ($s->monthlySppBills as $b) {
            if ($b->sisa() > 0) {
                $items->push([
                    'jenis' => 'spp', 'ref' => $b->id_monthly_spp_bills,
                    'label' => 'SPP '.\Illuminate\Support\Carbon::parse($b->bulan.'-01')->translatedFormat('F Y'),
                    'anak' => $s->nama_lengkap,
                    'total' => (int) $b->nominal, 'bayar' => (int) $b->jumlah_terbayar,
                    'sisa' => (int) $b->sisa(), 'status' => $b->status,
                    'pending' => $b->transactions->contains('status', 'pending'),
                    'terkunci' => false,
                    'blob' => 'bg-sky-50', 'text' => 'text-sky-600', 'bar' => 'bg-sky-500', 'btn' => 'bg-sky-600 hover:bg-sky-500',
                ]);
            }
        }
    }
@endphp

<div class="max-w-4xl mx-auto space-y-8" x-data="{
    uploadModalOpen: false,
    jenis: '', ref: null, label: '', sisa: 0,
    openUpload(jenis, ref, label, sisa) {
        this.jenis = jenis; this.ref = ref; this.label = label; this.sisa = sisa;
        this.uploadModalOpen = true;
    }
}">

    {{-- Flash / error --}}
    @if (session('success'))
        <div class="bg-emerald-50 border border-emerald-100 text-emerald-800 rounded-2xl px-5 py-3 text-xs font-semibold">{{ session('success') }}</div>
    @endif
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-100 text-rose-700 rounded-2xl px-5 py-3 text-xs font-semibold">{{ $errors->first() }}</div>
    @endif

    <!-- Summary of outstanding bills -->
    @if ($items->isEmpty())
        <div class="bg-white border border-slate-100 rounded-3xl p-8 shadow-xs text-center">
            <p class="text-sm text-slate-500">Tidak ada tunggakan aktif saat ini. Semua tagihan sudah lunas. 🎉</p>
        </div>
    @else
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        @foreach ($items as $it)
            @php $pct = $it['total'] > 0 ? min(100, round($it['bayar'] / $it['total'] * 100, 1)) : 0; @endphp
            <div class="bg-white border border-slate-100 rounded-3xl p-6 md:p-8 shadow-xs flex flex-col justify-between space-y-6 relative overflow-hidden">
                <div class="absolute -right-6 -bottom-6 w-24 h-24 {{ $it['blob'] }} rounded-full"></div>
                <div class="space-y-4 relative">
                    <div class="flex justify-between items-start gap-2">
                        <div class="min-w-0">
                            <span class="text-xs font-bold {{ $it['text'] }} tracking-wider uppercase block truncate">{{ $it['label'] }}</span>
                            @if ($students->count() > 1)
                                <span class="text-3xs text-slate-400">{{ $it['anak'] }}</span>
                            @endif
                        </div>
                        <span class="inline-flex items-center shrink-0 px-2.5 py-1 rounded-full text-4xs font-bold bg-amber-100 text-amber-700 uppercase">{{ str_replace('_', ' ', $it['status']) }}</span>
                    </div>
                    <div>
                        <span class="text-2xs font-semibold text-slate-400 block uppercase">Sisa Tunggakan</span>
                        <span class="text-3xl font-black text-slate-900 block">Rp {{ number_format($it['sisa'], 0, ',', '.') }}</span>
                    </div>
                    <div class="space-y-2">
                        <div class="flex justify-between text-2xs text-slate-400 font-semibold gap-2">
                            <span>Progress: {{ $pct }}%</span>
                            <span class="shrink-0">Rp {{ number_format($it['bayar'], 0, ',', '.') }} / Rp {{ number_format($it['total'], 0, ',', '.') }}</span>
                        </div>
                        <div class="w-full bg-slate-100 h-2 rounded-full overflow-hidden">
                            <div class="{{ $it['bar'] }} h-full rounded-full" style="width: {{ $pct }}%"></div>
                        </div>
                    </div>
                </div>
                <div class="pt-4 border-t border-slate-100 relative">
                    @if ($it['pending'])
                        <div class="w-full py-3 bg-amber-100 text-amber-700 font-bold rounded-xl shadow-xs text-xs text-center">Sedang Diverifikasi</div>
                    @elseif ($it['terkunci'])
                        <div class="w-full py-3 bg-rose-100 text-rose-700 font-bold rounded-xl shadow-xs text-xs text-center">Pendaftaran Ditutup — Tidak Dapat Dibayar</div>
                    @else
                        <button @click="openUpload('{{ $it['jenis'] }}', {{ $it['ref'] }}, '{{ $it['label'] }}', {{ $it['sisa'] }})" class="w-full py-3 {{ $it['btn'] }} text-white font-bold rounded-xl shadow-md transition-all text-xs">
                            {{ $it['jenis'] === 'spp' ? 'Bayar Lunas' : 'Bayar Cicilan' }}
                        </button>
                    @endif
                </div>
            </div>
        @endforeach
    </div>
    @endif

    <!-- History list -->
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <h3 class="text-base font-bold text-slate-950">Riwayat Pembayaran & Cicilan</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-4">No.</th>
                        <th class="py-4">Jenis</th>
                        <th class="py-4">Atas Nama</th>
                        <th class="py-4">Tanggal</th>
                        <th class="py-4 text-right">Jumlah</th>
                        <th class="py-4 text-right">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($history as $tx)
                        @php
                            $stClass = ['diverifikasi' => 'bg-emerald-100 text-emerald-700', 'ditolak' => 'bg-rose-100 text-rose-700'][$tx->status] ?? 'bg-amber-100 text-amber-700';
                            // K4.2: untuk SPP tampilkan bulan tagihan (dari relasi sppBill).
                            $jenisLabel = ucfirst(str_replace('_', ' ', $tx->jenis));
                            if ($tx->jenis === 'spp' && $tx->sppBill?->bulan) {
                                $jenisLabel .= ' '.\Illuminate\Support\Carbon::parse($tx->sppBill->bulan.'-01')->translatedFormat('F Y');
                            }
                        @endphp
                        <tr class="text-slate-600 hover:bg-slate-50 transition-colors">
                            <td class="py-4 font-bold text-slate-800">TX-{{ str_pad($tx->id_payment_transactions, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-4 font-semibold">{{ $jenisLabel }}</td>
                            <td class="py-4 text-slate-500">{{ $tx->student?->nama_lengkap ?? '-' }}</td>
                            <td class="py-4 text-slate-400">{{ $tx->tanggal_bayar?->translatedFormat('d M Y') }}</td>
                            <td class="py-4 text-right font-bold text-slate-900">Rp {{ number_format((int) $tx->jumlah, 0, ',', '.') }}</td>
                            <td class="py-4 text-right">
                                <span class="inline-flex items-center px-2 py-0.5 rounded text-4xs font-bold uppercase tracking-wider {{ $stClass }}">{{ ucfirst($tx->status) }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="py-6 text-center text-slate-400">Belum ada riwayat pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Upload Installment Modal (form asli POST) -->
    <div x-show="uploadModalOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-900/60 backdrop-blur-xs" x-cloak>
        <div class="bg-white rounded-[2.5rem] p-8 max-w-md w-full border border-slate-100 shadow-2xl space-y-6" @click.away="uploadModalOpen = false">
            <div class="flex justify-between items-start">
                <h3 class="text-lg font-bold text-slate-950">Bayar Cicilan — <span x-text="label"></span></h3>
                <button type="button" @click="uploadModalOpen = false" class="text-slate-400 hover:text-slate-600 p-1 rounded-lg hover:bg-slate-100 transition-colors">
                    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <form action="{{ route('ortu.payments.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                @csrf
                <input type="hidden" name="jenis" :value="jenis">
                <input type="hidden" name="referensi_id" :value="ref">

                <div>
                    <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Rekening Tujuan</label>
                    <div class="mt-1 p-3 bg-slate-50 border border-slate-200 rounded-xl text-xs space-y-1">
                        @if ($rekening['bank'] || $rekening['nomor'])
                            <p class="font-bold text-slate-800">{{ $rekening['bank'] ?: '—' }}</p>
                            <p class="text-slate-500">No. Rek: <strong>{{ $rekening['nomor'] ?: '—' }}</strong> a.n. <strong>{{ $rekening['atas_nama'] ?: 'RA Al Kautsar' }}</strong></p>
                        @else
                            <p class="text-3xs text-amber-600">Rekening sekolah belum diatur admin.</p>
                        @endif
                    </div>
                </div>

                <div class="space-y-1">
                    <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide" x-text="jenis === 'spp' ? 'Nominal Pembayaran (Harus Lunas)' : 'Nominal Pembayaran (Boleh Sebagian)'"></label>
                    <div class="relative">
                        <span class="absolute left-4 top-3 text-slate-400 font-bold text-sm">Rp</span>
                        <template x-if="jenis === 'spp'">
                            <input type="number" name="jumlah" :value="sisa" readonly required class="w-full pl-10 pr-4 py-3 bg-slate-100 text-slate-600 cursor-not-allowed border border-slate-200 rounded-xl text-sm font-bold focus:outline-none">
                        </template>
                        <template x-if="jenis !== 'spp'">
                            <input type="number" name="jumlah" min="1" :max="sisa" :value="sisa" required class="w-full pl-10 pr-4 py-3 bg-slate-50 border border-slate-200 rounded-xl text-sm font-bold text-slate-800 focus:outline-none focus:border-sky-500">
                        </template>
                    </div>
                    <span x-show="jenis !== 'spp'" class="text-4xs text-slate-400 block mt-1 leading-normal">* Maksimal sisa tunggakan: <strong x-text="'Rp ' + sisa.toLocaleString('id-ID')"></strong>.</span>
                    <span x-show="jenis === 'spp'" class="text-4xs text-emerald-600 block mt-1 leading-normal font-semibold">* SPP harus dibayar lunas (tidak dapat dicicil).</span>
                </div>

                <div class="space-y-1">
                    <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Tanggal Transfer</label>
                    <input type="date" name="tanggal_bayar" value="{{ date('Y-m-d') }}" required class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500">
                </div>

                <div class="space-y-1">
                    <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Bank Asal Transfer</label>
                    <x-bank-picker :banks="$daftarBank" class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-sm focus:outline-none focus:border-sky-500" />
                </div>

                <div class="space-y-1">
                    <label class="text-2xs font-bold text-slate-500 uppercase tracking-wide">Bukti Transfer (JPG/PDF, maks 2 MB)</label>
                    <input type="file" name="bukti" accept=".jpg,.jpeg,.pdf" required class="w-full text-xs text-slate-500 file:mr-4 file:py-2 file:px-4 file:rounded-xl file:border-0 file:text-xs file:font-semibold file:bg-sky-50 file:text-sky-700 hover:file:bg-sky-100 cursor-pointer">
                </div>

                <div class="flex space-x-3 pt-2">
                    <button type="button" @click="uploadModalOpen = false" class="flex-1 py-3 border border-slate-200 hover:bg-slate-50 font-bold rounded-xl text-xs text-slate-600 transition-colors">Batal</button>
                    <button type="submit" class="flex-1 py-3 bg-sky-600 hover:bg-sky-500 text-white font-bold rounded-xl shadow-md text-xs transition-colors" x-text="jenis === 'spp' ? 'Kirim Bukti Pembayaran' : 'Kirim Bukti Cicilan'"></button>
                </div>
            </form>
        </div>
    </div>

</div>
@endsection
