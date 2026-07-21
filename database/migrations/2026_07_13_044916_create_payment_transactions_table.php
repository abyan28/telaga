<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Membuat tabel payment_transactions (riwayat semua transaksi pembayaran).
     *
     * Mencatat SETIAP pembayaran (pendaftaran, daftar ulang, SPP) termasuk
     * cicilan/parsial (rules.md §1.3, PRD §13). Sisa tunggakan dihitung dinamis
     * dari SUM transaksi berstatus 'diverifikasi' — tabel ini adalah sumber
     * kebenaran (source of truth) untuk perhitungan saldo. Nominal pakai
     * decimal(12,2) agar presisi (bukan float).
     */
    public function up(): void
    {
        Schema::create('payment_transactions', function (Blueprint $table) {
            $table->id('id_payment_transactions');      // PK: id_payment_transactions

            // FK ke siswa (nullable — pembayaran pendaftaran bisa sebelum jadi siswa)
            $table->unsignedBigInteger('id_student')->nullable();
            $table->foreign('id_student')
                  ->references('id_students')->on('students')
                  ->nullOnDelete();

            // FK ke formulir pendaftaran (nullable — hanya untuk pembayaran pendaftaran)
            $table->unsignedBigInteger('id_registration_form')->nullable();
            $table->foreign('id_registration_form')
                  ->references('id_registration_forms')->on('registration_forms')
                  ->nullOnDelete();

            // FK ke user pengunggah bukti (wali)
            $table->unsignedBigInteger('id_user');
            $table->foreign('id_user')
                  ->references('id_users')->on('users')
                  ->cascadeOnDelete();

            $table->enum('jenis', ['pendaftaran', 'daftar_ulang', 'spp', 'refund']); // Jenis pembayaran (refund = catatan pembatalan DU, L2.1)
            // Referensi opsional ke tagihan spesifik (mis. id_monthly_spp_bills / id_re_registration_payments)
            $table->unsignedBigInteger('referensi_id')->nullable();
            $table->decimal('jumlah', 12, 2);            // Nominal dibayar (presisi uang)
            $table->string('bukti_path')->nullable();   // Path bukti transfer (nullable untuk jenis=refund)
            $table->enum('status', ['pending', 'diverifikasi', 'ditolak'])->default('pending');
            $table->text('catatan')->nullable();         // Catatan admin (mis. alasan tolak)
            $table->date('tanggal_bayar');               // Tanggal transaksi

            // FK admin yang memverifikasi (nullable sampai diverifikasi)
            $table->unsignedBigInteger('verified_by')->nullable();
            $table->foreign('verified_by')
                  ->references('id_users')->on('users')
                  ->nullOnDelete();

            $table->timestamps();
        });
    }

    /**
     * Menghapus tabel payment_transactions (rollback migration).
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_transactions');
    }
};
