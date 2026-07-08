<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Laporan Inventaris — {{ ucfirst($jenis) }}</title>
    <style>
        body {
            font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
            color: #333333;
            font-size: 11px;
            line-height: 1.4;
            margin: 0;
            padding: 0;
        }
        .header {
            border-bottom: 2px solid #E11E26; /* Brand Red */
            padding-bottom: 12px;
            margin-bottom: 20px;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
        }
        .header-title {
            font-size: 20px;
            font-weight: bold;
            color: #E11E26;
            margin: 0 0 4px 0;
        }
        .header-meta {
            font-size: 11px;
            color: #666666;
            margin: 0;
        }
        .section-title {
            font-size: 14px;
            font-weight: bold;
            color: #E11E26;
            margin: 20px 0 10px 0;
            border-bottom: 1px solid #e5e5e5;
            padding-bottom: 4px;
            page-break-after: avoid;
        }
        table.data-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table.data-table th {
            background-color: #f7f7f7;
            border: 1px solid #dddddd;
            color: #333333;
            font-weight: bold;
            padding: 6px 8px;
            text-align: left;
        }
        table.data-table td {
            border: 1px solid #dddddd;
            padding: 5px 8px;
        }
        table.data-table tr:nth-child(even) td {
            background-color: #fafafa;
        }
        .page-break {
            page-break-after: always;
        }
        .summary-box {
            background-color: #fff8f8;
            border: 1px solid #ffd1d1;
            border-radius: 4px;
            padding: 10px 15px;
            margin-bottom: 15px;
        }
        .summary-box p {
            margin: 4px 0;
        }
        .badge {
            display: inline-block;
            padding: 2px 5px;
            font-size: 9px;
            font-weight: bold;
            border-radius: 3px;
        }
        .badge-baik { background-color: #def7ec; color: #03543f; }
        .badge-rusak { background-color: #fde8e8; color: #9b1c1c; }
        .badge-kuning { background-color: #fef08a; color: #854d0e; }
        .badge-tersedia { background-color: #e1effe; color: #1e429f; }
        .text-right { text-align: right; }
        .font-mono { font-family: monospace; }
        .text-bold { font-weight: bold; }
    </style>
</head>
<body>

    <div class="header">
        <table class="header-table">
            <tr>
                <td>
                    <h1 class="header-title">LAPORAN INVENTARIS ASET</h1>
                    <p class="header-meta">PT DIGITAL INDONESIA — Divisi Operasional & Infrastruktur IT</p>
                </td>
                <td style="text-align: right; vertical-align: bottom;">
                    <p class="header-meta" style="font-weight: bold;">Jenis: {{ strtoupper($jenis) }}</p>
                    <p class="header-meta">Periode: {{ $periode_mulai->format('d M Y') }} s/d {{ $periode_selesai->format('d M Y') }}</p>
                </td>
            </tr>
        </table>
    </div>

    {{-- Ringkasan Eksekutif --}}
    <div class="summary-box">
        <p class="text-bold" style="font-size: 12px; color: #E11E26; margin-bottom: 6px;">Ringkasan Eksekutif</p>
        <table style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="width: 50%; vertical-align: top;">
                    <p>Total Pengajuan Masuk: <strong>{{ $aktivitas['masuk'] }}</strong></p>
                    <p>Total Unit Disetujui: <strong>{{ $aktivitas['disetujui'] }}</strong></p>
                    <p>Total Unit Terlambat: <strong>{{ $aktivitas['terlambat'] }}</strong></p>
                    <p>Total Nilai Aset Dimiliki: <strong>Rp {{ number_format($total_nilai_aset, 0, ',', '.') }}</strong></p>
                </td>
                <td style="width: 50%; vertical-align: top; text-align: right;">
                    <p>Total Insiden Dilaporkan: <strong>{{ array_sum($insiden) }}</strong></p>
                    <p>Total Kerugian Aset (Write-off): <strong style="color: #E11E26; font-size: 13px;">Rp {{ number_format($kerugian['total_kerugian'], 2, ',', '.') }}</strong></p>
                </td>
            </tr>
        </table>
    </div>

    {{-- a) Aktivitas Peminjaman --}}
    <h2 class="section-title">A. Aktivitas Peminjaman & Insiden</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Metrik Peminjaman</th>
                <th class="text-right">Jumlah</th>
                <th>Metrik Insiden & Kerusakan</th>
                <th class="text-right">Jumlah</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Pengajuan Masuk</td>
                <td class="text-right">{{ $aktivitas['masuk'] }}</td>
                <td>Insiden Rusak Ringan</td>
                <td class="text-right">{{ $insiden['rusak_ringan'] }}</td>
            </tr>
            <tr>
                <td>Pengajuan Disetujui</td>
                <td class="text-right">{{ $aktivitas['disetujui'] }}</td>
                <td>Insiden Rusak Berat</td>
                <td class="text-right">{{ $insiden['rusak_berat'] }}</td>
            </tr>
            <tr>
                <td>Pengajuan Ditolak</td>
                <td class="text-right">{{ $aktivitas['ditolak'] }}</td>
                <td>Unit Hilang (Dilaporkan)</td>
                <td class="text-right">{{ $insiden['hilang'] }}</td>
            </tr>
            <tr>
                <td>Pengajuan Selesai</td>
                <td class="text-right">{{ $aktivitas['selesai'] }}</td>
                <td class="text-bold">Total Insiden</td>
                <td class="text-right text-bold">{{ array_sum($insiden) }}</td>
            </tr>
            <tr>
                <td>Peminjaman Terlambat</td>
                <td class="text-right">{{ $aktivitas['terlambat'] }}</td>
                <td></td>
                <td></td>
            </tr>
        </tbody>
    </table>

    {{-- Tren Bulanan (Kuartalan / Tahunan) --}}
    @if(!empty($tren_bulanan))
        <h2 class="section-title">B. Tren Perbandingan Bulanan</h2>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Bulan</th>
                    <th class="text-right">Peminjaman Masuk</th>
                    <th class="text-right">Disetujui</th>
                    <th class="text-right">Selesai</th>
                    <th class="text-right">Insiden Rusak</th>
                    <th class="text-right">Insiden Hilang</th>
                    <th class="text-right">Valuasi Kerugian (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($tren_bulanan as $tren)
                    <tr>
                        <td>{{ $tren['bulan'] }}</td>
                        <td class="text-right">{{ $tren['peminjaman']['masuk'] }}</td>
                        <td class="text-right">{{ $tren['peminjaman']['disetujui'] }}</td>
                        <td class="text-right">{{ $tren['peminjaman']['selesai'] }}</td>
                        <td class="text-right">{{ $tren['insiden']['rusak_ringan'] + $tren['insiden']['rusak_berat'] }}</td>
                        <td class="text-right">{{ $tren['insiden']['hilang'] }}</td>
                        <td class="text-right">{{ number_format($tren['kerugian'], 2, ',', '.') }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div class="page-break"></div>

    {{-- b) Snapshot kondisi aset per kategori --}}
    <h2 class="section-title">C. Kondisi & Status Aset Per Kategori</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th rowspan="2">Kategori</th>
                <th colspan="3" style="text-align: center;">Kondisi</th>
                <th colspan="5" style="text-align: center;">Status</th>
                <th rowspan="2" class="text-right">Total</th>
            </tr>
            <tr>
                <th class="text-right">Baik</th>
                <th class="text-right">R. Ringan</th>
                <th class="text-right">R. Berat</th>
                <th class="text-right">Tersedia</th>
                <th class="text-right">Pinjam</th>
                <th class="text-right">Maint.</th>
                <th class="text-right">Lapor Hlg</th>
                <th class="text-right">Hlg Perm</th>
            </tr>
        </thead>
        <tbody>
            @foreach($snapshot as $snap)
                <tr>
                    <td class="text-bold">{{ $snap['kategori'] }}</td>
                    <td class="text-right">{{ $snap['kondisi']['baik'] }}</td>
                    <td class="text-right">{{ $snap['kondisi']['rusak_ringan'] }}</td>
                    <td class="text-right">{{ $snap['kondisi']['rusak_berat'] }}</td>
                    <td class="text-right">{{ $snap['status']['tersedia'] }}</td>
                    <td class="text-right">{{ $snap['status']['dipinjam'] }}</td>
                    <td class="text-right">{{ $snap['status']['maintenance'] }}</td>
                    <td class="text-right">{{ $snap['status']['dilaporkan_hilang'] }}</td>
                    <td class="text-right">{{ $snap['status']['hilang_permanen'] }}</td>
                    <td class="text-right text-bold">{{ $snap['total'] }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    {{-- d) Aset yang perlu perhatian --}}
    <h2 class="section-title">D. Aset Yang Perlu Perhatian Khusus</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>Kode Unit</th>
                <th>Nama Barang</th>
                <th>Status</th>
                <th>Kondisi</th>
                <th>Total Insiden (Periode)</th>
                <th>Catatan Masalah</th>
            </tr>
        </thead>
        <tbody>
            @forelse($perhatian as $p)
                <tr>
                    <td class="font-mono text-bold">{{ $p->kode_unit }}</td>
                    <td>{{ $p->product->nama_barang ?? '—' }}</td>
                    <td><span class="badge badge-kuning">{{ $p->status->value }}</span></td>
                    <td><span class="badge @if($p->kondisi->value === 'baik') badge-baik @else badge-rusak @endif">{{ $p->kondisi->value }}</span></td>
                    <td class="text-right">{{ $p->incidentReports->count() }}</td>
                    <td>
                        @if($p->status->value === 'maintenance')
                            Dalam pemeliharaan aktif.
                        @endif
                        @if($p->incidentReports->count() > 1)
                            Insiden berulang ({{ $p->incidentReports->count() }}x laporan).
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" style="text-align: center; color: #666666;">Tidak ada unit yang memerlukan perhatian khusus saat ini.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="page-break"></div>

    {{-- c) Daftar aset lengkap --}}
    <h2 class="section-title">E. Lampiran: Daftar Aset Lengkap</h2>
    <table class="data-table" style="font-size: 10px;">
        <thead>
            <tr>
                <th>Kode Unit</th>
                <th>Nama Barang</th>
                <th>Kategori</th>
                <th>Kondisi</th>
                <th>Status</th>
                <th>Lokasi</th>
                <th class="text-right">Harga Perolehan</th>
            </tr>
        </thead>
        <tbody>
            @foreach($daftar_aset as $unit)
                <tr>
                    <td class="font-mono text-bold">{{ $unit->kode_unit }}</td>
                    <td>{{ $unit->product->nama_barang ?? '—' }}</td>
                    <td>{{ $unit->product->category->nama_kategori ?? '—' }}</td>
                    <td>{{ $unit->kondisi->value }}</td>
                    <td>{{ $unit->status->value }}</td>
                    <td>{{ $unit->lokasi_penyimpanan }}</td>
                    <td class="text-right font-mono">Rp {{ number_format($unit->harga_perolehan, 2, ',', '.') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

</body>
</html>
