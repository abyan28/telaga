{{-- Tab switcher Masuk/Daftar — dipakai di kartu login & signup (auth/login.blade.php). --}}
<div class="flex border-b border-slate-100 mb-8">
    <button type="button" @click="tab = 'login'"
            :class="tab === 'login' ? 'text-sky-600 border-sky-500 font-bold' : 'text-slate-400 border-transparent hover:text-slate-600'"
            class="flex-1 text-center py-3.5 text-sm font-semibold border-b-2 transition-all">
        Masuk Portal
    </button>
    <button type="button" @click="tab = 'signup'"
            :class="tab === 'signup' ? 'text-sky-600 border-sky-500 font-bold' : 'text-slate-400 border-transparent hover:text-slate-600'"
            class="flex-1 text-center py-3.5 text-sm font-semibold border-b-2 transition-all">
        Daftar Baru
    </button>
</div>
