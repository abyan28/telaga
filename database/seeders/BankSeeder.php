<?php

namespace Database\Seeders;

use App\Models\Bank;
use Illuminate\Database\Seeder;

/**
 * BankSeeder — impor nama bank dari bank.csv ke tabel banks.
 * Jalankan sekali; CSV dihapus setelah ini tidak diperlukan lagi.
 */
class BankSeeder extends Seeder
{
    public function run(): void
    {
        $path = database_path('data/bank.csv');

        if (! is_file($path)) {
            $this->command?->warn('bank.csv tidak ditemukan — skip.');
            return;
        }

        $rows  = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $names = [];
        foreach (array_slice($rows, 1) as $line) {          // buang header
            $name = trim(str_getcsv($line, ',', '"', '\\')[0] ?? '');
            if ($name !== '') {
                $names[] = ['nama' => $name];
            }
        }

        // upsert: aman dijalankan ulang (migrate:fresh + seed)
        Bank::upsert($names, ['nama'], ['nama']);

        $this->command?->info('BankSeeder: '.count($names).' bank diimpor.');
    }
}
