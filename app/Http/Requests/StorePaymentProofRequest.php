<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StorePaymentProofRequest — validasi unggahan bukti pembayaran pendaftaran.
 *
 * Bukti transfer berformat JPG/PDF maksimal 2 MB (rules.md §3). Nominal mengikuti
 * setting biaya pendaftaran dinamis (diverifikasi manual oleh Admin).
 */
class StorePaymentProofRequest extends FormRequest
{
    /**
     * Otorisasi: hanya wali yang boleh mengunggah bukti bayar.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'ortu';
    }

    /**
     * Aturan validasi bukti pembayaran.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'id_registration_form' => ['required', 'exists:registration_forms,id_registration_forms'],
            'jumlah' => ['required', 'numeric', 'min:1'],
            'tanggal_bayar' => ['required', 'date'],
            'bank_asal' => ['nullable', 'string', 'max:100'],
            'bukti' => ['required', 'file', 'mimes:jpg,jpeg,pdf', 'max:2048'],
        ];
    }

    /**
     * Pesan validasi kustom (Bahasa Indonesia).
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'bukti.max' => 'Ukuran bukti transfer maksimal 2 MB.',
            'bukti.mimes' => 'Format bukti transfer harus JPG atau PDF.',
        ];
    }
}
