<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'category_id',
        'kode_produk',
        'nama_barang',
        'deskripsi',
        'foto',
        'stok_minimum',
        'created_by',
    ];

    /**
     * Get the category that owns the product.
     *
     * @return BelongsTo<Category, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the user who created the product.
     *
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Get the units for the product.
     *
     * @return HasMany<ProductUnit, $this>
     */
    public function units(): HasMany
    {
        return $this->hasMany(ProductUnit::class);
    }

    /**
     * Get the borrowing details associated with the product.
     *
     * @return HasMany<BorrowingDetail, $this>
     */
    public function borrowingDetails(): HasMany
    {
        return $this->hasMany(BorrowingDetail::class);
    }
}
