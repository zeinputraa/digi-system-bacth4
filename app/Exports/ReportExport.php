<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;
use Maatwebsite\Excel\Concerns\WithTitle;

class ReportExport implements WithMultipleSheets
{
    public function __construct(protected array $data) {}

    public function sheets(): array
    {
        return [
            new ReportSummarySheet($this->data),
            new ReportAktivitasSheet($this->data),
            new ReportKondisiSheet($this->data),
            new ReportDaftarAsetSheet($this->data),
            new ReportKerugianSheet($this->data),
        ];
    }
}

class ReportSummarySheet implements FromArray, WithTitle
{
    public function __construct(protected array $data) {}

    public function array(): array
    {
        $rows = [
            ['LAPORAN INVENTARIS - RINGKASAN'],
            [],
            ['Jenis Laporan', ucfirst($this->data['jenis'])],
            ['Periode Mulai', $this->data['periode_mulai']->format('d M Y')],
            ['Periode Selesai', $this->data['periode_selesai']->format('d M Y')],
            ['Total Kerugian', $this->data['kerugian']['total_kerugian']],
            ['Total Nilai Aset', $this->data['total_nilai_aset']],
            [],
        ];

        if (! empty($this->data['tren_bulanan'])) {
            $rows[] = ['TREN BULANAN'];
            $rows[] = ['Bulan', 'Peminjaman Masuk', 'Peminjaman Disetujui', 'Peminjaman Selesai', 'Insiden Rusak', 'Insiden Hilang', 'Kerugian (Rp)'];
            foreach ($this->data['tren_bulanan'] as $tren) {
                $rows[] = [
                    $tren['bulan'],
                    $tren['peminjaman']['masuk'],
                    $tren['peminjaman']['disetujui'],
                    $tren['peminjaman']['selesai'],
                    $tren['insiden']['rusak_ringan'] + $tren['insiden']['rusak_berat'],
                    $tren['insiden']['hilang'],
                    $tren['kerugian'],
                ];
            }
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Ringkasan';
    }
}

class ReportAktivitasSheet implements FromArray, WithTitle
{
    public function __construct(protected array $data) {}

    public function array(): array
    {
        return [
            ['AKTIVITAS PEMINJAMAN & INSIDEN'],
            [],
            ['Metrik', 'Jumlah'],
            ['Pengajuan Masuk', $this->data['aktivitas']['masuk']],
            ['Pengajuan Disetujui', $this->data['aktivitas']['disetujui']],
            ['Pengajuan Ditolak', $this->data['aktivitas']['ditolak']],
            ['Pengajuan Selesai', $this->data['aktivitas']['selesai']],
            ['Pengajuan Terlambat', $this->data['aktivitas']['terlambat']],
            [],
            ['Insiden Rusak Ringan', $this->data['insiden']['rusak_ringan']],
            ['Insiden Rusak Berat', $this->data['insiden']['rusak_berat']],
            ['Insiden Hilang', $this->data['insiden']['hilang']],
        ];
    }

    public function title(): string
    {
        return 'Aktivitas Peminjaman';
    }
}

class ReportKondisiSheet implements FromArray, WithTitle
{
    public function __construct(protected array $data) {}

    public function array(): array
    {
        $rows = [
            ['SNAPSHOT KONDISI & STATUS ASET PER KATEGORI'],
            [],
            [
                'Kategori',
                'Baik',
                'Rusak Ringan',
                'Rusak Berat',
                'Tersedia',
                'Dipinjam',
                'Maintenance',
                'Dilaporkan Hilang',
                'Hilang Permanen',
                'Total Unit',
            ],
        ];

        foreach ($this->data['snapshot'] as $snap) {
            $rows[] = [
                $snap['kategori'],
                $snap['kondisi']['baik'],
                $snap['kondisi']['rusak_ringan'],
                $snap['kondisi']['rusak_berat'],
                $snap['status']['tersedia'],
                $snap['status']['dipinjam'],
                $snap['status']['maintenance'],
                $snap['status']['dilaporkan_hilang'],
                $snap['status']['hilang_permanen'],
                $snap['total'],
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Kondisi Aset';
    }
}

class ReportDaftarAsetSheet implements FromArray, WithTitle
{
    public function __construct(protected array $data) {}

    public function array(): array
    {
        $rows = [
            ['DAFTAR ASET LENGKAP'],
            [],
            ['Kode Unit', 'Nama Barang', 'Kategori', 'Kondisi', 'Status', 'Lokasi Penyimpanan', 'Harga Perolehan'],
        ];

        foreach ($this->data['daftar_aset'] as $unit) {
            $rows[] = [
                $unit->kode_unit,
                $unit->product->nama_barang ?? '—',
                $unit->product->category->nama_kategori ?? '—',
                $unit->kondisi->value ?? '—',
                $unit->status->value ?? '—',
                $unit->lokasi_penyimpanan,
                $unit->harga_perolehan,
            ];
        }

        return $rows;
    }

    public function title(): string
    {
        return 'Daftar Aset';
    }
}

class ReportKerugianSheet implements FromArray, WithTitle
{
    public function __construct(protected array $data) {}

    public function array(): array
    {
        return [
            ['KERUGIAN ASET (WRITE-OFF)'],
            [],
            ['Total Nilai Kerugian (Rupiah)', $this->data['kerugian']['total_kerugian']],
        ];
    }

    public function title(): string
    {
        return 'Kerugian';
    }
}
