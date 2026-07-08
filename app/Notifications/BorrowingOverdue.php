<?php

namespace App\Notifications;

use App\Models\BorrowingDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BorrowingOverdue extends Notification
{
    use Queueable;

    public function __construct(protected BorrowingDetail $detail, protected int $hariTerlambat) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        $namaBarang = $this->detail->product->nama_barang ?? 'Barang';

        return [
            'borrowing_detail_id' => $this->detail->id,
            'nama_barang' => $namaBarang,
            'hari_terlambat' => $this->hariTerlambat,
            'message' => "{$namaBarang} sudah terlambat {$this->hariTerlambat} hari kerja",
            'url' => route('borrowings.my'),
        ];
    }
}
