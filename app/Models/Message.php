<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'conversation_id', 'sender_id', 'type', 'body',
        'file_path', 'file_name', 'file_size', 'read_at',
    ];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function conversation() { return $this->belongsTo(Conversation::class); }
    public function sender()       { return $this->belongsTo(User::class, 'sender_id'); }

    public function isRead(): bool { return $this->read_at !== null; }
}
