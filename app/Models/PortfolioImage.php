<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PortfolioImage extends Model
{
    public $timestamps = false;
    protected $fillable = ['portfolio_item_id', 'file_path', 'sort_order'];

    public function item() { return $this->belongsTo(PortfolioItem::class, 'portfolio_item_id'); }
}
