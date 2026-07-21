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
