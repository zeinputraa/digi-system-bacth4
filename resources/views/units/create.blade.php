@extends('layouts.app')

@php $pageTitle = 'Tambah Unit'; @endphp

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
            <span class="breadcrumb-current">Tambah Unit</span>
        </nav>
        <h1 class="page-title">Tambah Unit Baru</h1>
        <p class="page-subtitle">Produk: <strong>{{ $product->nama_barang }}</strong> ({{ $product->kode_produk }})</p>
    </div>

    {{-- Preview kode unit yang akan digenerate --}}
    <div class="alert-info mb-4">
        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <div>
            <p class="font-medium text-sm">Kode unit berikutnya akan digenerate otomatis:</p>
            <p class="font-mono text-base font-bold mt-0.5">
                {{ $product->kode_produk }}-U{{ str_pad($product->units->count() + 1, 2, '0', STR_PAD_LEFT) }}
            </p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('units.store', $product) }}" method="POST" class="space-y-5">
                @csrf

                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label for="lokasi_penyimpanan" class="form-label">Lokasi Penyimpanan <span class="text-red-500">*</span></label>
                        <input id="lokasi_penyimpanan" name="lokasi_penyimpanan" type="text"
                               value="{{ old('lokasi_penyimpanan') }}"
                               class="form-input" required placeholder="Contoh: Gudang Lt.2 Rak A"/>
                        @error('lokasi_penyimpanan') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="tahun_pengadaan" class="form-label">Tahun Pengadaan</label>
                        <input id="tahun_pengadaan" name="tahun_pengadaan" type="number"
                               value="{{ old('tahun_pengadaan', date('Y')) }}"
                               min="2000" max="{{ date('Y') }}"
                               class="form-input"/>
                        @error('tahun_pengadaan') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="harga_perolehan" class="form-label">Harga Perolehan (Rp)</label>
                    <div class="relative">
                        <span class="absolute left-3 top-1/2 -translate-y-1/2 text-sm text-gray-400">Rp</span>
                        <input id="harga_perolehan" name="harga_perolehan" type="number" step="1000"
                               value="{{ old('harga_perolehan') }}"
                               class="form-input pl-9" placeholder="0"/>
                    </div>
                    @error('harga_perolehan') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="catatan" class="form-label">Catatan</label>
                    <textarea id="catatan" name="catatan" rows="3"
                              class="form-textarea" placeholder="Catatan khusus tentang unit ini...">{{ old('catatan') }}</textarea>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-success">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                        </svg>
                        Tambah Unit
                    </button>
                    <a href="{{ route('products.show', $product) }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
