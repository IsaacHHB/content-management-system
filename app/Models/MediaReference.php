<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class MediaReference extends Model
{
    protected $fillable = ['media_asset_id', 'referencer_type', 'referencer_id', 'block_id', 'field'];

    /** @return BelongsTo<MediaAsset, $this> */
    public function mediaAsset(): BelongsTo
    {
        return $this->belongsTo(MediaAsset::class);
    }

    /** @return MorphTo<Model, $this> */
    public function referencer(): MorphTo
    {
        return $this->morphTo();
    }
}
