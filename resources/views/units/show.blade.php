@extends('layouts.app')

@php $pageTitle = 'Unit ' . $unit->kode_unit; @endphp

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb --}}
    <nav class="breadcrumb">
        <a href="{{ route('products.index') }}" class="hover:text-gray-600">Barang</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <a href="{{ route('products.show', $product) }}" class="hover:text-gray-600">{{ $product->nama_barang }}</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="breadcrumb-current">{{ $unit->kode_unit }}</span>
    </nav>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Info Unit + QR Code --}}
        <div class="space-y-4">
            {{-- QR Code Card --}}
            <div class="card text-center">
                <div class="card-header">
                    <p class="card-title">QR Code Unit</p>
                    <a href="{{ route('qr.show', $unit->qr_code) }}" target="_blank"
                       class="text-xs text-telkom-600 font-medium">Buka Publik →</a>
                </div>
                <div class="card-body flex flex-col items-center">
                    <canvas id="qr-canvas" class="rounded-lg border border-gray-200 p-2"></canvas>
                    <p class="font-mono text-xs text-gray-500 mt-3 break-all">{{ $unit->qr_code }}</p>
                    <div class="flex gap-2 mt-3">
                        <form method="POST" action="{{ route('labels.cetak') }}">
                            @csrf
                            <input type="hidden" name="unit_ids[]" value="{{ $unit->id }}">
                            <button type="submit" class="btn-sm btn-secondary flex items-center gap-1.5">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                </svg>
                                Cetak Label
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            {{-- Info Detail --}}
            <div class="card">
                <div class="card-header">
                    <p class="card-title">Informasi Unit</p>
                </div>
                <div class="card-body space-y-3">
                    @php
                        $statusBadge = match($unit->status->value) {
                            'tersedia'          => 'badge-tersedia',
                            'dipinjam'          => 'badge-dipinjam',
                            'maintenance'       => 'badge-maintenance',
                            'dilaporkan_hilang' => 'badge-hilang',
                            'hilang_permanen'   => 'badge-hilang-permanen',
                            default             => '',
                        };
                        $kondisiBadge = match($unit->kondisi->value ?? $unit->kondisi) {
                            'baik'         => 'badge-baik',
                            'rusak_ringan' => 'badge-rusak-ringan',
                            'rusak_berat'  => 'badge-rusak-berat',
                            default        => '',
                        };
                    @endphp
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Kode Unit</span>
                        <span class="font-mono font-semibold">{{ $unit->kode_unit }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Status</span>
                        <span class="badge {{ $statusBadge }}">{{ ucfirst(str_replace('_', ' ', $unit->status->value)) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Kondisi</span>
                        <span class="badge {{ $kondisiBadge }}">{{ ucfirst(str_replace('_', ' ', $unit->kondisi->value ?? $unit->kondisi)) }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Lokasi</span>
                        <span class="font-medium text-right">{{ $unit->lokasi_penyimpanan ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Tahun Pengadaan</span>
                        <span class="font-medium">{{ $unit->tahun_pengadaan ?? '—' }}</span>
                    </div>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500">Harga Perolehan</span>
                        <span class="font-medium">
                            @if($unit->harga_perolehan)
                                Rp {{ number_format($unit->harga_perolehan, 0, ',', '.') }}
                            @else
                                —
                            @endif
                        </span>
                    </div>
                    @if($unit->catatan)
                        <div class="divider"></div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Catatan</p>
                            <p class="text-sm text-gray-700">{{ $unit->catatan }}</p>
                        </div>
                    @endif
                </div>
                <div class="px-5 py-3 border-t border-gray-100 flex gap-2">
                    <a href="{{ route('units.edit', [$product, $unit]) }}" class="btn-sm btn-secondary">Edit Unit</a>
                </div>
            </div>
        </div>

        {{-- Riwayat Peminjaman & Insiden --}}
        <div class="lg:col-span-2 space-y-4">

            {{-- Riwayat Peminjaman --}}
            <div class="card">
                <div class="card-header">
                    <p class="card-title">Riwayat Peminjaman Unit Ini</p>
                </div>
                <div class="table-wrapper border-0 shadow-none">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Kode Pinjam</th>
                                <th>Peminjam</th>
                                <th>Tgl Pinjam</th>
                                <th>Tgl Kembali</th>
                                <th>Status</th>
                            </tr>
                        <tbody class="bg-white divide-y divide-gray-50">
                            @forelse($unit->borrowingDetails as $d)
                                @php
                                    $statusVal = $d->status->value ?? $d->status;
                                    $bStyle = match($statusVal) {
                                        'selesai'   => 'badge-selesai',
                                        'dipinjam'  => 'badge-berjalan',
                                        'terlambat' => 'badge-ditolak',
                                        default     => 'badge-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td class="font-mono text-xs">{{ $d->borrowing->kode_peminjaman }}</td>
                                    <td>{{ $d->borrowing->borrower->name }}</td>
                                    <td class="text-gray-500 text-xs">{{ $d->tanggal_pinjam_aktual ? $d->tanggal_pinjam_aktual->format('d M Y') : ($d->borrowing->tanggal_pinjam_rencana ? $d->borrowing->tanggal_pinjam_rencana->format('d M Y') : '—') }}</td>
                                    <td class="text-gray-500 text-xs">{{ $d->tanggal_kembali_aktual ? $d->tanggal_kembali_aktual->format('d M Y') : ($d->borrowing->tanggal_kembali_rencana ? $d->borrowing->tanggal_kembali_rencana->format('d M Y') : '—') }}</td>
                                    <td><span class="badge {{ $bStyle }}">{{ ucfirst($statusVal) }}</span></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-400 py-4 text-xs">Belum ada riwayat peminjaman untuk unit ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            {{-- Riwayat Insiden --}}
            <div class="card">
                <div class="card-header">
                    <p class="card-title">Riwayat Insiden Unit Ini</p>
                </div>
                <div class="table-wrapper border-0 shadow-none">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Jenis</th>
                                <th>Pelapor</th>
                                <th>Tanggal</th>
                                <th>Status</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50">
                            @forelse($unit->incidentReports as $inc)
                                @php
                                    $jenisVal = $inc->jenis->value ?? $inc->jenis;
                                    $jBadge = match($jenisVal) {
                                        'rusak_ringan' => 'badge-rusak-ringan',
                                        'rusak_berat'  => 'badge-rusak-berat',
                                        'hilang'       => 'badge-hilang',
                                        default        => 'badge-secondary',
                                    };
                                    $statusVal = $inc->status->value ?? $inc->status;
                                    $sBadge = match($statusVal) {
                                        'menunggu_verifikasi_staff'  => 'badge-menunggu',
                                        'terverifikasi_staff'        => 'badge-terverifikasi',
                                        'menunggu_finalisasi_admin' => 'badge-menunggu-admin',
                                        'difinalisasi_admin'         => 'badge-selesai',
                                        default                      => 'badge-secondary',
                                    };
                                @endphp
                                <tr>
                                    <td><span class="badge {{ $jBadge }}">{{ ucfirst(str_replace('_', ' ', $jenisVal)) }}</span></td>
                                    <td>{{ $inc->reporter->name }}</td>
                                    <td class="text-gray-500 text-xs">{{ $inc->created_at->format('d M Y') }}</td>
                                    <td><span class="badge {{ $sBadge }}">{{ ucfirst(str_replace('_', ' ', $statusVal)) }}</span></td>
                                    <td><a href="{{ route('incidents.show', $inc->id) }}" class="btn-sm btn-ghost p-0 font-semibold text-telkom-600">Detail</a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-400 py-4 text-xs">Belum ada riwayat insiden untuk unit ini.</td>
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

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    generateQrCode('qr-canvas', '{{ url(route("qr.show", $unit->qr_code)) }}');
});
</script>
@endpush
