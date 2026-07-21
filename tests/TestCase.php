<?php

namespace Tests;

use App\Models\OrangTua;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    /**
     * Buat ortu LENGKAP (ibu, ayah, alamat) untuk fixture test.
     * Override field via $extra. Default Ibu terisi, Ayah ada.
     */
    protected function completeOrtu(int $userId, array $extra = []): OrangTua
    {
        return OrangTua::create(array_merge([
            'id_user' => $userId,
            'ada_ayah' => true, 'ada_ibu' => true,
            'ayah_nama' => 'AYAH TEST', 'ayah_tempat_lahir' => 'JKT', 'ayah_tanggal_lahir' => '1990-01-01',
            'ayah_agama' => 'ISLAM', 'ayah_pendidikan' => 'SMA', 'ayah_pekerjaan' => 'PNS',
            'ayah_penghasilan' => '1 - 2 JUTA', 'ayah_no_hp' => '628'.substr((string) (10000000 + $userId), -8),
            'ibu_nama' => 'IBU TEST', 'ibu_tempat_lahir' => 'JKT', 'ibu_tanggal_lahir' => '1992-02-02',
            'ibu_agama' => 'ISLAM', 'ibu_pendidikan' => 'SMA', 'ibu_pekerjaan' => 'GURU',
            'ibu_penghasilan' => '1 - 2 JUTA', 'ibu_no_hp' => '628'.substr((string) (20000000 + $userId), -8),
            'alamat' => 'JL. TEST 123',
        ], $extra));
    }
}
