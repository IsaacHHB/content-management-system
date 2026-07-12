<?php

namespace App\Models\Concerns;

use App\Support\CacheInvalidation;

/**
 * The `public_menus` cache stores *resolved* menu URLs (a page's path, a post's
 * `/news/{slug}`, …), so it goes stale when a linkable target is re-slugged,
 * moved, or trashed — not only when the menu itself is edited. Any model that can
 * be a menu target must bust the cache on write.
 */
trait InvalidatesMenuCache
{
    public static function bootInvalidatesMenuCache(): void
    {
        $forget = static fn () => CacheInvalidation::forgetAfterCommit('public_menus');

        static::saved($forget);
        static::deleted($forget);
        static::restored($forget);
    }
}
