@extends('layouts.app')

@php $pageTitle = 'Kelola Pengguna'; @endphp

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb --}}
    <nav class="breadcrumb">
        <a href="{{ route('admin.users.index') }}" class="hover:text-gray-600">Pengguna</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="breadcrumb-current">{{ $user->name }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Edit Role Form --}}
        <div class="space-y-4">
            <div class="card">
                <div class="card-header">
                    <p class="card-title">Profil & Peran Pengguna</p>
                </div>
                <div class="card-body space-y-4">
                    <div class="flex items-center gap-3">
                        <div class="avatar w-12 h-12 text-base bg-telkom-600">
                            {{ substr($user->name, 0, 1) }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="font-bold text-gray-900 text-base truncate">{{ $user->name }}</p>
                            <p class="text-xs text-gray-400 font-mono truncate">{{ $user->email }}</p>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <div class="space-y-2 text-sm text-gray-600">
                        <div class="flex justify-between">
                            <span>Status Akun:</span>
                            @if($user->email_verified_at)
                                <span class="text-emerald-600 font-semibold">✓ Terverifikasi</span>
                            @else
                                <span class="text-amber-500 font-semibold">⚠ Menunggu Verifikasi</span>
                            @endif
                        </div>
                        <div class="flex justify-between">
                            <span>Bergabung sejak:</span>
                            <span>{{ $user->created_at->format('d M Y') }}</span>
                        </div>
                    </div>

                    <div class="divider"></div>

                    <form method="POST" action="{{ route('admin.users.update', $user->id) }}" class="space-y-4">
                        @csrf
                        <div class="form-group">
                            <label for="role-select" class="form-label">Role Pengguna <span class="text-red-500">*</span></label>
                            <select id="role-select" name="role_id" class="form-select" required>
                                @foreach($roles as $role)
                                    <option value="{{ $role->id }}" {{ $user->role_id == $role->id ? 'selected' : '' }}>
                                        {{ ucfirst($role->name) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <button type="submit" class="btn-primary w-full justify-center">
                            Simpan Perubahan Role
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: User Activity Stats & History --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Stat Cards --}}
            <div class="grid grid-cols-4 gap-4">
                <div class="card p-4 text-center">
                    <p class="text-xs text-gray-400 font-medium">Total Pinjam</p>
                    <p class="text-xl font-bold text-gray-800 mt-1">{{ $stats['total_pinjam'] }}</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-xs text-gray-400 font-medium">Berjalan</p>
                    <p class="text-xl font-bold text-blue-600 mt-1">{{ $stats['berjalan'] }}</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-xs text-gray-400 font-medium">Terlambat</p>
                    <p class="text-xl font-bold text-red-600 mt-1">{{ $stats['terlambat'] }}</p>
                </div>
                <div class="card p-4 text-center">
                    <p class="text-xs text-gray-400 font-medium">Insiden</p>
                    <p class="text-xl font-bold text-orange-600 mt-1">{{ $stats['insiden'] }}</p>
                </div>
            </div>

            {{-- History list --}}
            <div class="card">
                <div class="card-header"><p class="card-title">Riwayat Aktivitas Peminjaman</p></div>
                <div class="table-wrapper border-0 shadow-none">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode</th>
                                <th>Barang</th>
                                <th>Pinjam / Kembali</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50 text-xs">
                            @forelse($borrowings as $b)
                                @php
                                    $statusStyle = match($b->status->value ?? $b->status) {
                                        'diajukan'            => 'badge-diajukan',
                                        'disetujui'           => 'badge-disetujui',
                                        'berjalan'            => 'badge-berjalan',
                                        'selesai'             => 'badge-selesai',
                                        'ditolak'             => 'badge-ditolak',
                                        'dibatalkan_otomatis' => 'badge-ditolak',
                                        default               => 'badge-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="font-mono font-semibold">{{ $b->kode_peminjaman }}</td>
                                    <td>{{ $b->details->map(fn($d) => optional($d->product)->nama_barang)->filter()->unique()->implode(', ') }}</td>
                                    <td class="text-gray-500">
                                        {{ $b->tanggal_pinjam_rencana?->format('d M Y') ?? '—' }} - {{ $b->tanggal_kembali_rencana?->format('d M Y') ?? '—' }}
                                    </td>
                                    <td><span class="badge {{ $statusStyle }}">{{ ucfirst(str_replace('_', ' ', $b->status->value ?? $b->status)) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-gray-400 py-6 text-xs">Belum ada riwayat peminjaman.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
