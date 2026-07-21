<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Tambah enum 'refund' di payment_transactions.jenis + 'dibatalkan' di registration_forms.status.
     * MySQL-only: SQLite tak support MODIFY COLUMN (fresh DB sudah include nilai di migrasi create).
     * Plus: buat bukti_path nullable untuk jenis=refund (tidak punya bukti transfer).
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN jenis ENUM('pendaftaran','daftar_ulang','spp','refund') NOT NULL");
        DB::statement("ALTER TABLE registration_forms MODIFY COLUMN status ENUM('submitted','menunggu_bukti','menunggu_verifikasi','pembayaran_diverifikasi','diproses_seleksi','lulus','gagal','dibatalkan') NOT NULL DEFAULT 'submitted'");
        DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN bukti_path VARCHAR(255) NULL");
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'sqlite') {
            return;
        }

        DB::statement("ALTER TABLE payment_transactions MODIFY COLUMN jenis ENUM('pendaftaran','daftar_ulang','spp') NOT NULL");
        DB::statement("ALTER TABLE registration_forms MODIFY COLUMN status ENUM('submitted','menunggu_bukti','menunggu_verifikasi','pembayaran_diverifikasi','diproses_seleksi','lulus','gagal') NOT NULL DEFAULT 'submitted'");
    }
};
