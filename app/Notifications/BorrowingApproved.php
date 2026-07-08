<?php

namespace App\Notifications;

use App\Models\Borrowing;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class BorrowingApproved extends Notification
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
            'message' => "Pengajuan peminjaman Anda #{$this->borrowing->kode_peminjaman} telah disetujui",
            'url' => route('borrowings.my'),
        ];
    }
}
