<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class VerificationDocument extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'freelancer_profile_id', 'doc_type', 'file_path',
    ];

    protected function casts(): array
    {
        return ['uploaded_at' => 'datetime'];
    }

    public function freelancerProfile()
    {
        return $this->belongsTo(FreelancerProfile::class);
    }
}
