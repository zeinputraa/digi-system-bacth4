<?php

namespace App\Enums;

enum StatusBorrowingDetail: string
{
    case Diajukan = 'diajukan';
    case Disetujui = 'disetujui';
    case Ditolak = 'ditolak';
    case Dipinjam = 'dipinjam';
    case Dikembalikan = 'dikembalikan';
    case Terlambat = 'terlambat';
    case Bermasalah = 'bermasalah';
    case SelesaiBermasalah = 'selesai_bermasalah';
    case DibatalkanNoShow = 'dibatalkan_no_show';
    case DibatalkanSla = 'dibatalkan_sla';
}
