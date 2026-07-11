<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Model;

/**
 * Maintains the `slug_lock` discriminator so a soft-deleted row releases its
 * slug for reuse while a live row keeps it locked. Requires the model to use
 * SoftDeletes and to have a `slug_lock` column (see the content-table migration).
 */
trait HasReusableSlug
{
    public static function bootHasReusableSlug(): void
    {
        static::deleted(function (Model $model): void {
            // Only soft-deletes leave a row behind that must release its slug;
            // a force-delete removes the row entirely (deleted_at stays null).
            if (method_exists($model, 'trashed') && $model->trashed()
                && (int) $model->getAttribute('slug_lock') !== (int) $model->getKey()) {
                $model->setAttribute('slug_lock', $model->getKey());
                $model->saveQuietly();
            }
        });

        static::restoring(function (Model $model): void {
            $model->setAttribute('slug_lock', 0);
        });
    }
}
