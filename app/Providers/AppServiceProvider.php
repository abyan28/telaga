<?php

namespace App\Providers;

use App\Models\PaymentTransaction;
use App\Services\AuditLogService;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     *
     * Mendaftarkan listener event auth untuk mencatat aktivitas login/logout ke
     * audit log (PRD §7.14).
     */
    public function boot(): void
    {
        // Catat setiap login berhasil
        Event::listen(function (Login $event): void {
            AuditLogService::record('login', 'User#'.$event->user->getAuthIdentifier());
        });

        // Catat setiap logout
        Event::listen(function (Logout $event): void {
            if ($event->user) {
                AuditLogService::record('logout', 'User#'.$event->user->getAuthIdentifier());
            }
        });

        // K6.1: badge jumlah "perlu verifikasi" per tab sidebar admin.
        // Hanya dihitung untuk user admin (satu query bukti bayar pending + 1 query dokumen).
        View::composer('layouts.dashboard', function ($view): void {
            $badge = ['pendaftaran' => 0, 'daftar_ulang' => 0, 'spp' => 0];
            if (Auth::check() && Auth::user()->role === 'admin') {
                // Badge PPDB = jumlah PENDAFTAR yang masih perlu tindakan admin
                // (1 per form): bayar belum diverif, berkas, atau seleksi belum tuntas.
                $bayar = PaymentTransaction::where('status', 'pending')
                    ->whereIn('jenis', ['daftar_ulang', 'spp'])
                    ->selectRaw('jenis, COUNT(*) as c')->groupBy('jenis')->pluck('c', 'jenis');
                $badge = [
                    'pendaftaran' => \App\Models\RegistrationForm::whereIn('status',
                        ['menunggu_verifikasi', 'pembayaran_diverifikasi', 'diproses_seleksi'])->count(),
                    'daftar_ulang' => (int) ($bayar['daftar_ulang'] ?? 0),
                    'spp' => (int) ($bayar['spp'] ?? 0),
                ];
            }
            $view->with('sidebarBadge', $badge);
        });

        // Logo + data kontak/footer dari CMS (grup 'kontak') — dishare ke layout
        // publik agar header & footer tidak hardcode. Satu query ambil semua konten
        // grup kontak; logo tetap di key 'home.logo'. Null = fallback default view.
        View::composer('layouts.public', function ($view): void {
            $kontak = \App\Models\SiteContent::whereIn('grup', ['home', 'kontak'])
                ->pluck('value', 'key');
            $view->with('siteLogo', $kontak['home.logo'] ?? null);
            $view->with('kontak', $kontak);
        });
    }
}
