<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductUnitRequest extends FormRequest
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
        $currentYear = (int) date('Y');

        return [
            'lokasi_penyimpanan' => ['required', 'string', 'max:150'],
            'tahun_pengadaan' => ['required', 'digits:4', 'integer', 'min:1900', "max:$currentYear"],
            'harga_perolehan' => ['required', 'numeric', 'min:0'],
            'catatan' => ['nullable', 'string'],
        ];
    }
}
