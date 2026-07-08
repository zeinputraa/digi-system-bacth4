@extends('layouts.app')

@php $pageTitle = 'Pilih Unit Label'; @endphp

@section('content')
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Cetak Label QR Aset</h1>
            <p class="page-subtitle">Pilih unit barang yang aktif untuk mencetak stiker QR Code secara massal atau satuan.</p>
        </div>
    </div>

    {{-- Filter Form --}}
    <div class="card p-5">
        <form method="GET" action="{{ route('labels.pilih') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div>
                <label class="form-label">Kategori</label>
                <select name="kategori_id" class="form-select">
                    <option value="">Semua Kategori</option>
                    @foreach($categories as $cat)
                        <option value="{{ $cat->id }}" @selected(request('kategori_id') == $cat->id)>{{ $cat->nama_kategori }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Produk</label>
                <select name="produk_id" class="form-select">
                    <option value="">Semua Produk</option>
                    @foreach($products as $prod)
                        <option value="{{ $prod->id }}" @selected(request('produk_id') == $prod->id)>{{ $prod->nama_barang }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="form-label">Pencarian</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Kode unit / nama barang..." class="form-input">
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="btn-primary flex-1 justify-center py-2">Filter</button>
                <a href="{{ route('labels.pilih') }}" class="btn-secondary py-2">Reset</a>
            </div>
        </form>
    </div>

    {{-- Select Units Form --}}
    <form method="POST" action="{{ route('labels.cetak') }}">
        @csrf
        <div class="card">
            <div class="card-header justify-between">
                <p class="card-title">Daftar Unit Tersedia</p>
                <button type="submit" class="btn-sm btn-primary">Cetak Terpilih</button>
            </div>

            <div class="table-wrapper">
                <table class="table" x-data="{
                    selectAll: false,
                    toggleAll() {
                        this.selectAll = !this.selectAll;
                        document.querySelectorAll('.unit-checkbox').forEach(cb => {
                            cb.checked = this.selectAll;
                        });
                    }
                }">
                    <thead>
                        <tr>
                            <th class="w-12 text-center">
                                <input type="checkbox" @click="toggleAll()" class="rounded text-telkom-600 focus:ring-telkom-500 w-4 h-4 cursor-pointer">
                            </th>
                            <th>Kode Unit</th>
                            <th>Nama Barang</th>
                            <th>Kategori</th>
                            <th>Kondisi</th>
                            <th>Status</th>
                            <th>Lokasi Penyimpanan</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-50">
                        @forelse($units as $unit)
                            @php
                                $statusBadge = match($unit->status->value) {
                                    'tersedia'          => 'badge-tersedia',
                                    'dipinjam'          => 'badge-dipinjam',
                                    'maintenance'       => 'badge-maintenance',
                                    'dilaporkan_hilang' => 'badge-hilang',
                                    'hilang_permanen'   => 'badge-hilang-permanen',
                                    default             => 'badge-selesai',
                                };
                                $kondisiBadge = match($unit->kondisi->value ?? $unit->kondisi) {
                                    'baik'         => 'badge-baik',
                                    'rusak_ringan' => 'badge-rusak-ringan',
                                    'rusak_berat'  => 'badge-rusak-berat',
                                    default        => '',
                                };
                            @endphp
                            <tr class="hover:bg-gray-50/50">
                                <td class="text-center">
                                    <input type="checkbox" name="unit_ids[]" value="{{ $unit->id }}" class="unit-checkbox rounded text-telkom-600 focus:ring-telkom-500 w-4 h-4 cursor-pointer">
                                </td>
                                <td class="font-mono text-xs font-semibold text-gray-800">{{ $unit->kode_unit }}</td>
                                <td class="font-medium text-gray-800">{{ $unit->product->nama_barang ?? '—' }}</td>
                                <td class="text-gray-500 text-sm">{{ $unit->product->category->nama_kategori ?? '—' }}</td>
                                <td><span class="badge {{ $kondisiBadge }}">{{ ucfirst(str_replace('_', ' ', $unit->kondisi->value ?? $unit->kondisi)) }}</span></td>
                                <td><span class="badge {{ $statusBadge }}">{{ ucfirst(str_replace('_', ' ', $unit->status->value)) }}</span></td>
                                <td class="text-gray-500 text-sm">{{ $unit->lokasi_penyimpanan ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-gray-400 py-8">Tidak ada unit barang ditemukan.</td>
                            </tr>
                        @endempty
                    </tbody>
                </table>
            </div>

            @if($units->hasPages())
                <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                    {{ $units->links() }}
                </div>
            @endif
        </div>
    </form>
</div>
@endsection
