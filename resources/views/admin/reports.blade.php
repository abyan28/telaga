@extends('layouts.dashboard')

@section('title', 'Laporan & Audit Log — RA Al Kautsar')
@section('header_title', 'Laporan Keuangan & Audit Log')

@section('content')
<div class="space-y-8" x-data="{
    exportData(format, type) {
        alert('Mengunduh file rekapitulasi ' + type + ' dalam format ' + format.toUpperCase() + '...\nFile: Rekap_' + type + '_' + new Date().toISOString().split('T')[0] + '.' + format.toLowerCase());
    }
}">

    <!-- Export Buttons / Reports Center -->
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <h3 class="text-base font-bold text-slate-950">Pusat Ekspor Laporan Data</h3>
        <p class="text-xs text-slate-400 leading-normal">
            Unduh rekapitulasi transaksi keuangan, data tunggakan SPP per murid, dan daftar akademik dalam format PDF atau CSV.
        </p>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-2">
            <!-- Card 1: Transaksi Keuangan -->
            <div class="border border-slate-100 bg-slate-50 rounded-2xl p-5 flex flex-col justify-between space-y-4">
                <div class="space-y-1">
                    <span class="text-2xs font-extrabold uppercase tracking-widest text-slate-400 block">Laporan Keuangan</span>
                    <h4 class="text-sm font-bold text-slate-800">Rekap Transaksi Pembayaran</h4>
                </div>
                <div class="flex space-x-2 pt-2">
                    <button @click="exportData('csv', 'Transaksi_Keuangan')" class="flex-1 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-lg border border-slate-200 text-3xs uppercase tracking-wide transition-colors">CSV</button>
                    <button @click="exportData('pdf', 'Transaksi_Keuangan')" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg text-3xs uppercase tracking-wide transition-colors shadow-xs">PDF</button>
                </div>
            </div>

            <!-- Card 2: Tunggakan SPP -->
            <div class="border border-slate-100 bg-slate-50 rounded-2xl p-5 flex flex-col justify-between space-y-4">
                <div class="space-y-1">
                    <span class="text-2xs font-extrabold uppercase tracking-widest text-slate-400 block">Laporan Tunggakan</span>
                    <h4 class="text-sm font-bold text-slate-800">Rekap Sisa Tunggakan Murid</h4>
                </div>
                <div class="flex space-x-2 pt-2">
                    <button @click="exportData('csv', 'Tunggakan_Siswa')" class="flex-1 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-lg border border-slate-200 text-3xs uppercase tracking-wide transition-colors">CSV</button>
                    <button @click="exportData('pdf', 'Tunggakan_Siswa')" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg text-3xs uppercase tracking-wide transition-colors shadow-xs">PDF</button>
                </div>
            </div>

            <!-- Card 3: Data Murid/Guru -->
            <div class="border border-slate-100 bg-slate-50 rounded-2xl p-5 flex flex-col justify-between space-y-4">
                <div class="space-y-1">
                    <span class="text-2xs font-extrabold uppercase tracking-widest text-slate-400 block">Laporan Akademik</span>
                    <h4 class="text-sm font-bold text-slate-800">Daftar Profil Murid & Kelas</h4>
                </div>
                <div class="flex space-x-2 pt-2">
                    <button @click="exportData('csv', 'Data_Siswa')" class="flex-1 py-2 bg-white hover:bg-slate-50 text-slate-700 font-bold rounded-lg border border-slate-200 text-3xs uppercase tracking-wide transition-colors">CSV</button>
                    <button @click="exportData('pdf', 'Data_Siswa')" class="flex-1 py-2 bg-indigo-600 hover:bg-indigo-500 text-white font-bold rounded-lg text-3xs uppercase tracking-wide transition-colors shadow-xs">PDF</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Audit Log Table (PRD 8.15) -->
    <div class="bg-white border border-slate-100 rounded-[2rem] p-6 md:p-8 shadow-xs space-y-6">
        <h3 class="text-base font-bold text-slate-950">Audit Log Aktivitas Sistem</h3>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-100 text-slate-400 font-semibold uppercase tracking-wider">
                        <th class="py-4">Waktu Kejadian</th>
                        <th class="py-4">Pengguna</th>
                        <th class="py-4">Role</th>
                        <th class="py-4">Aktivitas Tindakan</th>
                        <th class="py-4">IP Address</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-50 text-slate-600">
                    @forelse ($auditLogs as $log)
                        <tr class="hover:bg-slate-50 transition-colors">
                            <td class="py-4 text-slate-400">{{ $log->created_at?->translatedFormat('d M Y H:i') }}</td>
                            <td class="py-4 font-bold text-slate-900">{{ $log->user?->displayName() ?? 'Sistem' }}</td>
                            <td class="py-4 font-semibold text-slate-500">{{ ucfirst($log->user?->role ?? '-') }}</td>
                            <td class="py-4">{{ str_replace('_', ' ', $log->aksi) }}{{ $log->model_terkait ? ' — '.$log->model_terkait : '' }}</td>
                            <td class="py-4 text-slate-400">{{ $log->ip_address ?? '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-6 text-center text-slate-400">Belum ada aktivitas tercatat.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <x-paginate :paginator="$auditLogs" />
    </div>

</div>
@endsection
