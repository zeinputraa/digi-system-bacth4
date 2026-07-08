@extends('layouts.app')

@php $pageTitle = 'Preview Laporan ' . ($archive->kode_arsip ?? 'LKP'); @endphp

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb --}}
    <nav class="breadcrumb">
        <a href="{{ route('reports.index') }}" class="hover:text-gray-600">Laporan Periodik</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="breadcrumb-current">Preview: Laporan #{{ $archive->id }}</span>
    </nav>

    {{-- Header --}}
    <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-3">
        <div>
            <h1 class="page-title text-gray-900">Laporan Aktivitas Inventaris ({{ ucfirst($archive->jenis->value ?? $archive->jenis) }})</h1>
            <p class="page-subtitle">Periode: <strong>{{ \Carbon\Carbon::parse($archive->periode_mulai)->format('d M Y') }} - {{ \Carbon\Carbon::parse($archive->periode_selesai)->format('d M Y') }}</strong> · Dibuat oleh: <strong>{{ $archive->generatedBy->name }}</strong></p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('reports.download', $archive->id) }}?format=pdf" target="_blank" class="btn-primary flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
                Unduh PDF
            </a>
            <a href="{{ route('reports.download', $archive->id) }}?format=excel" target="_blank" class="btn-secondary">
                Unduh Excel
            </a>
        </div>
    </div>

    {{-- Stats Cards --}}
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="card p-4 text-center">
            <p class="text-xs text-gray-400 font-semibold uppercase">Total Peminjaman</p>
            <p class="text-2xl font-bold text-gray-900 mt-1">{{ $aktivitas['masuk'] }}</p>
            <span class="text-[10px] text-emerald-600 font-semibold">pengajuan masuk</span>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-gray-400 font-semibold uppercase">Selesai Tepat Waktu</p>
            <p class="text-2xl font-bold text-emerald-600 mt-1">{{ $aktivitas['selesai'] }}</p>
            <span class="text-[10px] text-gray-400 font-semibold">unit dikembalikan</span>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-gray-400 font-semibold uppercase">Total Terlambat</p>
            <p class="text-2xl font-bold text-red-600 mt-1">{{ $aktivitas['terlambat'] }}</p>
            <span class="text-[10px] text-red-600 font-semibold">dalam rentang waktu</span>
        </div>
        <div class="card p-4 text-center">
            <p class="text-xs text-gray-400 font-semibold uppercase">Insiden Laporan</p>
            <p class="text-2xl font-bold text-orange-600 mt-1">{{ $insiden['rusak_ringan'] + $insiden['rusak_berat'] + $insiden['hilang'] }}</p>
            <span class="text-[10px] text-orange-600 font-semibold">rusak / hilang</span>
        </div>
    </div>

    {{-- Ringkasan & Chart --}}
    @php
        $labels = [];
        $values = [];
        foreach ($snapshot as $s) {
            $labels[] = $s['kategori'];
            $values[] = $s['total'];
        }
    @endphp
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Chart --}}
        <div class="card lg:col-span-2">
            <div class="card-header"><p class="card-title">Kategori Barang Terpopuler</p></div>
            <div class="card-body">
                <canvas id="categoryPopularityChart" height="220"></canvas>
            </div>
        </div>

        {{-- Ringkasan Ganti Rugi / Kehilangan --}}
        <div class="card">
            <div class="card-header"><p class="card-title">Kerugian & Ganti Rugi Aset</p></div>
            <div class="card-body space-y-4">
                <div class="bg-red-50 text-red-800 rounded-xl p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-red-600">Total Nilai Write-off</p>
                    <p class="text-2xl font-bold mt-1">Rp {{ number_format($kerugian['total_kerugian'], 0, ',', '.') }}</p>
                    <div class="space-y-1 mt-2">
                        @forelse($perhatian->where('status', \App\Enums\StatusUnit::HilangPermanen) as $p)
                            <p class="text-[10px] text-red-500 font-medium">- {{ $p->product->nama_barang }} ({{ $p->kode_unit }})</p>
                        @empty
                            <p class="text-[10px] text-gray-500">Tidak ada unit di-write-off dalam periode ini.</p>
                        @endforelse
                    </div>
                </div>

                <div class="bg-emerald-50 text-emerald-800 rounded-xl p-4">
                    <p class="text-xs font-semibold uppercase tracking-wider text-emerald-600">Unit dalam Maintenance</p>
                    <div class="space-y-1 mt-2">
                        @forelse($perhatian->where('status', \App\Enums\StatusUnit::Maintenance) as $p)
                            <p class="text-[10px] text-emerald-600 font-medium">- {{ $p->product->nama_barang }} ({{ $p->kode_unit }})</p>
                        @empty
                            <p class="text-[10px] text-gray-500">Tidak ada unit dalam maintenance dalam periode ini.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Detail Log Peminjaman --}}
    <div class="card">
        <div class="card-header"><p class="card-title">Log Peminjaman dalam Periode Laporan</p></div>
        <div class="table-wrapper border-0 shadow-none">
            <table class="table">
                <thead>
                    <tr>
                        <th>Kode</th>
                        <th>Peminjam</th>
                        <th>Barang</th>
                        <th>Tgl Pinjam</th>
                        <th>Tgl Kembali</th>
                        <th>SLA / Keterlambatan</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50 text-xs">
                    @forelse($borrowings as $b)
                        @php
                            $isLate = false;
                            foreach ($b->details as $d) {
                                if ($d->status->value === 'terlambat') {
                                    $isLate = true;
                                }
                            }
                        @endphp
                        <tr>
                            <td class="font-mono font-semibold">{{ $b->kode_peminjaman }}</td>
                            <td>{{ $b->borrower->name }}</td>
                            <td>{{ $b->details->map(fn($d) => optional($d->product)->nama_barang)->filter()->unique()->implode(', ') }}</td>
                            <td class="text-gray-500 text-xs">{{ $b->tanggal_pinjam_rencana?->format('d M Y') }}</td>
                            <td class="text-gray-500 text-xs">{{ $b->tanggal_kembali_rencana?->format('d M Y') }}</td>
                            <td>
                                @if($b->status->value === 'selesai')
                                    @if($isLate)
                                        <span class="text-amber-600 font-semibold">Selesai (Terlambat)</span>
                                    @else
                                        <span class="text-emerald-600 font-semibold">Tepat Waktu</span>
                                    @endif
                                @elseif($isLate)
                                    <span class="text-red-600 font-bold">Terlambat</span>
                                @else
                                    <span class="text-blue-600 font-semibold">Aktif</span>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center text-gray-400 py-6 text-xs">Tidak ada log peminjaman dalam periode ini.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const ctx = document.getElementById('categoryPopularityChart');
    if (!ctx) return;
    new Chart(ctx, {
        type: 'doughnut',
        data: {
            labels: @json($labels),
            datasets: [{
                data: @json($values),
                backgroundColor: ['#E10600', '#2563EB', '#8B5CF6', '#10B981', '#F59E0B'],
                borderWidth: 2,
                borderColor: '#ffffff'
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'right', labels: { boxWidth: 12 } }
            }
        }
    });
});
</script>
@endpush
