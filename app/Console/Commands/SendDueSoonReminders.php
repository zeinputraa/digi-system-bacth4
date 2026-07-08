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
        $details = BorrowingDetail::where('status', StatusBorrowingDetail::Dipinjam->value)
            ->with('borrowing.borrower')
            ->get();

        $sentCount = 0;

        // Pre-calculate next working day once using a small holiday fetch
        $nextWorkday = $this->nextWorkingDay(now());

        foreach ($details as $detail) {
            $planned = Carbon::parse($detail->tanggal_kembali_rencana);

            if ($planned->toDateString() === $nextWorkday->toDateString()) {
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

    /**
     * Return the first upcoming working day after now, skipping weekends and holidays.
     * Fetches holidays for the next 14 days in a single query.
     */
    private function nextWorkingDay(Carbon $from): Carbon
    {
        // Fetch the next 14 days of holidays in one query to avoid per-day DB hits
        $holidayDates = Holiday::whereBetween('tanggal', [
            $from->copy()->addDay()->format('Y-m-d'),
            $from->copy()->addDays(14)->format('Y-m-d'),
        ])->pluck('tanggal')->map(fn ($t) => $t->format('Y-m-d'))->flip()->all();

        $temp = $from->copy()->addDay();
        while ($temp->isWeekend() || isset($holidayDates[$temp->format('Y-m-d')])) {
            $temp->addDay();
        }

        return $temp;
    }
}
