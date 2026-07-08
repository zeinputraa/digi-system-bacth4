@extends('layouts.app')

@php $pageTitle = 'Lapor Insiden'; @endphp

@section('content')
<div class="max-w-2xl">
    <div class="mb-5">
        <nav class="breadcrumb mb-2">
            <a href="{{ route('borrowings.my') }}" class="hover:text-gray-600">Peminjaman</a>
            <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="breadcrumb-current">Lapor Insiden</span>
        </nav>
        <h1 class="page-title">Laporkan Masalah Aset</h1>
        <p class="page-subtitle">Laporkan kerusakan atau kehilangan barang inventaris yang Anda pinjam.</p>
    </div>

    <div class="card" x-data="{ jenisMasalah: 'rusak_ringan' }">
        <div class="card-body">
            <form method="POST" action="{{ route('incidents.store') }}" enctype="multipart/form-data" class="space-y-5">
                @csrf

                {{-- Pilih Unit --}}
                <div class="form-group">
                    <x-input-label for="borrowing_detail_id" :value="__('Aset Yang Bermasalah')" />
                    <select id="borrowing_detail_id" name="borrowing_detail_id" class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                        <option value="">-- Pilih Unit Barang --</option>
                        @foreach($activeDetails as $d)
                            <option value="{{ $d->id }}" {{ old('borrowing_detail_id', request('detail')) == $d->id ? 'selected' : '' }}>
                                {{ $d->product->nama_barang }} ({{ $d->productUnit ? $d->productUnit->kode_unit : 'Belum di-assign' }}) - Peminjaman {{ $d->borrowing->kode_peminjaman }}
                            </option>
                        @endforeach
                    </select>
                    <x-input-error :messages="$errors->get('borrowing_detail_id')" class="mt-2" />
                    <p class="form-hint mt-1 text-xs text-gray-400">Hanya menampilkan barang peminjaman aktif Anda saat ini.</p>
                </div>

                {{-- Jenis Masalah --}}
                <div class="form-group">
                    <x-input-label :value="__('Jenis Masalah')" />
                    <div class="grid grid-cols-3 gap-3 mt-1">
                        <label class="border rounded-lg p-3 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50 focus-within:ring-2 focus-within:ring-telkom-500" :class="jenisMasalah === 'rusak_ringan' ? 'border-amber-500 bg-amber-50/50' : 'border-gray-200'">
                            <input type="radio" name="jenis" value="rusak_ringan" x-model="jenisMasalah" class="text-telkom-600 focus:ring-telkom-500 mb-1"/>
                            <span class="text-xs font-semibold text-amber-600">Rusak Ringan</span>
                        </label>
                        <label class="border rounded-lg p-3 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50 focus-within:ring-2 focus-within:ring-telkom-500" :class="jenisMasalah === 'rusak_berat' ? 'border-red-500 bg-red-50/50' : 'border-gray-200'">
                            <input type="radio" name="jenis" value="rusak_berat" x-model="jenisMasalah" class="text-telkom-600 focus:ring-telkom-500 mb-1"/>
                            <span class="text-xs font-semibold text-red-600">Rusak Berat</span>
                        </label>
                        <label class="border rounded-lg p-3 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50 focus-within:ring-2 focus-within:ring-telkom-500" :class="jenisMasalah === 'hilang' ? 'border-gray-500 bg-gray-50' : 'border-gray-200'">
                            <input type="radio" name="jenis" value="hilang" x-model="jenisMasalah" class="text-telkom-600 focus:ring-telkom-500 mb-1"/>
                            <span class="text-xs font-semibold text-gray-600">Hilang</span>
                        </label>
                    </div>
                    <x-input-error :messages="$errors->get('jenis')" class="mt-2" />
                </div>

                {{-- Alert info kehilangan --}}
                <div x-show="jenisMasalah === 'hilang'" x-transition class="alert-warning text-xs">
                    <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <span>Laporan kehilangan memerlukan <strong>Verifikasi Staff</strong> dan <strong>Finalisasi Admin</strong> untuk proses ganti rugi/write-off.</span>
                </div>

                {{-- Kronologi --}}
                <div class="form-group">
                    <x-input-label for="kronologi" :value="__('Kronologi Kejadian')" />
                    <textarea id="kronologi" name="kronologi" rows="4" required
                              class="form-textarea text-sm mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm" placeholder="Jelaskan secara rinci detail kerusakan, waktu kejadian, and bagaimana masalah tersebut terjadi...">{{ old('kronologi') }}</textarea>
                    <x-input-error :messages="$errors->get('kronologi')" class="mt-2" />
                </div>

                {{-- Bukti Foto --}}
                <div class="form-group">
                    <x-input-label for="bukti_foto" :value="__('Bukti Foto Pendukung')" />
                    <input id="bukti_foto" name="foto_bukti" type="file" accept="image/*"
                           class="form-input mt-1 block w-full file:mr-3 file:py-1 file:px-3 file:rounded-lg file:border-0 file:text-xs file:bg-gray-100 file:text-gray-700"/>
                    <x-input-error :messages="$errors->get('foto_bukti')" class="mt-2" />
                    <p class="form-hint mt-1 text-xs text-gray-400">Unggah foto kondisi fisik barang (format JPG, PNG max 5MB).</p>
                </div>

                <div class="flex items-center gap-3 pt-2">
                    <x-primary-button type="submit">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                        </svg>
                        Kirim Laporan
                    </x-primary-button>
                    <a href="{{ route('borrowings.my') }}" class="btn-secondary">Batal</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
