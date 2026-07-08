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
        ])->with('borrowing.borrower')->get();

        if ($details->isEmpty()) {
            $this->info('Overdue check complete. No active borrowings.');

            return;
        }

        $recipients = User::whereHas('role', function ($q) {
            $q->whereIn('name', ['admin', 'staff']);
        })->get();

        // Single query: fetch all holidays between the earliest planned return and today
        $earliest = $details->min('tanggal_kembali_rencana');
        $today = now();
        $holidayDates = [];
        if ($earliest && $today->gt(Carbon::parse($earliest))) {
            $holidayDates = Holiday::whereBetween('tanggal', [
                Carbon::parse($earliest)->format('Y-m-d'),
                $today->format('Y-m-d'),
            ])->pluck('tanggal')->map(fn ($t) => $t->format('Y-m-d'))->flip()->all();
        }

        $sentCount = 0;

        foreach ($details as $detail) {
            $planned = Carbon::parse($detail->tanggal_kembali_rencana);

            if ($today->gt($planned)) {
                $lateDays = 0;
                $temp = $planned->copy()->addDay();
                while ($temp->lte($today)) {
                    if (! $temp->isWeekend() && ! isset($holidayDates[$temp->format('Y-m-d')])) {
                        $lateDays++;
                    }
                    $temp->addDay();
                }

                if ($lateDays > 0) {
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
                        $borrower = $detail->borrowing->borrower;
                        if ($borrower) {
                            $borrower->notify(new BorrowingOverdue($detail, $lateDays));
                        }

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
