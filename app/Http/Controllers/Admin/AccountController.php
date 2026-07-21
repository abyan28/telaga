<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

/**
 * AccountController (Admin) — CRUD akun semua role (L4.1).
 * List admin/wali/guru, search, filter status, nonaktifkan, edit.
 */
class AccountController extends Controller
{
    /**
     * Daftar akun semua role + pagination + search + filter status.
     */
    public function index(Request $request): View
    {
        $cari = $request->query('cari');
        $status = $request->query('status'); // ''=semua, 'aktif', 'nonaktif'
        $roleFilter = $request->query('role'); // ''=semua

        $query = User::with(['ortu', 'teacher']);

        if ($cari) {
            $query->where(fn ($q) => $q
                ->where('username', 'like', "%{$cari}%")
                ->orWhere('email', 'like', "%{$cari}%")
                ->orWhere('no_hp', 'like', "%{$cari}%")
            );
        }
        if ($status === 'aktif') {
            $query->where('is_aktif', true);
        } elseif ($status === 'nonaktif') {
            $query->where('is_aktif', false);
        }
        if ($roleFilter) {
            $query->where('role', $roleFilter);
        }

        $users = $query->orderBy('role')->orderBy('username')
            ->paginate(25)->withQueryString();

        return view('admin.accounts', compact('users', 'cari', 'status', 'roleFilter'));
    }

    /**
     * Membuat akun admin baru (sama seperti sebelumnya).
     */
    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'min:3', 'max:255', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8'],
        ]);

        User::create([
            'username' => $data['name'],
            'email' => $data['email'],
            'role' => 'admin',
            'password' => Hash::make($data['password']),
        ]);
        AuditLogService::record('tambah_admin', 'User', null, ['email' => $data['email']]);

        return back()->with('success', 'Akun admin berhasil dibuat.');
    }

    /**
     * L4.1: Edit username/email/no_hp/password akun (semua role).
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'username' => ['required', 'string', 'min:3', 'max:255', 'unique:users,username,'.$user->id_users.',id_users'],
            'email' => ['nullable', 'email', 'unique:users,email,'.$user->id_users.',id_users'],
            'no_hp' => ['nullable', 'string', 'max:20', 'unique:users,no_hp,'.$user->id_users.',id_users'],
            'password' => ['nullable', 'string', 'min:8'],
        ]);

        $payload = ['username' => $data['username'], 'email' => $data['email'] ?? null, 'no_hp' => $data['no_hp'] ?? null];
        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }
        $user->update($payload);

        AuditLogService::record('edit_akun', 'User#'.$user->id_users);

        return back()->with('success', 'Akun diperbarui.');
    }

    /**
     * L4.1: toggle nonaktif/aktif akun semua role.
     */
    public function toggleStatus(User $user): RedirectResponse
    {
        abort_unless($user->id_users !== Auth::id(), 403);

        $user->update(['is_aktif' => ! $user->is_aktif]);

        // Sinkron ke teachers.is_aktif bila guru (Data Guru filter pakai kolom itu).
        if ($user->role === 'guru' && $user->teacher) {
            $user->teacher->update(['is_aktif' => $user->is_aktif]);
        }

        AuditLogService::record('toggle_status_akun', 'User#'.$user->id_users, null, ['is_aktif' => $user->is_aktif]);

        return back()->with('success', 'Status akun diubah menjadi '.($user->is_aktif ? 'AKTIF' : 'NONAKTIF').'.');
    }

    /**
     * Menghapus akun admin. Tidak boleh menghapus diri sendiri.
     */
    public function destroy(User $user): RedirectResponse
    {
        abort_unless($user->role === 'admin', 404);
        if ($user->id_users === Auth::id()) {
            return back()->withErrors(['akun' => 'Tidak dapat menghapus akun Anda sendiri.']);
        }

        $user->delete();
        AuditLogService::record('hapus_admin', 'User#'.$user->id_users);

        return back()->with('success', 'Akun admin dihapus.');
    }
}
