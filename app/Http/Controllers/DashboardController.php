<?php

namespace App\Http\Controllers;

use App\Enums\StatusBorrowing;
use App\Enums\StatusUnit;
use App\Models\Borrowing;
use App\Models\BorrowingReturn;
use App\Models\Category;
use App\Models\IncidentReport;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user?->hasRole('manager')) {
            return redirect()->route('dashboard.manager');
        }

        $stats = [
            'total_kategori' => Category::count(),
            'total_barang' => Product::count(),
            'total_unit' => ProductUnit::count(),
            'unit_tersedia' => ProductUnit::where('status', StatusUnit::Tersedia->value)->count(),
            'sedang_dipinjam' => ProductUnit::where('status', StatusUnit::Dipinjam->value)->count(),
            'unit_bermasalah' => ProductUnit::whereIn('status', [StatusUnit::Maintenance->value, StatusUnit::DilaporkanHilang->value])->count(),
        ];

        // Chart data
        $monthlyBorrowings = [];
        $monthlyReturns = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyBorrowings[] = Borrowing::whereYear('created_at', date('Y'))->whereMonth('created_at', $m)->count();
            $monthlyReturns[] = BorrowingReturn::whereYear('tanggal_pengembalian', date('Y'))->whereMonth('tanggal_pengembalian', $m)->count();
        }

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

        $lowStocks = Product::withCount(['units' => function ($query) {
            $query->where('status', StatusUnit::Tersedia->value);
        }])->get()->filter(function ($product) {
            return $product->units_count < $product->stok_minimum;
        });

        $categoryStats = Category::with(['products.units'])->get()->map(function ($cat) {
            $units = $cat->products->flatMap->units;
            $total = $units->count();
            $tersedia = $units->where('status', StatusUnit::Tersedia)->count();

            return [
                'nama' => $cat->nama_kategori,
                'total' => $total,
                'tersedia' => $tersedia,
            ];
        });

        return view('dashboard.operasional', compact(
            'stats',
            'pendingList',
            'lowStocks',
            'categoryStats',
            'monthlyBorrowings',
            'monthlyReturns'
        ));
    }

    public function manager(): View
    {
        $stats = [
            'total_kategori' => Category::count(),
            'total_barang' => Product::count(),
            'total_unit' => ProductUnit::count(),
            'unit_tersedia' => ProductUnit::where('status', StatusUnit::Tersedia->value)->count(),
            'sedang_dipinjam' => ProductUnit::where('status', StatusUnit::Dipinjam->value)->count(),
            'unit_bermasalah' => ProductUnit::whereIn('status', [StatusUnit::Maintenance->value, StatusUnit::DilaporkanHilang->value])->count(),
        ];

        $monthlyBorrowings = [];
        $monthlyReturns = [];
        for ($m = 1; $m <= 12; $m++) {
            $monthlyBorrowings[] = Borrowing::whereYear('created_at', date('Y'))->whereMonth('created_at', $m)->count();
            $monthlyReturns[] = BorrowingReturn::whereYear('tanggal_pengembalian', date('Y'))->whereMonth('tanggal_pengembalian', $m)->count();
        }

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

        return view('dashboard.manager', compact(
            'stats',
            'overrides',
            'incidents',
            'writeoffs',
            'totalKerugian',
            'monthlyBorrowings',
            'monthlyReturns'
        ));
    }
}
