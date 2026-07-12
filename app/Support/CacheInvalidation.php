<?php

namespace App\Support;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class CacheInvalidation
{
    /**
     * Forget a cache key once the surrounding transaction commits.
     *
     * Model events fire *inside* the transaction, so forgetting there leaves a window
     * where a concurrent request re-populates a `rememberForever` key with pre-commit
     * data — which then sticks forever. Runs immediately when not in a transaction.
     */
    public static function forgetAfterCommit(string $key): void
    {
        DB::afterCommit(static fn () => Cache::forget($key));
    }
}
