@extends('layouts.app')

@php $pageTitle = 'Dashboard'; @endphp

@section('content')
{{-- ============================================================
     DASHBOARD KARYAWAN
     ============================================================ --}}
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Selamat datang, {{ explode(' ', auth()->user()->name)[0] }}! 👋</h1>
            <p class="page-subtitle">{{ \Carbon\Carbon::now()->isoFormat('dddd, D MMMM Y') }}</p>
        </div>
        <a href="{{ route('borrowings.create') }}" class="btn-primary">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Ajukan Peminjaman
        </a>
    </div>

    {{-- ===== PEMINJAMAN AKTIF ===== --}}
    @if($activeBorrowings->count() > 0)
        <div>
            <div class="flex items-center justify-between mb-3">
                <h2 class="text-sm font-semibold text-gray-700">Peminjaman Aktif</h2>
                <a href="{{ route('borrowings.my') }}" class="text-xs text-telkom-600 font-medium">Semua →</a>
            </div>
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-4">
                @foreach($activeBorrowings as $b)
                    @php
                        $isLate = $b->status->value === 'berjalan' && $b->tanggal_kembali_rencana && $b->tanggal_kembali_rencana->isPast();
                    @endphp
                    <div class="card @if($isLate) ring-1 ring-red-300 @endif">
                        <div class="card-header">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-800">{{ $b->kode_peminjaman }}</span>
                                @if($isLate)
                                    <span class="badge badge-ditolak">⚠ Terlambat</span>
                                @else
                                    <span class="badge badge-berjalan">Berjalan</span>
                                @endif
                            </div>
                            <span class="text-xs @if($isLate) text-red-600 font-semibold @else text-gray-400 @endif">
                                Kembali: {{ $b->tanggal_kembali_rencana ? $b->tanggal_kembali_rencana->isoFormat('D MMM Y') : '—' }}
                            </span>
                        </div>
                        <div class="px-5 py-3 space-y-2">
                            @foreach($b->details as $d)
                                <div class="flex items-center justify-between">
                                    <div class="min-w-0 flex-1 mr-2">
                                        <p class="text-sm text-gray-800 font-medium truncate">{{ $d->productUnit?->product?->nama_barang ?? $d->product?->nama_barang }}</p>
                                        <p class="text-xs text-gray-400 font-mono">{{ $d->productUnit?->kode_unit ?? 'Belum ditentukan' }}</p>
                                    </div>
                                    @if($d->productUnit)
                                        @php
                                            $condVal = $d->productUnit->kondisi->value ?? $d->productUnit->kondisi;
                                            $kondisiBadge = match($condVal) {
                                                'baik'         => 'badge-baik',
                                                'rusak_ringan' => 'badge-rusak-ringan',
                                                'rusak_berat'  => 'badge-rusak-berat',
                                                default        => 'badge-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $kondisiBadge }} text-[10px] shrink-0">{{ ucfirst(str_replace('_', ' ', $condVal)) }}</span>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                        <div class="px-5 py-3 border-t border-gray-100 flex gap-2">
                            <a href="{{ route('borrowings.my') }}" class="btn-sm btn-secondary">Detail</a>
                            @if(!$isLate)
                                <a href="{{ route('borrowings.my') }}" class="btn-sm btn-ghost text-blue-600">Perpanjang</a>
                            @endif
                            <a href="{{ route('incidents.create') }}" class="btn-sm btn-ghost text-orange-600">Lapor Insiden</a>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @else
        <div class="card">
            <div class="card-body empty-state">
                <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
                <p class="empty-state-title">Tidak ada peminjaman aktif</p>
                <p class="empty-state-desc">Ajukan peminjaman barang dari katalog</p>
                <a href="{{ route('borrowings.create') }}" class="btn-primary btn-sm mt-3">Mulai Pinjam</a>
            </div>
        </div>
    @endif

    {{-- ===== PENGAJUAN MENUNGGU ===== --}}
    <div class="card">
        <div class="card-header">
            <p class="card-title">Pengajuan Menunggu Approval</p>
            <span class="badge badge-diajukan">{{ $pendingBorrowings->count() }} pengajuan</span>
        </div>
        <div class="table-wrapper border-0 shadow-none">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Barang</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    @forelse($pendingBorrowings as $b)
                        <tr>
                            <td class="font-mono text-xs">{{ $b->kode_peminjaman }}</td>
                            <td>
                                {{ $b->details->map(fn($d) => optional($d->product)->nama_barang)->filter()->unique()->implode(', ') }}
                            </td>
                            <td class="text-gray-500 text-xs">{{ $b->tanggal_pinjam_rencana ? $b->tanggal_pinjam_rencana->format('d M Y') : '—' }}</td>
                            <td class="text-gray-500 text-xs">{{ $b->tanggal_kembali_rencana ? $b->tanggal_kembali_rencana->format('d M Y') : '—' }}</td>
                            <td><span class="badge badge-diajukan">Menunggu Approval</span></td>
                            <td>
                                <form method="POST" action="{{ route('borrowings.cancel', $b->id) }}" onsubmit="return confirm('Yakin batalkan pengajuan ini?')">
                                    @csrf
                                    <button type="submit" class="btn-sm btn-ghost text-red-600 p-0 font-semibold">Batalkan</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-400 py-4 text-xs">Tidak ada pengajuan peminjaman menunggu approval.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- ===== QUICK LINKS ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <a href="{{ route('products.index') }}"
           class="card p-4 flex flex-col items-center gap-2 text-center hover:shadow-card-hover transition-shadow cursor-pointer">
            <div class="w-10 h-10 bg-blue-50 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-700">Katalog Barang</p>
        </a>

        <a href="{{ route('borrowings.create') }}"
           class="card p-4 flex flex-col items-center gap-2 text-center hover:shadow-card-hover transition-shadow cursor-pointer">
            <div class="w-10 h-10 bg-telkom-50 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-telkom-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-700">Ajukan Pinjam</p>
        </a>

        <a href="{{ route('borrowings.my') }}"
           class="card p-4 flex flex-col items-center gap-2 text-center hover:shadow-card-hover transition-shadow cursor-pointer">
            <div class="w-10 h-10 bg-emerald-50 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-700">Riwayat Saya</p>
        </a>

        <a href="{{ route('incidents.create') }}"
           class="card p-4 flex flex-col items-center gap-2 text-center hover:shadow-card-hover transition-shadow cursor-pointer">
            <div class="w-10 h-10 bg-orange-50 rounded-xl flex items-center justify-center">
                <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>
            <p class="text-sm font-medium text-gray-700">Lapor Insiden</p>
        </a>
    </div>

</div>
@endsection
