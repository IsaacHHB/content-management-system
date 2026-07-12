<?php

namespace App\Models;

use App\Models\Concerns\HasEditorialAudit;
use App\Models\Concerns\RecordsActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaAsset extends Model implements HasMedia
{
    use HasEditorialAudit, InteractsWithMedia, LogsActivity, RecordsActivity, SoftDeletes {
        RecordsActivity::getActivitylogOptions insteadof LogsActivity;
    }

    protected $fillable = [
        'uuid', 'type', 'original_name', 'alt_text', 'caption', 'credit', 'focal_point',
        'status', 'created_by', 'updated_by',
    ];

    /** @var list<string> */
    protected $appends = ['url', 'thumb_url'];

    protected function casts(): array
    {
        return ['focal_point' => 'array'];
    }

    public function getUrlAttribute(): ?string
    {
        return $this->getFirstMedia('original')?->getUrl();
    }

    public function getThumbUrlAttribute(): ?string
    {
        $media = $this->getFirstMedia('original');

        if ($media === null) {
            return null;
        }

        return $this->type === 'image' ? $media->getUrl('thumb') : $media->getUrl();
    }

    /** @return HasMany<MediaReference, $this> */
    public function references(): HasMany
    {
        return $this->hasMany(MediaReference::class);
    }

    /** @return BelongsToMany<Gallery, $this> */
    public function galleries(): BelongsToMany
    {
        return $this->belongsToMany(Gallery::class);
    }

    public function isInUse(): bool
    {
        return $this->references()->exists()
            || $this->galleries()->exists()
            || Page::where('og_media_asset_id', $this->id)->exists()
            || Program::where('og_media_asset_id', $this->id)->exists()
            || Event::where('og_media_asset_id', $this->id)->exists()
            || Post::where('og_media_asset_id', $this->id)->exists()
            || TeamMember::where('photo_media_asset_id', $this->id)->exists();
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('original')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        // Detect "is image" from the file medialibrary passes in, not from
        // $this->type: medialibrary registers conversions against a bare
        // `new MediaAsset` (type null), so branching on the model attribute
        // would suppress every conversion. Using $media also keeps this method
        // free of model state, avoiding a serialization recursion between the
        // thumb_url accessor and $media->model.
        if ($media !== null && ! str_starts_with((string) $media->mime_type, 'image/')) {
            return;
        }

        $this->addMediaConversion('thumb')->width(400);
        $this->addMediaConversion('medium')->width(800);
        $this->addMediaConversion('large')->width(1600);
        $this->addMediaConversion('thumb-webp')->format('webp')->width(400);
        $this->addMediaConversion('medium-webp')->format('webp')->width(800);
        $this->addMediaConversion('large-webp')->format('webp')->width(1600);
    }
}
