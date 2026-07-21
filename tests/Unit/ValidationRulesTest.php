<?php

namespace Tests\Unit;

use App\Support\ValidationRules;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

/**
 * Uji aturan validasi terpusat K1 (No. HP & Nama).
 *
 * Menjaga: normalisasi 0→62, penolakan HP non-angka / di luar 9–14 digit,
 * penolakan nama beransur angka/simbol, penerimaan nama sah (titik/apostrof/hubung).
 */
class ValidationRulesTest extends TestCase
{
    /** normalizeNoHp: 0… → 62…, buang non-angka, null tetap null. */
    public function test_normalize_no_hp(): void
    {
        $this->assertSame('6281234567890', ValidationRules::normalizeNoHp('081234567890'));
        $this->assertSame('6281234567890', ValidationRules::normalizeNoHp('+62 812-3456-7890'));
        $this->assertSame('6281234567890', ValidationRules::normalizeNoHp('6281234567890'));
        $this->assertNull(ValidationRules::normalizeNoHp(null));
        $this->assertSame('', ValidationRules::normalizeNoHp('')); // dibiarkan untuk validator
    }

    /** Aturan noHp menolak non-angka & panjang di luar 9–14 digit. */
    public function test_no_hp_rule_rejects_invalid(): void
    {
        $rule = ['no_hp' => ValidationRules::noHp()];

        $this->assertTrue(Validator::make(['no_hp' => '6281234567890'], $rule)->passes());
        $this->assertFalse(Validator::make(['no_hp' => '08abc123456'], $rule)->passes()); // huruf
        $this->assertFalse(Validator::make(['no_hp' => '62812'], $rule)->passes());        // < 9 digit
        $this->assertFalse(Validator::make(['no_hp' => '628123456789012'], $rule)->passes()); // > 14
    }

    /** Aturan nama menerima nama sah, menolak angka/simbol. */
    public function test_nama_rule(): void
    {
        $rule = ['nama' => ValidationRules::nama()];

        $this->assertTrue(Validator::make(['nama' => 'M. RIDWAN'], $rule)->passes());
        $this->assertTrue(Validator::make(['nama' => "SITI NUR'AINI"], $rule)->passes());
        $this->assertTrue(Validator::make(['nama' => 'ABDUL-AZIZ'], $rule)->passes());
        $this->assertFalse(Validator::make(['nama' => 'BUDI 123'], $rule)->passes());   // angka
        $this->assertFalse(Validator::make(['nama' => 'BUDI @!'], $rule)->passes());    // simbol
        $this->assertFalse(Validator::make(['nama' => ''], $rule)->passes());           // wajib
    }
}
