<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LabelController extends Controller
{
    /**
     * Tampilkan halaman pilih unit untuk cetak label (manual).
     */
    public function pilih(Request $request): View
    {
        $query = ProductUnit::with(['product.category']);

        // Filter pencarian (kode_unit atau nama_barang)
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('kode_unit', 'like', "%{$search}%")
                    ->orWhereHas('product', function ($pq) use ($search) {
                        $pq->where('nama_barang', 'like', "%{$search}%");
                    });
            });
        }

        // Filter kategori
        if ($request->filled('kategori_id')) {
            $query->whereHas('product', function ($q) use ($request) {
                $q->where('category_id', $request->kategori_id);
            });
        }

        // Filter produk
        if ($request->filled('produk_id')) {
            $query->where('product_id', $request->produk_id);
        }

        // Tampilkan semua unit aktif (tidak soft-deleted)
        $units = $query->orderBy('kode_unit', 'asc')->paginate(12);

        $categories = Category::orderBy('nama_kategori', 'asc')->get();
        $products = Product::orderBy('nama_barang', 'asc')->get();

        return view('labels.pilih', compact('units', 'categories', 'products'));
    }

    /**
     * Tampilkan halaman cetak label untuk unit yang dipilih.
     */
    public function cetak(Request $request): View
    {
        $request->validate([
            'unit_ids' => 'required|array|min:1',
            'unit_ids.*' => 'required|integer|exists:product_units,id',
        ], [
            'unit_ids.required' => 'Pilih minimal satu unit untuk mencetak label.',
        ]);

        $units = ProductUnit::with('product.category')
            ->whereIn('id', $request->unit_ids)
            ->get();

        return view('labels.print', compact('units'));
    }
}
