<?php

namespace App\Models;

use App\Support\CacheInvalidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Menu extends Model
{
    protected $fillable = ['name', 'slot'];

    protected static function booted(): void
    {
        $forget = static fn () => CacheInvalidation::forgetAfterCommit('public_menus');
        static::saved($forget);
        static::deleted($forget);
    }

    /** @return HasMany<MenuItem, $this> */
    public function items(): HasMany
    {
        return $this->hasMany(MenuItem::class)->whereNull('parent_id')->orderBy('sort_order');
    }
}
