<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FreelancerProfile extends Model
{
    protected $fillable = [
        'user_id', 'headline', 'bio', 'hourly_rate', 'availability',
        'verification_status', 'verification_notes', 'resume_path',
        'total_earnings', 'rating_avg', 'rating_count',
    ];

    protected function casts(): array
    {
        return [
            'hourly_rate'    => 'decimal:2',
            'total_earnings' => 'decimal:2',
            'rating_avg'     => 'decimal:2',
        ];
    }

    public function user()           { return $this->belongsTo(User::class); }
    public function skills()         { return $this->belongsToMany(Skill::class, 'freelancer_skills'); }
    public function portfolio()      { return $this->hasMany(PortfolioItem::class); }
    public function experiences()    { return $this->hasMany(Experience::class); }
    public function educations()     { return $this->hasMany(Education::class); }
    public function certifications() { return $this->hasMany(Certification::class); }
    public function documents()      { return $this->hasMany(VerificationDocument::class); }

    public function isVerified(): bool { return $this->verification_status === 'approved'; }
}
