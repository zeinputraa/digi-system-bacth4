<?php

namespace App\Console\Commands;

use App\Enums\StatusBorrowingDetail;
use App\Models\BorrowingDetail;
use App\Models\Holiday;
use App\Models\User;
use App\Notifications\BorrowingOverdue;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendOverdueNotifications extends Command
{
    protected $signature = 'notifications:overdue';

    protected $description = 'Kirim notifikasi keterlambatan unit yang lewat batas waktu ke peminjam, admin & staff';

    public function handle(): void
    {
        $details = BorrowingDetail::whereIn('status', [
            StatusBorrowingDetail::Dipinjam->value,
            StatusBorrowingDetail::Terlambat->value,
        ])->get();

        $recipients = User::whereHas('role', function ($q) {
            $q->whereIn('name', ['admin', 'staff']);
        })->get();

        $sentCount = 0;

        foreach ($details as $detail) {
            $planned = Carbon::parse($detail->tanggal_kembali_rencana);
            $today = now();

            if ($today->gt($planned)) {
                $lateDays = 0;
                $temp = $planned->copy()->addDay();
                while ($temp->lte($today)) {
                    if (! $temp->isWeekend() && ! Holiday::where('tanggal', $temp->format('Y-m-d'))->exists()) {
                        $lateDays++;
                    }
                    $temp->addDay();
                }

                if ($lateDays > 0) {
                    // Update status detail di DB menjadi Terlambat
                    $detail->update([
                        'status' => StatusBorrowingDetail::Terlambat->value,
                        'hari_terlambat' => $lateDays,
                    ]);

                    // Kirim notifikasi jika belum dikirim hari ini
                    $alreadySent = DB::table('notifications')
                        ->whereDate('created_at', now()->toDateString())
                        ->where(function ($q) use ($detail) {
                            $q->where('data->borrowing_detail_id', $detail->id)
                                ->orWhere('data', 'like', '%"borrowing_detail_id":'.$detail->id.'%')
                                ->orWhere('data', 'like', '%"borrowing_detail_id":"'.$detail->id.'"%');
                        })
                        ->exists();

                    if (! $alreadySent) {
                        // 1. Kirim ke peminjam
                        $borrower = $detail->borrowing->borrower;
                        if ($borrower) {
                            $borrower->notify(new BorrowingOverdue($detail, $lateDays));
                        }

                        // 2. Kirim ke Admin & Staff
                        foreach ($recipients as $user) {
                            $user->notify(new BorrowingOverdue($detail, $lateDays));
                        }

                        $sentCount++;
                    }
                }
            }
        }

        $this->info("Overdue check complete. Sent {$sentCount} overdue alerts.");
    }
}
