@extends('layouts.app')

@php $pageTitle = 'Edit Barang'; @endphp

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
            <span class="breadcrumb-current">Edit</span>
        </nav>
        <h1 class="page-title">Edit Barang</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('products.update', $product) }}" method="POST" enctype="multipart/form-data" class="space-y-5">
                @csrf
                @method('PUT')

                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label for="kode_produk" class="form-label">Kode Produk <span class="text-red-500">*</span></label>
                        <input id="kode_produk" name="kode_produk" type="text"
                               value="{{ old('kode_produk', $product->kode_produk) }}"
                               class="form-input font-mono uppercase" required/>
                        @error('kode_produk') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="category_id" class="form-label">Kategori <span class="text-red-500">*</span></label>
                        <select id="category_id" name="category_id" class="form-select" required>
                            <option value="">-- Pilih Kategori --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}"
                                        {{ old('category_id', $product->category_id) == $cat->id ? 'selected' : '' }}>
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
                           value="{{ old('nama_barang', $product->nama_barang) }}"
                           class="form-input" required/>
                    @error('nama_barang') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-group">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3"
                              class="form-textarea">{{ old('deskripsi', $product->deskripsi) }}</textarea>
                    @error('deskripsi') <p class="form-error">{{ $message }}</p> @enderror
                </div>

                <div class="form-row grid-cols-2">
                    <div class="form-group">
                        <label for="stok_minimum" class="form-label">Stok Minimum</label>
                        <input id="stok_minimum" name="stok_minimum" type="number" min="1"
                               value="{{ old('stok_minimum', $product->stok_minimum) }}"
                               class="form-input"/>
                        @error('stok_minimum') <p class="form-error">{{ $message }}</p> @enderror
                    </div>

                    <div class="form-group">
                        <label for="foto" class="form-label">Ganti Foto</label>
                        @if($product->foto)
                            <div class="mb-2">
                                <img src="{{ Storage::url($product->foto) }}" alt="Foto saat ini"
                                     class="h-16 w-16 object-cover rounded-lg border border-gray-200"/>
                                <p class="text-xs text-gray-400 mt-1">Foto saat ini</p>
                            </div>
                        @endif
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
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('products.show', $product) }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
