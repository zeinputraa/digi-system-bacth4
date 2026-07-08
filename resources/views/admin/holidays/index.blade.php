@extends('layouts.app')

@php $pageTitle = 'Kelola Hari Libur'; @endphp

@section('content')
<div class="space-y-5" x-data="{ showModal: false }">
    {{-- Page Header --}}
    <div class="page-header">
        <div>
            <h1 class="page-title">Hari Libur Nasional</h1>
            <p class="page-subtitle">Daftar hari libur nasional untuk kalkulasi tanggal pengembalian dan pengecualian denda/SLA.</p>
        </div>
        <div class="flex gap-2 items-center">
            <form method="POST" action="{{ route('admin.holidays.sync') }}" style="display:inline">
                @csrf
                <button type="submit" class="btn-secondary">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0l3.181 3.183a8.25 8.25 0 0013.803-3.7M4.031 9.865a8.25 8.25 0 0113.803-3.7l3.181 3.182m0-4.991v4.99"/>
                    </svg>
                    Sync Hari Libur
                </button>
            </form>
            <button type="button" @click="showModal = true" class="btn btn-primary">
                + Tambah Manual
            </button>
        </div>
    </div>

    {{-- Table --}}
    <div class="table-wrapper">
        <table class="table">
            <thead>
                <tr>
                    <th>No</th>
                    <th>Nama Hari Libur</th>
                    <th>Tanggal</th>
                    <th>Tipe</th>
                    <th>Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-50">
                @forelse($holidays as $h)
                    <tr>
                        <td class="text-gray-400 text-xs w-12">{{ $loop->iteration }}</td>
                        <td class="font-medium text-gray-900">{{ $h->keterangan }}</td>
                        <td class="font-mono text-sm text-gray-600">{{ \Carbon\Carbon::parse($h->tanggal)->format('d M Y') }}</td>
                        <td>
                            @php
                                $tipeVal = $h->jenis->value ?? $h->jenis;
                                $tipeBadge = match($tipeVal) {
                                    'Nasional' => 'badge-disetujui',
                                    'Keagamaan' => 'badge-maintenance',
                                    default => 'badge-secondary',
                                };
                            @endphp
                            <span class="badge {{ $tipeBadge }} text-[10px]">
                                {{ $tipeVal }}
                            </span>
                        </td>
                        <td class="w-20">
                            <form method="POST" action="{{ route('admin.holidays.destroy', $h->id) }}" onsubmit="return confirm('Hapus hari libur ini?')" style="display:inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="text-red-600 hover:text-red-800 text-sm font-medium">Hapus</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="5" class="text-center text-gray-400 py-6 text-xs">Belum ada data hari libur nasional. Klik Sync atau Tambah Manual.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Modal Tambah Manual -->
    <div x-show="showModal" class="fixed inset-0 bg-slate-900/40 backdrop-blur-sm flex items-center justify-center p-4 z-50" x-transition style="display: none;">
        <div class="bg-white rounded-xl shadow-xl max-w-md w-full border border-gray-100 p-6 space-y-4" @click.away="showModal = false">
            <div class="flex justify-between items-center">
                <h2 class="text-base font-bold text-gray-800">Tambah Hari Libur Manual</h2>
                <button type="button" @click="showModal = false" class="text-gray-400 hover:text-gray-600 text-xl">&times;</button>
            </div>
            <form method="POST" action="{{ route('admin.holidays.store') }}" class="space-y-4">
                @csrf
                <div class="form-group">
                    <label class="form-label">Tanggal <span class="text-red-500">*</span></label>
                    <input type="date" name="tanggal" required class="form-input"/>
                </div>
                <div class="form-group">
                    <label class="form-label">Keterangan / Nama Hari Libur <span class="text-red-500">*</span></label>
                    <input type="text" name="keterangan" placeholder="Contoh: Tahun Baru Hijriah" required class="form-input"/>
                </div>
                <div class="form-group">
                    <label class="form-label">Tipe Hari Libur <span class="text-red-500">*</span></label>
                    <select name="jenis" class="form-select" required>
                        <option value="Nasional">Nasional</option>
                        <option value="Keagamaan">Keagamaan</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" @click="showModal = false" class="btn btn-secondary">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Hari Libur</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
