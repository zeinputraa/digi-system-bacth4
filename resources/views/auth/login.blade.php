<x-guest-layout>
    {{-- Session Status --}}
    @if(session('status'))
        <div class="alert-success mb-5 text-sm">{{ session('status') }}</div>
    @endif

    <div class="mb-6">
        <h2 class="text-xl font-bold text-gray-900">Masuk ke Sistem</h2>
        <p class="text-sm text-gray-500 mt-1">Gunakan akun yang sudah terdaftar</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="space-y-5">
        @csrf

        {{-- Email --}}
        <div class="form-group">
            <label for="email" class="form-label">Alamat Email</label>
            <input id="email" name="email" type="email"
                   value="{{ old('email') }}"
                   class="form-input" required autofocus autocomplete="username"
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
                       class="form-input" style="padding-right: 2.5rem;" required autocomplete="current-password"
                       placeholder="••••••••"/>
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

        {{-- Remember & Forgot --}}
        <div class="flex items-center justify-between">
            <label for="remember_me" class="flex items-center gap-2 cursor-pointer">
                <input id="remember_me" type="checkbox" name="remember"
                       class="rounded border-gray-300 text-telkom-600 focus:ring-telkom-500 w-4 h-4"/>
                <span class="text-sm text-gray-600">Ingat saya</span>
            </label>
            @if(Route::has('password.request'))
                <a href="{{ route('password.request') }}"
                   class="text-sm text-telkom-600 hover:text-telkom-700 font-medium">
                    Lupa password?
                </a>
            @endif
        </div>

        {{-- Submit --}}
        <button type="submit" class="btn-primary w-full justify-center py-2.5 text-base">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/>
            </svg>
            Masuk
        </button>

        {{-- Register link --}}
        <p class="text-center text-sm text-gray-500">
            Belum punya akun?
            <a href="{{ route('register') }}" class="text-telkom-600 hover:text-telkom-700 font-medium">
                Daftar sekarang
            </a>
        </p>
    </form>
</x-guest-layout>
