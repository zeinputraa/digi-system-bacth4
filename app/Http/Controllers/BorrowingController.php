<?php

namespace App\Http\Controllers;

use App\Enums\StatusBorrowing;
use App\Enums\StatusBorrowingDetail;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingDetail;
use App\Models\Product;
use App\Models\ProductUnit;
use App\Notifications\BorrowingApproved;
use App\Notifications\BorrowingRejected;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

class BorrowingController extends Controller
{
    /**
     * Display a listing of borrowings.
     */
    public function index(Request $request): View
    {
        $this->syncPendingBorrowings();

        $user = auth()->user();
        $query = Borrowing::with(['borrower', 'details.product']);

        // Filter based on role
        if (! $user->hasRole('admin', 'staff')) {
            $query->where('user_id', $user->id);
        }

        // Filter based on status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $borrowings = $query->orderBy('created_at', 'desc')->paginate(12);

        return view('borrowings.index', compact('borrowings'));
    }

    /**
     * Show personal borrowings (My Borrowings).
     */
    public function my(Request $request): View
    {
        $user = auth()->user();
        $borrowings = Borrowing::with(['details.product', 'details.productUnit'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('borrowings.my', compact('borrowings'));
    }

    /**
     * Show form to create borrowing request.
     */
    public function create(Request $request): View
    {
        $products = Product::withCount(['units' => function ($q) {
            $q->where('status', StatusUnit::Tersedia->value);
        }])->get();

        return view('borrowings.create', compact('products'));
    }

    /**
     * Store a new borrowing request.
     */
    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.qty' => 'required|integer|min:1',
            'tanggal_pinjam_rencana' => 'required|date|after_or_equal:yesterday',
            'tanggal_kembali_rencana' => 'required|date|after:tanggal_pinjam_rencana',
            'catatan' => 'nullable|string|max:500',
        ]);

        $user = auth()->user();
        $start = $request->tanggal_pinjam_rencana;
        $end = $request->tanggal_kembali_rencana;

        // Perform date-range overlap check per product requested
        foreach ($request->items as $item) {
            $productId = $item['product_id'];
            $qty = $item['qty'];

            $product = Product::findOrFail($productId);
            $totalUnits = $product->units()
                ->whereIn('status', [StatusUnit::Tersedia->value, StatusUnit::Dipinjam->value])
                ->count();

            // Hitung unit dari produk ini yang statusnya DIPINJAM pada range tanggal yang sama
            $overlappingQty = BorrowingDetail::where('product_id', $productId)
                ->whereHas('borrowing', function ($q) use ($start, $end) {
                    $q->whereIn('status', [StatusBorrowing::Disetujui->value, StatusBorrowing::Berjalan->value])
                        ->where(function ($query) use ($start, $end) {
                            $query->whereBetween('tanggal_pinjam_rencana', [$start, $end])
                                ->orWhereBetween('tanggal_kembali_rencana', [$start, $end])
                                ->orWhere(function ($sub) use ($start, $end) {
                                    $sub->where('tanggal_pinjam_rencana', '<=', $start)
                                        ->where('tanggal_kembali_rencana', '>=', $end);
                                });
                        });
                })->count();

            $availableUnitsCount = $totalUnits - $overlappingQty;

            if ($availableUnitsCount < $qty) {
                return redirect()->back()
                    ->withInput()
                    ->with('error', "Stok untuk '{$product->nama_barang}' tidak mencukupi pada tanggal terpilih. Tersedia: {$availableUnitsCount} unit.");
            }
        }

        // Generate kode_peminjaman: BRW-YYYYMM-XXXX
        // Gunakan MAX atas suffix numerik dalam bulan berjalan (collision-safe seperti kode_unit)
        $bulanPrefix = 'BRW-'.date('Ym').'-';
        $lastNumber = Borrowing::where('kode_peminjaman', 'like', $bulanPrefix.'%')
            ->get()
            ->map(function (Borrowing $b): ?int {
                preg_match('/-(\d+)$/', $b->kode_peminjaman, $matches);

                return isset($matches[1]) ? (int) $matches[1] : null;
            })
            ->filter()
            ->max() ?? 0;

        $kode = $bulanPrefix.str_pad($lastNumber + 1, 4, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $request, $kode) {
            $borrowing = Borrowing::create([
                'user_id' => $user->id,
                'kode_peminjaman' => $kode,
                'tanggal_pengajuan' => now(),
                'tanggal_pinjam_rencana' => $request->tanggal_pinjam_rencana,
                'tanggal_kembali_rencana' => $request->tanggal_kembali_rencana,
                'status' => StatusBorrowing::Diajukan->value,
                'catatan' => $request->catatan,
            ]);

            foreach ($request->items as $item) {
                for ($i = 0; $i < $item['qty']; $i++) {
                    BorrowingDetail::create([
                        'borrowing_id' => $borrowing->id,
                        'product_id' => $item['product_id'],
                        'status' => StatusBorrowingDetail::Diajukan->value,
                        'tanggal_kembali_rencana' => $request->tanggal_kembali_rencana,
                    ]);
                }
            }
        });

        return redirect()->route('borrowings.my')->with('success', 'Permohonan peminjaman berhasil dikirim!');
    }

    /**
     * Show details of a borrowing.
     */
    public function show(string $id): View
    {
        $this->syncPendingBorrowings();

        $borrowing = Borrowing::with(['borrower', 'details.product', 'details.productUnit'])
            ->where('id', $id)
            ->firstOrFail();

        // Check if FIFO override warning is triggered
        $isFifoOverrideNeeded = false;
        $isStockInsufficient = false;

        if ($borrowing->status === StatusBorrowing::Diajukan) {
            foreach ($borrowing->details as $detail) {
                $hasEarlierQueue = Borrowing::where('status', StatusBorrowing::Diajukan->value)
                    ->where('created_at', '<', $borrowing->created_at)
                    ->whereHas('details', function ($q) use ($detail) {
                        $q->where('product_id', $detail->product_id);
                    })->exists();

                if ($hasEarlierQueue) {
                    $isFifoOverrideNeeded = true;
                    break;
                }
            }

            foreach ($borrowing->details->groupBy('product_id') as $prodId => $detailsGroup) {
                $reqQty = $detailsGroup->count();
                $isSufficient = $this->isStockSufficientForPeriod(
                    $prodId,
                    $borrowing->tanggal_pinjam_rencana,
                    $borrowing->tanggal_kembali_rencana,
                    $reqQty
                );
                if (! $isSufficient) {
                    $isStockInsufficient = true;
                    break;
                }
            }
        }

        return view('borrowings.show', compact('borrowing', 'isFifoOverrideNeeded', 'isStockInsufficient'));
    }

    /**
     * Approve a borrowing request while preserving FIFO queue rules when an override is requested.
     */
    public function approve(Request $request, string $id): RedirectResponse
    {
        $borrowing = Borrowing::findOrFail($id);

        $request->validate([
            'fifo_override' => 'nullable|boolean',
            'alasan_override' => 'required_if:fifo_override,1|nullable|string|max:500',
        ]);

        // Check if we have enough physical units with status 'Tersedia' before doing anything
        $insufficientProducts = [];
        foreach ($borrowing->details->groupBy('product_id') as $prodId => $detailsGroup) {
            $reqQty = $detailsGroup->count();
            $availableUnitsCount = ProductUnit::where('product_id', $prodId)
                ->where('status', StatusUnit::Tersedia->value)
                ->count();

            if ($availableUnitsCount < $reqQty) {
                $productName = optional($detailsGroup->first()->product)->nama_barang ?? 'Barang';
                $insufficientProducts[] = $productName;
            }
        }

        if (! empty($insufficientProducts)) {
            // Automatically cancel/reject this borrowing because the stock is not available in the database
            DB::transaction(function () use ($borrowing) {
                $borrowing->update([
                    'status' => StatusBorrowing::DibatalkanOtomatis->value,
                    'alasan_penolakan' => 'Stok unit fisik di gudang tidak mencukupi saat ini.',
                ]);

                foreach ($borrowing->details as $detail) {
                    $detail->update([
                        'status' => StatusBorrowingDetail::Ditolak->value,
                    ]);
                }
            });

            // Send notification to the borrower
            $productNamesStr = implode(', ', $insufficientProducts);
            $borrowing->borrower->notify(new \App\Notifications\StockExhaustedForQueue($borrowing, $productNamesStr));

            return redirect()->route('borrowings.show', $borrowing->id)
                ->with('error', 'Pengajuan otomatis dibatalkan karena unit fisik yang berstatus tersedia tidak mencukupi di gudang saat ini.');
        }

        try {
            DB::transaction(function () use ($borrowing, $request) {
                // Update status peminjaman
                $borrowing->update([
                    'status' => StatusBorrowing::Disetujui->value,
                    'approved_by' => auth()->id(),
                    'approved_at' => now(),
                    'fifo_override' => $request->boolean('fifo_override'),
                    'alasan_override' => $request->alasan_override,
                ]);

                // Alokasikan unit fisik otomatis yang statusnya Tersedia (1 unit per detail baris)
                foreach ($borrowing->details as $detail) {
                    $unit = ProductUnit::where('product_id', $detail->product_id)
                        ->where('status', StatusUnit::Tersedia->value)
                        ->whereNotIn('id', $borrowing->details()
                            ->whereNotNull('product_unit_id')
                            ->pluck('product_unit_id'))
                        ->first();

                    if (! $unit) {
                        throw new \Exception('Gagal menyetujui, unit fisik yang berstatus tersedia tidak mencukupi saat ini.');
                    }

                    $detail->update([
                        'product_unit_id' => $unit->id,
                        'status' => StatusBorrowingDetail::Disetujui->value,
                        'kondisi_saat_pinjam' => $unit->kondisi,
                    ]);

                    // Ubah status unit menjadi dipinjam (agar tidak dipakai orang lain)
                    $unit->update(['status' => StatusUnit::Dipinjam->value]);
                }
            });
        } catch (\Exception $e) {
            return redirect()->back()->with('error', $e->getMessage());
        }

        $borrowing->borrower->notify(new BorrowingApproved($borrowing));

        // Find and notify overlapping pending requests that are now blocked/insufficient
        $startStr = $borrowing->tanggal_pinjam_rencana->format('Y-m-d');
        $endStr = $borrowing->tanggal_kembali_rencana->format('Y-m-d');

        $overlappingPending = Borrowing::where('status', StatusBorrowing::Diajukan->value)
            ->where('id', '!=', $borrowing->id)
            ->where('tanggal_pinjam_rencana', '<=', $endStr)
            ->where('tanggal_kembali_rencana', '>=', $startStr)
            ->whereHas('details', function ($q) use ($borrowing) {
                $q->whereIn('product_id', $borrowing->details->pluck('product_id'));
            })
            ->get();

        foreach ($overlappingPending as $op) {
            $hasChange = false;
            foreach ($op->details->groupBy('product_id') as $prodId => $detailsGroup) {
                $reqQty = $detailsGroup->count();
                $isSufficient = $this->isStockSufficientForPeriod(
                    $prodId,
                    $op->tanggal_pinjam_rencana,
                    $op->tanggal_kembali_rencana,
                    $reqQty
                );

                if (! $isSufficient) {
                    $hasChange = true;
                    $productName = optional($detailsGroup->first()->product)->nama_barang ?? 'Barang';

                    // Update detail status to ditolak
                    foreach ($detailsGroup as $detail) {
                        $detail->update([
                            'status' => StatusBorrowingDetail::Ditolak->value,
                        ]);
                    }

                    // Notify borrower
                    $op->borrower->notify(new \App\Notifications\StockExhaustedForQueue($op, $productName));
                }
            }

            if ($hasChange) {
                // Update parent status to dibatalkan_otomatis
                $op->update([
                    'status' => StatusBorrowing::DibatalkanOtomatis->value,
                    'alasan_penolakan' => 'Stok habis dialokasikan ke antrean sebelumnya.',
                ]);
            }
        }

        return redirect()->route('borrowings.show', $borrowing->id)->with('success', 'Pengajuan peminjaman berhasil disetujui!');
    }

    /**
     * Reject borrowing request.
     */
    public function reject(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'alasan_penolakan' => 'required|string|max:500',
        ]);

        $borrowing = Borrowing::findOrFail($id);

        $borrowing->update([
            'status' => StatusBorrowing::Ditolak->value,
            'alasan_penolakan' => $request->alasan_penolakan,
        ]);

        $borrowing->borrower->notify(new BorrowingRejected($borrowing));

        return redirect()->route('borrowings.show', $borrowing->id)->with('success', 'Pengajuan peminjaman ditolak.');
    }

    /**
     * Show handover scan panel.
     */
    public function handover(string $id): View
    {
        $borrowing = Borrowing::with(['borrower', 'details.product', 'details.productUnit'])
            ->where('id', $id)
            ->firstOrFail();

        return view('borrowings.handover', compact('borrowing'));
    }

    /**
     * Confirm physical asset handover.
     */
    public function confirmHandover(Request $request, string $id): RedirectResponse
    {
        $request->validate([
            'kode_unit' => 'required|string|exists:product_units,kode_unit',
        ]);

        $borrowing = Borrowing::findOrFail($id);

        if ($borrowing->status !== StatusBorrowing::Disetujui) {
            return redirect()->back()->with('error', 'Status peminjaman harus disetujui terlebih dahulu.');
        }

        $unit = ProductUnit::where('kode_unit', $request->kode_unit)->firstOrFail();

        $detail = $borrowing->details()
            ->where('product_unit_id', $unit->id)
            ->where('status', StatusBorrowingDetail::Disetujui->value)
            ->first();

        if (! $detail) {
            return redirect()->back()
                ->withInput()
                ->with('error', "Unit '{$request->kode_unit}' tidak terdaftar sebagai bagian dari peminjaman ini atau sudah diserahkan.");
        }

        DB::transaction(function () use ($borrowing, $detail) {
            $detail->update([
                'status' => StatusBorrowingDetail::Dipinjam->value,
                'tanggal_pinjam_aktual' => now(),
            ]);

            $allHandedOver = ! $borrowing->details()
                ->where('status', StatusBorrowingDetail::Disetujui->value)
                ->exists();

            if ($allHandedOver) {
                $borrowing->update(['status' => StatusBorrowing::Berjalan->value]);
            }
        });

        return redirect()->route('borrowings.handover', $borrowing->id)
            ->with('success', "Unit '{$request->kode_unit}' berhasil diserahkan!");
    }

    /**
     * Cancel borrowing request by borrower.
     */
    public function cancel(string $id): RedirectResponse
    {
        $borrowing = Borrowing::findOrFail($id);

        if ($borrowing->user_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak membatalkan pengajuan ini.');
        }

        if ($borrowing->status !== StatusBorrowing::Diajukan) {
            return redirect()->back()->with('error', 'Pengajuan yang sudah diproses tidak dapat dibatalkan sendiri.');
        }

        $borrowing->update(['status' => StatusBorrowing::DibatalkanUser->value]);

        return redirect()->route('borrowings.my')->with('success', 'Pengajuan berhasil dibatalkan.');
    }

    /**
     * Extend the return date for a single borrowing detail (per-unit).
     *
     * Checks for overlapping bookings on the same product before allowing the extension.
     */
    public function extend(Request $request, string $detailId): RedirectResponse
    {
        $detail = BorrowingDetail::findOrFail($detailId);

        if ($detail->borrowing->user_id !== auth()->id()) {
            abort(403, 'Anda tidak berhak memperpanjang peminjaman ini.');
        }

        if ($detail->status !== StatusBorrowingDetail::Dipinjam) {
            return redirect()->back()->with('error', 'Hanya barang yang sedang dipinjam yang bisa diperpanjang.');
        }

        $request->validate([
            'tanggal_kembali_baru' => 'required|date|after:'.$detail->tanggal_kembali_rencana->format('Y-m-d'),
        ]);

        $bentrok = BorrowingDetail::where('product_id', $detail->product_id)
            ->where('id', '!=', $detail->id)
            ->whereHas('borrowing', function ($q) use ($request, $detail) {
                $q->whereIn('status', [StatusBorrowing::Disetujui->value, StatusBorrowing::Berjalan->value])
                    ->where('tanggal_pinjam_rencana', '<=', $request->tanggal_kembali_baru)
                    ->where('tanggal_kembali_rencana', '>=', $detail->tanggal_kembali_rencana->format('Y-m-d'));
            })->exists();

        if ($bentrok) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Barang sudah dibooking pengguna lain pada rentang tanggal tersebut, silakan pilih tanggal lain.');
        }

        $detail->update(['tanggal_kembali_rencana' => $request->tanggal_kembali_baru]);

        return redirect()->route('borrowings.my')->with('success', 'Perpanjangan berhasil dikonfirmasi.');
    }

    private function isStockSufficientForPeriod(int $productId, Carbon $start, Carbon $end, int $reqQty): bool
    {
        $totalUnits = ProductUnit::where('product_id', $productId)
            ->whereIn('status', [StatusUnit::Tersedia->value, StatusUnit::Dipinjam->value])
            ->count();

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dateStr = $date->format('Y-m-d');

            $terpakai = BorrowingDetail::where('product_id', $productId)
                ->whereHas('borrowing', function ($q) use ($dateStr) {
                    $q->whereIn('status', [
                        StatusBorrowing::Disetujui->value,
                        StatusBorrowing::Berjalan->value,
                    ])
                        ->where('tanggal_pinjam_rencana', '<=', $dateStr)
                        ->where('tanggal_kembali_rencana', '>=', $dateStr);
                })->count();

            $available = max(0, $totalUnits - $terpakai);

            if ($available < $reqQty) {
                return false;
            }
        }

        return true;
    }

    private function syncPendingBorrowings(): void
    {
        $pendingBorrowings = Borrowing::where('status', StatusBorrowing::Diajukan->value)->get();
        foreach ($pendingBorrowings as $pb) {
            $hasChange = false;
            foreach ($pb->details->groupBy('product_id') as $prodId => $detailsGroup) {
                $reqQty = $detailsGroup->count();
                $isSufficient = $this->isStockSufficientForPeriod(
                    $prodId,
                    $pb->tanggal_pinjam_rencana,
                    $pb->tanggal_kembali_rencana,
                    $reqQty
                );

                if (! $isSufficient) {
                    $hasChange = true;

                    // Update detail status to ditolak
                    foreach ($detailsGroup as $detail) {
                        $detail->update([
                            'status' => StatusBorrowingDetail::Ditolak->value,
                        ]);
                    }

                    // Notify borrower
                    $productName = optional($detailsGroup->first()->product)->nama_barang ?? 'Barang';
                    $pb->borrower->notify(new \App\Notifications\StockExhaustedForQueue($pb, $productName));
                }
            }

            if ($hasChange) {
                // Update parent status to dibatalkan_otomatis
                $pb->update([
                    'status' => StatusBorrowing::DibatalkanOtomatis->value,
                    'alasan_penolakan' => 'Stok habis dialokasikan ke antrean sebelumnya.',
                ]);
            }
        }
    }
}
