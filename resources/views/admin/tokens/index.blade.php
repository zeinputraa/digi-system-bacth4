@extends('layouts.app')

@php $pageTitle = 'Token API Sanctum'; @endphp

@section('content')
<div class="space-y-6">
    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Personal Access Token (Sanctum)</h1>
            <p class="page-subtitle">Gunakan token API ini untuk mengintegrasikan sistem eksternal atau melakukan pengujian endpoint inventaris.</p>
        </div>
    </div>

    {{-- Alert generated token / Flash Messages --}}
    @if(session('success'))
        <div class="alert-success p-4 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-xl text-xs font-semibold relative">
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Form Generate Token --}}
        <div class="space-y-4">
            <div class="card">
                <div class="card-header"><p class="card-title">Buat Token Baru</p></div>
                <div class="card-body">
                    <form method="POST" action="{{ route('admin.tokens.generate') }}" class="space-y-4">
                        @csrf
                        <div class="form-group">
                            <label for="token_name" class="form-label">Nama Sistem / Client <span class="text-red-500">*</span></label>
                            <input id="token_name" name="token_name" type="text" class="form-input" required placeholder="Contoh: Mobile App, Third Party Integrator"/>
                        </div>

                        <div class="form-group">
                            <label class="form-label">Scope / Hak Akses API</label>
                            <div class="space-y-2 text-sm text-gray-700">
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="abilities[]" value="read" checked class="rounded text-telkom-600 focus:ring-telkom-500 w-4 h-4"/>
                                    <span>Read-only (Membaca Data Barang & Status)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="abilities[]" value="write" class="rounded text-telkom-600 focus:ring-telkom-500 w-4 h-4"/>
                                    <span>Write (Mengajukan Peminjaman & Insiden)</span>
                                </label>
                                <label class="flex items-center gap-2 cursor-pointer">
                                    <input type="checkbox" name="abilities[]" value="admin" class="rounded text-telkom-600 focus:ring-telkom-500 w-4 h-4"/>
                                    <span>Admin (Mengelola Master & Approval)</span>
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full justify-center">
                            Generate Token
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: Active Tokens List --}}
        <div class="lg:col-span-2">
            <div class="card">
                <div class="card-header"><p class="card-title">Token Aktif</p></div>
                <div class="table-wrapper border-0 shadow-none">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nama Client</th>
                                <th>Terakhir Digunakan</th>
                                <th>Tanggal Dibuat</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50 text-xs">
                            @forelse($tokens as $t)
                                <tr>
                                    <td class="font-medium text-gray-800">
                                        <div>{{ $t->name }}</div>
                                        <div class="text-[10px] text-gray-400">User: {{ $t->user_name }} · Scope: {{ implode(', ', json_decode($t->abilities ?? '[]')) }}</div>
                                    </td>
                                    <td class="text-gray-500 font-mono">
                                        {{ $t->last_used_at ? \Carbon\Carbon::parse($t->last_used_at)->diffForHumans() : 'Belum pernah digunakan' }}
                                    </td>
                                    <td class="text-gray-500">
                                        {{ \Carbon\Carbon::parse($t->created_at)->format('d M Y') }}
                                    </td>
                                    <td>
                                        <form method="POST" action="{{ route('admin.tokens.revoke', $t->id) }}" onsubmit="return confirm('Revoke token ini?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800 font-semibold bg-transparent border-0 p-0 cursor-pointer">Revoke</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-gray-400 py-6 text-xs">Tidak ada token API aktif saat ini.</td>
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
