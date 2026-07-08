<?php

namespace App\Models;

use App\Enums\JenisInsiden;
use App\Enums\StatusGantiRugi;
use App\Enums\StatusInsiden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentReport extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'borrowing_detail_id',
        'product_unit_id',
        'reported_by',
        'jenis',
        'kronologi',
        'foto_bukti',
        'status',
        'verified_by',
        'verified_at',
        'batas_investigasi',
        'finalized_by',
        'finalized_at',
        'status_ganti_rugi',
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
            'verified_at' => 'datetime',
            'batas_investigasi' => 'date',
            'finalized_at' => 'datetime',
            'jenis' => JenisInsiden::class,
            'status' => StatusInsiden::class,
            'status_ganti_rugi' => StatusGantiRugi::class,
        ];
    }

    /**
     * Get the borrowing detail that this report relates to.
     *
     * @return BelongsTo<BorrowingDetail, $this>
     */
    public function borrowingDetail(): BelongsTo
    {
        return $this->belongsTo(BorrowingDetail::class);
    }

    /**
     * Get the product unit involved in the incident.
     *
     * @return BelongsTo<ProductUnit, $this>
     */
    public function productUnit(): BelongsTo
    {
        return $this->belongsTo(ProductUnit::class);
    }

    /**
     * Get the user who reported the incident.
     *
     * @return BelongsTo<User, $this>
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reported_by');
    }

    /**
     * Get the user (staff) who verified the report.
     *
     * @return BelongsTo<User, $this>
     */
    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Get the user (admin) who finalized the report.
     *
     * @return BelongsTo<User, $this>
     */
    public function finalizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalized_by');
    }
}
