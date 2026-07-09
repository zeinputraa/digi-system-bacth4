<?php

namespace App\Http\Controllers;

use App\Enums\StatusBorrowing;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingReturn;
use App\Models\Category;
use App\Models\IncidentReport;
use App\Models\ProcurementRequest;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user?->hasRole('manager')) {
            return redirect()->route('dashboard.manager');
        }

        $stats = $this->buildStats();

        $monthlyBorrowings = $this->buildMonthlyBorrowings();
        $monthlyReturns = $this->buildMonthlyReturns();

        if ($user?->hasRole('karyawan')) {
            $activeBorrowings = Borrowing::where('user_id', $user->id)
                ->whereIn('status', [StatusBorrowing::Disetujui->value, StatusBorrowing::Berjalan->value])
                ->with(['details.productUnit.product'])
                ->get();

            $pendingBorrowings = Borrowing::where('user_id', $user->id)
                ->where('status', StatusBorrowing::Diajukan->value)
                ->with(['details.product'])
                ->get();

            return view('dashboard.karyawan', compact('stats', 'activeBorrowings', 'pendingBorrowings'));
        }

        $pendingList = Borrowing::where('status', StatusBorrowing::Diajukan->value)
            ->with(['borrower', 'details.product'])
            ->orderBy('tanggal_pengajuan', 'asc')
            ->get();

        // N+1 fix: filter low stocks at DB level with WHERE subquery to support strict SQL modes
        $lowStocks = Product::withCount(['units as units_count' => function ($query) {
            $query->where('status', StatusUnit::Tersedia->value);
        }])
            ->where(function ($query) {
                $query->selectRaw('count(*)')
                    ->from('product_units')
                    ->whereColumn('products.id', 'product_units.product_id')
                    ->where('status', StatusUnit::Tersedia->value)
                    ->whereNull('deleted_at');
            }, '<', DB::raw('stok_minimum'))
            ->get();

        // N+1 fix: aggregate category stats with a single join query
        $categoryStats = $this->buildCategoryStats();

        $procurementRequests = ProcurementRequest::with(['product', 'requester'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'asc')
            ->get();

        return view('dashboard.operasional', compact(
            'stats',
            'pendingList',
            'lowStocks',
            'categoryStats',
            'monthlyBorrowings',
            'monthlyReturns',
            'procurementRequests'
        ));
    }

    public function manager(): View
    {
        $stats = $this->buildStats();

        $monthlyBorrowings = $this->buildMonthlyBorrowings();
        $monthlyReturns = $this->buildMonthlyReturns();

        $overrides = Borrowing::where('fifo_override', true)
            ->with(['borrower'])
            ->orderBy('approved_at', 'desc')
            ->limit(5)
            ->get();

        $incidents = IncidentReport::with(['productUnit.product', 'reporter'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        $writeoffs = ProductUnit::where('status', StatusUnit::HilangPermanen->value)
            ->with(['product', 'incidentReports'])
            ->orderBy('updated_at', 'desc')
            ->limit(5)
            ->get();

        $totalKerugian = (float) ProductUnit::where('status', StatusUnit::HilangPermanen->value)
            ->sum('harga_perolehan');

        $pendingApprovals = Borrowing::where('needs_manager_approval', true)
            ->whereNull('manager_approved')
            ->where('status', StatusBorrowing::Diajukan->value)
            ->with(['borrower', 'details.product'])
            ->orderBy('tanggal_pengajuan', 'asc')
            ->get();

        // N+1 fix: DB-level filter for low stock using WHERE subquery to support strict SQL modes
        $lowStocks = Product::withCount(['units as units_count' => function ($query) {
            $query->where('status', StatusUnit::Tersedia->value);
        }])
            ->where(function ($query) {
                $query->selectRaw('count(*)')
                    ->from('product_units')
                    ->whereColumn('products.id', 'product_units.product_id')
                    ->where('status', StatusUnit::Tersedia->value)
                    ->whereNull('deleted_at');
            }, '<', DB::raw('stok_minimum'))
            ->get();

        $activeProcurements = ProcurementRequest::with(['product', 'requester'])
            ->where('status', 'pending')
            ->orderBy('created_at', 'desc')
            ->get();

        // N+1 fix: use withCount for utilization and merge active borrowed in a second query
        $productUtils = Product::withCount([
            'borrowingDetails',
            'units',
            'units as active_borrowed_count' => function ($q) {
                $q->where('status', StatusUnit::Dipinjam->value);
            },
        ])->get()->map(function ($p) {
            $totalUnits = $p->units_count;
            $activeCount = $p->active_borrowed_count;
            $activePercentage = $totalUnits > 0 ? round(($activeCount / $totalUnits) * 100) : 0;

            return [
                'nama' => $p->nama_barang,
                'total_units' => $totalUnits,
                'active_borrowed' => $activeCount,
                'active_percentage' => $activePercentage,
                'times_borrowed' => $p->borrowing_details_count,
            ];
        })->sortByDesc('times_borrowed')->take(5);

        // N+1 fix: aggregate category reliability in memory after a single eager load
        $categoryReliability = $this->buildCategoryReliability();

        return view('dashboard.manager', compact(
            'stats',
            'overrides',
            'incidents',
            'writeoffs',
            'totalKerugian',
            'monthlyBorrowings',
            'monthlyReturns',
            'pendingApprovals',
            'lowStocks',
            'activeProcurements',
            'productUtils',
            'categoryReliability'
        ));
    }

    public function storeProcurement(Request $request, Product $product): RedirectResponse
    {
        if (! auth()->user()->hasRole('manager', 'admin')) {
            abort(403, 'Hanya Manager yang berwenang mengajukan pengadaan.');
        }

        $request->validate([
            'qty' => 'required|integer|min:1',
        ]);

        ProcurementRequest::create([
            'product_id' => $product->id,
            'quantity' => $request->qty,
            'requested_by' => auth()->id(),
            'status' => 'pending',
        ]);

        return redirect()->back()->with('success', 'Rekomendasi pengadaan barang "'.$product->nama_barang.'" berhasil diajukan.');
    }

    public function approveProcurement(Request $request, string $id): RedirectResponse
    {
        if (! auth()->user()->hasRole('admin', 'staff')) {
            abort(403, 'Hanya Admin/Staff yang berwenang memproses pengadaan.');
        }

        $procurement = ProcurementRequest::findOrFail($id);

        $request->validate([
            'harga_perolehan' => 'required|numeric|min:0',
            'lokasi_penyimpanan' => 'required|string|max:150',
        ]);

        $product = $procurement->product;

        DB::transaction(function () use ($procurement, $product, $request) {
            for ($i = 0; $i < $procurement->quantity; $i++) {
                $lastNumber = $product->units()->withTrashed()
                    ->select('kode_unit')
                    ->get()
                    ->map(function (ProductUnit $unit): ?int {
                        preg_match('/-U(\d+)$/', $unit->kode_unit, $matches);

                        return $matches[1] ?? null;
                    })
                    ->filter()
                    ->map(fn (string $value): int => (int) $value)
                    ->max() ?? 0;

                $nextNumber = $lastNumber + 1;
                $kodeUnit = $product->kode_produk.'-U'.str_pad($nextNumber, 2, '0', STR_PAD_LEFT);

                ProductUnit::create([
                    'product_id' => $product->id,
                    'kode_unit' => $kodeUnit,
                    'qr_code' => Str::random(32),
                    'kondisi' => 'baik',
                    'status' => 'tersedia',
                    'lokasi_penyimpanan' => $request->lokasi_penyimpanan,
                    'tahun_pengadaan' => (int) date('Y'),
                    'harga_perolehan' => $request->harga_perolehan,
                ]);
            }

            $procurement->update([
                'status' => 'completed',
            ]);
        });

        return redirect()->back()->with('success', 'Pengadaan barang "'.$product->nama_barang.'" berhasil diselesaikan dan '.$procurement->quantity.' unit baru telah terdaftar di sistem!');
    }

    public function rejectProcurement(Request $request, string $id): RedirectResponse
    {
        if (! auth()->user()->hasRole('admin', 'staff')) {
            abort(403, 'Hanya Admin/Staff yang berwenang menolak pengadaan.');
        }

        $procurement = ProcurementRequest::findOrFail($id);

        $procurement->update([
            'status' => 'rejected',
        ]);

        return redirect()->back()->with('success', 'Pengadaan barang "'.$procurement->product->nama_barang.'" berhasil ditolak.');
    }

    /**
     * Build basic inventory statistics.
     *
     * @return array<string, int>
     */
    private function buildStats(): array
    {
        return [
            'total_kategori' => Category::count(),
            'total_barang' => Product::count(),
            'total_unit' => ProductUnit::count(),
            'unit_tersedia' => ProductUnit::where('status', StatusUnit::Tersedia->value)->count(),
            'sedang_dipinjam' => ProductUnit::where('status', StatusUnit::Dipinjam->value)->count(),
            'unit_bermasalah' => ProductUnit::whereIn('status', [StatusUnit::Maintenance->value, StatusUnit::DilaporkanHilang->value])->count(),
        ];
    }

    /**
     * Build monthly borrowing counts using a single aggregate query.
     *
     * @return array<int, int>
     */
    private function buildMonthlyBorrowings(): array
    {
        $isMySQL = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);

        $monthExpression = $isMySQL
            ? DB::raw('MONTH(created_at) as month')
            : DB::raw("CAST(strftime('%m', created_at) AS INTEGER) as month");

        $rows = Borrowing::selectRaw('COUNT(*) as count')
            ->addSelect($monthExpression)
            ->whereYear('created_at', date('Y'))
            ->groupBy('month')
            ->pluck('count', 'month');

        return array_map(fn ($m) => (int) ($rows[$m] ?? 0), range(1, 12));
    }

    /**
     * Build monthly return counts using a single aggregate query.
     *
     * @return array<int, int>
     */
    private function buildMonthlyReturns(): array
    {
        $isMySQL = in_array(DB::getDriverName(), ['mysql', 'mariadb'], true);

        $monthExpression = $isMySQL
            ? DB::raw('MONTH(tanggal_pengembalian) as month')
            : DB::raw("CAST(strftime('%m', tanggal_pengembalian) AS INTEGER) as month");

        $rows = BorrowingReturn::selectRaw('COUNT(*) as count')
            ->addSelect($monthExpression)
            ->whereYear('tanggal_pengembalian', date('Y'))
            ->groupBy('month')
            ->pluck('count', 'month');

        return array_map(fn ($m) => (int) ($rows[$m] ?? 0), range(1, 12));
    }

    /**
     * Build category availability stats with a single eager load.
     *
     * @return Collection<int, array<string, int|string>>
     */
    private function buildCategoryStats(): Collection
    {
        return Category::with(['products.units'])->get()->map(function ($cat) {
            $units = $cat->products->flatMap->units;
            $total = $units->count();
            $tersedia = $units->where('status', StatusUnit::Tersedia)->count();

            return [
                'nama' => $cat->nama_kategori,
                'total' => $total,
                'tersedia' => $tersedia,
            ];
        });
    }

    /**
     * Build category reliability metrics with a single eager load query.
     *
     * @return Collection<int, array<string, float|int|string>>
     */
    private function buildCategoryReliability(): Collection
    {
        // Load all incident report counts per product_unit eager to avoid per-category N+1
        $incidentCountsByUnit = IncidentReport::selectRaw('product_unit_id, COUNT(*) as cnt')
            ->groupBy('product_unit_id')
            ->pluck('cnt', 'product_unit_id');

        return Category::with(['products.units'])->get()->map(function ($cat) use ($incidentCountsByUnit) {
            $units = $cat->products->flatMap->units;
            $writeOffCount = $units->where('status', StatusUnit::HilangPermanen->value)->count();
            $totalWriteOffLoss = $units->where('status', StatusUnit::HilangPermanen->value)->sum('harga_perolehan');

            $incidentCount = $units->sum(fn ($u) => $incidentCountsByUnit[$u->id] ?? 0);

            return [
                'nama' => $cat->nama_kategori,
                'incident_count' => $incidentCount,
                'write_off_count' => $writeOffCount,
                'total_loss' => (float) $totalWriteOffLoss,
            ];
        });
    }
}
