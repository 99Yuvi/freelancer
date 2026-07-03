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

    protected $appends = ['file_url'];

    protected function casts(): array
    {
        return ['read_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function conversation() { return $this->belongsTo(Conversation::class); }
    public function sender()       { return $this->belongsTo(User::class, 'sender_id'); }

    public function isRead(): bool { return $this->read_at !== null; }

    /** Full public URL for the attached file — null for text messages */
    public function getFileUrlAttribute(): ?string
    {
        return $this->file_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->file_path)
            : null;
    }
}
