@extends('layouts.app')

@php $pageTitle = 'Detail Pengajuan'; @endphp

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb --}}
    <nav class="breadcrumb">
        <a href="{{ route('borrowings.index') }}" class="hover:text-gray-600">Peminjaman</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="breadcrumb-current">{{ $borrowing->kode_peminjaman }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Sidebar Info --}}
        <div class="space-y-4">
            <div class="card">
                <div class="card-header">
                    <p class="card-title">Informasi Pengajuan</p>
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
                        $statusVal = $borrowing->status->value;
                    @endphp
                    <span class="badge {{ $badgeMap[$statusVal] ?? '' }}">
                        {{ $labelMap[$statusVal] ?? $statusVal }}
                    </span>
                </div>
                <div class="card-body space-y-3">
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Kode</span>
                        <span class="font-mono font-semibold">{{ $borrowing->kode_peminjaman }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Peminjam</span>
                        <div class="text-right">
                            <p class="font-medium text-gray-800">{{ $borrowing->borrower->name }}</p>
                            <p class="text-xs text-gray-400">{{ $borrowing->borrower->email }}</p>
                        </div>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Tgl Pinjam</span>
                        <span class="font-medium">{{ $borrowing->tanggal_pinjam_rencana ? $borrowing->tanggal_pinjam_rencana->format('d M Y') : '—' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Tgl Kembali</span>
                        <span class="font-medium">{{ $borrowing->tanggal_kembali_rencana ? $borrowing->tanggal_kembali_rencana->format('d M Y') : '—' }}</span>
                    </div>
                </div>
                @if($borrowing->catatan)
                    <div class="px-5 pb-4">
                        <p class="text-xs text-gray-500 mb-1">Catatan Peminjam</p>
                        <p class="text-sm text-gray-700 bg-gray-50 rounded-lg p-3">{{ $borrowing->catatan }}</p>
                    </div>
                @endif
            </div>

            {{-- Override FIFO Alert --}}
            @if($isFifoOverrideNeeded)
                <div class="alert-warning">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-sm">Ada pengajuan lain yang lebih dulu untuk produk ini</p>
                        <p class="text-xs mt-1">Gunakan opsi FIFO Override jika ingin menyetujui pengajuan ini terlebih dahulu.</p>
                    </div>
                </div>
            @endif

            {{-- Stock Insufficient Alert --}}
            @if($isStockInsufficient)
                <div class="alert-danger">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-sm">Stok Unit Tidak Mencukupi</p>
                        <p class="text-xs mt-1">Stok unit untuk barang ini pada rentang tanggal terpilih sudah teralokasi penuh ke peminjam lain.</p>
                    </div>
                </div>
            @endif

            @if($borrowing->fifo_override)
                <div class="alert-info">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-sm">FIFO Override Diaktifkan</p>
                        <p class="text-xs mt-1">{{ $borrowing->alasan_override }}</p>
                    </div>
                </div>
            @endif

            @if($borrowing->alasan_penolakan)
                <div class="alert-danger">
                    <svg class="w-5 h-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="font-semibold text-sm">Pengajuan Ditolak</p>
                        <p class="text-xs mt-1">Alasan: {{ $borrowing->alasan_penolakan }}</p>
                    </div>
                </div>
            @endif
        </div>

        {{-- Main: Detail Barang + Aksi --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Tabel Detail Unit --}}
            <div class="card">
                <div class="card-header">
                    <p class="card-title">Detail Barang yang Diminta</p>
                </div>
                <table class="table">
                    <thead>
                        <tr>
                            <th>Produk</th>
                            <th>Qty</th>
                            <th>Unit Assigned</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        @foreach($borrowing->details as $d)
                            <tr>
                                <td class="font-medium text-gray-800">{{ $d->product->nama_barang }}</td>
                                <td class="text-gray-500">1</td>
                                <td>
                                    @if($d->productUnit)
                                        <span class="badge badge-tersedia font-mono">{{ $d->productUnit->kode_unit }}</span>
                                    @else
                                        <span class="text-gray-400 text-xs italic">Belum di-assign</span>
                                    @endif
                                </td>
                                <td>
                                    @php
                                        $detailBadgeMap = [
                                            'diajukan' => 'badge-diajukan',
                                            'disetujui' => 'badge-disetujui',
                                            'ditolak' => 'badge-ditolak',
                                            'dipinjam' => 'badge-berjalan',
                                            'dikembalikan' => 'badge-selesai',
                                            'terlambat' => 'badge-ditolak',
                                            'bermasalah' => 'badge-maintenance',
                                            'selesai_bermasalah' => 'badge-dibatalkan',
                                            'dibatalkan_no_show' => 'badge-dibatalkan',
                                            'dibatalkan_sla' => 'badge-dibatalkan',
                                        ];
                                        $detailLabelMap = [
                                            'diajukan' => 'Diajukan',
                                            'disetujui' => 'Disetujui',
                                            'ditolak' => 'Ditolak',
                                            'dipinjam' => 'Dipinjam',
                                            'dikembalikan' => 'Dikembalikan',
                                            'terlambat' => 'Terlambat',
                                            'bermasalah' => 'Ada Insiden',
                                            'selesai_bermasalah' => 'Selesai (Bermasalah)',
                                            'dibatalkan_no_show' => 'Batal (No Show)',
                                            'dibatalkan_sla' => 'Batal (SLA)',
                                        ];
                                        $statusStr = $d->status->value;
                                    @endphp
                                    <span class="badge {{ $detailBadgeMap[$statusStr] ?? 'badge-dibatalkan' }}">
                                        {{ $detailLabelMap[$statusStr] ?? $statusStr }}
                                    </span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Aksi Approval (Hanya untuk Admin/Staff dan jika status diajukan) --}}
            @if(auth()->user()->hasRole('admin', 'staff') && $statusVal === 'diajukan')
                <div class="card" x-data="{ showApprove: false, showReject: false, fifoOverride: false }">
                    <div class="card-header">
                        <p class="card-title">Tindakan Persetujuan</p>
                    </div>
                    <div class="card-body">
                        <div class="flex gap-3 mb-4">
                            <button @click="showApprove = !showApprove; showReject = false"
                                    :class="showApprove ? 'btn-success' : 'btn-secondary'"
                                    class="btn">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Setujui Pengajuan
                            </button>
                            <button @click="showReject = !showReject; showApprove = false"
                                    :class="showReject ? 'btn-danger' : 'btn-secondary'"
                                    class="btn">
                                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                Tolak Pengajuan
                            </button>
                        </div>

                        {{-- Panel Setujui --}}
                        <div x-show="showApprove" x-transition class="bg-emerald-50 border border-emerald-200 rounded-xl p-4 space-y-3">
                            <p class="text-sm font-semibold text-emerald-800">Konfirmasi Persetujuan</p>
                            <form method="POST" action="{{ route('borrowings.approve', $borrowing->id) }}" class="space-y-3">
                                @csrf
                                <div class="flex items-start gap-2">
                                    <input type="checkbox" id="fifo_override" name="fifo_override" value="1" x-model="fifoOverride" class="rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500 mt-1"/>
                                    <div>
                                        <label for="fifo_override" class="text-sm font-medium text-emerald-800">FIFO Override</label>
                                        <p class="text-xs text-emerald-600">Centang jika ingin menyetujui mendahului antrean pengajuan produk ini.</p>
                                    </div>
                                </div>
                                <div class="form-group" x-show="fifoOverride">
                                    <x-input-label for="alasan_override" :value="__('Alasan Override FIFO')" class="text-emerald-800" />
                                    <textarea id="alasan_override" name="alasan_override" rows="2" 
                                              :required="fifoOverride"
                                              class="mt-1 block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                              placeholder="Jelaskan alasan mengapa pengajuan ini diprioritaskan..."></textarea>
                                    <x-input-error :messages="$errors->get('alasan_override')" class="mt-2" />
                                </div>
                                <p class="text-xs text-emerald-700">Setelah disetujui, unit fisik yang berstatus tersedia akan dialokasikan secara otomatis.</p>
                                <div class="flex gap-2">
                                    <button type="submit" class="btn-success btn-sm">Konfirmasi Setujui</button>
                                    <button type="button" @click="showApprove = false" class="btn-ghost btn-sm text-gray-500">Batal</button>
                                </div>
                            </form>
                        </div>

                        {{-- Panel Tolak --}}
                        <div x-show="showReject" x-transition class="bg-red-50 border border-red-200 rounded-xl p-4 space-y-3">
                            <p class="text-sm font-semibold text-red-800">Konfirmasi Penolakan</p>
                            <form method="POST" action="{{ route('borrowings.reject', $borrowing->id) }}" class="space-y-3">
                                @csrf
                                <div class="form-group">
                                    <x-input-label for="alasan_penolakan" :value="__('Alasan Penolakan')" class="text-red-800" />
                                    <textarea id="alasan_penolakan" name="alasan_penolakan" rows="2" required
                                              class="mt-1 block w-full text-sm border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" 
                                              placeholder="Jelaskan alasan penolakan kepada peminjam..."></textarea>
                                    <x-input-error :messages="$errors->get('alasan_penolakan')" class="mt-2" />
                                </div>
                                <div class="flex gap-2">
                                    <button type="submit" class="btn-danger btn-sm">Konfirmasi Tolak</button>
                                    <button type="button" @click="showReject = false" class="btn-ghost btn-sm text-gray-500">Batal</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
