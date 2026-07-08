@extends('layouts.app')

@php $pageTitle = 'Laporan Periodik'; @endphp

@section('content')
<div class="space-y-6" x-data="{
    reportType: 'bulanan',
    bulan: '{{ date('Y-m') }}',
    kuartal: '3',
    kuartalTahun: '{{ date('Y') }}',
    tahun: '{{ date('Y') }}',
    kustomMulai: '{{ date('Y-m-d') }}',
    kustomSelesai: '{{ date('Y-m-d') }}',

    get periodeMulai() {
        if (this.reportType === 'bulanan') {
            return this.bulan + '-01';
        }
        if (this.reportType === 'kuartalan') {
            const startMonth = (parseInt(this.kuartal) - 1) * 3 + 1;
            return this.kuartalTahun + '-' + String(startMonth).padStart(2, '0') + '-01';
        }
        if (this.reportType === 'tahunan') {
            return this.tahun + '-01-01';
        }
        return this.kustomMulai;
    },
    get periodeSelesai() {
        if (this.reportType === 'bulanan') {
            const year = parseInt(this.bulan.split('-')[0]);
            const month = parseInt(this.bulan.split('-')[1]);
            const lastDay = new Date(year, month, 0).getDate();
            return this.bulan + '-' + String(lastDay).padStart(2, '0');
        }
        if (this.reportType === 'kuartalan') {
            const endMonth = parseInt(this.kuartal) * 3;
            const lastDay = new Date(parseInt(this.kuartalTahun), endMonth, 0).getDate();
            return this.kuartalTahun + '-' + String(endMonth).padStart(2, '0') + '-' + String(lastDay).padStart(2, '0');
        }
        if (this.reportType === 'tahunan') {
            return this.tahun + '-12-31';
        }
        return this.kustomSelesai;
    },
    formatDate(dateStr) {
        if (!dateStr) return '—';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        return date.getDate() + ' ' + months[date.getMonth()] + ' ' + date.getFullYear();
    },
    submitForm() {
        this.$refs.reportForm.submit();
    }
}">
    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Laporan Periodik</h1>
            <p class="page-subtitle">Daftar arsip laporan, audit valuasi aset, dan insiden kerusakan.</p>
        </div>
    </div>

    {{-- Stats Cards Grid --}}
    @php
        $totalArchives = \App\Models\ReportArchive::count();
        $lastArchive = \App\Models\ReportArchive::with('generatedBy')->latest('generated_at')->first();
    @endphp
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="stat-card flex items-center gap-4">
            <div class="stat-card-icon bg-red-50 text-red-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                </svg>
            </div>
            <div>
                <p class="stat-card-label">Total Arsip Laporan</p>
                <h3 class="stat-card-value">{{ $totalArchives }}</h3>
            </div>
        </div>

        <div class="stat-card flex items-center gap-4 md:col-span-2">
            <div class="stat-card-icon bg-blue-50 text-blue-600">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
            </div>
            <div class="flex-1 min-w-0">
                <p class="stat-card-label">Laporan Tergenerasi Terakhir</p>
                @if($lastArchive)
                    <p class="text-sm font-semibold text-gray-800 mt-1 truncate">
                        Periode: {{ \Carbon\Carbon::parse($lastArchive->periode_mulai)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($lastArchive->periode_selesai)->format('d M Y') }}
                    </p>
                    <p class="text-xs text-gray-400">
                        Dibuat oleh <span class="font-medium text-gray-600">{{ $lastArchive->generatedBy->name }}</span> pada {{ $lastArchive->created_at->format('d M Y H:i') }}
                    </p>
                @else
                    <p class="text-sm font-semibold text-gray-400 mt-1">Belum ada laporan tergenerasi</p>
                @endif
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Generate Form --}}
        <div class="space-y-4 lg:sticky lg:top-20 self-start">
            <div class="card">
                <div class="card-header"><p class="card-title">Buat Laporan Baru</p></div>
                <div class="card-body">
                    <form x-ref="reportForm" method="POST" action="{{ route('reports.generate') }}" @submit.prevent="submitForm()" class="space-y-5">
                        @csrf
                        <input type="hidden" name="jenis_laporan" :value="reportType"/>
                        <input type="hidden" name="tanggal_mulai" :value="periodeMulai"/>
                        <input type="hidden" name="tanggal_selesai" :value="periodeSelesai"/>

                        <div class="form-group">
                            <label class="form-label">Pilih Tipe Periode <span class="text-red-500">*</span></label>
                            
                            {{-- Visual Selector Cards instead of standard dropdown --}}
                            <div class="grid grid-cols-2 gap-3">
                                <button type="button" @click="reportType = 'bulanan'"
                                        :class="reportType === 'bulanan' ? 'border-telkom-600 ring-2 ring-telkom-500 bg-red-50/5 text-telkom-600' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50'"
                                        class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition duration-150">
                                    <svg class="w-5 h-5 mb-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <span class="text-xs font-semibold">Bulanan</span>
                                </button>
                                
                                <button type="button" @click="reportType = 'kuartalan'"
                                        :class="reportType === 'kuartalan' ? 'border-telkom-600 ring-2 ring-telkom-500 bg-red-50/5 text-telkom-600' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50'"
                                        class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition duration-150">
                                    <svg class="w-5 h-5 mb-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 3.055A9.001 9.001 0 1020.945 13H11V3.055z"/>
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M20.488 9H15V3.512A9.025 9.025 0 0120.488 9z"/>
                                    </svg>
                                    <span class="text-xs font-semibold">Kuartalan</span>
                                </button>

                                <button type="button" @click="reportType = 'tahunan'"
                                        :class="reportType === 'tahunan' ? 'border-telkom-600 ring-2 ring-telkom-500 bg-red-50/5 text-telkom-600' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50'"
                                        class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition duration-150">
                                    <svg class="w-5 h-5 mb-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    <span class="text-xs font-semibold">Tahunan</span>
                                </button>

                                <button type="button" @click="reportType = 'custom'"
                                        :class="reportType === 'custom' ? 'border-telkom-600 ring-2 ring-telkom-500 bg-red-50/5 text-telkom-600' : 'border-gray-200 bg-white text-gray-500 hover:bg-gray-50'"
                                        class="flex flex-col items-center justify-center p-3 rounded-xl border text-center transition duration-150">
                                    <svg class="w-5 h-5 mb-1.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>
                                    </svg>
                                    <span class="text-xs font-semibold">Kustom</span>
                                </button>
                            </div>
                        </div>

                        {{-- Bulanan --}}
                        <div class="form-group" x-show="reportType === 'bulanan'" x-transition>
                            <label class="form-label">Pilih Bulan</label>
                            <input type="month" x-model="bulan" class="form-input"/>
                        </div>

                        {{-- Kuartalan --}}
                        <div class="form-group" x-show="reportType === 'kuartalan'" x-transition>
                            <label class="form-label">Pilih Kuartal</label>
                            <div class="grid grid-cols-3 gap-2">
                                <select x-model="kuartal" class="form-select col-span-2">
                                    <option value="1">Kuartal 1 (Jan - Mar)</option>
                                    <option value="2">Kuartal 2 (Apr - Jun)</option>
                                    <option value="3">Kuartal 3 (Jul - Sep)</option>
                                    <option value="4">Kuartal 4 (Okt - Des)</option>
                                </select>
                                <input type="number" x-model="kuartalTahun" min="2000" max="{{ date('Y') }}" class="form-input"/>
                            </div>
                        </div>

                        {{-- Tahunan --}}
                        <div class="form-group" x-show="reportType === 'tahunan'" x-transition>
                            <label class="form-label">Pilih Tahun</label>
                            <input type="number" x-model="tahun" min="2000" max="{{ date('Y') }}" class="form-input"/>
                        </div>

                        {{-- Kustom --}}
                        <div class="form-row grid-cols-2" x-show="reportType === 'custom'" x-transition>
                            <div class="form-group">
                                <label class="form-label">Tanggal Mulai</label>
                                <input type="date" x-model="kustomMulai" class="form-input"/>
                            </div>
                            <div class="form-group">
                                <label class="form-label">Tanggal Selesai</label>
                                <input type="date" x-model="kustomSelesai" class="form-input"/>
                            </div>
                        </div>

                        {{-- Dynamic Date Range Preview --}}
                        <div class="bg-gray-50 border border-gray-100 rounded-xl p-4 text-xs space-y-1">
                            <span class="text-gray-400 font-semibold uppercase tracking-wider block text-[10px]">Cakupan Tanggal</span>
                            <div class="flex items-center gap-2">
                                <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                </svg>
                                <span class="text-sm font-semibold text-gray-800">
                                    <span x-text="formatDate(periodeMulai)"></span> s/d <span x-text="formatDate(periodeSelesai)"></span>
                                </span>
                            </div>
                        </div>

                        <button type="submit" class="btn-primary w-full justify-center py-2.5">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3m0 0v3m0-3h3m-3 0H9m12 0a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            Generate Laporan
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: Archives list --}}
        <div class="lg:col-span-2">
            <div class="card border-0 shadow-sm overflow-hidden">
                <div class="card-header bg-white">
                    <p class="card-title">Arsip Laporan Tersimpan</p>
                </div>
                <div class="table-wrapper border-0 shadow-none">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Periode Laporan</th>
                                <th>Tanggal Dibuat</th>
                                <th>Tipe</th>
                                <th>Dibuat Oleh</th>
                                <th class="text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-50 text-xs">
                            @forelse($archives as $arc)
                                <tr>
                                    <td class="font-semibold text-gray-800">
                                        <div class="flex items-center gap-2">
                                            <svg class="w-4 h-4 text-gray-400 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                            </svg>
                                            <span>
                                                {{ \Carbon\Carbon::parse($arc->periode_mulai)->format('d M Y') }} s/d {{ \Carbon\Carbon::parse($arc->periode_selesai)->format('d M Y') }}
                                            </span>
                                        </div>
                                    </td>
                                    <td class="text-gray-500">{{ $arc->created_at->format('d M Y H:i') }}</td>
                                    <td>
                                        @php
                                            $tipeBadge = match($arc->jenis->value ?? $arc->jenis) {
                                                'bulanan'   => 'badge-diajukan', // Kuning
                                                'kuartalan' => 'badge-staff',    // Ungu
                                                'tahunan'   => 'badge-tersedia', // Hijau
                                                default     => 'badge-secondary',
                                            };
                                        @endphp
                                        <span class="badge {{ $tipeBadge }} uppercase tracking-wider text-[9px]">
                                            {{ $arc->jenis->value ?? $arc->jenis }}
                                        </span>
                                    </td>
                                    <td>
                                        <div class="font-medium text-gray-800">{{ $arc->generatedBy->name }}</div>
                                    </td>
                                    <td class="text-right">
                                        <div class="inline-flex items-center gap-2 justify-end">
                                            <a href="{{ route('reports.show', $arc->id) }}" class="btn-sm btn-secondary font-semibold hover:bg-gray-100">
                                                Lihat
                                            </a>
                                            <a href="{{ route('reports.download', $arc->id) }}?format=pdf" target="_blank" class="btn-sm btn-danger py-1.5 px-3 flex items-center gap-1 font-medium rounded-lg">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                PDF
                                            </a>
                                            <a href="{{ route('reports.download', $arc->id) }}?format=excel" target="_blank" class="btn-sm btn-success py-1.5 px-3 flex items-center gap-1 font-medium rounded-lg">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                                </svg>
                                                Excel
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-gray-400 py-8 text-sm">Belum ada laporan tergenerasi. Silakan buat laporan baru.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if(method_exists($archives, 'links'))
                    <div class="px-5 py-4 border-t border-gray-100 bg-gray-50/50">
                        {{ $archives->links() }}
                    </div>
                @endif
            </div>
        </div>

    </div>
</div>
@endsection
