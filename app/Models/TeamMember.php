<?php

namespace App\Models;

use App\Contracts\SoftDeletableContent;
use App\Models\Concerns\HasEditorialAudit;
use App\Models\Concerns\HasReusableSlug;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;

class TeamMember extends Model implements SoftDeletableContent
{
    use HasEditorialAudit, HasReusableSlug, HasSlug, LogsActivity, RecordsActivity, SoftDeletes {
        RecordsActivity::getActivitylogOptions insteadof LogsActivity;
    }

    protected $fillable = [
        'name', 'slug', 'title', 'bio', 'email', 'show_email', 'phone', 'show_phone',
        'photo_media_asset_id', 'sort_order', 'is_active', 'created_by', 'updated_by',
    ];

    protected function casts(): array
    {
        return ['show_email' => 'boolean', 'show_phone' => 'boolean', 'is_active' => 'boolean'];
    }

    public function getSlugOptions(): SlugOptions
    {
        return SlugOptions::create()->generateSlugsFrom('name')->saveSlugsTo('slug')
            ->extraScope(fn ($query) => $query->whereNull('deleted_at'));
    }

    /** @return BelongsTo<MediaAsset, $this> */
    public function photo(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class, 'photo_media_asset_id');
    }
}
