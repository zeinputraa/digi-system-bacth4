@extends('layouts.app')

@php $pageTitle = 'Peminjaman Saya'; @endphp

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Peminjaman Saya</h1>
            <p class="page-subtitle">Daftar pengajuan peminjaman pribadi dan status pengembalian barang Anda.</p>
        </div>
        <a href="{{ route('borrowings.create') }}" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
            </svg>
            Ajukan Peminjaman
        </a>
    </div>

    {{-- Tabs --}}
    @php
        $aktif = $borrowings->whereIn('status', [
            \App\Enums\StatusBorrowing::Diajukan,
            \App\Enums\StatusBorrowing::Disetujui,
            \App\Enums\StatusBorrowing::Berjalan
        ]);

        $riwayat = $borrowings->whereIn('status', [
            \App\Enums\StatusBorrowing::Selesai,
            \App\Enums\StatusBorrowing::Ditolak,
            \App\Enums\StatusBorrowing::DibatalkanUser,
            \App\Enums\StatusBorrowing::DibatalkanOtomatis
        ]);
    @endphp

    <div x-data="{ tab: 'aktif' }">
        <div class="tabs">
            <button @click="tab='aktif'"     :class="tab==='aktif'     ? 'active' : ''" class="tab-link">Peminjaman Aktif ({{ $aktif->count() }})</button>
            <button @click="tab='selesai'"   :class="tab==='selesai'   ? 'active' : ''" class="tab-link">Selesai & Riwayat ({{ $riwayat->count() }})</button>
        </div>

        {{-- Main List --}}
        <div class="space-y-4">

            {{-- 1. PEMINJAMAN AKTIF TAB --}}
            <div x-show="tab === 'aktif'" class="space-y-4">
                @forelse($aktif as $b)
                    @php
                        $isLate = $b->status === \App\Enums\StatusBorrowing::Berjalan && $b->tanggal_kembali_rencana && $b->tanggal_kembali_rencana->isPast();
                    @endphp
                    <div class="card @if($isLate) ring-1 ring-red-300 bg-red-50/10 @endif">
                        <div class="card-header">
                            <div class="flex items-center gap-2">
                                <span class="text-sm font-semibold text-gray-800">{{ $b->kode_peminjaman }}</span>
                                @if($isLate)
                                    <span class="badge badge-ditolak">⚠ Terlambat</span>
                                @elseif($b->status === \App\Enums\StatusBorrowing::Diajukan)
                                    <span class="badge badge-diajukan">Menunggu Approval</span>
                                @elseif($b->status === \App\Enums\StatusBorrowing::Disetujui)
                                    <span class="badge badge-disetujui">Disetujui</span>
                                @elseif($b->status === \App\Enums\StatusBorrowing::Berjalan)
                                    <span class="badge badge-berjalan">Berjalan</span>
                                @endif
                            </div>
                            <span class="text-xs @if($isLate) text-red-600 font-bold @else text-gray-400 @endif">
                                Tanggal Pengembalian: {{ $b->tanggal_kembali_rencana ? $b->tanggal_kembali_rencana->format('d M Y') : '—' }}
                            </span>
                        </div>
                        <div class="divide-y divide-gray-100">
                            @foreach($b->details as $d)
                                <div class="px-5 py-3.5 flex items-center justify-between">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate">{{ $d->product->nama_barang }}</p>
                                        <p class="font-mono text-xs text-gray-400 mt-0.5">{{ $d->productUnit ? $d->productUnit->kode_unit : 'Belum di-assign' }}</p>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        @if($d->productUnit)
                                            <span class="badge badge-baik text-[10px]">{{ $d->productUnit->kondisi->value }}</span>
                                        @endif
                                        @if($d->status === \App\Enums\StatusBorrowingDetail::Bermasalah)
                                            <span class="badge badge-maintenance text-[10px]">Dalam Peninjauan Insiden</span>
                                        @endif
                                        @if($b->status === \App\Enums\StatusBorrowing::Berjalan && $d->productUnit && $d->status !== \App\Enums\StatusBorrowingDetail::Bermasalah && $d->status !== \App\Enums\StatusBorrowingDetail::SelesaiBermasalah)
                                            <a href="{{ route('incidents.create') }}?detail={{ $d->id }}" class="btn-sm btn-ghost text-orange-600 font-semibold p-0">
                                                 Lapor Insiden
                                            </a>
                                        @endif
                                        @if($d->status === \App\Enums\StatusBorrowingDetail::Dipinjam)
                                            <form method="POST" action="{{ route('borrowings.extend', $d->id) }}" class="flex items-center gap-2">
                                                @csrf
                                                <input type="date"
                                                       name="tanggal_kembali_baru"
                                                       min="{{ $d->tanggal_kembali_rencana->addDay()->format('Y-m-d') }}"
                                                       required
                                                       class="text-xs border-gray-300 rounded">
                                                <x-secondary-button type="submit" class="text-xs">Perpanjang</x-secondary-button>
                                            </form>
                                        @endif
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="px-5 py-3 bg-gray-50/50 flex gap-2 justify-end">
                            @if($b->status === \App\Enums\StatusBorrowing::Diajukan)
                                <form method="POST" action="{{ route('borrowings.cancel', $b->id) }}" 
                                      onsubmit="return confirm('Yakin batalkan pengajuan ini?')">
                                    @csrf
                                    <x-danger-button type="submit" class="text-xs py-1.5 px-3">Batalkan Pengajuan</x-danger-button>
                                </form>
                            @else
                                <span class="text-xs text-gray-400 self-center">Harap serahkan barang ke Staff untuk diproses pengembalian.</span>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="card p-6 text-center text-gray-400 text-sm">Tidak ada peminjaman aktif.</div>
                @endforelse
            </div>

            {{-- 2. SELESAI & RIWAYAT TAB --}}
            <div x-show="tab === 'selesai'" class="table-wrapper">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Barang</th>
                            <th>Tanggal Pinjam</th>
                            <th>Tanggal Kembali</th>
                            <th>Status Akhir</th>
                            <th>Kondisi Pengembalian</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        @forelse($riwayat as $b)
                            @php
                                $badgeMap = [
                                    'selesai' => 'badge-selesai',
                                    'ditolak' => 'badge-ditolak',
                                    'dibatalkan_user' => 'badge-dibatalkan',
                                    'dibatalkan_otomatis' => 'badge-dibatalkan',
                                ];
                                $labelMap = [
                                    'selesai' => 'Selesai',
                                    'ditolak' => 'Ditolak',
                                    'dibatalkan_user' => 'Dibatalkan',
                                    'dibatalkan_otomatis' => 'Dibatalkan Otomatis',
                                ];
                            @endphp
                            <tr>
                                <td class="font-mono text-xs font-semibold">{{ $b->kode_peminjaman }}</td>
                                <td class="font-medium text-gray-800">
                                    <div class="space-y-0.5">
                                        @foreach($b->details->groupBy('product_id') as $productId => $group)
                                            <p>{{ $group->first()->product->nama_barang }} ({{ $group->count() }}x)</p>
                                        @endforeach
                                    </div>
                                </td>
                                <td class="text-gray-500 text-xs">{{ $b->tanggal_pinjam_rencana ? $b->tanggal_pinjam_rencana->format('d M Y') : '—' }}</td>
                                <td class="text-gray-500 text-xs">{{ $b->tanggal_kembali_rencana ? $b->tanggal_kembali_rencana->format('d M Y') : '—' }}</td>
                                <td><span class="badge {{ $badgeMap[$b->status->value] ?? '' }}">{{ $labelMap[$b->status->value] ?? $b->status->value }}</span></td>
                                <td>
                                    <div class="space-y-0.5 text-xs text-gray-600">
                                        @foreach($b->details as $d)
                                            @if($d->kondisi_saat_kembali)
                                                <p>{{ $d->productUnit->kode_unit ?? '' }}: {{ $d->kondisi_saat_kembali }}</p>
                                            @endif
                                        @endforeach
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-gray-400 py-6 text-sm">Tidak ada riwayat peminjaman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

        </div>
    </div>
</div>
@endsection
