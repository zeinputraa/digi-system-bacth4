<?php

namespace App\Http\Controllers;

use App\Enums\StatusUnit;
use App\Http\Requests\StoreProductUnitRequest;
use App\Models\Product;
use App\Models\ProductUnit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class ProductUnitController extends Controller
{
    public function create(Product $product): View
    {
        return view('units.create', compact('product'));
    }

    public function store(StoreProductUnitRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

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

        $unit = ProductUnit::create([
            'product_id' => $product->id,
            'kode_unit' => $kodeUnit,
            'qr_code' => Str::random(32),
            'kondisi' => 'baik',
            'status' => 'tersedia',
            'lokasi_penyimpanan' => $validated['lokasi_penyimpanan'],
            'tahun_pengadaan' => $validated['tahun_pengadaan'],
            'harga_perolehan' => $validated['harga_perolehan'],
            'catatan' => $validated['catatan'] ?? null,
        ]);

        return redirect()->route('products.show', $product)->with('success', 'Unit berhasil ditambahkan.');
    }

    public function show(Product $product, ProductUnit $unit): View
    {
        $unit->load([
            'borrowingDetails.borrowing.borrower',
            'incidentReports.reporter',
        ]);

        return view('units.show', compact('product', 'unit'));
    }

    public function edit(Product $product, ProductUnit $unit): View
    {
        return view('units.edit', compact('product', 'unit'));
    }

    public function update(Request $request, Product $product, ProductUnit $unit): RedirectResponse
    {
        $currentYear = (int) date('Y');

        $validated = $request->validate([
            'status' => ['required', 'string', Rule::in(collect(StatusUnit::cases())->pluck('value')->toArray())],
            'kondisi' => ['required', 'string', Rule::in(['baik', 'rusak_ringan', 'rusak_berat'])],
            'lokasi_penyimpanan' => ['required', 'string', 'max:150'],
            'tahun_pengadaan' => ['required', 'digits:4', 'integer', 'min:1900', "max:$currentYear"],
            'harga_perolehan' => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
        ]);

        $unit->update($validated);

        return redirect()->route('units.show', [$product, $unit])->with('success', 'Unit berhasil diperbarui.');
    }

    public function showByQr(string $token): View
    {
        $unit = ProductUnit::where('qr_code', $token)->firstOrFail();

        // limited info for public view
        return view('units.public_show', ['unit' => $unit]);
    }
}
