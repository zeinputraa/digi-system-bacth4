@extends('layouts.app')

@php $pageTitle = 'Dashboard'; @endphp

@section('content')
{{-- ============================================================
     DASHBOARD OPERASIONAL — Admin / Staff
     ============================================================ --}}
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard Operasional</h1>
            <p class="page-subtitle">Ringkasan inventaris dan aktivitas hari ini</p>
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

    {{-- ===== STAT CARDS ===== --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        {{-- Total Unit --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Total Unit</p>
                    <p class="stat-card-value">{{ $stats['total_unit'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">{{ $stats['total_barang'] }} jenis barang</p>
                </div>
                <div class="stat-card-icon bg-blue-50">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Unit Tersedia --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Unit Tersedia</p>
                    <p class="stat-card-value text-emerald-600">{{ $stats['unit_tersedia'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">siap dipinjam</p>
                </div>
                <div class="stat-card-icon bg-emerald-50">
                    <svg class="w-5 h-5 text-emerald-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Sedang Dipinjam --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Sedang Dipinjam</p>
                    <p class="stat-card-value text-blue-600">{{ $stats['sedang_dipinjam'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">aktif digunakan</p>
                </div>
                <div class="stat-card-icon bg-blue-50">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
            </div>
        </div>

        {{-- Bermasalah --}}
        <div class="stat-card">
            <div class="flex items-start justify-between">
                <div>
                    <p class="stat-card-label">Bermasalah</p>
                    <p class="stat-card-value text-orange-600">{{ $stats['unit_bermasalah'] }}</p>
                    <p class="text-xs text-gray-400 mt-1">maintenance / hilang</p>
                </div>
                <div class="stat-card-icon bg-orange-50">
                    <svg class="w-5 h-5 text-orange-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== GRAFIK + APPROVAL ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Grafik Peminjaman --}}
        <div class="card lg:col-span-2">
            <div class="card-header">
                <p class="card-title">Aktivitas Peminjaman</p>
                <span class="text-xs text-gray-400 font-semibold bg-gray-100 rounded-md px-2 py-0.5">Tahun {{ date('Y') }}</span>
            </div>
            <div class="card-body">
                <canvas id="borrowingChart" height="220"></canvas>
            </div>
        </div>

        {{-- Pengajuan Menunggu Approval --}}
        <div class="card">
            <div class="card-header">
                <p class="card-title">Menunggu Approval</p>
                <span class="badge badge-diajukan">{{ $pendingList->count() }}</span>
            </div>
            <div class="divide-y divide-gray-50 max-h-[250px] overflow-y-auto">
                @forelse($pendingList as $p)
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 truncate">{{ $p->borrower->name }}</p>
                            <p class="text-xs text-gray-400 truncate">
                                {{ $p->details->map(fn($d) => optional($d->product)->nama_barang)->filter()->unique()->implode(', ') }} · {{ $p->tanggal_pengajuan->format('d M Y') }}
                            </p>
                        </div>
                        <div class="flex items-center gap-1 shrink-0">
                            @if($p->fifo_override)
                                <span class="badge badge-warning text-[10px]" title="{{ $p->alasan_override }}">Override</span>
                            @endif
                            <a href="{{ route('borrowings.show', $p->id) }}" class="btn btn-sm btn-primary">Proses</a>
                        </div>
                    </div>
                @empty
                    <div class="px-5 py-6 text-center text-xs text-gray-400">Tidak ada pengajuan menunggu approval.</div>
                @endforelse
                <div class="px-5 py-3">
                    <a href="{{ route('borrowings.index') }}" class="text-sm text-telkom-600 hover:text-telkom-700 font-medium">
                        Lihat semua pengajuan →
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== ALERT STOK MINIMUM + KATEGORI ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">

        {{-- Alert Stok Minimum --}}
        <div class="card">
            <div class="card-header bg-white border-b border-gray-100">
                <p class="card-title flex items-center gap-2">
                    <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    Stok Di Bawah Minimum
                </p>
                <span class="badge badge-ditolak text-[10px]">{{ $lowStocks->count() }} Produk</span>
            </div>
            <div class="overflow-x-auto max-h-[300px] overflow-y-auto">
                <table class="table">
                    <thead>
                        <tr class="bg-gray-50/50">
                            <th class="py-2.5 text-[10px] text-gray-500 font-semibold uppercase tracking-wider pl-5">Nama Barang</th>
                            <th class="py-2.5 text-[10px] text-gray-500 font-semibold uppercase tracking-wider text-center">Tersedia</th>
                            <th class="py-2.5 text-[10px] text-gray-500 font-semibold uppercase tracking-wider text-center pr-5">Minimum</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($lowStocks as $s)
                            <tr class="hover:bg-gray-50/50 transition">
                                <td class="py-3 pl-5">
                                    <p class="text-sm font-semibold text-gray-800">{{ $s->nama_barang }}</p>
                                    <p class="text-xs text-gray-400 mt-0.5">{{ $s->category?->nama_kategori ?? '—' }}</p>
                                </td>
                                <td class="py-3 text-center">
                                    @if($s->units_count == 0)
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-bold bg-red-100 text-red-800">
                                            Habis
                                        </span>
                                    @else
                                        <span class="text-sm font-bold text-red-600">
                                            {{ $s->units_count }} Unit
                                        </span>
                                    @endif
                                </td>
                                <td class="py-3 text-center pr-5">
                                    <span class="text-xs font-semibold text-gray-500 bg-gray-100 rounded-md px-2 py-0.5">
                                        {{ $s->stok_minimum }} Unit
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="3" class="text-center text-gray-400 py-8 text-xs">Semua stok berada dalam batas aman.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Ringkasan Kategori --}}
        <div class="card">
            <div class="card-header bg-white border-b border-gray-100">
                <p class="card-title">Ringkasan per Kategori</p>
                <a href="{{ route('categories.index') }}" class="text-xs text-telkom-600 font-semibold hover:underline">Kelola Kategori</a>
            </div>
            <div class="p-5 max-h-[300px] overflow-y-auto">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @forelse($categoryStats as $c)
                        @php
                            $percentage = $c['total'] > 0 ? round(($c['tersedia'] / $c['total']) * 100) : 0;
                            // Dynamic color classes based on availability percentage
                            $bgProgress = 'bg-emerald-500';
                            $textClass = 'text-emerald-700';
                            $bgClass = 'bg-emerald-50';
                            if ($percentage < 30) {
                                $bgProgress = 'bg-red-500';
                                $textClass = 'text-red-700';
                                $bgClass = 'bg-red-50';
                            } elseif ($percentage < 70) {
                                $bgProgress = 'bg-amber-500';
                                $textClass = 'text-amber-700';
                                $bgClass = 'bg-amber-50';
                            }
                        @endphp
                        <div class="p-4 rounded-xl border border-gray-100 bg-white shadow-sm flex flex-col justify-between space-y-3">
                            <div class="flex items-start justify-between">
                                <div class="min-w-0">
                                    <h4 class="text-xs font-bold text-gray-800 truncate" title="{{ $c['nama'] }}">{{ $c['nama'] }}</h4>
                                    <p class="text-[10px] text-gray-400 mt-0.5">Tersedia / Total Unit</p>
                                </div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold {{ $bgClass }} {{ $textClass }}">
                                    {{ $percentage }}%
                                </span>
                            </div>
                            <div class="space-y-1">
                                <div class="w-full bg-gray-100 rounded-full h-1.5 overflow-hidden">
                                    <div class="{{ $bgProgress }} h-1.5 rounded-full" style="width: {{ $percentage }}%"></div>
                                </div>
                                <div class="flex justify-between items-center text-[10px] text-gray-500 font-semibold">
                                    <span>{{ $c['tersedia'] }} Tersedia</span>
                                    <span>{{ $c['total'] }} Total</span>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-span-2 text-center text-gray-400 py-8 text-xs">Belum ada kategori terdaftar.</div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('borrowingChart');
    if (!ctx) return;

    // Set global font family to Poppins
    Chart.defaults.font.family = 'Poppins';
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#64748B';

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'],
            datasets: [
                {
                    label: 'Pengajuan Baru',
                    data: @json($monthlyBorrowings),
                    backgroundColor: '#E10600',
                    borderRadius: 6,
                    borderSkipped: 'bottom',
                    barThickness: 12,
                },
                {
                    label: 'Aset Kembali',
                    data: @json($monthlyReturns),
                    backgroundColor: '#94A3B8',
                    borderRadius: 6,
                    borderSkipped: 'bottom',
                    barThickness: 12,
                },
            ],
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { 
                    position: 'top', 
                    align: 'end',
                    labels: { 
                        usePointStyle: true, 
                        boxWidth: 6,
                        padding: 15,
                        font: {
                            weight: '500'
                        }
                    } 
                },
                tooltip: {
                    backgroundColor: '#1E293B',
                    padding: 10,
                    bodyFont: { family: 'Poppins' },
                    titleFont: { family: 'Poppins', weight: '600' }
                }
            },
            scales: {
                x: { 
                    grid: { display: false },
                    border: { display: false }
                },
                y: { 
                    beginAtZero: true, 
                    grid: { color: '#F1F5F9' },
                    border: { display: false }
                },
            },
        },
    });
});
</script>
@endpush
