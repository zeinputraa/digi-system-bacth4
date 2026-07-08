@extends('layouts.app')

@php $pageTitle = 'Serah Terima Peminjaman'; @endphp

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb --}}
    <nav class="breadcrumb">
        <a href="{{ route('borrowings.index') }}" class="hover:text-gray-600">Peminjaman</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <a href="{{ route('borrowings.show', $borrowing->id) }}" class="hover:text-gray-600">Detail {{ $borrowing->kode_peminjaman }}</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="breadcrumb-current">Serah Terima</span>
    </nav>

    <div class="page-header mb-0">
        <div>
            <h1 class="page-title">Serah Terima Aset</h1>
            <p class="page-subtitle">Scan QR code fisik atau masukkan kode unit untuk mengonfirmasi penyerahan kepada peminjam.</p>
        </div>
    </div>

    @if (session('success'))
        <div class="alert-success text-sm p-4 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div class="alert-danger text-sm p-4 bg-red-50 text-red-800 border border-red-200 rounded-lg">
            {{ session('error') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left Column: Input Form --}}
        <div class="lg:col-span-2 space-y-4">
            @if ($borrowing->status === \App\Enums\StatusBorrowing::Berjalan)
                <div class="card bg-emerald-50 border border-emerald-200 rounded-xl p-6 text-center space-y-3">
                    <div class="w-12 h-12 bg-emerald-100 rounded-full flex items-center justify-center mx-auto text-emerald-600">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <h2 class="text-lg font-bold text-emerald-800">Serah Terima Selesai</h2>
                    <p class="text-sm text-emerald-600">Peminjaman ini sudah aktif dan seluruh barang telah diserahkan.</p>
                    <a href="{{ route('borrowings.show', $borrowing->id) }}" class="btn btn-secondary inline-block mt-2">
                        Kembali ke Detail
                    </a>
                </div>
            @else
                <div class="card">
                    <div class="card-header bg-gray-50">
                        <p class="card-title">Konfirmasi Kode Unit</p>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('borrowings.confirmHandover', $borrowing->id) }}">
                            @csrf
                            <div>
                                <x-input-label for="kode_unit" value="Masukkan Kode Unit Fisik" />
                                <x-text-input id="kode_unit" name="kode_unit" type="text" required autofocus class="mt-1 block w-full" placeholder="Contoh: LAP-001-U01" />
                                <x-input-error :messages="$errors->get('kode_unit')" class="mt-2" />
                            </div>
                            <div class="flex items-center gap-3 mt-5">
                                <x-primary-button>Konfirmasi Unit</x-primary-button>
                                <a href="{{ route('borrowings.show', $borrowing->id) }}" class="btn-secondary">Batal</a>
                            </div>
                        </form>
                    </div>
                </div>
            @endif
        </div>

        {{-- Right Column: Checklist Unit --}}
        <div class="space-y-4">
            <div class="card">
                <div class="card-header">
                    <p class="card-title">Daftar Unit Peminjaman</p>
                </div>
                
                @php
                    $belum = $borrowing->details->where('status', \App\Enums\StatusBorrowingDetail::Disetujui);
                    $sudah = $borrowing->details->where('status', \App\Enums\StatusBorrowingDetail::Dipinjam);
                @endphp

                <div class="divide-y divide-gray-100">
                    {{-- Belum Diserahkan --}}
                    @if ($belum->isNotEmpty())
                        <div class="p-4 bg-gray-50/50">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">Belum Diserahkan ({{ $belum->count() }})</p>
                            <div class="space-y-2">
                                @foreach($belum as $d)
                                    <div class="flex items-center justify-between p-3 bg-white rounded-lg border border-gray-100 shadow-sm">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-gray-800 truncate">{{ $d->product->nama_barang }}</p>
                                            <p class="font-mono text-xs text-gray-500 mt-0.5">
                                                @if($d->productUnit)
                                                    Target: {{ $d->productUnit->kode_unit }}
                                                @else
                                                    Menunggu unit di-scan
                                                @endif
                                            </p>
                                        </div>
                                        <span class="badge badge-diajukan text-[10px]">Menunggu</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    {{-- Sudah Diserahkan --}}
                    @if ($sudah->isNotEmpty())
                        <div class="p-4">
                            <p class="text-xs font-semibold text-emerald-600 uppercase tracking-wider mb-2">Sudah Diserahkan ({{ $sudah->count() }})</p>
                            <div class="space-y-2">
                                @foreach($sudah as $d)
                                    <div class="flex items-center justify-between p-3 bg-emerald-50/30 rounded-lg border border-emerald-100 shadow-sm">
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-emerald-800 truncate">{{ $d->product->nama_barang }}</p>
                                            <p class="font-mono text-xs text-emerald-600 mt-0.5">
                                                {{ $d->productUnit ? $d->productUnit->kode_unit : '—' }}
                                            </p>
                                            @if($d->tanggal_pinjam_aktual)
                                                <p class="text-[10px] text-gray-400 mt-0.5">Diserahkan: {{ $d->tanggal_pinjam_aktual->format('d M Y H:i') }}</p>
                                            @endif
                                        </div>
                                        <span class="badge badge-tersedia text-[10px]">✓ Diserahkan</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>

                <div class="card-body border-t border-gray-100 space-y-3">
                    <div class="bg-gray-50 rounded-xl p-3 space-y-2 text-xs">
                        <div class="flex justify-between">
                            <span class="text-gray-500">Peminjam:</span>
                            <span class="font-medium text-gray-800">{{ $borrowing->borrower->name }}</span>
                        </div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Email:</span>
                            <span class="font-medium text-gray-800">{{ $borrowing->borrower->email }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
@endsection
