<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Education extends Model
{
    protected $table = 'educations';

    public $timestamps = false;

    protected $fillable = [
        'freelancer_profile_id', 'institution', 'degree',
        'field_of_study', 'start_year', 'end_year',
    ];

    public function freelancerProfile()
    {
        return $this->belongsTo(FreelancerProfile::class);
    }
}
