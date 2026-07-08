<?php

namespace App\Console\Commands;

use App\Enums\StatusUnit;
use App\Models\Product;
use App\Models\User;
use App\Notifications\StockBelowMinimum;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckStockMinimum extends Command
{
    protected $signature = 'notifications:check-stock';

    protected $description = 'Cek stok barang di bawah minimum dan kirim notifikasi ke admin & staff';

    public function handle(): void
    {
        // DB-level filter: only load products that actually breach the minimum
        $products = Product::withCount(['units as units_count' => function ($q) {
            $q->where('status', StatusUnit::Tersedia->value);
        }])
            ->having('units_count', '<=', DB::raw('stok_minimum'))
            ->get();

        if ($products->isEmpty()) {
            $this->info('Stock check complete. No products below minimum.');

            return;
        }

        $recipients = User::whereHas('role', function ($q) {
            $q->whereIn('name', ['admin', 'staff']);
        })->get();

        $sentCount = 0;

        foreach ($products as $product) {
            $available = $product->units_count;

            // Cek apakah hari ini sudah pernah dikirim notifikasi untuk produk ini
            $alreadySent = DB::table('notifications')
                ->whereDate('created_at', now()->toDateString())
                ->where(function ($q) use ($product) {
                    $q->where('data->product_id', $product->id)
                        ->orWhere('data', 'like', '%"product_id":'.$product->id.'%')
                        ->orWhere('data', 'like', '%"product_id":"'.$product->id.'"%');
                })
                ->exists();

            if (! $alreadySent) {
                foreach ($recipients as $user) {
                    $user->notify(new StockBelowMinimum($product, $available));
                }
                $sentCount++;
            }
        }

        $this->info("Stock check complete. Sent warning for {$sentCount} products.");
    }
}
