<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Milestone extends Model
{
    protected $fillable = [
        'contract_id', 'title', 'description', 'amount',
        'due_date', 'sort_order', 'status',
    ];

    protected function casts(): array
    {
        return [
            'amount'   => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function contract()    { return $this->belongsTo(Contract::class); }
    public function deliveries()  { return $this->hasMany(MilestoneDelivery::class); }
    public function payment()     { return $this->hasOne(Payment::class); }

    public function scopeForContract($q, $id) { return $q->where('contract_id', $id); }
    public function isPaid(): bool { return $this->status === 'paid'; }
    public function canBeDelivered(): bool { return in_array($this->status, ['in_progress', 'revision_requested']); }
}
