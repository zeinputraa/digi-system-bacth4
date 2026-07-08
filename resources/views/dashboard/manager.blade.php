@extends('layouts.app')

@php $pageTitle = 'Dashboard'; @endphp

@section('content')
{{-- ============================================================
     DASHBOARD MANAGER — Read-only, identik operasional
     ============================================================ --}}
<div class="space-y-6">

    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Dashboard Manager</h1>
            <p class="page-subtitle">Pantau aktivitas inventaris secara keseluruhan</p>
        </div>
        {{-- No action buttons for manager --}}
        <span class="badge badge-manager">Read Only</span>
    </div>

    {{-- STAT CARDS --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
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

    {{-- GRAFIK + AUDIT TRAIL --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="card lg:col-span-2">
            <div class="card-header">
                <p class="card-title">Aktivitas Peminjaman</p>
                <span class="text-xs text-gray-400">Tahun {{ date('Y') }}</span>
            </div>
            <div class="card-body">
                <canvas id="borrowingChartMgr" height="220"></canvas>
            </div>
        </div>

        {{-- Audit: Override FIFO --}}
        <div class="card">
            <div class="card-header">
                <p class="card-title">Audit Override FIFO</p>
                <span class="badge badge-warning">{{ $overrides->count() }} kali</span>
            </div>
            <div class="divide-y divide-gray-50 max-h-[250px] overflow-y-auto">
                @forelse($overrides as $o)
                    <div class="px-5 py-3">
                        <p class="text-sm font-medium text-gray-800">{{ $o->borrower->name }}</p>
                        <p class="text-xs text-gray-500 truncate">"{{ $o->alasan_override }}"</p>
                        <p class="text-[10px] text-gray-400 mt-0.5">{{ $o->approved_at?->format('d M Y') ?? '—' }}</p>
                    </div>
                @empty
                    <div class="px-5 py-6 text-center text-xs text-gray-400">Tidak ada audit override FIFO saat ini.</div>
                @endforelse
                <div class="px-5 py-3">
                    <a href="{{ route('reports.index') }}" class="text-sm text-telkom-600 hover:text-telkom-700 font-medium">
                        Lihat laporan lengkap →
                    </a>
                </div>
            </div>
        </div>
    </div>

    {{-- INSIDEN & KERUGIAN ASET --}}
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div class="card">
            <div class="card-header">
                <p class="card-title">Laporan Insiden Terbaru</p>
                <a href="{{ route('incidents.index') }}" class="text-xs text-telkom-600 font-medium">Semua →</a>
            </div>
            <div class="divide-y divide-gray-50 max-h-[300px] overflow-y-auto">
                @forelse($incidents as $inc)
                    @php
                        $badgeColor = match($inc->status->value) {
                            'menunggu_verifikasi_staff'  => 'badge-menunggu',
                            'terverifikasi_staff'        => 'badge-terverifikasi',
                            'menunggu_finalisasi_admin' => 'badge-menunggu-admin',
                            'difinalisasi_admin'         => 'badge-selesai',
                            default                      => 'badge-secondary',
                        };
                    @endphp
                    <div class="px-5 py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-gray-800 truncate">
                                {{ $inc->productUnit?->kode_unit ?? '—' }} · {{ ucfirst(str_replace('_', ' ', $inc->jenis->value)) }}
                            </p>
                            <p class="text-xs text-gray-400 truncate">Pelapor: {{ $inc->reporter->name }}</p>
                        </div>
                        <span class="badge {{ $badgeColor }} shrink-0 text-[10px]">{{ ucfirst(str_replace('_', ' ', $inc->status->value)) }}</span>
                    </div>
                @empty
                    <div class="px-5 py-6 text-center text-xs text-gray-400">Tidak ada laporan insiden saat ini.</div>
                @endforelse
            </div>
        </div>

        {{-- Kerugian Aset (Write-off) --}}
        <div class="card">
            <div class="card-header">
                <p class="card-title">Kerugian Aset (Write-off)</p>
                <span class="text-xs text-gray-400">Tahun {{ date('Y') }}</span>
            </div>
            <div class="card-body">
                <div class="flex items-center justify-between mb-4">
                    <div>
                        <p class="text-3xl font-bold text-red-600">Rp {{ number_format($totalKerugian, 0, ',', '.') }}</p>
                        <p class="text-xs text-gray-400 mt-1">{{ $writeoffs->count() }} unit di-write-off</p>
                    </div>
                </div>
                <div class="space-y-2 max-h-[190px] overflow-y-auto">
                    @forelse($writeoffs as $w)
                        <div class="flex items-center justify-between py-2 border-t border-gray-100">
                            <div class="min-w-0 mr-2">
                                <p class="text-sm text-gray-700 truncate">{{ $w->product->nama_barang }} ({{ $w->kode_unit }})</p>
                                <p class="text-xs text-gray-400">{{ $w->updated_at->format('d M Y') }}</p>
                            </div>
                            <span class="text-sm font-semibold text-red-600 shrink-0">Rp {{ number_format($w->harga_perolehan, 0, ',', '.') }}</span>
                        </div>
                    @empty
                        <div class="py-6 text-center text-xs text-gray-400 border-t border-gray-100">Belum ada kerugian aset tercatat.</div>
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
    const ctx = document.getElementById('borrowingChartMgr');
    if (!ctx) return;

    // Set global font family to Poppins
    Chart.defaults.font.family = 'Poppins';
    Chart.defaults.font.size = 11;
    Chart.defaults.color = '#64748B';

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul'],
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
