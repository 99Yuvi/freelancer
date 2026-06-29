<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Review extends Model
{
    protected $fillable = [
        'contract_id', 'reviewer_id', 'reviewee_id',
        'communication', 'quality', 'timeliness', 'overall',
        'body', 'response', 'response_at', 'is_visible', 'is_hidden',
    ];

    protected function casts(): array
    {
        return [
            'is_visible'  => 'boolean',
            'is_hidden'   => 'boolean',
            'response_at' => 'datetime',
        ];
    }

    public function contract()  { return $this->belongsTo(Contract::class); }
    public function reviewer()  { return $this->belongsTo(User::class, 'reviewer_id'); }
    public function reviewee()  { return $this->belongsTo(User::class, 'reviewee_id'); }

    public function scopePublic($q) { return $q->where('is_visible', true)->where('is_hidden', false); }
    public function scopeForReviewee($q, $id) { return $q->where('reviewee_id', $id); }
}
