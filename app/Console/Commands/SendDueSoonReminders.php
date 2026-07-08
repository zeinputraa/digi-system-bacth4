<?php

namespace App\Console\Commands;

use App\Enums\StatusBorrowingDetail;
use App\Models\BorrowingDetail;
use App\Models\Holiday;
use App\Notifications\BorrowingDueSoon;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SendDueSoonReminders extends Command
{
    protected $signature = 'notifications:due-soon-reminders';

    protected $description = 'Kirim notifikasi pengembalian unit H-1 hari kerja ke peminjam';

    public function handle(): void
    {
        $details = BorrowingDetail::where('status', StatusBorrowingDetail::Dipinjam->value)->get();
        $sentCount = 0;

        foreach ($details as $detail) {
            $planned = Carbon::parse($detail->tanggal_kembali_rencana);

            if ($this->isDueSoonWorkday($planned)) {
                $alreadySent = DB::table('notifications')
                    ->whereDate('created_at', now()->toDateString())
                    ->where(function ($q) use ($detail) {
                        $q->where('data->borrowing_detail_id', $detail->id)
                            ->orWhere('data', 'like', '%"borrowing_detail_id":'.$detail->id.'%')
                            ->orWhere('data', 'like', '%"borrowing_detail_id":"'.$detail->id.'"%');
                    })
                    ->exists();

                if (! $alreadySent) {
                    $borrower = $detail->borrowing->borrower;
                    if ($borrower) {
                        $borrower->notify(new BorrowingDueSoon($detail));
                        $sentCount++;
                    }
                }
            }
        }

        $this->info("Due soon reminders complete. Sent {$sentCount} notifications.");
    }

    private function isDueSoonWorkday(Carbon $planned): bool
    {
        $temp = now()->copy()->addDay();
        while ($temp->isWeekend() || Holiday::where('tanggal', $temp->format('Y-m-d'))->exists()) {
            $temp->addDay();
        }

        return $temp->toDateString() === $planned->toDateString();
    }
}
