<?php

namespace App\Notifications;

use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BorrowingRejected extends Notification
{
    use Queueable;

    public function __construct(protected Borrowing $borrowing) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'borrowing_id' => $this->borrowing->id,
            'kode_peminjaman' => $this->borrowing->kode_peminjaman,
            'alasan_penolakan' => $this->borrowing->alasan_penolakan,
            'message' => "Pengajuan peminjaman Anda #{$this->borrowing->kode_peminjaman} ditolak: {$this->borrowing->alasan_penolakan}",
            'url' => route('borrowings.my'),
        ];
    }
}
