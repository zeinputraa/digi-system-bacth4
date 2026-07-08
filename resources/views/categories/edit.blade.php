@extends('layouts.app')

@php $pageTitle = 'Edit Kategori'; @endphp

@section('content')
<div class="max-w-lg">
    <div class="mb-5">
        <nav class="breadcrumb mb-2">
            <a href="{{ route('categories.index') }}" class="hover:text-gray-600">Kategori</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="breadcrumb-current">Edit: {{ $category->nama_kategori }}</span>
        </nav>
        <h1 class="page-title">Edit Kategori</h1>
    </div>

    <div class="card">
        <div class="card-body">
            <form action="{{ route('categories.update', $category) }}" method="POST" class="space-y-4">
                @csrf
                @method('PUT')

                <div class="form-group">
                    <label for="nama_kategori" class="form-label">Nama Kategori <span class="text-red-500">*</span></label>
                    <input id="nama_kategori" name="nama_kategori" type="text"
                           value="{{ old('nama_kategori', $category->nama_kategori) }}"
                           class="form-input" required/>
                    @error('nama_kategori')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="form-group">
                    <label for="deskripsi" class="form-label">Deskripsi</label>
                    <textarea id="deskripsi" name="deskripsi" rows="3"
                              class="form-textarea">{{ old('deskripsi', $category->deskripsi) }}</textarea>
                    @error('deskripsi')
                        <p class="form-error">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <button type="submit" class="btn-primary">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                        Simpan Perubahan
                    </button>
                    <a href="{{ route('categories.index') }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
