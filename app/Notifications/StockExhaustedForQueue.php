<?php

namespace App\Notifications;

use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockExhaustedForQueue extends Notification
{
    use Queueable;

    public function __construct(protected Borrowing $borrowing, protected string $productName) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'borrowing_id' => $this->borrowing->id,
            'kode_peminjaman' => $this->borrowing->kode_peminjaman,
            'message' => "Stok untuk '{$this->productName}' pada pengajuan #{$this->borrowing->kode_peminjaman} Anda sudah habis dialokasikan ke antrean sebelumnya.",
            'url' => route('borrowings.my'),
        ];
    }
}
