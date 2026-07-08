@extends('layouts.app')

@php $pageTitle = 'Katalog Barang'; @endphp

@section('content')
<div class="space-y-5">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Katalog Barang</h1>
            <p class="page-subtitle">
                {{ $products->total() }} jenis barang tersedia dalam inventaris
            </p>
        </div>
        @if(auth()->user()->hasRole('admin', 'staff'))
            <a href="{{ route('products.create') }}" class="btn-primary">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                </svg>
                Tambah Barang
            </a>
        @endif
    </div>

    {{-- Search Bar --}}
    <form method="GET" action="{{ route('products.index') }}" class="flex gap-3">
        <div class="relative flex-1 max-w-sm">
            <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
            </svg>
            <input name="search" type="text" placeholder="Cari nama barang atau kode..."
                   value="{{ request('search') }}"
                   class="form-input pl-9"/>
        </div>
        <button type="submit" class="btn-secondary">Cari</button>
        @if(request('search'))
            <a href="{{ route('products.index') }}" class="btn-ghost text-gray-500">Reset</a>
        @endif
    </form>

    {{-- Grid Produk --}}
    @if($products->isEmpty())
        <div class="card">
            <div class="card-body empty-state">
                <svg class="empty-state-icon" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
                <p class="empty-state-title">Tidak ada barang ditemukan</p>
                <p class="empty-state-desc">Coba kata kunci berbeda atau tambah barang baru</p>
                @if(auth()->user()->hasRole('admin', 'staff'))
                    <a href="{{ route('products.create') }}" class="btn-primary btn-sm mt-3">Tambah Barang</a>
                @endif
            </div>
        </div>
    @else
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            @foreach($products as $product)
                @php
                    $pct = $product->total_unit > 0
                        ? round(($product->unit_tersedia / $product->total_unit) * 100)
                        : 0;
                    $stokClass = $pct >= 50 ? 'bg-emerald-500'
                        : ($pct >= 25 ? 'bg-amber-400' : 'bg-red-500');
                    $badgeClass = $product->unit_tersedia > 0 ? 'badge-tersedia' : 'badge-ditolak';
                    $badgeText = $product->unit_tersedia > 0 ? 'Tersedia' : 'Habis';
                @endphp
                <div class="card flex flex-col hover:shadow-card-hover transition-shadow duration-200 group">
                    {{-- Foto --}}
                    <a href="{{ route('products.show', $product) }}" class="block overflow-hidden">
                        <div class="h-40 bg-gray-100 flex items-center justify-center overflow-hidden rounded-t-xl group-hover:opacity-90 transition">
                            @if($product->foto)
                                <img src="{{ Storage::url($product->foto) }}"
                                     alt="{{ $product->nama_barang }}"
                                     class="w-full h-full object-cover"/>
                            @else
                                <svg class="w-16 h-16 text-gray-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                </svg>
                            @endif
                        </div>
                    </a>

                    {{-- Info --}}
                    <div class="p-4 flex-1 flex flex-col">
                        <div class="flex items-start justify-between gap-1 mb-1">
                            <a href="{{ route('products.show', $product) }}"
                               class="text-sm font-semibold text-gray-900 hover:text-telkom-600 leading-tight line-clamp-2">
                                {{ $product->nama_barang }}
                            </a>
                            <span class="badge {{ $badgeClass }} shrink-0 text-[10px]">{{ $badgeText }}</span>
                        </div>
                        <p class="text-xs text-gray-400 font-mono mb-2">{{ $product->kode_produk }}</p>
                        <p class="text-xs text-gray-500 mb-3">{{ $product->category->nama_kategori ?? '—' }}</p>

                        {{-- Stok bar --}}
                        <div class="mt-auto">
                            <div class="flex items-center justify-between text-xs text-gray-500 mb-1">
                                <span>Stok tersedia</span>
                                <span class="font-semibold">{{ $product->unit_tersedia }}/{{ $product->total_unit }} unit</span>
                            </div>
                            <div class="w-full bg-gray-100 rounded-full h-1.5">
                                <div class="{{ $stokClass }} h-1.5 rounded-full transition-all duration-300"
                                     style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    </div>

                    {{-- Actions --}}
                    <div class="px-4 py-3 border-t border-gray-100 flex gap-2">
                        <a href="{{ route('products.show', $product) }}" class="btn-sm btn-secondary flex-1 justify-center">
                            Detail
                        </a>
                        @if(auth()->user()->hasRole('karyawan'))
                            <a href="{{ route('borrowings.create') }}?produk={{ $product->id }}"
                               class="btn-sm btn-primary flex-1 justify-center">
                                Pinjam
                            </a>
                        @elseif(auth()->user()->hasRole('admin', 'staff'))
                            <a href="{{ route('products.edit', $product) }}" class="btn-sm btn-ghost">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                                </svg>
                            </a>
                        @endif
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Pagination --}}
        @if($products->hasPages())
            <div>
                {{ $products->withQueryString()->links() }}
            </div>
        @endif
    @endif
</div>
@endsection
