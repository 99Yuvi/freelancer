<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Certification extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'freelancer_profile_id', 'name', 'issuer',
        'issued_date', 'certificate_url',
    ];

    protected function casts(): array
    {
        return ['issued_date' => 'date'];
    }

    public function freelancerProfile()
    {
        return $this->belongsTo(FreelancerProfile::class);
    }
}
