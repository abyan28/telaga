<?php

namespace Tests\Unit;

use App\Http\Middleware\UppercaseInput;
use Illuminate\Http\Request;
use PHPUnit\Framework\TestCase;

class UppercaseInputTest extends TestCase
{
    /** Field teks di-uppercase; kredensial & *_id dibiarkan apa adanya. */
    public function test_uppercases_text_but_skips_sensitive_fields(): void
    {
        $req = Request::create('/x', 'POST', [
            'nama' => 'budi santoso',
            'alamat' => 'jl. melati 4',
            'email' => 'Budi@Mail.com',
            'password' => 'RahasiaKu',
            'nik' => '1234',
            'provinsi_id' => '32',
            'provinsi_nama' => 'jawa barat',
            'id_class' => 'none',
        ]);

        (new UppercaseInput)->handle($req, fn ($r) => new \Illuminate\Http\Response);

        $this->assertSame('BUDI SANTOSO', $req->input('nama'));
        $this->assertSame('JL. MELATI 4', $req->input('alamat'));
        $this->assertSame('JAWA BARAT', $req->input('provinsi_nama'));
        $this->assertSame('Budi@Mail.com', $req->input('email'));
        $this->assertSame('RahasiaKu', $req->input('password'));
        $this->assertSame('1234', $req->input('nik'));
        $this->assertSame('32', $req->input('provinsi_id'));
        $this->assertSame('none', $req->input('id_class')); // FK filter tak di-uppercase
    }
}
