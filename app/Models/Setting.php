<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class Setting extends Model
{
    public $timestamps = false;
    protected $primaryKey = 'key_name';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key_name', 'value', 'type', 'group_name', 'label'];

    /** Get a setting value, cast to the correct type */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = Cache::remember("setting:{$key}", 600, fn() => static::find($key));
        if (!$setting) return $default;

        return match ($setting->type) {
            'integer' => (int) $setting->value,
            'decimal' => (float) $setting->value,
            'boolean' => (bool) $setting->value,
            'json'    => json_decode($setting->value, true),
            default   => $setting->value,
        };
    }

    /** Update a setting and clear the cache */
    public static function set(string $key, mixed $value): void
    {
        static::where('key_name', $key)->update(['value' => (string) $value]);
        Cache::forget("setting:{$key}");
    }
}
