<?php

namespace App\Providers;

use App\Models\PaymentTransaction;
use App\Models\RegistrationDocument;
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
                // Bukti bayar pending dikelompokkan per jenis.
                $bayar = PaymentTransaction::where('status', 'pending')
                    ->selectRaw('jenis, COUNT(*) as c')->groupBy('jenis')->pluck('c', 'jenis');
                // Dokumen pendaftaran (KK/Akta/Foto) belum diverifikasi masuk badge PPDB.
                $dok = RegistrationDocument::where('status', 'pending')->count();
                $badge = [
                    'pendaftaran' => (int) ($bayar['pendaftaran'] ?? 0) + $dok,
                    'daftar_ulang' => (int) ($bayar['daftar_ulang'] ?? 0),
                    'spp' => (int) ($bayar['spp'] ?? 0),
                ];
            }
            $view->with('sidebarBadge', $badge);
        });

        // Logo web (header & footer) dari CMS — dishare ke layout publik. Null = fallback "AK".
        View::composer('layouts.public', function ($view): void {
            $view->with('siteLogo', \App\Models\SiteContent::where('key', 'home.logo')->value('value'));
        });
    }
}
