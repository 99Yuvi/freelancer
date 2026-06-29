<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Contract extends Model
{
    protected $fillable = [
        'proposal_id', 'project_id', 'client_id', 'freelancer_id',
        'total_amount', 'commission_rate', 'status', 'started_at', 'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'total_amount'    => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'started_at'      => 'datetime',
            'completed_at'    => 'datetime',
        ];
    }

    public function proposal()      { return $this->belongsTo(Proposal::class); }
    public function project()       { return $this->belongsTo(Project::class); }
    public function client()        { return $this->belongsTo(User::class, 'client_id'); }
    public function freelancer()    { return $this->belongsTo(User::class, 'freelancer_id'); }
    public function milestones()    { return $this->hasMany(Milestone::class)->orderBy('sort_order'); }
    public function conversation()  { return $this->hasOne(Conversation::class); }
    public function payments()      { return $this->hasMany(Payment::class); }
    public function reviews()       { return $this->hasMany(Review::class); }

    public function scopeActive($q)  { return $q->where('status', 'active'); }
    public function scopeForUser($q, User $user) {
        return $q->where('client_id', $user->id)->orWhere('freelancer_id', $user->id);
    }

    public function freelancerProfile()
    {
        return $this->belongsTo(FreelancerProfile::class, 'freelancer_id', 'user_id');
    }

    public function clientProfile()
    {
        return $this->belongsTo(ClientProfile::class, 'client_id', 'user_id');
    }

    public function getIsCompleteAttribute(): bool
    {
        return $this->milestones->isNotEmpty()
            && $this->milestones->every(fn($m) => $m->status === 'paid');
    }
}
