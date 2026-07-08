<?php

namespace App\Http\Controllers;

use App\Enums\StatusBorrowing;
use App\Enums\StatusUnit;
use App\Models\BorrowingDetail;
use App\Models\Holiday;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AvailabilityController extends Controller
{
    public function calendar(Request $request, Product $product): JsonResponse
    {
        $request->validate([
            'bulan' => 'required|date_format:Y-m',
        ]);

        $start = Carbon::parse($request->bulan.'-01')->startOfMonth();
        $end = $start->copy()->endOfMonth();

        $totalUnits = $product->units()
            ->whereIn('status', [StatusUnit::Tersedia->value, StatusUnit::Dipinjam->value])
            ->count();

        // Single query: get all holidays in the month
        $holidays = Holiday::whereBetween('tanggal', [$start->format('Y-m-d'), $end->format('Y-m-d')])
            ->get()
            ->keyBy(fn ($item) => $item->tanggal->format('Y-m-d'));

        $today = Carbon::today();

        // Single query: fetch all overlapping bookings for the month in one shot
        $allBookedDetails = BorrowingDetail::where('product_id', $product->id)
            ->whereHas('borrowing', function ($q) use ($start, $end) {
                $q->whereIn('status', [
                    StatusBorrowing::Disetujui->value,
                    StatusBorrowing::Berjalan->value,
                ])
                    ->where('tanggal_pinjam_rencana', '<=', $end->format('Y-m-d'))
                    ->where('tanggal_kembali_rencana', '>=', $start->format('Y-m-d'));
            })
            ->with('borrowing:id,tanggal_pinjam_rencana,tanggal_kembali_rencana')
            ->get();

        $hasil = [];
        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');
            $holiday = $holidays->get($dateStr);

            if ($date->isBefore($today)) {
                $hasil[$dateStr] = [
                    'total' => $totalUnits,
                    'tersedia' => 0,
                    'status' => 'merah',
                    'libur' => $holiday ? $holiday->keterangan : null,
                ];

                continue;
            }

            if ($holiday) {
                $hasil[$dateStr] = [
                    'total' => $totalUnits,
                    'tersedia' => 0,
                    'status' => 'merah',
                    'libur' => $holiday->keterangan,
                ];

                continue;
            }

            // Filter in memory: count details whose borrowing period overlaps this date
            $terpakai = $allBookedDetails->filter(function ($detail) use ($dateStr) {
                $bStart = $detail->borrowing->tanggal_pinjam_rencana->format('Y-m-d');
                $bEnd = $detail->borrowing->tanggal_kembali_rencana->format('Y-m-d');

                return $bStart <= $dateStr && $bEnd >= $dateStr;
            })->count();

            $tersedia = max(0, $totalUnits - $terpakai);
            $hasil[$dateStr] = [
                'total' => $totalUnits,
                'tersedia' => $tersedia,
                'status' => $tersedia === 0 ? 'merah' : ($tersedia < $totalUnits ? 'kuning' : 'hijau'),
            ];
        }

        return response()->json($hasil);
    }
}
