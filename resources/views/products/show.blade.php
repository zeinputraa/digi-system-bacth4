@extends('layouts.app')

@php $pageTitle = $product->nama_barang; @endphp

@section('content')
<div class="space-y-6">

    {{-- Breadcrumb --}}
    <nav class="breadcrumb">
        <a href="{{ route('products.index') }}" class="hover:text-gray-600">Barang</a>
        <svg class="w-3 h-3" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
        </svg>
        <span class="breadcrumb-current">{{ $product->nama_barang }}</span>
    </nav>

    {{-- ===== PRODUCT HEADER ===== --}}
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">

        {{-- Foto + Info Dasar --}}
        <div class="card">
            <div class="h-56 bg-gray-100 flex items-center justify-center overflow-hidden rounded-t-xl">
                @if($product->foto)
                    <img src="{{ Storage::url($product->foto) }}"
                         alt="{{ $product->nama_barang }}"
                         class="w-full h-full object-cover"/>
                @else
                    <svg class="w-20 h-20 text-gray-300" fill="none" stroke="currentColor" stroke-width="1" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                    </svg>
                @endif
            </div>
            <div class="p-5 space-y-3">
                <div class="flex items-start justify-between gap-2">
                    <h1 class="text-lg font-bold text-gray-900 leading-tight">{{ $product->nama_barang }}</h1>
                </div>
                <p class="font-mono text-xs text-gray-500 bg-gray-100 inline-block px-2 py-0.5 rounded">{{ $product->kode_produk }}</p>

                <div class="space-y-2 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Kategori</span>
                        <span class="font-medium text-gray-800">{{ $product->category->nama_kategori ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Total Unit</span>
                        <span class="font-medium text-gray-800">{{ $product->units->count() }} unit</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Tersedia</span>
                        @php $tersedia = $product->units->where('status', \App\Enums\StatusUnit::Tersedia)->count(); @endphp
                        <span class="font-bold {{ $tersedia > 0 ? 'text-emerald-600' : 'text-red-600' }}">
                            {{ $tersedia }} unit
                        </span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-gray-500">Stok Minimum</span>
                        <span class="font-medium {{ $tersedia < $product->stok_minimum ? 'text-red-600' : 'text-gray-800' }}">
                            {{ $product->stok_minimum }} unit
                            @if($tersedia < $product->stok_minimum)
                                <span class="badge badge-ditolak text-[9px] ml-1">Di Bawah Minimum</span>
                            @endif
                        </span>
                    </div>
                </div>

                @if($product->deskripsi)
                    <div class="divider"></div>
                    <p class="text-sm text-gray-500">{{ $product->deskripsi }}</p>
                @endif

                {{-- Actions --}}
                <div class="divider"></div>
                <div class="flex flex-col gap-2">
                    @if(auth()->user()->hasRole('karyawan'))
                        <a href="{{ route('borrowings.create') }}?produk={{ $product->id }}"
                           class="btn-primary w-full justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Ajukan Peminjaman
                        </a>
                    @endif
                    @if(auth()->user()->hasRole('admin', 'staff'))
                        <a href="{{ route('units.create', $product) }}" class="btn-success w-full justify-center">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4v16m8-8H4"/>
                            </svg>
                            Tambah Unit Baru
                        </a>
                        <div class="flex gap-2">
                            <a href="{{ route('products.edit', $product) }}" class="btn-secondary flex-1 justify-center">Edit Barang</a>
                            <form method="POST" action="{{ route('products.destroy', $product) }}"
                                  onsubmit="return confirm('Hapus barang ini beserta semua unitnya?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn-danger btn-icon">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                    </svg>
                                </button>
                            </form>
                        </div>
                        @if($product->units->isNotEmpty())
                            <form method="POST" action="{{ route('labels.cetak') }}">
                                @csrf
                                @foreach($product->units as $u)
                                    <input type="hidden" name="unit_ids[]" value="{{ $u->id }}">
                                @endforeach
                                <button type="submit" class="btn-ghost w-full justify-center text-gray-600 flex items-center gap-2">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/>
                                    </svg>
                                    Cetak Semua Label QR
                                </button>
                            </form>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        {{-- Kalender Ketersediaan --}}
        <div class="card lg:col-span-2" x-data="{
            currentMonth: '{{ now()->format('Y-m') }}',
            calendarData: {},
            monthName: '',
            blankDays: 0,
            init() {
                this.loadCalendar();
            },
            getMonthName(yearMonth) {
                if (!yearMonth) return '';
                const [year, month] = yearMonth.split('-');
                const date = new Date(year, month - 1, 1);
                return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
            },
            async loadCalendar() {
                const res = await fetch('/produk/{{ $product->id }}/ketersediaan?bulan=' + this.currentMonth);
                if (res.ok) {
                    this.calendarData = await res.json();
                    const dates = Object.keys(this.calendarData);
                    if (dates.length > 0) {
                        const firstDate = dates[0];
                        const dayOfWeek = new Date(firstDate).getDay();
                        this.blankDays = (dayOfWeek + 6) % 7;
                    }
                    this.monthName = this.getMonthName(this.currentMonth);
                }
            },
            prevMonth() {
                const [year, month] = this.currentMonth.split('-').map(Number);
                let prevYear = year;
                let prevMonth = month - 1;
                if (prevMonth === 0) {
                    prevMonth = 12;
                    prevYear = year - 1;
                }
                this.currentMonth = `${prevYear}-${String(prevMonth).padStart(2, '0')}`;
                this.loadCalendar();
            },
            nextMonth() {
                const [year, month] = this.currentMonth.split('-').map(Number);
                let nextYear = year;
                let nextMonth = month + 1;
                if (nextMonth === 13) {
                    nextMonth = 1;
                    nextYear = year + 1;
                }
                this.currentMonth = `${nextYear}-${String(nextMonth).padStart(2, '0')}`;
                this.loadCalendar();
            },
            pilihTanggal(tanggal, status) {
                @if(auth()->user()->hasRole('admin', 'staff', 'karyawan'))
                    window.location.href = '{{ route("borrowings.create") }}?produk={{ $product->id }}&tanggal=' + tanggal;
                @endif
            }
        }">
            <div class="card-header">
                <p class="card-title">Kalender Ketersediaan</p>
                <div class="flex items-center gap-3 text-xs">
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded bg-emerald-400 inline-block"></span> Tersedia
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded bg-amber-400 inline-block"></span> Sebagian
                    </span>
                    <span class="flex items-center gap-1">
                        <span class="w-3 h-3 rounded bg-red-500 inline-block"></span> Penuh
                    </span>
                </div>
            </div>
            <div class="card-body">
                {{-- Header Bulan dan Navigasi --}}
                <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-100 mb-4">
                    <span class="text-sm font-semibold text-gray-700 w-48 text-left" x-text="monthName"></span>
                    <div class="flex items-center gap-2">
                        <button type="button" @click="prevMonth()" class="p-1.5 rounded hover:bg-gray-200 transition">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                            </svg>
                        </button>
                        <button type="button" @click="nextMonth()" class="p-1.5 rounded hover:bg-gray-200 transition">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        </button>
                    </div>
                </div>

                {{-- Kalender Grid --}}
                <div class="border border-gray-100 rounded-xl p-3 bg-white">
                    {{-- Hari --}}
                    <div class="grid grid-cols-7 gap-1 text-center text-xs font-semibold text-gray-400 mb-2">
                        <div>Sen</div>
                        <div>Sel</div>
                        <div>Rab</div>
                        <div>Kam</div>
                        <div>Jum</div>
                        <div>Sab</div>
                        <div>Min</div>
                    </div>

                    {{-- Grid Tanggal --}}
                    <div class="grid grid-cols-7 gap-1">
                        <template x-for="i in blankDays">
                            <div class="aspect-square bg-gray-50/50 rounded-lg"></div>
                        </template>

                        <template x-for="(day, tanggal) in calendarData" :key="tanggal">
                            <button type="button"
                                @click="pilihTanggal(tanggal, day.status)"
                                :disabled="day.status === 'merah'"
                                :class="{
                                    'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-100': day.status === 'hijau',
                                    'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-100': day.status === 'kuning',
                                    'bg-red-50 text-red-400 cursor-not-allowed opacity-50 border border-red-100': day.status === 'merah',
                                }"
                                class="aspect-square rounded-lg flex flex-col items-center justify-between p-1.5 transition text-xs relative">
                                <span x-text="new Date(tanggal).getDate()" class="font-semibold"></span>
                                <template x-if="day.libur">
                                    <span class="text-[8px] text-red-500 font-bold truncate max-w-full text-center px-0.5" :title="day.libur" x-text="day.libur"></span>
                                </template>
                                <template x-if="!day.libur">
                                    <span class="text-[9px] scale-90 font-medium" x-text="day.tersedia + '/' + day.total"></span>
                                </template>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ===== TABEL UNIT ===== --}}
    <div class="card">
        <div class="card-header">
            <p class="card-title">Daftar Unit ({{ $product->units->count() }} unit)</p>
            @if(auth()->user()->hasRole('admin', 'staff'))
                <a href="{{ route('units.create', $product) }}" class="btn-sm btn-success">+ Tambah Unit</a>
            @endif
        </div>

        {{-- Tab Filter Status Unit --}}
        <div class="px-5 border-b border-gray-100" x-data="{ tab: 'semua' }">
            <div class="tabs mb-0 -mb-px">
                @php
                    $semua   = $product->units->count();
                    $avail   = $product->units->where('status', \App\Enums\StatusUnit::Tersedia)->count();
                    $dipinjam = $product->units->where('status', \App\Enums\StatusUnit::Dipinjam)->count();
                    $maint   = $product->units->where('status', \App\Enums\StatusUnit::Maintenance)->count();
                    $hilang  = $product->units->whereIn('status', [\App\Enums\StatusUnit::DilaporkanHilang, \App\Enums\StatusUnit::HilangPermanen])->count();
                @endphp
                <button @click="tab='semua'"    :class="tab==='semua'    ? 'active' : ''" class="tab-link">Semua ({{ $semua }})</button>
                <button @click="tab='tersedia'" :class="tab==='tersedia' ? 'active' : ''" class="tab-link">Tersedia ({{ $avail }})</button>
                <button @click="tab='dipinjam'" :class="tab==='dipinjam' ? 'active' : ''" class="tab-link">Dipinjam ({{ $dipinjam }})</button>
                <button @click="tab='maint'"    :class="tab==='maint'    ? 'active' : ''" class="tab-link">Maintenance ({{ $maint }})</button>
                @if($hilang > 0)
                    <button @click="tab='hilang'" :class="tab==='hilang' ? 'active' : ''" class="tab-link text-red-600">Hilang ({{ $hilang }})</button>
                @endif
            </div>

            <table class="table mt-0">
                <thead>
                    <tr>
                        <th>Kode Unit</th>
                        <th>Status</th>
                        <th>Kondisi</th>
                        <th>Lokasi</th>
                        <th>Tahun</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    @forelse($product->units as $unit)
                        @php
                            $unitTab = match($unit->status->value) {
                                'tersedia' => 'tersedia',
                                'dipinjam' => 'dipinjam',
                                'maintenance' => 'maint',
                                default => 'hilang',
                            };
                            $statusBadge = match($unit->status->value) {
                                'tersedia'          => 'badge-tersedia',
                                'dipinjam'          => 'badge-dipinjam',
                                'maintenance'       => 'badge-maintenance',
                                'dilaporkan_hilang' => 'badge-hilang',
                                'hilang_permanen'   => 'badge-hilang-permanen',
                                default             => 'badge-selesai',
                            };
                            $kondisiBadge = match($unit->kondisi->value ?? $unit->kondisi) {
                                'baik'         => 'badge-baik',
                                'rusak_ringan' => 'badge-rusak-ringan',
                                'rusak_berat'  => 'badge-rusak-berat',
                                default        => '',
                            };
                        @endphp
                        <tr x-show="tab === 'semua' || tab === '{{ $unitTab }}'">
                            <td class="font-mono text-xs font-semibold text-gray-800">{{ $unit->kode_unit }}</td>
                            <td><span class="badge {{ $statusBadge }}">{{ ucfirst(str_replace('_', ' ', $unit->status->value)) }}</span></td>
                            <td><span class="badge {{ $kondisiBadge }}">{{ ucfirst(str_replace('_', ' ', $unit->kondisi->value ?? $unit->kondisi)) }}</span></td>
                            <td class="text-gray-500">{{ $unit->lokasi_penyimpanan ?? '—' }}</td>
                            <td class="text-gray-500">{{ $unit->tahun_pengadaan ?? '—' }}</td>
                            <td>
                                <div class="flex items-center gap-1">
                                    <a href="{{ route('units.show', [$product, $unit]) }}"
                                       class="btn-sm btn-ghost text-gray-600">Detail</a>
                                    @if(auth()->user()->hasRole('admin', 'staff'))
                                        <a href="{{ route('qr.show', $unit->qr_code) }}"
                                           target="_blank"
                                           class="btn-sm btn-ghost text-blue-600">QR</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6">
                                <div class="empty-state py-8">
                                    <p class="empty-state-title">Belum ada unit terdaftar</p>
                                    @if(auth()->user()->hasRole('admin', 'staff'))
                                        <a href="{{ route('units.create', $product) }}" class="btn-primary btn-sm mt-3">Tambah Unit Pertama</a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>
@endsection


