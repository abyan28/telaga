<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Command storage:reset — mengosongkan berkas unggahan hasil pengujian.
 *
 * Dijalankan bersama `migrate:fresh` agar folder upload (dokumen pendaftaran,
 * bukti pembayaran, konten web) tidak menyisakan file yatim setelah data DB
 * di-reset. Symlink public/storage tidak disentuh.
 */
class ResetStorage extends Command
{
    protected $signature = 'storage:reset {--force : Lewati konfirmasi}';

    protected $description = 'Hapus berkas unggahan (pendaftaran, pembayaran, konten, profil) di storage/app/public';

    // Folder yang dibersihkan (relatif disk 'public')
    private const FOLDERS = ['pendaftaran', 'pembayaran', 'konten', 'profil'];

    /**
     * Menghapus tiap folder unggahan bila ada.
     */
    public function handle(): int
    {
        if (! $this->option('force') && ! $this->confirm('Hapus semua berkas unggahan di storage? Data tak bisa dikembalikan.')) {
            $this->info('Dibatalkan.');

            return self::SUCCESS;
        }

        $disk = Storage::disk('public');
        foreach (self::FOLDERS as $folder) {
            if ($disk->exists($folder)) {
                $disk->deleteDirectory($folder);
                $this->line("Dihapus: {$folder}/");
            }
        }
        $this->info('Storage upload dibersihkan.');

        return self::SUCCESS;
    }
}
