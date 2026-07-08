@extends('layouts.app')

@php $pageTitle = 'Proses Pengembalian'; @endphp

@section('content')
<div class="space-y-6" x-data="{
    activeScanner: false,
    unitScanned: false,
    manualCode: '',
    unitData: {
        kode: '',
        nama: '',
        peminjam: '',
        tgl_pinjam: '',
        tgl_kembali_rencana: '',
        is_late: false,
        keterlambatan: 0
    },
    scanReturn(code) {
        if (!code) return;
        fetch('{{ route("returns.search") }}?code=' + encodeURIComponent(code))
            .then(res => {
                if (!res.ok) {
                    return res.json().then(err => { throw new Error(err.error || 'Terjadi kesalahan'); });
                }
                return res.json();
            })
            .then(data => {
                this.unitData = {
                    kode: data.kode_unit,
                    nama: data.nama_barang,
                    peminjam: data.peminjam,
                    tgl_pinjam: data.tgl_pinjam,
                    tgl_kembali_rencana: data.tgl_kembali_rencana,
                    is_late: data.is_late,
                    keterlambatan: data.keterlambatan
                };
                this.unitScanned = true;
                this.activeScanner = false;
            })
            .catch(err => {
                alert(err.message);
            });
    },
    submitReturn() {
        this.$refs.returnForm.submit();
    }
}">

    <div class="page-header">
        <div>
            <h1 class="page-title">Proses Pengembalian Unit</h1>
            <p class="page-subtitle">Scan QR code fisik pada unit barang yang dikembalikan untuk memproses status kembali.</p>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Left: Scanner & Manual input --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Camera Scanner --}}
            <div class="card overflow-hidden" x-show="!unitScanned">
                <div class="card-header bg-gray-50 flex items-center justify-between">
                    <p class="card-title">Kamera QR Scanner</p>
                    <button type="button" @click="activeScanner = !activeScanner; if(activeScanner) {
                        $nextTick(() => {
                            startQrScanner('return-scanner-container', (code) => {
                                scanReturn(code);
                            });
                        });
                    }" class="btn btn-sm" :class="activeScanner ? 'btn-danger' : 'btn-primary'">
                        <span x-text="activeScanner ? 'Matikan Kamera' : 'Aktifkan Kamera'"></span>
                    </button>
                </div>
                <div class="card-body flex flex-col items-center justify-center min-h-[250px] bg-gray-900 text-center">
                    <div x-show="!activeScanner" class="text-gray-500 space-y-2">
                        <div class="w-12 h-12 bg-gray-800 rounded-full flex items-center justify-center mx-auto text-gray-400">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 015.186 7.23c-.38.054-.757.112-1.134.175C2.999 7.58 2.25 8.507 2.25 9.574V18a2.25 2.25 0 002.25 2.25h15A2.25 2.25 0 0021.75 18V9.574c0-1.067-.75-1.994-1.802-2.169a47.865 47.865 0 00-1.134-.175 2.31 2.31 0 01-1.64-1.055l-.822-1.316a2.192 2.192 0 00-1.736-1.039 48.774 48.774 0 00-5.232 0 2.192 2.192 0 00-1.736 1.039l-.821 1.316z"/>
                            </svg>
                        </div>
                        <p class="text-sm font-medium">Scan QR Unit</p>
                        <p class="text-xs">Klik Aktifkan Kamera untuk memindai.</p>
                    </div>

                    <div x-show="activeScanner" class="w-full max-w-sm rounded-lg overflow-hidden border border-gray-700 bg-black" id="return-scanner-container">
                        {{-- scanner --}}
                    </div>
                </div>
            </div>

            {{-- Fallback Input --}}
            <div class="card" x-show="!unitScanned">
                <div class="card-body">
                    <p class="text-sm font-semibold text-gray-800 mb-2">Input Manual Kode Unit</p>
                    <div class="flex gap-2">
                        <input type="text" x-model="manualCode" placeholder="Contoh: LAP-001-U01 atau CAM-003-U01" class="form-input font-mono uppercase flex-1"/>
                        <button type="button" @click="scanReturn(manualCode.trim().toUpperCase()); manualCode = '';" class="btn btn-secondary">
                            Proses
                        </button>
                    </div>
                </div>
            </div>

            {{-- Info Unit Setelah di-Scan --}}
            <div class="card" x-show="unitScanned" x-transition>
                <div class="card-header flex items-center justify-between">
                    <p class="card-title text-emerald-600 font-bold">✓ Unit Terdeteksi</p>
                    <button type="button" @click="unitScanned = false" class="btn-sm btn-ghost text-gray-500">Scan Ulang</button>
                </div>
                <div class="card-body space-y-4">
                    <div class="grid grid-cols-2 gap-4 bg-gray-50 rounded-xl p-4 text-sm">
                        <div>
                            <p class="text-gray-400 text-xs">KODE UNIT</p>
                            <p class="font-mono font-bold text-gray-800" x-text="unitData.kode"></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">NAMA BARANG</p>
                            <p class="font-medium text-gray-800" x-text="unitData.nama"></p>
                        </div>
                        <div class="col-span-2 divider my-1"></div>
                        <div>
                            <p class="text-gray-400 text-xs">PEMINJAM</p>
                            <p class="font-medium text-gray-800" x-text="unitData.peminjam"></p>
                        </div>
                        <div>
                            <p class="text-gray-400 text-xs">JADWAL RENCANA KEMBALI</p>
                            <p class="font-medium text-gray-800" x-text="unitData.tgl_kembali_rencana"></p>
                        </div>
                    </div>

                    {{-- Warning Keterlambatan --}}
                    <div x-show="unitData.is_late" class="alert-error text-xs flex gap-2">
                        <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                        </svg>
                        <span>Unit ini terlambat dikembalikan selama <strong x-text="unitData.keterlambatan"></strong> hari.</span>
                    </div>

                    {{-- Form Kondisi Pengembalian --}}
                    <form x-ref="returnForm" method="POST" action="{{ route('returns.store') }}" @submit.prevent="submitReturn()" class="space-y-4 pt-2">
                        @csrf
                        <input type="hidden" name="kode_unit" :value="unitData.kode"/>

                        <div class="form-group">
                            <label class="form-label">Kondisi Pengembalian Aset <span class="text-red-500">*</span></label>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-2">
                                <label class="border rounded-lg p-3 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50 focus-within:ring-2 focus-within:ring-telkom-500">
                                    <input type="radio" name="kondisi_barang" value="baik" checked class="text-telkom-600 focus:ring-telkom-500 mb-1"/>
                                    <span class="text-xs font-semibold text-emerald-600">Baik</span>
                                </label>
                                <label class="border rounded-lg p-3 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50 focus-within:ring-2 focus-within:ring-telkom-500">
                                    <input type="radio" name="kondisi_barang" value="rusak_ringan" class="text-telkom-600 focus:ring-telkom-500 mb-1"/>
                                    <span class="text-xs font-semibold text-amber-600">Rusak Ringan</span>
                                </label>
                                <label class="border rounded-lg p-3 flex flex-col items-center justify-center cursor-pointer hover:bg-gray-50 focus-within:ring-2 focus-within:ring-telkom-500">
                                    <input type="radio" name="kondisi_barang" value="rusak_berat" class="text-telkom-600 focus:ring-telkom-500 mb-1"/>
                                    <span class="text-xs font-semibold text-red-600">Rusak Berat</span>
                                </label>
                            </div>
                            <p class="text-xs text-amber-600 bg-amber-50 rounded-lg p-2.5 border border-amber-200">
                                Info: Jika barang hilang, laporkan lewat menu Lapor Insiden, bukan di sini.
                            </p>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Catatan Pengembalian</label>
                            <textarea name="catatan" class="form-textarea" rows="2" placeholder="Catatan fisik unit saat ini (goresan, kelengkapan kabel, dll)..."></textarea>
                        </div>

                        <button type="submit" class="btn-primary w-full justify-center py-2.5">
                            Konfirmasi Pengembalian
                        </button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Right: Panduan --}}
        <div class="card h-fit">
            <div class="card-header"><p class="card-title">Petunjuk</p></div>
            <div class="card-body text-xs text-gray-500 space-y-2 leading-relaxed">
                <p>1. Dekatkan QR code stiker di body aset ke kamera scanner.</p>
                <p>2. Pastikan pencahayaan cukup agar kamera bisa fokus membaca data.</p>
                <p>3. Jika kamera tidak dapat digunakan, gunakan form input manual dengan mengetik kode unit.</p>
                <p>4. Periksa kondisi unit fisik sebelum menyetujui form pengembalian.</p>
            </div>
        </div>

    </div>
</div>
@endsection
