<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Experience extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'freelancer_profile_id', 'title', 'company',
        'start_date', 'end_date', 'is_current', 'description',
    ];

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date'   => 'date',
            'is_current' => 'boolean',
        ];
    }

    public function freelancerProfile()
    {
        return $this->belongsTo(FreelancerProfile::class);
    }
}
