<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Generate tagihan SPP bulanan otomatis tiap awal bulan (rules.md §1.3.5).
// Produksi: cron menjalankan `php artisan schedule:run` tiap menit.
Schedule::command('spp:generate')->monthlyOn(1, '00:05');

// Proses antrean email notifikasi SPP & lainnya setiap menit (database queue driver).
// --stop-when-empty: worker mati setelah semua job selesai, tidak perlu daemon.
// withoutOverlapping: cegah tumpukan worker jika job lebih lama dari 1 menit.
Schedule::command('queue:work --queue=spp-notifications --stop-when-empty --tries=3')
    ->everyMinute()
    ->withoutOverlapping();
