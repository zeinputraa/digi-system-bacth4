@extends('layouts.app')

@php $pageTitle = 'Ajukan Peminjaman'; @endphp

@section('content')
<div class="space-y-5">
    <div class="page-header">
        <div>
            <h1 class="page-title">Ajukan Peminjaman</h1>
            <p class="page-subtitle">Pilih barang dan tentukan jadwal peminjaman</p>
        </div>
        <a href="{{ route('borrowings.my') }}" class="btn-secondary">Peminjaman Saya</a>
    </div>

    {{-- Form pengajuan --}}
    <form method="POST" action="{{ route('borrowings.store') }}">
        @csrf

        <div x-data="{
            step: 1,
            cart: [],
            searchQuery: '',
            tanggalPinjam: '{{ old('tanggal_pinjam_rencana') }}',
            tanggalKembali: '{{ old('tanggal_kembali_rencana') }}',
            selectedProduct: '',
            currentMonth: '{{ now()->format('Y-m') }}',
            calendarData: {},
            monthName: '',
            blankDays: 0,
            init() {
                this.$watch('step', value => {
                    if (value === 2 && this.cart.length > 0) {
                        if (!this.selectedProduct || !this.cart.find(c => c.product_id == this.selectedProduct)) {
                            this.selectedProduct = this.cart[0].product_id;
                        }
                        this.loadCalendar();
                    }
                });
            },
            getMonthName(yearMonth) {
                if (!yearMonth) return '';
                const [year, month] = yearMonth.split('-');
                const date = new Date(year, month - 1, 1);
                return date.toLocaleDateString('id-ID', { month: 'long', year: 'numeric' });
            },
            async loadCalendar() {
                if (!this.selectedProduct) {
                    this.calendarData = {};
                    this.blankDays = 0;
                    return;
                }
                const res = await fetch(`/produk/${this.selectedProduct}/ketersediaan?bulan=${this.currentMonth}`);
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
                if (status === 'merah') return;
                if (!this.tanggalPinjam || (this.tanggalPinjam && this.tanggalKembali)) {
                    this.tanggalPinjam = tanggal;
                    this.tanggalKembali = '';
                } else {
                    if (new Date(tanggal) < new Date(this.tanggalPinjam)) {
                        this.tanggalPinjam = tanggal;
                        this.tanggalKembali = '';
                    } else {
                        this.tanggalKembali = tanggal;
                    }
                }
            },
            isDateInRange(tanggal) {
                if (!this.tanggalPinjam) return false;
                if (tanggal === this.tanggalPinjam || tanggal === this.tanggalKembali) return true;
                if (this.tanggalPinjam && this.tanggalKembali) {
                    const date = new Date(tanggal);
                    return date >= new Date(this.tanggalPinjam) && date <= new Date(this.tanggalKembali);
                }
                return false;
            },
            addProduct(id, nama, tersedia) {
                let existing = this.cart.find(item => item.product_id === id);
                if (existing) {
                    if (existing.qty < tersedia) {
                        existing.qty++;
                    } else {
                        alert('Tidak dapat melebihi unit tersedia.');
                    }
                } else {
                    this.cart.push({
                        product_id: id,
                        nama: nama,
                        qty: 1,
                        tersedia: tersedia
                    });
                }
            },
            removeProduct(id) {
                this.cart = this.cart.filter(item => item.product_id !== id);
                if (this.selectedProduct == id) {
                    this.selectedProduct = '';
                }
            },
            updateQty(id, qty) {
                let item = this.cart.find(i => i.product_id === id);
                if (item) {
                    item.qty = Math.max(1, Math.min(qty, item.tersedia));
                }
            }
        }">

            {{-- Step Indicator --}}
            <div class="flex items-center gap-2 mb-6">
                @foreach([1 => 'Pilih Barang', 2 => 'Pilih Jadwal', 3 => 'Review & Kirim'] as $s => $label)
                    <div class="flex items-center gap-2">
                        <div class="flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm font-medium transition-all"
                             :class="step === {{ $s }} ? 'bg-telkom-600 text-white' : (step > {{ $s }} ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-400')">
                            <span class="w-5 h-5 rounded-full flex items-center justify-center text-xs font-bold"
                                  :class="step === {{ $s }} ? 'bg-white text-telkom-600' : (step > {{ $s }} ? 'bg-emerald-400 text-white' : 'bg-gray-300 text-gray-500')">
                                <span x-show="step > {{ $s }}">✓</span>
                                <span x-show="step <= {{ $s }}">{{ $s }}</span>
                            </span>
                            <span class="hidden sm:inline">{{ $label }}</span>
                        </div>
                        @if($s < 3)
                            <svg class="w-4 h-4 text-gray-300" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                            </svg>
                        @endif
                    </div>
                @endforeach
            </div>

            {{-- STEP 1: Pilih Barang --}}
            <div x-show="step === 1">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    {{-- Search & Katalog --}}
                    <div class="lg:col-span-2 space-y-4">
                        <div class="card">
                            <div class="card-header"><p class="card-title">Pilih Barang dari Katalog</p></div>
                            <div class="p-4 border-b border-gray-100">
                                <input type="text" placeholder="Cari nama barang..."
                                       class="form-input" id="search-product" x-model="searchQuery"/>
                            </div>
                            <div class="divide-y divide-gray-50">
                                @foreach($products as $p)
                                    <div class="flex items-center justify-between px-4 py-3 hover:bg-gray-50 transition"
                                         x-show="searchQuery === '' || '{{ strtolower($p->nama_barang) }}'.includes(searchQuery.toLowerCase())">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gray-100 rounded-lg flex items-center justify-center shrink-0">
                                                <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                                                </svg>
                                            </div>
                                            <div>
                                                <p class="text-sm font-medium text-gray-800">{{ $p->nama_barang }}</p>
                                                <p class="text-xs text-gray-400">{{ $p->units_count }} tersedia</p>
                                            </div>
                                        </div>
                                        @if($p->units_count > 0)
                                            <button type="button" @click="addProduct({{ $p->id }}, '{{ $p->nama_barang }}', {{ $p->units_count }})" class="btn-sm btn-primary">+ Tambah</button>
                                        @else
                                            <span class="badge badge-ditolak text-[10px]">Habis</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>

                    {{-- Keranjang --}}
                    <div class="card h-fit">
                        <div class="card-header">
                            <p class="card-title">Keranjang</p>
                            <span class="badge badge-berjalan" x-text="cart.length + ' jenis'"></span>
                        </div>
                        <div class="divide-y divide-gray-50">
                            <template x-for="(item, index) in cart" :key="item.product_id">
                                <div class="px-4 py-3 flex items-center justify-between gap-2">
                                    <div class="min-w-0">
                                        <p class="text-sm font-medium text-gray-800 truncate" x-text="item.nama"></p>
                                        <div class="flex items-center gap-2 mt-1">
                                            <span class="text-xs text-gray-400">Qty:</span>
                                            <input type="number" min="1" :max="item.tersedia" 
                                                   :value="item.qty" 
                                                   @input="updateQty(item.product_id, parseInt($event.target.value))"
                                                   class="w-16 px-1.5 py-0.5 border border-gray-200 rounded text-xs"/>
                                        </div>
                                        <input type="hidden" :name="'items['+index+'][product_id]'" :value="item.product_id">
                                        <input type="hidden" :name="'items['+index+'][qty]'" :value="item.qty">
                                    </div>
                                    <button type="button" @click="removeProduct(item.product_id)" class="text-red-400 hover:text-red-600">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12"/>
                                        </svg>
                                    </button>
                                </div>
                            </template>
                            <div x-show="cart.length === 0" class="p-4 text-center text-xs text-gray-400">
                                Keranjang kosong. Pilih barang di katalog.
                            </div>
                        </div>
                        <div class="p-4 border-t border-gray-100">
                            <button type="button" @click="step = 2" class="btn-primary w-full justify-center" :disabled="cart.length === 0">
                                Lanjut Pilih Jadwal →
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            {{-- STEP 2: Pilih Jadwal --}}
            <div x-show="step === 2" class="space-y-4">
                <div class="card">
                    <div class="card-header">
                        <p class="card-title">Pilih Tanggal Pinjam & Kembali</p>
                    </div>
                    <div class="card-body">
                        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                            
                            {{-- Kalender --}}
                            <div class="lg:col-span-2 space-y-4">
                                <div class="flex items-center justify-between bg-gray-50 p-3 rounded-lg border border-gray-100">
                                    <div class="w-1/2">
                                        <x-input-label for="select_availability_product" :value="__('Cek Ketersediaan Aset:')" />
                                        <select x-model="selectedProduct" @change="loadCalendar()" 
                                                x-effect="
                                                    $el.innerHTML = '';
                                                    cart.forEach(item => {
                                                        let opt = document.createElement('option');
                                                        opt.value = item.product_id;
                                                        opt.textContent = item.nama;
                                                        opt.selected = (item.product_id == selectedProduct);
                                                        $el.appendChild(opt);
                                                    });
                                                "
                                                class="form-select mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-xs py-1">
                                        </select>
                                    </div>
                                    <div class="flex items-center gap-2">
                                        <button type="button" @click="prevMonth()" class="p-1 rounded hover:bg-gray-200">
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/>
                                            </svg>
                                        </button>
                                        <span class="text-sm font-semibold text-gray-700 w-32 text-center" x-text="monthName"></span>
                                        <button type="button" @click="nextMonth()" class="p-1 rounded hover:bg-gray-200">
                                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/>
                                            </svg>
                                        </button>
                                    </div>
                                </div>

                                <div x-show="selectedProduct" class="border border-gray-100 rounded-xl p-3 bg-white">
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
                                                    'bg-emerald-50 text-emerald-700 hover:bg-emerald-100 border border-emerald-100': day.status === 'hijau' && !isDateInRange(tanggal),
                                                    'bg-amber-50 text-amber-700 hover:bg-amber-100 border border-amber-100': day.status === 'kuning' && !isDateInRange(tanggal),
                                                    'bg-red-50 text-red-400 cursor-not-allowed opacity-50 border border-red-100': day.status === 'merah' && !isDateInRange(tanggal),
                                                    'bg-indigo-600 text-white shadow-md ring-2 ring-offset-2 ring-indigo-500': isDateInRange(tanggal),
                                                }"
                                                class="aspect-square flex flex-col items-center justify-between p-1 text-xs rounded-lg transition-all relative">
                                                <span class="font-semibold" x-text="tanggal.split('-')[2]"></span>
                                                <template x-if="day.libur">
                                                    <span class="text-[8px] text-red-500 font-bold truncate max-w-full text-center px-0.5" :title="day.libur" x-text="day.libur"></span>
                                                </template>
                                                <template x-if="!day.libur">
                                                    <span class="text-[9px]" :class="isDateInRange(tanggal) ? 'text-indigo-100' : 'text-gray-400'" x-text="day.tersedia + '/' + day.total"></span>
                                                </template>
                                            </button>
                                        </template>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3 mt-3 text-[10px] text-gray-500 justify-center">
                                    <span class="flex items-center gap-1">
                                        <span class="w-3 h-3 rounded bg-emerald-50 border border-emerald-100 inline-block"></span> Tersedia
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span class="w-3 h-3 rounded bg-amber-50 border border-amber-100 inline-block"></span> Terpakai Sebagian
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span class="w-3 h-3 rounded bg-red-50 border border-red-100 inline-block"></span> Penuh (Tidak Tersedia)
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <span class="w-3 h-3 rounded bg-indigo-600 inline-block"></span> Terpilih
                                    </span>
                                </div>
                            </div>

                            {{-- Input Manual / Review --}}
                            <div class="space-y-4">
                                <div class="bg-indigo-50/50 border border-indigo-100 rounded-xl p-4 space-y-3">
                                    <h4 class="text-xs font-bold text-indigo-800 uppercase tracking-wider">Periode Terpilih</h4>
                                    
                                    <div class="space-y-1">
                                        <span class="text-xs text-gray-400">Tanggal Mulai:</span>
                                        <div class="text-sm font-semibold text-gray-800" x-text="tanggalPinjam ? tanggalPinjam : 'Belum dipilih'"></div>
                                    </div>
                                    <div class="space-y-1">
                                        <span class="text-xs text-gray-400">Tanggal Kembali:</span>
                                        <div class="text-sm font-semibold text-gray-800" x-text="tanggalKembali ? tanggalKembali : 'Belum dipilih'"></div>
                                    </div>
                                </div>

                                <div class="form-group">
                                    <x-input-label for="catatan" :value="__('Catatan (Opsional)')" />
                                    <textarea id="catatan" name="catatan" rows="3" class="mt-1 block w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm text-sm" placeholder="Keperluan peminjaman...">{{ old('catatan') }}</textarea>
                                </div>
                            </div>

                        </div>
                    </div>

                    {{-- Hidden inputs to submit --}}
                    <input type="hidden" name="tanggal_pinjam_rencana" :value="tanggalPinjam">
                    <input type="hidden" name="tanggal_kembali_rencana" :value="tanggalKembali">

                    <div class="px-5 py-3 border-t border-gray-100 flex gap-3">
                        <button type="button" @click="step = 1" class="btn-secondary">← Kembali</button>
                        <button type="button" @click="step = 3" class="btn-primary" :disabled="!tanggalPinjam || !tanggalKembali">Lanjut Review →</button>
                    </div>
                </div>
            </div>

            {{-- STEP 3: Review & Submit --}}
            <div x-show="step === 3" class="max-w-xl">
                <div class="card">
                    <div class="card-header"><p class="card-title">Review Pengajuan</p></div>
                    <div class="card-body space-y-4">
                        <div class="bg-gray-50 rounded-xl p-4 space-y-2">
                            <div class="text-sm font-semibold text-gray-700">Barang yang Diajukan:</div>
                            <template x-for="item in cart" :key="item.product_id">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-500" x-text="item.nama"></span>
                                    <span class="font-medium" x-text="item.qty + ' unit'"></span>
                                </div>
                            </template>
                            <div class="border-t border-gray-100 my-2 pt-2"></div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tgl Pinjam</span>
                                <span class="font-medium" x-text="tanggalPinjam"></span>
                            </div>
                            <div class="flex justify-between text-sm">
                                <span class="text-gray-500">Tgl Kembali</span>
                                <span class="font-medium" x-text="tanggalKembali"></span>
                            </div>
                        </div>
                        <div class="alert-info text-xs">
                            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span>Pengajuan akan diproses sesuai antrian FIFO. Notifikasi dikirim setelah disetujui Staff.</span>
                        </div>
                    </div>
                    <div class="px-5 py-3 border-t border-gray-100 flex gap-3">
                        <button type="button" @click="step = 2" class="btn-secondary">← Kembali</button>
                        <x-primary-button type="submit">
                            <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                            </svg>
                            Kirim Pengajuan
                        </x-primary-button>
                    </div>
                </div>
            </div>

        </div>
    </form>
</div>
@endsection
