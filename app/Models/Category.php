<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = ['name', 'slug', 'parent_id', 'icon', 'sort_order', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function parent()   { return $this->belongsTo(Category::class, 'parent_id'); }
    public function children() { return $this->hasMany(Category::class, 'parent_id'); }
    public function skills()   { return $this->hasMany(Skill::class); }
    public function projects() { return $this->hasMany(Project::class); }

    public function scopeActive($q) { return $q->where('is_active', true); }
    public function scopeTopLevel($q) { return $q->whereNull('parent_id'); }
}
