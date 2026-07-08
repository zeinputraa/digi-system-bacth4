@extends('layouts.app')

@php $pageTitle = 'Cetak Label Aset'; @endphp

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb --}}
    <nav class="breadcrumb no-print">
        <a href="{{ route('labels.pilih') }}" class="hover:text-gray-600">Pilih Unit</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="breadcrumb-current">Cetak Label Aset</span>
    </nav>

    <div class="page-header no-print">
        <div>
            <h1 class="page-title">Cetak Label Aset (Stiker QR)</h1>
            <p class="page-subtitle">Siapkan kertas stiker, lalu klik tombol cetak untuk mencetak stiker label fisik.</p>
        </div>
        <button onclick="window.print()" class="btn-primary">
            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
            </svg>
            Cetak Label
        </button>
    </div>

    {{-- Label Grid Preview --}}
    <div class="card bg-gray-50 p-6 print:p-0 print:border-0 print:shadow-none print:bg-white">
        <p class="text-xs text-gray-400 font-semibold mb-4 uppercase no-print">Preview Layout Stiker Label ({{ $units->count() }} unit)</p>

        {{-- Printable label sheet --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 bg-white p-4 border rounded-xl print:p-0 print:border-0 print:shadow-none print:grid-cols-2" id="label-sheet">
            @foreach($units as $unit)
                <div class="border-2 border-gray-900 rounded-lg p-3 flex gap-3 bg-white print:break-inside-avoid print:border-black print:mb-2">
                    {{-- QR Code side --}}
                    <div class="flex flex-col items-center justify-center shrink-0">
                        <div class="p-1 border border-gray-200 rounded bg-white">
                            {!! QrCode::size(80)->generate($unit->qr_code) !!}
                        </div>
                        <span class="text-[7px] font-mono text-gray-400 mt-1 uppercase tracking-wider">Scan Untuk Detail</span>
                    </div>

                    {{-- Brand & details --}}
                    <div class="flex-1 min-w-0 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-1.5 mb-1">
                                <div class="w-3.5 h-3.5 bg-red-600 rounded flex items-center justify-center text-white text-[8px] font-bold">D</div>
                                <span class="text-[9px] font-bold text-red-600 tracking-wider">PROPERTY OF COMPANY</span>
                            </div>
                            <p class="text-[11px] font-bold text-gray-900 truncate">{{ $unit->product->nama_barang ?? '—' }}</p>
                            @if($unit->tahun_pengadaan)
                                <p class="text-[8px] text-gray-500 mt-0.5">Tahun Pengadaan: {{ $unit->tahun_pengadaan }}</p>
                            @endif
                        </div>
                        <div class="mt-2">
                            <p class="text-[7px] text-gray-400 uppercase tracking-widest font-semibold leading-none">Nomor Inventaris</p>
                            <p class="font-mono text-xs font-extrabold text-gray-900 leading-tight">{{ $unit->kode_unit }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</div>

<style>
@media print {
    /* Sembunyikan semua elemen kecuali area print */
    .no-print, aside, header, .app-topbar, .breadcrumb, button, nav {
        display: none !important;
    }
    .app-content {
        margin-left: 0 !important;
        padding: 0 !important;
    }
    .app-page {
        padding: 0 !important;
    }
    body {
        background: white !important;
        color: black !important;
    }
    #label-sheet {
        grid-template-cols: 1fr 1fr !important;
        gap: 15px !important;
    }
}
</style>
@endsection
