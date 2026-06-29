<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioItem extends Model
{
    protected $fillable = ['freelancer_profile_id','title','description','project_url','category_id','sort_order'];

    public function freelancerProfile() { return $this->belongsTo(FreelancerProfile::class); }
    public function category()          { return $this->belongsTo(Category::class); }
    public function images()            { return $this->hasMany(PortfolioImage::class)->orderBy('sort_order'); }
}
