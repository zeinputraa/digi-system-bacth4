@extends('layouts.app')

@php $pageTitle = 'Kelola Peminjaman'; @endphp

@section('content')
<div class="space-y-5">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Kelola Peminjaman</h1>
            <p class="page-subtitle">Daftar semua pengajuan peminjaman barang inventaris</p>
        </div>
    </div>

    {{-- Tabs --}}
    <div x-data="{ tab: 'semua' }">
        <div class="tabs">
            <button @click="tab='semua'"     :class="tab==='semua'     ? 'active' : ''" class="tab-link">Semua</button>
            <button @click="tab='diajukan'"  :class="tab==='diajukan'  ? 'active' : ''" class="tab-link">Menunggu Approval</button>
            <button @click="tab='berjalan'"  :class="tab==='berjalan'  ? 'active' : ''" class="tab-link">Berjalan</button>
            <button @click="tab='terlambat'" :class="tab==='terlambat' ? 'active' : ''" class="tab-link text-red-600">Terlambat</button>
            <button @click="tab='selesai'"   :class="tab==='selesai'   ? 'active' : ''" class="tab-link">Selesai</button>
        </div>

        {{-- Table --}}
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Peminjam</th>
                        <th>Barang Dipinjam</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>Status</th>
                        <th>FIFO</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    @php
                        $badgeMap = [
                            'diajukan' => 'badge-diajukan',
                            'disetujui' => 'badge-disetujui',
                            'berjalan' => 'badge-berjalan',
                            'selesai' => 'badge-selesai',
                            'ditolak' => 'badge-ditolak',
                            'dibatalkan_user' => 'badge-dibatalkan',
                            'dibatalkan_otomatis' => 'badge-dibatalkan',
                        ];
                        $labelMap = [
                            'diajukan' => 'Menunggu Approval',
                            'disetujui' => 'Disetujui',
                            'berjalan' => 'Berjalan',
                            'selesai' => 'Selesai',
                            'ditolak' => 'Ditolak',
                            'dibatalkan_user' => 'Dibatalkan',
                            'dibatalkan_otomatis' => 'Dibatalkan Otomatis',
                        ];
                    @endphp
                    @forelse($borrowings as $b)
                        @php
                            $statusVal = $b->status->value;
                            $tabVal = $statusVal;
                            if ($statusVal === 'berjalan' && $b->tanggal_kembali_rencana && $b->tanggal_kembali_rencana->isPast()) {
                                $tabVal = 'terlambat';
                            }
                        @endphp
                        <tr x-show="tab === 'semua' || tab === '{{ $tabVal }}'">
                            <td class="font-mono text-xs text-gray-700">{{ $b->kode_peminjaman }}</td>
                            <td class="font-medium text-gray-900">{{ $b->borrower->name }}</td>
                            <td>
                                <div class="space-y-0.5">
                                    {{-- Group details by product_id to show a clean product listing --}}
                                    @foreach($b->details->groupBy('product_id') as $productId => $detailsGroup)
                                        @php $detail = $detailsGroup->first(); @endphp
                                        <p class="text-xs font-medium text-gray-800">
                                            {{ $detail->product->nama_barang }} ({{ $detailsGroup->count() }}x)
                                        </p>
                                    @endforeach
                                </div>
                            </td>
                            <td class="text-gray-500 text-xs">{{ $b->tanggal_pinjam_rencana ? $b->tanggal_pinjam_rencana->format('d M Y') : '—' }}</td>
                            <td class="text-gray-500 text-xs {{ $tabVal === 'terlambat' ? 'text-red-600 font-bold' : '' }}">{{ $b->tanggal_kembali_rencana ? $b->tanggal_kembali_rencana->format('d M Y') : '—' }}</td>
                            <td>
                                <span class="badge {{ $badgeMap[$statusVal] ?? '' }}">
                                    {{ $labelMap[$statusVal] ?? $statusVal }}
                                </span>
                            </td>
                            <td>
                                @if($b->fifo_override)
                                    <span class="badge badge-warning text-[10px]">Override</span>
                                @else
                                    <span class="text-gray-300 text-xs">—</span>
                                @endif
                            </td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('borrowings.show', $b->id) }}" class="btn-sm btn-secondary">
                                        {{ in_array($statusVal, ['diajukan']) ? 'Proses' : 'Detail' }}
                                    </a>
                                    @if($statusVal === 'disetujui')
                                        <a href="{{ route('borrowings.handover', $b->id) }}" class="btn-sm btn-success">Serah Terima</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-gray-400 py-6 text-sm">Tidak ada transaksi peminjaman ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination links --}}
        <div class="mt-4">
            {{ $borrowings->links() }}
        </div>
    </div>
</div>
@endsection
