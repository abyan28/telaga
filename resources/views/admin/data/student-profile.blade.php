@extends('layouts.dashboard')

@section('title', 'Profil Murid — RA Al Kautsar')
@section('header_title', 'Profil Murid')

@section('content')
@php
    $bulanLabel = fn ($b) => \Illuminate\Support\Carbon::parse($b.'-01')->translatedFormat('F Y');
    $stColor = fn ($s) => ['lunas' => 'bg-emerald-100 text-emerald-700', 'kurang' => 'bg-amber-100 text-amber-700', 'belum_lunas' => 'bg-rose-100 text-rose-700', 'diverifikasi' => 'bg-emerald-100 text-emerald-700', 'ditolak' => 'bg-rose-100 text-rose-700', 'pending' => 'bg-amber-100 text-amber-700'][$s] ?? 'bg-slate-100 text-slate-600';
@endphp

<div class="max-w-4xl mx-auto space-y-8" x-data="hashTabs('orang-tua')">

    <a href="{{ route('admin.data.students') }}" class="inline-flex items-center space-x-1 text-xs font-bold text-slate-500 hover:text-indigo-600">
        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/></svg>
        <span>Kembali ke Data Murid</span>
    </a>

    {{-- Biodata (selalu tampil) --}}
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <div class="flex items-center gap-4">
            <x-avatar :path="$student->foto_path" :name="$student->nama_lengkap" size="w-20 h-20" />
            <h3 class="text-base font-bold text-slate-950">{{ $student->nama_lengkap }}</h3>
        </div>
        <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-xs">
            @foreach ([
                'NIK' => $student->nik, 'NISN' => $student->nisn ?? '—',
                'Jenis Kelamin' => ['L' => 'Laki-laki', 'P' => 'Perempuan'][$student->jenis_kelamin] ?? '-',
                'Tempat, Tgl Lahir' => $student->tempat_lahir.', '.optional($student->tanggal_lahir)->translatedFormat('d F Y'),
                'Kelas' => $student->schoolClass?->nama_kelas ?? 'Belum Terbagi',
                'Wali Kelas' => $student->schoolClass?->homeroomTeacher?->nama ?? '-',
                'Orang Tua' => $student->ortu?->namaWali() ?? '-',
                'Tahun Ajaran' => $student->academicYear?->tahun ?? '-',
                'Status' => ucfirst($student->status),
            ] as $label => $val)
                <div>
                    <dt class="text-slate-400 font-semibold uppercase tracking-wide text-3xs">{{ $label }}</dt>
                    <dd class="font-bold text-slate-800 mt-0.5">{{ $val }}</dd>
                </div>
            @endforeach
        </dl>
    </div>

    {{-- Tab bar (slidebar dalam halaman) — hindari scroll panjang (rules §6.2) --}}
    <div class="flex gap-2 border-b border-slate-100 overflow-x-auto">
        @foreach (['orang-tua' => 'Profil Orang Tua', 'daftar-ulang' => 'Daftar Ulang', 'spp' => 'Tagihan SPP', 'riwayat' => 'Riwayat Pembayaran'] as $key => $label)
            <button @click="setTab('{{ $key }}')" :class="tab === '{{ $key }}' ? 'border-indigo-500 text-indigo-600' : 'border-transparent text-slate-400 hover:text-slate-600'"
                    class="whitespace-nowrap px-4 py-3 border-b-2 font-bold text-xs transition-colors">{{ $label }}</button>
        @endforeach
    </div>

    {{-- Profil Orang Tua / Orang Tua --}}
    <div x-show="tab === 'orang-tua'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <h3 class="text-base font-bold text-slate-950">Profil Orang Tua / Orang Tua</h3>
        @if ($student->ortu)
            @php $g = $student->ortu; @endphp
            <div class="space-y-5">
                @foreach (['ibu' => 'Ibu', 'ayah' => 'Ayah'] as $p => $sebutan)
                    @if ($g->{"ada_$p"} && $g->{"{$p}_nama"})
                        <div>
                            <h4 class="text-3xs font-extrabold uppercase tracking-widest text-indigo-500 mb-2">Data {{ $sebutan }}</h4>
                            <dl class="grid grid-cols-2 md:grid-cols-3 gap-4 text-xs">
                                @foreach ([
                                    'Nama' => $g->{"{$p}_nama"} ?: '-',
                                    'No. HP' => $g->{"{$p}_no_hp"} ?: '-',
                                    'Tempat, Tgl Lahir' => trim(($g->{"{$p}_tempat_lahir"} ?: '').', '.optional($g->{"{$p}_tanggal_lahir"})->translatedFormat('d F Y'), ', ') ?: '-',
                                    'Agama' => $g->{"{$p}_agama"} ?: '-',
                                    'Pendidikan' => $g->{"{$p}_pendidikan"} ?: '-',
                                    'Pekerjaan' => ($g->{"{$p}_pekerjaan"} === 'LAINNYA' ? $g->{"{$p}_pekerjaan_lain"} : $g->{"{$p}_pekerjaan"}) ?: '-',
                                    'Penghasilan' => $g->{"{$p}_penghasilan"} ?: '-',
                                ] as $label => $val)
                                    <div>
                                        <dt class="text-slate-400 font-semibold uppercase tracking-wide text-3xs">{{ $label }}</dt>
                                        <dd class="font-bold text-slate-800 mt-0.5">{{ $val }}</dd>
                                    </div>
                                @endforeach
                            </dl>
                        </div>
                    @endif
                @endforeach
                <div>
                    <dt class="text-slate-400 font-semibold uppercase tracking-wide text-3xs">Email Akun</dt>
                    <dd class="font-bold text-slate-800 mt-0.5">{{ $g->user?->email ?: '-' }}</dd>
                </div>
                <div>
                    <dt class="text-slate-400 font-semibold uppercase tracking-wide text-3xs">Alamat Keluarga</dt>
                    <dd class="font-bold text-slate-800 mt-0.5">{{ trim(($g->alamat ?: '').' '.collect([$g->kelurahan_nama, $g->kecamatan_nama, $g->kota_nama, $g->provinsi_nama])->filter()->implode(', ')) ?: '-' }}</dd>
                </div>
            </div>
        @else
            <p class="text-xs text-slate-400">Murid ini belum tertaut ke akun orang tua.</p>
        @endif
    </div>

    {{-- Tagihan Daftar Ulang --}}
    <div x-show="tab === 'daftar-ulang'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <h3 class="text-base font-bold text-slate-950">Tagihan Daftar Ulang</h3>
        @forelse ($student->reRegistrationPayments as $r)
            <div class="flex flex-wrap justify-between items-center gap-2 border border-slate-100 rounded-xl p-4 text-xs">
                <span class="font-bold text-slate-800">T.A. {{ $r->academicYear?->tahun ?? '-' }}</span>
                <span class="text-slate-500">Terbayar Rp {{ number_format((int) $r->jumlah_terbayar, 0, ',', '.') }} / Rp {{ number_format((int) $r->total_biaya, 0, ',', '.') }}</span>
                <span class="font-bold text-slate-800">Sisa Rp {{ number_format((int) $r->sisa(), 0, ',', '.') }}</span>
                <span class="inline-flex px-2 py-0.5 rounded text-4xs font-bold uppercase {{ $stColor($r->status) }}">{{ str_replace('_', ' ', $r->status) }}</span>
            </div>
        @empty
            <p class="text-xs text-slate-400">Belum ada tagihan daftar ulang.</p>
        @endforelse
    </div>

    {{-- Tagihan SPP --}}
    <div x-show="tab === 'spp'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <h3 class="text-base font-bold text-slate-950">Tagihan SPP Bulanan</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3">Bulan</th>
                        <th class="py-3 text-right">Nominal</th>
                        <th class="py-3 text-right">Terbayar</th>
                        <th class="py-3 text-right">Sisa</th>
                        <th class="py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($student->monthlySppBills as $b)
                        <tr class="text-slate-600">
                            <td class="py-3 font-bold text-slate-800">{{ $bulanLabel($b->bulan) }}</td>
                            <td class="py-3 text-right">Rp {{ number_format((int) $b->nominal, 0, ',', '.') }}</td>
                            <td class="py-3 text-right">Rp {{ number_format((int) $b->jumlah_terbayar, 0, ',', '.') }}</td>
                            <td class="py-3 text-right font-bold text-slate-900">Rp {{ number_format((int) $b->sisa(), 0, ',', '.') }}</td>
                            <td class="py-3"><span class="inline-flex px-2 py-0.5 rounded text-4xs font-bold uppercase {{ $stColor($b->status) }}">{{ str_replace('_', ' ', $b->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-400">Belum ada tagihan SPP.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Riwayat Transaksi --}}
    <div x-show="tab === 'riwayat'" x-cloak class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-4">
        <h3 class="text-base font-bold text-slate-950">Riwayat Pembayaran</h3>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-3">No. Ref</th>
                        <th class="py-3">Jenis</th>
                        <th class="py-3">Tanggal</th>
                        <th class="py-3 text-right">Jumlah</th>
                        <th class="py-3">Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50">
                    @forelse ($student->paymentTransactions as $tx)
                        <tr class="text-slate-600">
                            <td class="py-3 font-bold text-slate-800">TX-{{ str_pad($tx->id_payment_transactions, 4, '0', STR_PAD_LEFT) }}</td>
                            <td class="py-3">{{ ucfirst(str_replace('_', ' ', $tx->jenis)) }}</td>
                            <td class="py-3 text-slate-400">{{ $tx->tanggal_bayar?->translatedFormat('d M Y') ?? '-' }}</td>
                            <td class="py-3 text-right font-bold text-slate-900">Rp {{ number_format((int) $tx->jumlah, 0, ',', '.') }}</td>
                            <td class="py-3"><span class="inline-flex px-2 py-0.5 rounded text-4xs font-bold uppercase {{ $stColor($tx->status) }}">{{ ucfirst($tx->status) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-400">Belum ada riwayat pembayaran.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection
