<?php

namespace App\Models;

use App\Contracts\SoftDeletableContent;
use App\Models\Concerns\HasEditorialAudit;
use App\Models\Concerns\HasReusableSlug;
use App\Models\Concerns\RecordsActivity;
use App\Support\CacheInvalidation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class Partner extends Model implements SoftDeletableContent
{
    use HasEditorialAudit, HasReusableSlug, HasSlug, LogsActivity, RecordsActivity, SoftDeletes {
        RecordsActivity::getActivitylogOptions insteadof LogsActivity;
    }

    protected $fillable = [
        'name', 'slug', 'website_url', 'logo_media_asset_id',
        'sort_order', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    protected static function booted(): void
    {
        $forget = fn () => CacheInvalidation::forgetAfterCommit('public_partners');
        static::saved($forget);
        static::deleted($forget);
        static::restored($forget);
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')
            ->extraScope(fn ($query) => $query->whereNull('deleted_at'));
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function logo(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'logo_media_asset_id');
    }
}
