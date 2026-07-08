<x-guest-layout>
    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Buat Akun Baru</h2>
        <p class="text-sm text-gray-500 mt-1">Daftarkan diri Anda untuk mengakses sistem inventaris</p>
    </div>

    {{-- Info role default --}}
    <div class="alert-info mb-5 text-xs">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <span>Akun baru otomatis mendapat role <strong>Karyawan</strong>. Untuk perubahan role, hubungi Admin.</span>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        {{-- Nama --}}
        <div class="form-group">
            <label for="name" class="form-label">Nama Lengkap</label>
            <input id="name" name="name" type="text"
                   value="{{ old('name') }}"
                   class="form-input" required autofocus autocomplete="name"
                   placeholder="Nama Lengkap Anda"/>
            @error('name')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Email --}}
        <div class="form-group">
            <label for="email" class="form-label">Alamat Email</label>
            <input id="email" name="email" type="email"
                   value="{{ old('email') }}"
                   class="form-input" required autocomplete="username"
                   placeholder="nama@company.co.id"/>
            @error('email')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Password --}}
        <div class="form-group" x-data="{ showPass: false }">
            <label for="password" class="form-label">Password</label>
            <div class="relative">
                <input id="password" name="password" :type="showPass ? 'text' : 'password'"
                       class="form-input" style="padding-right: 2.5rem;" required autocomplete="new-password"
                       placeholder="Minimal 8 karakter"/>
                <button type="button" @click="showPass = !showPass" class="text-gray-400 hover:text-gray-600 focus:outline-none" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%);">
                    {{-- Eye Icon when hidden --}}
                    <svg x-show="!showPass" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{-- Eye Off Icon when shown --}}
                    <svg x-show="showPass" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                    </svg>
                </button>
            </div>
            @error('password')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Konfirmasi Password --}}
        <div class="form-group" x-data="{ showPassConfirm: false }">
            <label for="password_confirmation" class="form-label">Konfirmasi Password</label>
            <div class="relative">
                <input id="password_confirmation" name="password_confirmation" :type="showPassConfirm ? 'text' : 'password'"
                       class="form-input" style="padding-right: 2.5rem;" required autocomplete="new-password"
                       placeholder="Ulangi password"/>
                <button type="button" @click="showPassConfirm = !showPassConfirm" class="text-gray-400 hover:text-gray-600 focus:outline-none" style="position: absolute; right: 0.75rem; top: 50%; transform: translateY(-50%);">
                    {{-- Eye Icon when hidden --}}
                    <svg x-show="!showPassConfirm" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    {{-- Eye Off Icon when shown --}}
                    <svg x-show="showPassConfirm" class="w-5 h-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" style="display: none;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 001.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0112 4.5c4.756 0 8.773 3.162 10.065 7.498a10.523 10.523 0 01-4.293 5.774M6.228 6.228L3 3m3.228 3.228l3.65 3.65m7.894 7.894L21 21m-3.228-3.228l-3.65-3.65m0 0a3 3 0 10-4.243-4.243m4.242 4.242L9.88 9.88"/>
                    </svg>
                </button>
            </div>
            @error('password_confirmation')
                <p class="form-error">{{ $message }}</p>
            @enderror
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-primary w-full justify-center py-2.5 text-base mt-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/>
            </svg>
            Daftar Sekarang
        </button>

        <p class="text-center text-sm text-gray-500">
            Sudah punya akun?
            <a href="{{ route('login') }}" class="text-telkom-600 hover:text-telkom-700 font-medium">
                Masuk di sini
            </a>
        </p>
    </form>
</x-guest-layout>
