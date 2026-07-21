<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\WilayahController;
use App\Http\Controllers\Admin\AccountController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\MasterDataController;
use App\Http\Controllers\Admin\RegistrationController as AdminRegistrationController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\SettingController;
use App\Http\Controllers\Admin\SiteContentController;
use App\Http\Controllers\Guru\GuruController;
use App\Http\Controllers\Wali\DashboardController as WaliDashboardController;
use App\Http\Controllers\Wali\PaymentController;
use App\Http\Controllers\Wali\RegistrationController as WaliRegistrationController;
use App\Http\Controllers\Wali\StatusController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes — TELAGA AL KAUTSAR
|--------------------------------------------------------------------------
| Auth: satu tabel users + kolom role, RBAC via middleware `role:` (rules.md
| §1.5). Route portal dilindungi middleware `auth` + `role:<role>`.
*/

// --- Halaman Publik (konten dari CMS — rules.md §1.8) ---
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/profile', [PublicController::class, 'profile'])->name('profile');
Route::get('/info', [PublicController::class, 'info'])->name('info');

// --- Autentikasi (tamu) ---
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.submit');
Route::post('/register', [RegisterController::class, 'register'])->name('register.submit');
Route::get('/register/check-username', [RegisterController::class, 'checkUsername'])->name('register.check-username');

// --- Logout (harus login) ---
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth')->name('logout');

// --- Dropdown alamat wilayah berjenjang (T2.1) — data referensi, dipakai form wali & guru. ---
Route::middleware('auth')->get('/wilayah/{level}', [WilayahController::class, 'index'])->name('wilayah');

// --- Portal Wali Murid (role: wali) ---
Route::middleware(['auth', 'role:ortu'])->prefix('portal/ortu')->name('ortu.')->group(function () {
    Route::get('/', [WaliDashboardController::class, 'index'])->name('dashboard');

    // Pendaftaran murid baru (form + simpan)
    Route::get('/register', [WaliRegistrationController::class, 'create'])->name('register');
    Route::post('/register', [WaliRegistrationController::class, 'store'])->name('register.store');

    // K2.5: edit pendaftaran (selama boleh — canBeEditedByWali)
    Route::get('/register/{form}/edit', [WaliRegistrationController::class, 'edit'])->name('register.edit');
    Route::post('/register/{form}', [WaliRegistrationController::class, 'update'])->name('register.update');

    // Status pendaftaran + unggah bukti bayar pendaftaran
    Route::get('/status', [StatusController::class, 'index'])->name('status');
    Route::post('/status/proof', [StatusController::class, 'uploadProof'])->name('status.proof');

    Route::get('/payments', [PaymentController::class, 'index'])->name('payments');
    Route::post('/payments', [PaymentController::class, 'store'])->name('payments.store');

    // K10.3: daftar anak (read-only) + profil & catatan guru per anak
    Route::get('/children', [WaliDashboardController::class, 'children'])->name('children');
    Route::get('/children/{student}', [WaliDashboardController::class, 'childProfile'])->name('children.show');

    // Pengaturan akun mandiri: update email & no. HP (T2.3)
    Route::get('/account', [WaliDashboardController::class, 'accountForm'])->name('account');
    Route::post('/account', [WaliDashboardController::class, 'updateAccount'])->name('account.update');

    // Profil wali (K2.1: wajib lengkap sebelum daftar/bayar — gate RequireOrtuProfile)
    Route::get('/profile', [WaliDashboardController::class, 'profileForm'])->name('profile');
    Route::post('/profile', [WaliDashboardController::class, 'updateProfile'])->name('profile.update');

    // Ganti password (T9.1: akun dibuat admin, password awal = no_hp)
    Route::get('/password', [WaliDashboardController::class, 'passwordForm'])->name('password');
    Route::post('/password', [WaliDashboardController::class, 'updatePassword'])->name('password.update');
});

// --- Portal Admin / CMS (role: admin) ---
Route::middleware(['auth', 'role:admin'])->prefix('portal/admin')->name('admin.')->group(function () {
    Route::get('/', [AdminDashboardController::class, 'index'])->name('dashboard');

    // Pendaftaran: daftar, detail, verifikasi dokumen/pembayaran, keputusan seleksi
    Route::get('/registrations', [AdminRegistrationController::class, 'index'])->name('registrations');
    Route::post('/ppdb/toggle', [AdminRegistrationController::class, 'togglePpdb'])->name('ppdb.toggle');
    Route::get('/daftar-ulang', [AdminRegistrationController::class, 'daftarUlang'])->name('daftar-ulang');
    Route::get('/calon-murid', [AdminRegistrationController::class, 'calonMurid'])->name('calon-murid');
    Route::post('/calon-murid/{student}/cancel', [AdminRegistrationController::class, 'cancelCalon'])->name('calon-murid.cancel');
    Route::post('/calon-murid/generate-nis', [AdminRegistrationController::class, 'generateNis'])->name('calon-murid.generate-nis');
    Route::get('/spp', [AdminRegistrationController::class, 'spp'])->name('spp');
    Route::get('/registrations/{form}', [AdminRegistrationController::class, 'show'])->name('registrations.show');
    Route::post('/documents/{document}/verify', [AdminRegistrationController::class, 'verifyDocument'])->name('documents.verify');
    Route::post('/payments/{payment}/verify', [AdminRegistrationController::class, 'verifyPayment'])->name('payments.verify');
    Route::post('/registrations/{form}/decide', [AdminRegistrationController::class, 'decide'])->name('registrations.decide');
    Route::post('/registrations/{form}/reopen-decision', [AdminRegistrationController::class, 'reopenDecision'])->name('registrations.reopen-decision');
    Route::post('/registrations/{form}/allow-edit', [AdminRegistrationController::class, 'allowEdit'])->name('registrations.allow-edit');

    // Manajemen data — tab terpisah Siswa / Guru / Kelas (T5.1)
    Route::get('/users', [MasterDataController::class, 'index'])->name('users'); // alias -> students
    Route::get('/data/students', [MasterDataController::class, 'students'])->name('data.students');
    Route::get('/data/students/import', [MasterDataController::class, 'importForm'])->name('students.import');
    Route::post('/data/students/import', [MasterDataController::class, 'importStudents'])->name('students.import.run');
    Route::get('/data/students/import/template', [MasterDataController::class, 'downloadTemplate'])->name('students.import.template');
    Route::get('/data/students/{student}', [MasterDataController::class, 'showStudent'])->name('students.show');
    Route::post('/data/students', [MasterDataController::class, 'storeStudent'])->name('students.store');
    Route::put('/data/students/{student}', [MasterDataController::class, 'updateStudent'])->name('students.update');
    Route::post('/data/students/{student}/ortu-account', [MasterDataController::class, 'createOrtuAccount'])->name('students.ortu-account');
    Route::get('/data/teachers', [MasterDataController::class, 'teachers'])->name('data.teachers');
    Route::get('/data/teachers/{teacher}', [MasterDataController::class, 'showTeacher'])->name('teachers.show');
    Route::get('/data/classes', [MasterDataController::class, 'classes'])->name('data.classes');
    Route::post('/teachers', [MasterDataController::class, 'storeTeacher'])->name('teachers.store');
    Route::put('/teachers/{teacher}', [MasterDataController::class, 'updateTeacher'])->name('teachers.update');
    Route::post('/classes', [MasterDataController::class, 'storeClass'])->name('classes.store');
    Route::put('/classes/{class}', [MasterDataController::class, 'updateClass'])->name('classes.update');
    Route::delete('/classes/{class}', [MasterDataController::class, 'destroyClass'])->name('classes.destroy');
    Route::post('/teachers/{teacher}/classes', [MasterDataController::class, 'assignClasses'])->name('teachers.classes');
    Route::post('/teachers/{teacher}/web', [MasterDataController::class, 'updateTeacherWeb'])->name('teachers.web');
    Route::post('/teachers/{teacher}/toggle-status', [MasterDataController::class, 'toggleTeacherStatus'])->name('teachers.toggle-status');

    // Pengaturan nominal biaya dinamis + generate SPP manual (tab Set Pembayaran)
    Route::get('/settings', [SettingController::class, 'index'])->name('settings');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/generate-spp', [SettingController::class, 'generateSpp'])->name('settings.generate-spp');
    // L2.4: navigasi tahun ajaran (scope=aktif|ppdb, arah=prev|next) — independen.
    Route::post('/settings/academic-year', [SettingController::class, 'setAcademicYear'])->name('settings.academic-year');

    // L2.1: Pengaturan Sistem (NSM, refund, TA)
    Route::get('/system', [SettingController::class, 'system'])->name('system');
    Route::post('/system', [SettingController::class, 'updateSystem'])->name('system.update');

    // Data Akun — CRUD akun semua role (T5.6i + L4.1)
    Route::get('/accounts', [AccountController::class, 'index'])->name('accounts');
    Route::post('/accounts', [AccountController::class, 'store'])->name('accounts.store');
    Route::put('/accounts/{user}', [AccountController::class, 'update'])->name('accounts.update');
    Route::post('/accounts/{user}/toggle-status', [AccountController::class, 'toggleStatus'])->name('accounts.toggle-status');
    Route::delete('/accounts/{user}', [AccountController::class, 'destroy'])->name('accounts.destroy');

    // Laporan transaksi + export CSV/PDF
    Route::get('/reports', [ReportController::class, 'index'])->name('reports');
    Route::get('/reports/export/csv', [ReportController::class, 'exportCsv'])->name('reports.csv');
    Route::get('/reports/export/pdf', [ReportController::class, 'exportPdf'])->name('reports.pdf');

    // CMS konten website publik (rules.md §1.8)
    Route::get('/content', [SiteContentController::class, 'index'])->name('content');
    Route::post('/content', [SiteContentController::class, 'updateContents'])->name('content.update');
    Route::post('/content/media', [SiteContentController::class, 'uploadMedia'])->name('content.media');
    Route::post('/content/features', [SiteContentController::class, 'storeFeature'])->name('content.features.store');
    Route::put('/content/features/{feature}', [SiteContentController::class, 'updateFeature'])->name('content.features.update');
    Route::delete('/content/features/{feature}', [SiteContentController::class, 'destroyFeature'])->name('content.features.destroy');
});

// --- Portal Guru (role: guru) ---
Route::middleware(['auth', 'role:guru'])->prefix('portal/guru')->name('guru.')->group(function () {
    Route::get('/', [GuruController::class, 'index'])->name('dashboard');
    Route::get('/password', [GuruController::class, 'passwordForm'])->name('password');
    Route::post('/password', [GuruController::class, 'updatePassword'])->name('password.update');
    // Pengaturan akun mandiri guru: update email & no. HP (T2.3)
    Route::post('/account', [GuruController::class, 'updateAccount'])->name('account.update');
    // Update profil guru sendiri: nama, TTL, riwayat pendidikan (T6.4)
    Route::post('/profile', [GuruController::class, 'updateProfile'])->name('profile.update');
    Route::get('/students/{student}', [GuruController::class, 'showStudent'])->name('students.show');
    Route::put('/students/{student}', [GuruController::class, 'updateStudent'])->name('students.update');
    Route::post('/students', [GuruController::class, 'storeStudent'])->name('students.store');
});
