<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'admin_id', 'action', 'target_type', 'target_id',
        'payload', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['payload' => 'array', 'created_at' => 'datetime'];
    }

    public function admin() { return $this->belongsTo(User::class, 'admin_id'); }

    public static function record(User $admin, string $action, ?Model $target = null, array $payload = []): void
    {
        static::create([
            'admin_id'    => $admin->id,
            'action'      => $action,
            'target_type' => $target ? class_basename($target) : null,
            'target_id'   => $target?->getKey(),
            'payload'     => $payload ?: null,
            'ip_address'  => request()->ip(),
            'user_agent'  => request()->userAgent(),
        ]);
    }
}
