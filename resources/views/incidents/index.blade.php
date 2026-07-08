@extends('layouts.app')

@php $pageTitle = 'Laporan Insiden'; @endphp

@section('content')
<div class="space-y-5">
    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Laporan Insiden Aset</h1>
            <p class="page-subtitle">Kelola laporan kerusakan, kehilangan, dan proses audit ganti rugi barang.</p>
        </div>
    </div>

    {{-- Tabs / Filter status --}}
    <div x-data="{ tab: 'semua' }">
        <div class="tabs">
            <button @click="tab='semua'"       :class="tab==='semua'       ? 'active' : ''" class="tab-link">Semua</button>
            <button @click="tab='menunggu'"    :class="tab==='menunggu'    ? 'active' : ''" class="tab-link">Menunggu Verifikasi</button>
            <button @click="tab='finalisasi'"  :class="tab==='finalisasi'  ? 'active' : ''" class="tab-link">Menunggu Finalisasi</button>
            <button @click="tab='selesai'"     :class="tab==='selesai'     ? 'active' : ''" class="tab-link">Selesai</button>
        </div>

        {{-- Table --}}
        <div class="table-wrapper">
            <table class="table">
                <thead>
                    <tr>
                        <th>ID Laporan</th>
                        <th>Kode Unit</th>
                        <th>Barang</th>
                        <th>Pelapor</th>
                        <th>Jenis Masalah</th>
                        <th>Status Verifikasi</th>
                        <th>Tanggal Lapor</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    @php
                        $badgeKondisi = [
                            'rusak_ringan' => 'bg-amber-100 text-amber-800 border-amber-200',
                            'rusak_berat' => 'bg-red-100 text-red-800 border-red-200',
                            'hilang' => 'bg-gray-100 text-gray-800 border-gray-200',
                        ];
                        $labelKondisi = [
                            'rusak_ringan' => 'Rusak Ringan',
                            'rusak_berat' => 'Rusak Berat',
                            'hilang' => 'Hilang',
                        ];
                        $badgeStatus = [
                            'menunggu_verifikasi_staff' => 'badge-diajukan',
                            'terverifikasi_staff' => 'badge-disetujui',
                            'menunggu_finalisasi_admin' => 'badge-maintenance',
                            'difinalisasi_admin' => 'badge-selesai',
                            'dibatalkan_ditemukan' => 'badge-dibatalkan',
                        ];
                        $labelStatus = [
                            'menunggu_verifikasi_staff' => 'Menunggu Verifikasi Staff',
                            'terverifikasi_staff' => 'Terverifikasi Staff',
                            'menunggu_finalisasi_admin' => 'Menunggu Finalisasi Admin',
                            'difinalisasi_admin' => 'Selesai',
                            'dibatalkan_ditemukan' => 'Dibatalkan (Ditemukan)',
                        ];
                    @endphp
                    @forelse($incidents as $inc)
                        @php
                            $statusVal = $inc->status->value;
                            $jenisVal = $inc->jenis->value;
                            
                            // Map tab values
                            $tabVal = 'selesai';
                            if ($statusVal === 'menunggu_verifikasi_staff') {
                                $tabVal = 'menunggu';
                            } elseif ($statusVal === 'menunggu_finalisasi_admin') {
                                $tabVal = 'finalisasi';
                            }
                        @endphp
                        <tr x-show="tab === 'semua' || tab === '{{ $tabVal }}'">
                            <td class="font-mono text-xs font-semibold text-gray-700">INC-{{ str_pad($inc->id, 5, '0', STR_PAD_LEFT) }}</td>
                            <td class="font-mono text-xs">{{ $inc->productUnit ? $inc->productUnit->kode_unit : '—' }}</td>
                            <td class="font-medium text-gray-900">{{ $inc->productUnit ? $inc->productUnit->product->nama_barang : '—' }}</td>
                            <td>{{ $inc->reporter ? $inc->reporter->name : '—' }}</td>
                            <td>
                                <span class="badge {{ $badgeKondisi[$jenisVal] ?? '' }}">
                                    {{ $labelKondisi[$jenisVal] ?? $jenisVal }}
                                </span>
                            </td>
                            <td>
                                <span class="badge {{ $badgeStatus[$statusVal] ?? '' }}">
                                    {{ $labelStatus[$statusVal] ?? $statusVal }}
                                </span>
                            </td>
                            <td class="text-gray-500 text-xs">{{ $inc->created_at ? $inc->created_at->format('d M Y') : '—' }}</td>
                            <td>
                                <a href="{{ route('incidents.show', $inc->id) }}" class="btn-sm btn-secondary font-medium">Detail</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center text-gray-400 py-6 text-sm">Tidak ada laporan insiden ditemukan.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4">
            {{ $incidents->links() }}
        </div>
    </div>
</div>
@endsection
