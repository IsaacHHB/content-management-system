<?php

namespace App\Models;

use App\Contracts\SoftDeletableContent;
use App\Enums\PublishStatus;
use App\Models\Concerns\HasEditorialAudit;
use App\Models\Concerns\HasPublishing;
use App\Models\Concerns\HasReusableSlug;
use App\Models\Concerns\InvalidatesMenuCache;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Spatie\Activitylog\Models\Concerns\LogsActivity;

/**
 * @property int $id
 * @property int|null $parent_id
 * @property string $slug
 * @property bool $is_locked
 * @property string $path
 * @property PublishStatus $status
 * @property Carbon|null $published_at
 * @property string|null $seo_title
 * @property string|null $seo_description
 * @property array<int, array<string, mixed>> $blocks
 */
class Page extends Model implements SoftDeletableContent
{
    use HasEditorialAudit, HasPublishing, HasReusableSlug, InvalidatesMenuCache, LogsActivity, RecordsActivity, SoftDeletes {
        RecordsActivity::getActivitylogOptions insteadof LogsActivity;
    }

    protected $fillable = [
        'parent_id', 'title', 'slug', 'blocks', 'status', 'published_at', 'seo_title',
        'seo_description', 'og_media_asset_id', 'locale', 'is_locked', 'sort_order',
        'created_by', 'updated_by',
    ];

    protected static function booted(): void
    {
        static::saving(fn (Page $page) => $page->parent_key = max(0, (int) ($page->parent_id ?? 0)));
    }

    protected function casts(): array
    {
        return [
            'blocks' => 'array',
            'status' => PublishStatus::class,
            'published_at' => 'datetime',
            'is_locked' => 'boolean',
        ];
    }

    /** @return BelongsTo<Page, $this> */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /** @return HasMany<Page, $this> */
    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function ogMediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'og_media_asset_id');
    }

    public function getPathAttribute(): string
    {
        $segments = [$this->slug];
        $parent = $this->parent;

        while ($parent !== null) {
            array_unshift($segments, $parent->slug);
            $parent = $parent->parent;
        }

        return '/'.implode('/', $segments);
    }
}
