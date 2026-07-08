@extends('layouts.app')

@php $pageTitle = 'Kelola User'; @endphp

@section('content')
<div class="space-y-5">
    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Kelola Pengguna</h1>
            <p class="page-subtitle">Manajemen hak akses, role pengguna, dan verifikasi akun Digi Inventory.</p>
        </div>
    </div>

    {{-- Filter & Search --}}
    <form method="GET" action="{{ route('admin.users.index') }}" class="flex flex-col sm:flex-row gap-3">
        <div class="relative flex-1 max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau email..." class="form-input pl-9"/>
        </div>
        <select name="role" onchange="this.form.submit()" class="form-select w-full sm:w-48">
            <option value="">Semua Role</option>
            @foreach($roles as $role)
                <option value="{{ $role->name }}" {{ request('role') == $role->name ? 'selected' : '' }}>{{ ucfirst($role->name) }}</option>
            @endforeach
        </select>
        <button type="submit" class="btn btn-secondary">Cari</button>
    </form>

    {{-- Table --}}
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>Nama</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Verifikasi Email</th>
                    <th>Tanggal Terdaftar</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-50">
                @forelse($users as $u)
                    @php
                        $roleName = $u->role?->name ?? 'karyawan';
                        $badgeClass = match($roleName) {
                            'admin'   => 'badge-admin',
                            'staff'   => 'badge-staff',
                            'manager' => 'badge-manager',
                            default   => 'badge-karyawan',
                        };
                    @endphp
                    <tr>
                        <td class="font-medium text-gray-900">{{ $u->name }}</td>
                        <td class="text-gray-500 font-mono text-xs">{{ $u->email }}</td>
                        <td>
                            <span class="badge {{ $badgeClass }}">{{ ucfirst($roleName) }}</span>
                        </td>
                        <td>
                            @if($u->email_verified_at)
                                <span class="text-emerald-600 text-xs font-semibold flex items-center gap-1">
                                    ✓ Terverifikasi
                                </span>
                            @else
                                <span class="text-amber-500 text-xs font-semibold flex items-center gap-1">
                                    ⚠ Menunggu
                                </span>
                            @endif
                        </td>
                        <td class="text-gray-500 text-xs">{{ $u->created_at->format('d M Y') }}</td>
                        <td>
                            <a href="{{ route('admin.users.show', $u->id) }}" class="btn-sm btn-secondary">
                                Kelola Role
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center text-gray-400 py-6 text-xs">Tidak ada user ditemukan.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if(method_exists($users, 'links'))
        <div class="mt-4">
            {{ $users->links() }}
        </div>
    @endif
</div>
@endsection
