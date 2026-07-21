<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * StoreInstallmentRequest — validasi unggahan cicilan SPP / daftar ulang (wali).
 *
 * Pembayaran parsial diperbolehkan (rules.md §1.3). Bukti transfer JPG/PDF <=2MB.
 */
class StoreInstallmentRequest extends FormRequest
{
    /**
     * Otorisasi: hanya wali yang boleh membayar.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->role === 'ortu';
    }

    /**
     * Aturan validasi cicilan.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'jenis' => ['required', 'in:spp,daftar_ulang'],
            'referensi_id' => ['required', 'integer'],
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
            'jumlah.min' => 'Nominal pembayaran tidak valid.',
        ];
    }
}
