<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Conversation extends Model
{
    public $timestamps = false;
    protected $fillable = ['contract_id', 'client_id', 'freelancer_id', 'last_message_at'];

    protected function casts(): array
    {
        return ['last_message_at' => 'datetime', 'created_at' => 'datetime'];
    }

    public function contract()
    {
        return $this->belongsTo(Contract::class);
    }
    public function client()
    {
        return $this->belongsTo(User::class, 'client_id');
    }
    public function freelancer()
    {
        return $this->belongsTo(User::class, 'freelancer_id');
    }
    public function messages()
    {
        return $this->hasMany(Message::class)->orderBy('created_at', 'desc');
    }
    public function latestMessage()
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function isParty(User $user): bool
    {
        return $this->client_id === $user->id || $this->freelancer_id === $user->id;
    }
}
