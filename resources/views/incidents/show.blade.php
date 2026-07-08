@extends('layouts.app')

@php $pageTitle = 'Detail Laporan Insiden'; @endphp

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb --}}
    <nav class="breadcrumb">
        <a href="{{ route('incidents.index') }}" class="hover:text-gray-600">Laporan Insiden</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="breadcrumb-current">INC-{{ str_pad($incident->id, 5, '0', STR_PAD_LEFT) }}</span>
    </nav>

    @if (session('success'))
        <div class="alert-success text-sm p-4 bg-emerald-50 text-emerald-800 border border-emerald-200 rounded-lg">
            {{ session('success') }}
        </div>
    @endif

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Sidebar Detail --}}
        <div class="space-y-4">
            <div class="card">
                <div class="card-header">
                    <p class="card-title">Detail Insiden</p>
                    @php
                        $badgeStatus = [
                            'menunggu_verifikasi_staff' => 'badge-diajukan',
                            'terverifikasi_staff' => 'badge-disetujui',
                            'menunggu_finalisasi_admin' => 'badge-maintenance',
                            'difinalisasi_admin' => 'badge-selesai',
                            'dibatalkan_ditemukan' => 'badge-dibatalkan',
                        ];
                        $labelStatus = [
                            'menunggu_verifikasi_staff' => 'Menunggu Verifikasi Staff',
                            'terverifikasi_staff' => 'Terverifikasi Staff',
                            'menunggu_finalisasi_admin' => 'Menunggu Finalisasi Admin',
                            'difinalisasi_admin' => 'Selesai',
                            'dibatalkan_ditemukan' => 'Dibatalkan (Ditemukan)',
                        ];
                        $statusVal = $incident->status->value;
                        $jenisVal = $incident->jenis->value;
                    @endphp
                    <span class="badge {{ $badgeStatus[$statusVal] ?? '' }}">
                        {{ $labelStatus[$statusVal] ?? $statusVal }}
                    </span>
                </div>
                <div class="card-body space-y-3 text-sm">
                    <div class="flex justify-between">
                        <span class="text-gray-500">ID Laporan</span>
                        <span class="font-mono font-bold">INC-{{ str_pad($incident->id, 5, '0', STR_PAD_LEFT) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Unit Terlibat</span>
                        <span class="font-mono font-semibold">{{ $incident->productUnit ? $incident->productUnit->kode_unit : '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Nama Barang</span>
                        <span class="font-medium text-gray-800 text-right">{{ $incident->productUnit ? $incident->productUnit->product->nama_barang : '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Pelapor</span>
                        <span class="font-medium text-gray-800">{{ $incident->reporter ? $incident->reporter->name : '—' }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Jenis Masalah</span>
                        <span class="badge bg-gray-100 text-gray-800">{{ ucfirst(str_replace('_', ' ', $jenisVal)) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-500">Tanggal Lapor</span>
                        <span class="font-medium text-gray-800">{{ $incident->created_at ? $incident->created_at->format('d M Y') : '—' }}</span>
                    </div>
                    @if($incident->status_ganti_rugi)
                        <div class="divider my-2 border-t border-gray-100"></div>
                        <div class="flex justify-between">
                            <span class="text-gray-500">Status Ganti Rugi</span>
                            <span class="font-semibold text-orange-600">{{ ucfirst(str_replace('_', ' ', $incident->status_ganti_rugi->value)) }}</span>
                        </div>
                    @endif
                </div>
            </div>

            {{-- Timeline Insiden --}}
            <div class="card">
                <div class="card-header"><p class="card-title">Jejak Peninjauan</p></div>
                <div class="card-body">
                    <div class="timeline space-y-4">
                        <div class="timeline-item flex items-start gap-2">
                            <div class="timeline-dot bg-red-500 w-3 h-3 rounded-full mt-1.5 shrink-0"></div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">Dilaporkan {{ ucfirst(str_replace('_', ' ', $jenisVal)) }}</p>
                                <p class="text-xs text-gray-400">{{ $incident->created_at ? $incident->created_at->format('d M Y, H:i') : '—' }} WIB oleh {{ $incident->reporter ? $incident->reporter->name : '—' }}</p>
                            </div>
                        </div>
                        @if ($incident->verified_by)
                            <div class="timeline-item flex items-start gap-2">
                                <div class="timeline-dot bg-blue-500 w-3 h-3 rounded-full mt-1.5 shrink-0"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Diverifikasi Staff</p>
                                    <p class="text-xs text-gray-400">{{ $incident->verified_at ? $incident->verified_at->format('d M Y, H:i') : '—' }} WIB oleh {{ $incident->verifier ? $incident->verifier->name : '—' }}</p>
                                    @if($incident->catatan && !$incident->finalized_by)
                                        <p class="text-xs text-gray-500 mt-1 italic">"{{ $incident->catatan }}"</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                        @if ($incident->finalized_by)
                            <div class="timeline-item flex items-start gap-2">
                                <div class="timeline-dot bg-emerald-500 w-3 h-3 rounded-full mt-1.5 shrink-0"></div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">Difinalisasi Admin</p>
                                    <p class="text-xs text-gray-400">{{ $incident->finalized_at ? $incident->finalized_at->format('d M Y, H:i') : '—' }} WIB oleh {{ $incident->finalizer ? $incident->finalizer->name : '—' }}</p>
                                    @if($incident->catatan)
                                        <p class="text-xs text-gray-500 mt-1 italic">"{{ $incident->catatan }}"</p>
                                    @endif
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        {{-- Main: Kronologi & Form Aksi --}}
        <div class="lg:col-span-2 space-y-4">
            {{-- Detail Laporan --}}
            <div class="card">
                <div class="card-header"><p class="card-title">Kronologi Kejadian</p></div>
                <div class="card-body space-y-4">
                    <p class="text-sm text-gray-700 leading-relaxed bg-gray-50 p-4 rounded-xl">
                        {{ $incident->kronologi }}
                    </p>

                    <div>
                        <p class="text-xs font-semibold text-gray-500 mb-2">Foto / Bukti Dukung</p>
                        @if ($incident->foto_bukti)
                            <img src="{{ asset('storage/' . $incident->foto_bukti) }}" alt="Bukti Foto" class="max-w-sm rounded-xl border border-gray-200 shadow-sm"/>
                        @else
                            <div class="h-32 w-full max-w-sm bg-gray-100 rounded-xl flex items-center justify-center border border-dashed border-gray-200 text-gray-400 text-sm">
                                <span>Tidak ada bukti foto terlampir</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Form Aksi Sesuai Role --}}
            @if(auth()->user()->hasRole('admin', 'staff'))
                {{-- STAFF VERIFICATION FORM (Only if status is waiting verif) --}}
                @if($statusVal === 'menunggu_verifikasi_staff')
                    <div class="card">
                        <div class="card-header">
                            <p class="card-title">Aksi Peninjauan Aset (Staff Verifikasi)</p>
                        </div>
                        <div class="card-body">
                            <p class="text-xs text-gray-500 mb-4">Sebagai Staff, Anda dapat memverifikasi laporan di lapangan dan memutuskan tindakan penanganan unit.</p>
                            <form method="POST" action="{{ route('incidents.verify', $incident->id) }}" class="space-y-4">
                                @csrf
                                <div class="form-group">
                                    <x-input-label :value="__('Tindakan Rekomendasi')" />
                                    <div class="flex gap-4 mt-2">
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="tindakan" value="tarik_maintenance" checked class="text-telkom-600 focus:ring-telkom-500"/>
                                            <span class="text-sm font-semibold text-red-600">Tarik ke Maintenance</span>
                                        </label>
                                        <label class="flex items-center gap-2">
                                            <input type="radio" name="tindakan" value="tetap_dipinjam" class="text-telkom-600 focus:ring-telkom-500"/>
                                            <span class="text-sm font-semibold text-emerald-600">Tetap Dipinjam</span>
                                        </label>
                                    </div>
                                    <x-input-error :messages="$errors->get('tindakan')" class="mt-2" />
                                </div>
                                <div class="form-group">
                                    <x-input-label for="catatan_staff" :value="__('Catatan Pemeriksaan')" />
                                    <textarea id="catatan_staff" name="catatan_staff" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="Catatan kondisi saat diperiksa..."></textarea>
                                    <x-input-error :messages="$errors->get('catatan_staff')" class="mt-2" />
                                </div>
                                <x-primary-button>Simpan Verifikasi</x-primary-button>
                            </form>
                        </div>
                    </div>
                @endif

                {{-- ADMIN FINALIZATION FORM (Only if status is waiting finalization AND role is admin) --}}
                @if($statusVal === 'menunggu_finalisasi_admin' && auth()->user()->hasRole('admin'))
                    <div class="card" x-data="{ state: 'write_off' }">
                        <div class="card-header">
                            <p class="card-title">Aksi Peninjauan Aset (Admin Finalisasi)</p>
                        </div>
                        <div class="card-body">
                            <p class="text-xs text-gray-500 mb-4">Sebagai Admin, Anda berwenang melakukan finalisasi write-off permanen atau menuntut ganti rugi aset yang rusak/hilang.</p>
                            <form method="POST" action="{{ route('incidents.finalize', $incident->id) }}" class="space-y-4">
                                @csrf
                                <div class="form-group">
                                    <x-input-label :value="__('Tindakan Finalisasi')" />
                                    <div class="flex gap-4 mt-2">
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="status_final" value="write_off" x-model="state" class="text-telkom-600 focus:ring-telkom-500"/>
                                            <span class="text-sm font-semibold text-red-600">Write-off Aset</span>
                                        </label>
                                        <label class="flex items-center gap-2 cursor-pointer">
                                            <input type="radio" name="status_final" value="tuntut_ganti_rugi" x-model="state" class="text-telkom-600 focus:ring-telkom-500"/>
                                            <span class="text-sm font-semibold text-orange-600">Tuntut Ganti Rugi</span>
                                        </label>
                                    </div>
                                    <x-input-error :messages="$errors->get('status_final')" class="mt-2" />
                                </div>

                                <div x-show="state === 'write_off'" x-transition class="text-xs text-red-600 bg-red-50 p-2.5 rounded-lg border border-red-200">
                                    ⚠ Tindakan ini akan menghapus unit fisik dari daftar unit aktif secara permanen di database inventaris.
                                </div>

                                <div x-show="state === 'tuntut_ganti_rugi'" x-transition class="form-group">
                                    <x-input-label for="ganti_rugi_nominal" :value="__('Nominal Ganti Rugi (Rp)')" />
                                    <x-text-input id="ganti_rugi_nominal" name="ganti_rugi_nominal" type="number" class="mt-1 block w-full" placeholder="Contoh: 3500000" x-bind:required="state === 'tuntut_ganti_rugi'"/>
                                    <x-input-error :messages="$errors->get('ganti_rugi_nominal')" class="mt-2" />
                                </div>

                                <div class="form-group">
                                    <x-input-label for="catatan_admin" :value="__('Catatan Keputusan Admin')" />
                                    <textarea id="catatan_admin" name="catatan_admin" rows="2" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="Alasan keputusan write-off atau nominal denda..."></textarea>
                                    <x-input-error :messages="$errors->get('catatan_admin')" class="mt-2" />
                                </div>

                                <x-primary-button>Konfirmasi Keputusan</x-primary-button>
                            </form>
                        </div>
                    </div>
                @endif
            @endif
        </div>
    </div>
</div>
@endsection
