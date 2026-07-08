<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name', 'Digi Inventory') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased bg-slate-50">

<div class="min-h-screen flex items-center justify-center px-4 py-10">

    {{-- Decorative background --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute -top-40 -right-40 w-96 h-96 rounded-full bg-telkom-600 opacity-5 blur-3xl"></div>
        <div class="absolute -bottom-40 -left-40 w-96 h-96 rounded-full bg-telkom-700 opacity-5 blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full max-w-md">

        {{-- Logo & Brand --}}
        <div class="text-center mb-8">
            <div class="inline-flex items-center justify-center w-16 h-16 bg-telkom-600 rounded-2xl shadow-md mb-4">
                <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Digi Inventory</h1>
            <p class="text-gray-500 text-sm mt-1">Sistem Manajemen Inventaris Perusahaan</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-xl overflow-hidden border border-gray-100">
            <div class="h-1 bg-gradient-to-r from-telkom-600 to-telkom-400"></div>
            <div class="p-8">
                {{ $slot }}
            </div>
        </div>

        {{-- Footer --}}
        <p class="text-center text-gray-400 text-xs mt-6">
            &copy; {{ date('Y') }} PT Telekomunikasi Selular. Hak cipta dilindungi.
        </p>
    </div>
</div>

</body>
</html>
