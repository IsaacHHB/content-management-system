<?php

namespace App\Models;

use App\Contracts\SoftDeletableContent;
use App\Enums\PublishStatus;
use App\Models\Concerns\HasEditorialAudit;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasReusableSlug;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

/**
 * @property PublishStatus $status
 * @property Carbon|null $published_at
 */
class Gallery extends Model implements SoftDeletableContent
{
    use HasEditorialAudit, HasPublishing, HasReusableSlug, HasSlug, LogsActivity, RecordsActivity, SoftDeletes {
        RecordsActivity::getActivitylogOptions insteadof LogsActivity;
    }

    protected $fillable = ['title', 'slug', 'description', 'status', 'published_at', 'sort_order', 'created_by', 'updated_by'];

    protected function casts(): array
    {
        return ['status' => PublishStatus::class, 'published_at' => 'datetime'];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug')
            ->extraScope(fn ($query) => $query->whereNull('deleted_at'));
    }

    /** @return BelongsToMany<MediaAsset, $this> */
    public function mediaAssets(): BelongsToMany
    {
        return $this->belongsToMany(MediaAsset::class)
            ->withPivot(['alt_text', 'caption', 'sort_order'])
            ->withTimestamps()
            ->orderByPivot('sort_order');
    }
}
