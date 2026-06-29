<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MilestoneDelivery extends Model
{
    public $timestamps = false;
    protected $fillable = ['milestone_id', 'note'];
    protected function casts(): array { return ['created_at' => 'datetime']; }

    public function milestone() { return $this->belongsTo(Milestone::class); }
    public function files()     { return $this->hasMany(MilestoneDeliveryFile::class, 'delivery_id'); }
}
