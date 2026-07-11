<?php

namespace App\Models;

use App\Contracts\SoftDeletableContent;
use App\Enums\PublishStatus;
use App\Models\Concerns\HasEditorialAudit;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasReusableSlug;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property PublishStatus $status
 * @property Carbon|null $published_at
 */
class Program extends Model implements SoftDeletableContent
{
    use HasEditorialAudit, HasPublishing, HasReusableSlug, HasSlug, LogsActivity, RecordsActivity, SoftDeletes {
        RecordsActivity::getActivitylogOptions insteadof LogsActivity;
    }

    protected $fillable = [
        'title', 'slug', 'excerpt', 'blocks', 'status', 'published_at', 'seo_title',
        'seo_description', 'og_media_asset_id', 'contact_name', 'contact_email',
        'contact_phone', 'external_url', 'sort_order', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['blocks' => 'array', 'status' => PublishStatus::class, 'published_at' => 'datetime'];
    }

    public function getSlugOptions(): SlugOptions
    {
        // Only consider live rows for uniqueness, so a soft-deleted row's slug
        // can be reused. The slug_lock discriminator + restore guard keep this safe.
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug')
            ->extraScope(fn ($query) => $query->whereNull('deleted_at'));
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function ogMediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_media_asset_id');
    }
}
