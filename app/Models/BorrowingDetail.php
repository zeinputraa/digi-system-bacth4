<?php

namespace App\Models;

use App\Enums\KondisiUnit;
use App\Enums\StatusBorrowingDetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BorrowingDetail extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'borrowing_id',
        'product_id',
        'product_unit_id',
        'status',
        'tanggal_kembali_rencana',
        'tanggal_pinjam_aktual',
        'tanggal_kembali_aktual',
        'kondisi_saat_pinjam',
        'kondisi_saat_kembali',
        'hari_terlambat',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'tanggal_kembali_rencana' => 'date',
            'tanggal_pinjam_aktual' => 'datetime',
            'tanggal_kembali_aktual' => 'datetime',
            'status' => StatusBorrowingDetail::class,
            'kondisi_saat_pinjam' => KondisiUnit::class,
            'kondisi_saat_kembali' => KondisiUnit::class,
        ];
    }

    /**
     * Get the borrowing request that contains this detail.
     *
     * @return BelongsTo<Borrowing, $this>
     */
    public function borrowing(): BelongsTo
    {
        return $this->belongsTo(Borrowing::class);
    }

    /**
     * Get the product requested.
     *
     * @return BelongsTo<Product, $this>
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the specific unit assigned to this detail, if any.
     *
     * @return BelongsTo<ProductUnit, $this>
     */
    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    /**
     * Get the returns associated with this borrowing detail.
     *
     * @return HasMany<BorrowingReturn, $this>
     */
    public function returns(): HasMany
    {
        return $this->hasMany(BorrowingReturn::class);
    }

    /**
     * Get the incident reports associated with this borrowing detail.
     *
     * @return HasMany<IncidentReport, $this>
     */
    public function incidentReports(): HasMany
    {
        return $this->hasMany(IncidentReport::class);
    }
}
