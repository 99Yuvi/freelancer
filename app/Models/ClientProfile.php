<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClientProfile extends Model
{
    protected $fillable = [
        'user_id', 'company_name', 'website', 'industry', 'location', 'bio', 'total_spent',
    ];

    protected function casts(): array
    {
        return ['total_spent' => 'decimal:2'];
    }

    public function user() { return $this->belongsTo(User::class); }
}
