<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' — ' : '' }}{{ config('app.name', 'Digi Inventory') }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-surface font-sans antialiased">

{{-- ============================================================
     APP SHELL
     ============================================================ --}}
<div x-data="{ mobileSidebarOpen: false }" class="flex h-screen overflow-hidden">

    {{-- ===== SIDEBAR ===== --}}
    <aside class="app-sidebar transform -translate-x-full lg:translate-x-0 transition-transform duration-300 z-50"
           :class="mobileSidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
           id="sidebar">

        {{-- Logo --}}
        <div class="sidebar-logo">
            <div class="w-8 h-8 bg-telkom-600 rounded-lg flex items-center justify-center shrink-0">
                <svg class="w-4 h-4 text-white" fill="currentColor" viewBox="0 0 24 24">
                    <path d="M12 2L2 7l10 5 10-5-10-5zM2 17l10 5 10-5M2 12l10 5 10-5"/>
                </svg>
            </div>
            <div>
                <p class="text-white font-semibold text-sm leading-tight">Digi</p>
                <p class="text-gray-500 text-[10px] leading-tight">Inventory System</p>
            </div>
        </div>

        {{-- Nav --}}
        <nav class="flex-1 overflow-y-auto py-3 space-y-0.5">

            {{-- Dashboard --}}
            <p class="sidebar-section-label">Menu Utama</p>

            <a href="{{ route('dashboard') }}"
               class="sidebar-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                </svg>
                Dashboard
            </a>

            {{-- Admin, Staff, & Manager: Master Data --}}
            @if(auth()->user()->hasRole('admin', 'staff', 'manager'))
                <p class="sidebar-section-label">Master Data</p>
                <a href="{{ route('categories.index') }}"
                   class="sidebar-link {{ request()->routeIs('categories.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z"/>
                    </svg>
                    Kategori
                </a>
                <a href="{{ route('products.index') }}"
                   class="sidebar-link {{ request()->routeIs('products.*') || request()->routeIs('units.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                    Barang & Unit
                </a>
            @endif

            {{-- Karyawan: Katalog --}}
            @if(auth()->user()->hasRole('karyawan'))
                <p class="sidebar-section-label">Katalog</p>
                <a href="{{ route('products.index') }}"
                   class="sidebar-link {{ request()->routeIs('products.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                    </svg>
                    Katalog Barang
                </a>
            @endif

            {{-- Operasional --}}
            @if(auth()->user()->hasRole('admin', 'staff', 'manager'))
                <p class="sidebar-section-label">Operasional</p>
                @if(auth()->user()->hasRole('admin', 'staff'))
                    <a href="{{ route('borrowings.index') }}"
                       class="sidebar-link {{ request()->routeIs('borrowings.index') || request()->routeIs('borrowings.show') ? 'active' : '' }}">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                        </svg>
                        Kelola Peminjaman
                    </a>
                    <a href="{{ route('returns.create') }}"
                       class="sidebar-link {{ request()->routeIs('returns.create') ? 'active' : '' }}">
                        <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/>
                        </svg>
                        Proses Pengembalian
                    </a>
                @endif
                <a href="{{ route('incidents.index') }}"
                   class="sidebar-link {{ request()->routeIs('incidents.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Insiden
                </a>
            @endif

            @if(auth()->user()->hasRole('karyawan'))
                <p class="sidebar-section-label">Peminjaman</p>
                <a href="{{ route('borrowings.my') }}"
                   class="sidebar-link {{ request()->routeIs('borrowings.my') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    Peminjaman Saya
                </a>
                <a href="{{ route('borrowings.create') }}"
                   class="sidebar-link {{ request()->routeIs('borrowings.create') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                    </svg>
                    Ajukan Peminjaman
                </a>
                <a href="{{ route('incidents.create') }}"
                   class="sidebar-link {{ request()->routeIs('incidents.create') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Lapor Insiden
                </a>
            @endif

            {{-- Laporan --}}
            @if(auth()->user()->hasRole('admin', 'staff', 'manager'))
                <p class="sidebar-section-label">Laporan</p>
                <a href="{{ route('reports.index') }}"
                   class="sidebar-link {{ request()->routeIs('reports.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    Laporan Periodik
                </a>
            @endif

            {{-- Admin Panel --}}
            @if(auth()->user()->hasRole('admin'))
                <p class="sidebar-section-label">Administrasi</p>
                <a href="{{ route('admin.users.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.users.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    Kelola User
                </a>
                <a href="{{ route('admin.holidays.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.holidays.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    Hari Libur
                </a>
                <a href="{{ route('admin.tokens.index') }}"
                   class="sidebar-link {{ request()->routeIs('admin.tokens.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                    Token API
                </a>
            @endif

            {{-- Label Aset --}}
            @if(auth()->user()->hasRole('admin', 'staff'))
                <a href="{{ route('labels.pilih') }}"
                   class="sidebar-link {{ request()->routeIs('labels.*') ? 'active' : '' }}">
                    <svg fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                    </svg>
                    Cetak Label Aset
                </a>
            @endif
        </nav>

        {{-- User Footer --}}
        <div class="border-t border-sidebar-border px-4 py-3">
            <div class="flex items-center gap-3">
                <div class="avatar w-8 h-8 text-xs bg-telkom-600 shrink-0">
                    {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-white text-sm font-medium truncate">{{ auth()->user()->name }}</p>
                    <p class="text-gray-500 text-[10px] truncate capitalize">{{ auth()->user()->role?->name ?? 'karyawan' }}</p>
                </div>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-gray-500 hover:text-white transition-colors" title="Logout">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </aside>

    {{-- Backdrop for mobile --}}
    <div x-show="mobileSidebarOpen" 
         @click="mobileSidebarOpen = false" 
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="fixed inset-0 bg-black/40 z-40 lg:hidden">
    </div>

    {{-- ===== MAIN AREA ===== --}}
    <div class="app-content flex-1 overflow-y-auto">

        {{-- Topbar --}}
        <header class="app-topbar shrink-0">
            {{-- Breadcrumb / Page title --}}
            <div class="breadcrumb">
                @isset($breadcrumbs)
                    @foreach($breadcrumbs as $crumb)
                        @if(!$loop->last)
                            <a href="{{ $crumb['url'] ?? '#' }}" class="hover:text-gray-600 transition-colors">{{ $crumb['label'] }}</a>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        @else
                            <span class="breadcrumb-current">{{ $crumb['label'] }}</span>
                        @endif
                    @endforeach
                @else
                    <span class="breadcrumb-current">{{ $pageTitle ?? 'Dashboard' }}</span>
                @endisset
            </div>

            {{-- Right side: notif + profile --}}
            <div class="flex items-center gap-3">
                {{-- Mobile Menu Toggle (Three dots) --}}
                <button @click="mobileSidebarOpen = !mobileSidebarOpen" class="btn-icon btn-ghost lg:hidden shrink-0" type="button" title="Menu">
                    <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 12.75a.75.75 0 110-1.5.75.75 0 010 1.5zM12 18.75a.75.75 0 110-1.5.75.75 0 010 1.5z" />
                    </svg>
                </button>

                {{-- Notifikasi bell --}}
                @php
                    $unreadCount = auth()->user()->unreadNotifications->count();
                    $latestNotifications = auth()->user()->notifications()->latest()->take(5)->get();
                @endphp
                <div x-data="{ openNotif: false }" class="relative">
                    <button @click="openNotif = !openNotif" class="btn-icon btn-ghost relative">
                        <svg class="w-5 h-5 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                        </svg>
                        @if($unreadCount > 0)
                            <span class="absolute -top-1 -right-1 w-4 h-4 bg-telkom-600 text-white rounded-full flex items-center justify-center text-[9px] font-bold">{{ $unreadCount }}</span>
                        @endif
                    </button>
                    {{-- Dropdown Notif --}}
                    <div x-show="openNotif" @click.away="openNotif = false" x-transition
                         class="absolute right-0 mt-2 w-80 bg-white rounded-xl shadow-lg border border-gray-100 py-2 z-50">
                        <div class="px-4 py-2 border-b border-gray-100 flex items-center justify-between">
                            <span class="font-bold text-xs text-gray-900 uppercase">Notifikasi</span>
                            @if($unreadCount > 0)
                                <form method="POST" action="{{ route('notifications.readAll') }}">
                                    @csrf
                                    <button type="submit" class="text-[10px] text-telkom-600 font-semibold cursor-pointer">Tandai dibaca</button>
                                </form>
                            @endif
                        </div>
                        <div class="max-h-64 overflow-y-auto divide-y divide-gray-50">
                            @forelse($latestNotifications as $notif)
                                <a href="{{ route('notifications.read', $notif->id) }}" class="px-4 py-3 hover:bg-slate-50 transition cursor-pointer flex gap-3 {{ $notif->unread() ? 'bg-telkom-50/10' : '' }}">
                                    <div class="w-2 h-2 rounded-full shrink-0 mt-1.5 {{ $notif->unread() ? 'bg-telkom-600' : 'bg-gray-300' }}"></div>
                                    <div class="text-left min-w-0 flex-1">
                                        <p class="text-xs text-gray-800 leading-normal truncate">{{ $notif->data['message'] ?? '' }}</p>
                                        <p class="text-[9px] text-gray-400 mt-1">{{ $notif->created_at->diffForHumans() }}</p>
                                    </div>
                                </a>
                            @empty
                                <div class="px-4 py-6 text-center text-xs text-gray-400">Tidak ada notifikasi baru.</div>
                            @endforelse
                        </div>
                        <div class="px-4 py-2 border-t border-gray-100 text-center">
                            <a href="{{ route('notifications.index') }}" class="text-[10px] text-gray-400 font-medium hover:text-gray-600 transition block">Lihat semua notifikasi</a>
                        </div>
                    </div>
                </div>

                {{-- Profile dropdown --}}
                <div x-data="{ open: false }" class="relative">
                    <button @click="open = !open" class="flex items-center gap-2 hover:opacity-80 transition">
                        <div class="avatar w-8 h-8 text-xs bg-telkom-600">
                            {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                        </div>
                        <svg class="w-3.5 h-3.5 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div x-show="open" @click.away="open = false" x-transition
                         class="absolute right-0 mt-2 w-48 bg-white rounded-xl shadow-lg border border-gray-100 py-1 z-50">
                        <a href="{{ route('profile.edit') }}" class="flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-50">
                            <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                            Profil Saya
                        </a>
                        <div class="border-t border-gray-100 my-1"></div>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                                </svg>
                                Keluar
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        {{-- Validation Errors --}}
        @if($errors->any())
            <div class="px-6 pt-5">
                <div class="alert-error mb-0">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex flex-col">
                        <span class="font-bold">Terjadi kesalahan validasi:</span>
                        <ul class="list-disc list-inside text-xs mt-1">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        @endif

        {{-- Flash Messages --}}
        @if(session('success') || session('error') || session('warning'))
            <div class="px-6 pt-5">
                @if(session('success'))
                    <div class="alert-success mb-0">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ session('success') }}</span>
                    </div>
                @endif
                @if(session('error'))
                    <div class="alert-error mb-0">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span>{{ session('error') }}</span>
                    </div>
                @endif
                @if(session('warning'))
                    <div class="alert-warning mb-0">
                        <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>{{ session('warning') }}</span>
                    </div>
                @endif
            </div>
        @endif

        {{-- Page Content --}}
        <main class="app-page">
            @hasSection('content')
                @yield('content')
            @else
                {{ $slot ?? '' }}
            @endif
        </main>
    </div>
</div>

    @stack('scripts')
</body>
</html>
