<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

/**
 * Model User — akun login untuk semua role (wali, admin, guru).
 *
 * Sesuai rules.md §1.5, sistem memakai SATU tabel users dengan kolom `role`
 * sebagai pembeda hak akses. Data profil spesifik role disimpan terpisah:
 * wali -> parents, guru -> teachers (relasi 1-1, rules.md §1.6).
 * Primary key non-standar id_users didefinisikan eksplisit (rules.md §2.1).
 */
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    // Nama tabel & primary key eksplisit (rules.md §2.3)
    protected $table = 'users';
    protected $primaryKey = 'id_users';

    // Kolom yang boleh diisi massal
    protected $fillable = ['username', 'email', 'no_hp', 'role', 'password', 'must_change_password', 'is_aktif'];

    // Kolom yang disembunyikan saat serialisasi
    protected $hidden = ['password', 'remember_token'];

    /**
     * Route-model binding memakai primary key non-standar (rules.md §2).
     */
    public function getRouteKeyName(): string
    {
        return 'id_users';
    }

    /**
     * Cast tipe atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'must_change_password' => 'boolean',
            'is_aktif' => 'boolean',
        ];
    }

    /**
     * Relasi 1-1: profil wali murid (hanya untuk user role 'ortu').
     * FK id_user berada di tabel parents, menunjuk PK id_users.
     */
    public function ortu(): HasOne
    {
        return $this->hasOne(OrangTua::class, 'id_user', 'id_users');
    }

    /**
     * Relasi 1-1: profil guru (hanya untuk user role 'guru').
     * FK id_user berada di tabel teachers, menunjuk PK id_users.
     */
    public function teacher(): HasOne
    {
        return $this->hasOne(Teacher::class, 'id_user', 'id_users');
    }

    /**
     * Helper: cek apakah user memiliki role tertentu.
     */
    public function hasRole(string $role): bool
    {
        return $this->role === $role;
    }

    /**
     * Helper: cek apakah user adalah admin.
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * Helper: cek apakah user adalah guru.
     */
    public function isGuru(): bool
    {
        return $this->role === 'guru';
    }

    /**
     * Helper: cek apakah user adalah wali murid.
     */
    public function isOrtu(): bool
    {
        return $this->role === 'ortu';
    }

    /**
     * Nama tampilan (T3.2): nama asli ada di profil (parents/teachers);
     * users.username hanya fallback (diisi email/HP saat auto-akun guru).
     */
    public function displayName(): string
    {
        return $this->ortu?->namaWali() ?? $this->teacher?->nama ?? $this->username;
    }

    /**
     * Akun ortu auto-create (T9.1) belum disetup: email kosong atau username
     * masih = no_hp (belum diubah wali). Gate wajib isi pengaturan akun dulu.
     * ponytail: username===no_hp sbg proxy "belum diubah"; add flag kolom kalau proxy kelewat.
     */
    public function needsAccountSetup(): bool
    {
        return $this->role === 'ortu'
            && ($this->email === null || $this->username === $this->no_hp);
    }
}
