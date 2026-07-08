@extends('layouts.app')

@php $pageTitle = 'Edit Unit ' . $unit->kode_unit; @endphp

@section('content')
<div class="max-w-2xl">
    <div class="mb-5">
        <nav class="breadcrumb mb-2">
            <a href="{{ route('products.index') }}" class="hover:text-gray-600">Barang</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('products.show', $product) }}" class="hover:text-gray-600">{{ $product->nama_barang }}</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="{{ route('units.show', [$product, $unit]) }}" class="hover:text-gray-600">{{ $unit->kode_unit }}</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="breadcrumb-current">Edit Unit</span>
        </nav>
        <h1 class="page-title">Edit Unit</h1>
        <p class="page-subtitle">Kode Unit: <span class="font-mono font-bold">{{ $unit->kode_unit }}</span></p>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('units.update', [$product, $unit]) }}" method="POST" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label for="status" class="form-label">Status Unit <span class="text-red-500">*</span></label>
                        <select id="status" name="status" class="form-select" required>
                            @foreach(\App\Enums\StatusUnit::cases() as $case)
                                <option value="{{ $case->value }}" {{ ($unit->status->value ?? $unit->status) == $case->value ? 'selected' : '' }}>
                                    {{ ucfirst(str_replace('_', ' ', $case->value)) }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="kondisi" class="form-label">Kondisi Unit <span class="text-red-500">*</span></label>
                        <select id="kondisi" name="kondisi" class="form-select" required>
                            <option value="baik" {{ ($unit->kondisi->value ?? $unit->kondisi) == 'baik' ? 'selected' : '' }}>Baik</option>
                            <option value="rusak_ringan" {{ ($unit->kondisi->value ?? $unit->kondisi) == 'rusak_ringan' ? 'selected' : '' }}>Rusak Ringan</option>
                            <option value="rusak_berat" {{ ($unit->kondisi->value ?? $unit->kondisi) == 'rusak_berat' ? 'selected' : '' }}>Rusak Berat</option>
                        </select>
                    </div>
                </div>

                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label for="lokasi_penyimpanan" class="form-label">Lokasi Penyimpanan <span class="text-red-500">*</span></label>
                        <input id="lokasi_penyimpanan" name="lokasi_penyimpanan" type="text"
                               value="{{ old('lokasi_penyimpanan', $unit->lokasi_penyimpanan) }}"
                               class="form-input" required />
                    </div>

                    <div class="form-group">
                        <label for="tahun_pengadaan" class="form-label">Tahun Pengadaan</label>
                        <input id="tahun_pengadaan" name="tahun_pengadaan" type="number"
                               value="{{ old('tahun_pengadaan', $unit->tahun_pengadaan) }}"
                               min="2000" max="{{ date('Y') }}"
                               class="form-input"/>
                    </div>
                </div>

                <div class="form-group">
                    <label for="harga_perolehan" class="form-label">Harga Perolehan (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">Rp</span>
                        <input id="harga_perolehan" name="harga_perolehan" type="number" step="1000"
                               value="{{ old('harga_perolehan', $unit->harga_perolehan) }}"
                               class="form-input pl-9"/>
                    </div>
                </div>

                <div class="form-group">
                    <label for="catatan" class="form-label">Catatan</label>
                    <textarea id="catatan" name="catatan" rows="3"
                              class="form-textarea" placeholder="Catatan khusus tentang unit ini...">{{ old('catatan', $unit->catatan) }}</textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('units.show', [$product, $unit]) }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
