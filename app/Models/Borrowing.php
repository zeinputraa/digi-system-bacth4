<?php

namespace App\Models;

use App\Enums\StatusBorrowing;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Borrowing extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'user_id',
        'kode_peminjaman',
        'tanggal_pengajuan',
        'tanggal_pinjam_rencana',
        'tanggal_kembali_rencana',
        'status',
        'approved_by',
        'approved_at',
        'fifo_override',
        'alasan_override',
        'alasan_penolakan',
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
            'tanggal_pengajuan' => 'datetime',
            'tanggal_pinjam_rencana' => 'date',
            'tanggal_kembali_rencana' => 'date',
            'approved_at' => 'datetime',
            'fifo_override' => 'boolean',
            'status' => StatusBorrowing::class,
        ];
    }

    /**
     * Get the borrower (user) who requested this borrowing.
     *
     * @return BelongsTo<User, $this>
     */
    public function borrower(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Get the user who approved this borrowing.
     *
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Get the details (assigned units/products) for this borrowing.
     *
     * @return HasMany<BorrowingDetail, $this>
     */
    public function details(): HasMany
    {
        return $this->hasMany(BorrowingDetail::class);
    }
}
