<?php

namespace App\Notifications;

use App\Models\Product;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;

class StockBelowMinimum extends Notification
{
    use Queueable;

    public function __construct(protected Product $product, protected int $availableStock) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'product_id' => $this->product->id,
            'nama_barang' => $this->product->nama_barang,
            'stok_tersedia' => $this->availableStock,
            'stok_minimum' => $this->product->stok_minimum,
            'message' => "Stok {$this->product->nama_barang} tinggal {$this->availableStock} (ambang: {$this->product->stok_minimum})",
            'url' => route('products.show', $this->product->id),
        ];
    }
}
