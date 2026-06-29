<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Proposal extends Model
{
    protected $fillable = [
        'project_id', 'freelancer_id', 'cover_letter',
        'bid_amount', 'duration_days', 'status', 'rejected_reason',
    ];

    protected function casts(): array
    {
        return ['bid_amount' => 'decimal:2'];
    }

    public function project()    { return $this->belongsTo(Project::class); }
    public function freelancer() { return $this->belongsTo(User::class, 'freelancer_id'); }
    public function contract()   { return $this->hasOne(Contract::class); }

    public function scopePending($q)     { return $q->where('status', 'pending'); }
    public function scopeActive($q)      { return $q->whereIn('status', ['pending', 'shortlisted']); }
    public function scopeForProject($q, $id) { return $q->where('project_id', $id); }
}
