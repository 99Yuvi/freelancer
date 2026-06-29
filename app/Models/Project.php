<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'client_id', 'category_id', 'title', 'description',
        'budget_type', 'budget_min', 'budget_max', 'deadline',
        'visibility', 'status', 'view_count',
    ];

    protected function casts(): array
    {
        return [
            'budget_min' => 'decimal:2',
            'budget_max' => 'decimal:2',
            'deadline'   => 'date',
        ];
    }

    public function client()   { return $this->belongsTo(User::class, 'client_id'); }
    public function category() { return $this->belongsTo(Category::class); }
    public function skills()   { return $this->belongsToMany(Skill::class, 'project_skills'); }
    public function proposals(){ return $this->hasMany(Proposal::class); }
    public function contract() { return $this->hasOneThrough(Contract::class, Proposal::class, 'project_id', 'proposal_id'); }

    public function scopeOpen($q)   { return $q->where('status', 'open')->where('visibility', 'public'); }
    public function scopeForClient($q, $clientId) { return $q->where('client_id', $clientId); }

    public function hasAcceptedProposal(): bool
    {
        return $this->proposals()->where('status', 'accepted')->exists();
    }
}
