<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Info Unit {{ $unit->kode_unit }} — Digi Inventory</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gray-950 min-h-screen flex items-center justify-center p-4">

<div class="w-full max-w-sm">

    {{-- Background decoration --}}
    <div class="absolute inset-0 overflow-hidden pointer-events-none">
        <div class="absolute top-0 right-0 w-64 h-64 rounded-full bg-telkom-600 opacity-5 blur-3xl"></div>
        <div class="absolute bottom-0 left-0 w-64 h-64 rounded-full bg-telkom-700 opacity-5 blur-3xl"></div>
    </div>

    <div class="relative">
        {{-- Header --}}
        <div class="text-center mb-6">
            <div class="inline-flex items-center justify-center w-12 h-12 bg-telkom-600 rounded-xl shadow-lg mb-3">
                <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" stroke-width="1.5" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
                </svg>
            </div>
            <h1 class="text-white font-bold text-lg">Digi Inventory</h1>
            <p class="text-gray-400 text-xs mt-0.5">Informasi Aset</p>
        </div>

        {{-- Card --}}
        <div class="bg-white rounded-2xl shadow-2xl overflow-hidden">
            <div class="h-1.5 bg-gradient-to-r from-telkom-600 to-telkom-400"></div>

            <div class="p-6">
                {{-- QR Code --}}
                <div class="flex justify-center mb-5">
                    <div class="p-3 border-2 border-gray-100 rounded-xl">
                        <canvas id="qr-public" class="block"></canvas>
                    </div>
                </div>

                {{-- Kode Unit --}}
                <div class="text-center mb-5">
                    <p class="font-mono text-2xl font-bold text-gray-900">{{ $unit->kode_unit }}</p>
                    <p class="text-gray-500 text-sm mt-1">{{ $unit->product->nama_barang }}</p>
                </div>

                <div class="divider"></div>

                {{-- Info Grid --}}
                <div class="space-y-3">
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Status</span>
                        @php
                            $statusBadge = match($unit->status->value) {
                                'tersedia'          => 'badge-tersedia',
                                'dipinjam'          => 'badge-dipinjam',
                                'maintenance'       => 'badge-maintenance',
                                'dilaporkan_hilang' => 'badge-hilang',
                                'hilang_permanen'   => 'badge-hilang-permanen',
                                default             => '',
                            };
                        @endphp
                        <span class="badge {{ $statusBadge }}">{{ ucfirst(str_replace('_', ' ', $unit->status->value)) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Kondisi</span>
                        <span class="text-sm font-medium text-gray-800">{{ ucfirst(str_replace('_', ' ', $unit->kondisi->value ?? $unit->kondisi)) }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Lokasi</span>
                        <span class="text-sm font-medium text-gray-800 text-right">{{ $unit->lokasi_penyimpanan ?? '—' }}</span>
                    </div>
                    <div class="flex items-center justify-between">
                        <span class="text-sm text-gray-500">Kategori</span>
                        <span class="text-sm font-medium text-gray-800">{{ $unit->product->category->nama_kategori ?? '—' }}</span>
                    </div>
                </div>

                <div class="divider"></div>

                {{-- CTA --}}
                @auth
                    @if(auth()->user()->hasRole('karyawan'))
                        <a href="{{ route('borrowings.create') }}?unit={{ $unit->id }}"
                           class="btn-primary w-full justify-center">
                            Ajukan Peminjaman Unit Ini
                        </a>
                    @else
                        <a href="{{ route('units.show', [$unit->product, $unit]) }}"
                           class="btn-secondary w-full justify-center">
                            Lihat Detail Lengkap
                        </a>
                    @endif
                @else
                    <a href="{{ route('login') }}"
                       class="btn-primary w-full justify-center">
                        Login untuk Pinjam
                    </a>
                @endauth
            </div>
        </div>

        <p class="text-center text-gray-600 text-xs mt-4">
            &copy; {{ date('Y') }} PT Digital Indonesia
        </p>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    generateQrCode('qr-public', '{{ url()->current() }}');
});
</script>
@endpush

@stack('scripts')
</body>
</html>
