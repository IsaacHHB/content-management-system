<?php

namespace App\Models;

use App\Contracts\SoftDeletableContent;
use App\Enums\PublishStatus;
use App\Models\Concerns\HasEditorialAudit;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasReusableSlug;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Builder;
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
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property Carbon|null $starts_at
 * @property Carbon|null $ends_at
 * @property Carbon|null $start_date
 * @property Carbon|null $end_date
 * @property bool $all_day
 * @property array<int, array<string, mixed>> $description
 */
class Event extends Model implements SoftDeletableContent
{
    use HasEditorialAudit, HasPublishing, HasReusableSlug, HasSlug, LogsActivity, RecordsActivity, SoftDeletes {
        RecordsActivity::getActivitylogOptions insteadof LogsActivity;
    }

    protected $fillable = [
        'title', 'slug', 'description', 'status', 'published_at', 'seo_title', 'seo_description',
        'og_media_asset_id', 'starts_at', 'ends_at', 'start_date', 'end_date', 'all_day', 'timezone', 'location_name',
        'address', 'city', 'state', 'zip', 'is_virtual', 'virtual_url', 'registration_url',
        'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'description' => 'array', 'status' => PublishStatus::class, 'published_at' => 'datetime',
            'starts_at' => 'datetime', 'ends_at' => 'datetime', 'all_day' => 'boolean',
            'start_date' => 'date', 'end_date' => 'date',
            'is_virtual' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug')
            ->extraScope(fn ($query) => $query->whereNull('deleted_at'));
    }

    /**
     * @param  Builder<Event>  $query
     * @return Builder<Event>
     */
    public function scopeUpcoming(Builder $query): Builder
    {
        return $query
            ->where(function (Builder $query): void {
                $query->where(function (Builder $query): void {
                    $query->where('all_day', true)
                        ->whereRaw('COALESCE(end_date, start_date) >= ?', [today()->toDateString()]);
                })->orWhere(function (Builder $query): void {
                    $query->where('all_day', false)
                        ->whereRaw('COALESCE(ends_at, starts_at) >= ?', [now()]);
                });
            })
            ->orderByRaw('CASE WHEN all_day = 1 THEN start_date ELSE starts_at END');
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function ogMediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_media_asset_id');
    }
}
