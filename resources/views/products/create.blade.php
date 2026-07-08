@extends('layouts.app')

@php $pageTitle = 'Tambah Barang'; @endphp

@section('content')
<div class="max-w-2xl">
    <div class="mb-5">
        <nav class="breadcrumb mb-2">
            <a href="{{ route('products.index') }}" class="hover:text-gray-600">Barang</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="breadcrumb-current">Tambah</span>
        </nav>
        <h1 class="page-title">Tambah Barang Baru</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('products.store') }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf

                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label for="kode_produk" class="form-label">Kode Produk <span class="text-red-500">*</span></label>
                        <input id="kode_produk" name="kode_produk" type="text"
                               value="{{ old('kode_produk') }}"
                               class="form-input font-mono uppercase" required
                               placeholder="Contoh: LAP-001"/>
                        <p class="form-hint">Kode unik untuk identifikasi barang</p>
                        @error('kode_produk') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Kategori <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                        {{ old('category_id') == $cat->id ? 'selected' : '' }}>
                                    {{ $cat->nama_kategori }}
                                </option>
                            @endforeach
                        </select>
                        @error('category_id') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="form-group">
                    <label for="nama_barang" class="form-label">Nama Barang <span class="text-red-500">*</span></label>
                    <input id="nama_barang" name="nama_barang" type="text"
                           value="{{ old('nama_barang') }}"
                           class="form-input" required placeholder="Contoh: Laptop Dell XPS 15"/>
                    @error('nama_barang') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3"
                              class="form-textarea" placeholder="Deskripsi singkat dan spesifikasi barang...">{{ old('deskripsi') }}</textarea>
                    @error('deskripsi') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label for="stok_minimum" class="form-label">Stok Minimum <span class="text-red-500">*</span></label>
                        <input id="stok_minimum" name="stok_minimum" type="number" min="1"
                               value="{{ old('stok_minimum', 1) }}"
                               class="form-input"/>
                        <p class="form-hint">Alert muncul jika stok tersedia di bawah angka ini</p>
                        @error('stok_minimum') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="foto" class="form-label">Foto Barang</label>
                        <input id="foto" name="foto" type="file" accept="image/*"
                               class="form-input file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-gray-100 file:text-gray-700"/>
                        @error('foto') <p class="form-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Barang
                    </button>
                    <a href="{{ route('products.index') }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
