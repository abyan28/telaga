# Akun Login — TELAGA AL KAUTSAR

> Cheat sheet copas kredensial per role. Berlaku setelah:
> `php artisan migrate:fresh --seed && php artisan db:seed --class=DemoSeeder`
> Login menerima **email / username / no_hp** (Fase D). URL login: `/login`

## Admin
| Field | Nilai |
|-------|-------|
| Email | `admin@telaga.sch.id` |
| Password | `admin123` |

> ⚠️ Seeder asli generate password acak (dicetak ke console). `admin123` = di-set manual tiap sesi.
> Reset manual: `php artisan tinker --execute="\App\Models\User::where('email','admin@telaga.sch.id')->update(['password'=>bcrypt('admin123')]);"`

## Guru
| Field | Nilai |
|-------|-------|
| Email | `guru.ahmad@telaga.sch.id` |
| No. HP | `081311112222` |
| Password | `1234567890123456` |

> Password awal guru = NUPTK.

## Wali A (2 anak, tahun aktif)
| Field | Nilai |
|-------|-------|
| Email | `wali@telaga.sch.id` |
| No. HP | `081234567890` |
| Password | `password` |

## Wali B (kakak thn lalu + adik thn ini)
| Field | Nilai |
|-------|-------|
| Email | `rizal@telaga.sch.id` |
| No. HP | `081298765432` |
| Password | `password` |
