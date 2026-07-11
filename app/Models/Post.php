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
class Post extends Model implements SoftDeletableContent
{
    use HasEditorialAudit, HasPublishing, HasReusableSlug, HasSlug, LogsActivity, RecordsActivity, SoftDeletes {
        RecordsActivity::getActivitylogOptions insteadof LogsActivity;
    }

    protected $fillable = [
        'title', 'slug', 'excerpt', 'blocks', 'status', 'published_at', 'seo_title',
        'seo_description', 'og_media_asset_id', 'author_id', 'is_featured', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'blocks' => 'array', 'status' => PublishStatus::class, 'published_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('title')->saveSlugsTo('slug')
            ->extraScope(fn ($query) => $query->whereNull('deleted_at'));
    }

    /** @return BelongsTo<User, $this> */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    /** @return BelongsToMany<Category, $this> */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class);
    }
}
