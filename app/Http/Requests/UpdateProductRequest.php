<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $product = $this->route('product');
        $productId = is_object($product) ? $product->id : $product;

        return [
            'category_id' => ['required', 'exists:categories,id'],
            'kode_produk' => ['required', 'string', 'max:50', 'unique:products,kode_produk'.($productId ? ",$productId" : '')],
            'nama_barang' => ['required', 'string', 'max:150'],
            'deskripsi' => ['nullable', 'string'],
            'foto' => ['nullable', 'image', 'max:2048'],
            'stok_minimum' => ['nullable', 'integer', 'min:1'],
        ];
    }
}
