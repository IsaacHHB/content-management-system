<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

/**
 * @property mixed $value JSON-cast: string, int, array, or null depending on the key.
 */
class Setting extends Model
{
    protected $fillable = ['key', 'value', 'group'];

    protected function casts(): array
    {
        return ['value' => 'json'];
    }

    protected static function booted(): void
    {
        static::saved(fn () => Cache::forget('settings'));
        static::deleted(fn () => Cache::forget('settings'));
    }

    /** @return array<string, mixed> */
    public static function allCached(): array
    {
        return Cache::rememberForever('settings', fn () => self::query()->pluck('value', 'key')->all());
    }
}
