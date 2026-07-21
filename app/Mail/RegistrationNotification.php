<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * RegistrationNotification — email notifikasi generik untuk trigger PRD §7.13.
 *
 * Dipakai untuk berbagai peristiwa (pendaftaran terkirim, bukti diunggah,
 * pembayaran diverifikasi/ditolak, kelulusan, tagihan SPP baru). Judul & isi
 * pesan diinjeksikan agar satu Mailable melayani banyak trigger (DRY).
 */
class RegistrationNotification extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  string  $judul  Subjek/judul notifikasi.
     * @param  string  $pesan  Isi pesan notifikasi.
     */
    public function __construct(
        public string $judul,
        public string $pesan,
    ) {}

    /**
     * Amplop email (subjek).
     */
    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->judul.' — TELAGA AL KAUTSAR');
    }

    /**
     * Isi email (view Blade sederhana).
     */
    public function content(): Content
    {
        return new Content(view: 'emails.notification');
    }
}
