<?php

namespace App\Models;

use App\Enums\KondisiUnit;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BorrowingReturn extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'borrowing_detail_id',
        'tanggal_pengembalian',
        'diterima_oleh',
        'kondisi_barang',
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
            'tanggal_pengembalian' => 'datetime',
            'kondisi_barang' => KondisiUnit::class,
        ];
    }

    /**
     * Get the borrowing detail that was returned.
     *
     * @return BelongsTo<BorrowingDetail, $this>
     */
    public function borrowingDetail(): BelongsTo
    {
        return $this->belongsTo(BorrowingDetail::class);
    }

    /**
     * Get the staff member who received the return.
     *
     * @return BelongsTo<User, $this>
     */
    public function diterimaOleh(): BelongsTo
    {
        return $this->belongsTo(User::class, 'diterima_oleh');
    }
}
