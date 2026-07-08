<?php

namespace App\Notifications;

use App\Models\BorrowingDetail;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BorrowingDueSoon extends Notification
{
    use Queueable;

    public function __construct(protected BorrowingDetail $detail) {}

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
            'tanggal_kembali_rencana' => $this->detail->tanggal_kembali_rencana->toDateString(),
            'message' => "Pengembalian {$namaBarang} jatuh tempo besok",
            'url' => route('borrowings.my'),
        ];
    }
}
