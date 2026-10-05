<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class PayoutRequest extends Model
{
    protected $fillable = [
        'freelancer_id',
        'amount',
        'bank_account_name',
        'bank_account_number',
        'ifsc_code',
        'upi_id',
        'status',
        'admin_notes',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'       => 'decimal:2',
            'processed_at' => 'datetime',
        ];
    }

    // ── Relationships ────────────────────────────────────────────────────────

    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }

    // ── Scopes ───────────────────────────────────────────────────────────────

    public function scopePending(Builder $query): Builder
    {
        return $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): Builder
    {
        return $query->where('status', 'approved');
    }
}
