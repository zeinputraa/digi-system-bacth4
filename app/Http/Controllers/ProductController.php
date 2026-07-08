<?php

namespace App\Http\Controllers;

use App\Enums\StatusUnit;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function index(Request $request): View
    {
        $products = Product::with('category')
            ->when($request->get('search'), function ($q, $search) {
                $q->where('nama_barang', 'like', "%{$search}%")
                    ->orWhere('kode_produk', 'like', "%{$search}%");
            })
            ->withCount([
                'units as total_unit',
                'units as unit_tersedia' => function ($q) {
                    $q->where('status', StatusUnit::Tersedia->value);
                },
            ])
            ->paginate(12)
            ->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create(): View
    {
        $categories = Category::orderBy('nama_kategori')->get();

        return view('products.create', compact('categories'));
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('products', 'public');
            $data['foto'] = $path;
        }

        $data['created_by'] = auth()->id();

        Product::create($data);

        return redirect()->route('products.index')->with('success', 'Produk berhasil ditambahkan.');
    }

    public function show(Product $product): View
    {
        $product->load(['category', 'units' => fn ($q) => $q->orderBy('kode_unit')]);

        return view('products.show', compact('product'));
    }

    public function edit(Product $product): View
    {
        $categories = Category::orderBy('nama_kategori')->get();

        return view('products.edit', compact('product', 'categories'));
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $data = $request->validated();

        if ($request->hasFile('foto')) {
            $path = $request->file('foto')->store('products', 'public');
            $data['foto'] = $path;
            if ($product->foto) {
                Storage::disk('public')->delete($product->foto);
            }
        }

        $product->update($data);

        return redirect()->route('products.index')->with('success', 'Produk berhasil diperbarui.');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $product->delete();

        return redirect()->route('products.index')->with('success', 'Produk dihapus.');
    }
}
