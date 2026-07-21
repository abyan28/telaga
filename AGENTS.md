# AGENTS.md — TELAGA AL KAUTSAR

Panduan untuk AI agent (Hermes, Claude Code, Cursor, dll) yang bekerja di proyek ini.
**Baca dokumen berikut LEBIH DULU sebelum mengerjakan apa pun:**

1. `.agents/session-log.md` — ringkasan konteks, keputusan arsitektur final, akun demo, quirk lingkungan, status & sisa pekerjaan. **Mulai dari sini.**
2. `.agents/tasklist.md` — sumber kebenaran progres (checklist per fase/task). Update tiap task selesai.
3. `.agents/prd.md` — spesifikasi produk & kebutuhan fungsional.
4. `.agents/rules.md` — aturan bisnis & konvensi WAJIB (PK/FK non-standar §2, auth §1.5, CMS §1.8, dll).
5. `.agents/workflow.md` — alur proses tiap fitur.

## Ringkasan kilat
- Laravel 13 + MySQL (`telaga`) + TailwindCSS v4 + AlpineJS + Vite. App: "TELAGA AL KAUTSAR" (SI Sekolah RA Al Kautsar).
- Konvensi DB NON-STANDAR: PK `id_<tabel>`, FK `id_<singular>`. Model wajib set `$table`+`$primaryKey`+relasi eksplisit+`getRouteKeyName()`.
- Auth: satu tabel `users` + kolom `role` + middleware `role:`.
- Model kelas sekolah bernama `SchoolClass` (bukan `Class`).

## Lingkungan (Windows + git-bash)
- Terminal = git-bash; path POSIX (`/d/Laravel/Al Kautsar`).
- Composer: `php "C:\ProgramData\ComposerSetup\bin\composer.phar" <cmd>` (wrapper rusak).
- Verify: `npm run build`. Test: `php artisan test`. Server: `php artisan serve`.

## Aturan kerja
- Update `.agents/tasklist.md` setiap menyelesaikan task.
- Komentari setiap fungsi (rules.md §5).
- Jangan ubah keputusan arsitektur final (lihat session-log.md) tanpa konfirmasi user.
- Bahasa komunikasi dengan user: Indonesia.
