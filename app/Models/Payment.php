<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'contract_id', 'milestone_id', 'client_id', 'freelancer_id',
        'razorpay_order_id', 'razorpay_payment_id',
        'amount', 'commission_rate', 'commission_amount', 'net_amount',
        'currency', 'invoice_path', 'status', 'captured_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'            => 'decimal:2',
            'commission_rate'   => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'net_amount'        => 'decimal:2',
            'captured_at'       => 'datetime',
        ];
    }

    public function contract()         { return $this->belongsTo(Contract::class); }
    public function milestone()        { return $this->belongsTo(Milestone::class); }
    public function client()           { return $this->belongsTo(User::class, 'client_id'); }
    public function freelancer()       { return $this->belongsTo(User::class, 'freelancer_id'); }
    public function freelancerProfile(){ return $this->belongsTo(FreelancerProfile::class, 'freelancer_id', 'user_id'); }
    public function clientProfile()    { return $this->belongsTo(ClientProfile::class, 'client_id', 'user_id'); }

    public function isCaptured(): bool { return $this->status === 'captured'; }
}
