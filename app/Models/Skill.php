<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Skill extends Model
{
    public $timestamps = false;
    protected $fillable = ['name', 'slug', 'category_id', 'is_approved'];

    protected function casts(): array
    {
        return ['is_approved' => 'boolean', 'created_at' => 'datetime'];
    }

    public function category()     { return $this->belongsTo(Category::class); }
    public function projects()     { return $this->belongsToMany(Project::class, 'project_skills'); }
    public function freelancers()  { return $this->belongsToMany(FreelancerProfile::class, 'freelancer_skills'); }

    public function scopeApproved($q) { return $q->where('is_approved', true); }
}
