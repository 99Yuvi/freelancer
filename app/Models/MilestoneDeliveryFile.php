<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MilestoneDeliveryFile extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'delivery_id', 'file_path', 'original_name', 'mime_type', 'file_size',
    ];

    public function delivery()
    {
        return $this->belongsTo(MilestoneDelivery::class, 'delivery_id');
    }
}
