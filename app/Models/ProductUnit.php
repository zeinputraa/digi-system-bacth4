<?php

namespace App\Models;

use App\Enums\KondisiUnit;
use App\Enums\StatusUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProductUnit extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'product_id',
        'kode_unit',
        'qr_code',
        'kondisi',
        'status',
        'lokasi_penyimpanan',
        'tahun_pengadaan',
        'harga_perolehan',
        'catatan',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kondisi' => KondisiUnit::class,
            'status' => StatusUnit::class,
            'harga_perolehan' => 'decimal:2',
            'tahun_pengadaan' => 'integer',
        ];
    }

    /**
     * Get the product that owns the unit.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the borrowing details associated with the unit.
     *
     * @return HasMany<BorrowingDetail, $this>
     */
    public function borrowingDetails(): HasMany
    {
        return $this->hasMany(BorrowingDetail::class);
    }

    /**
     * Get the incident reports associated with the unit.
     *
     * @return HasMany<IncidentReport, $this>
     */
    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class);
    }
}
