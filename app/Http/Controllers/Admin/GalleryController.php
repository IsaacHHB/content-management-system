<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Models\Gallery;
use App\Rules\ExistingImageAsset;
use App\Services\BlockRenderer;
use App\Services\MediaReferenceSynchronizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class GalleryController extends ContentController
{
    /** @var list<array{id: int, alt_text: string, caption?: string|null, sort_order: int}>|null */
    private ?array $assets = null;

    protected function model(): string
    {
        return Gallery::class;
    }

    protected function key(): string
    {
        return 'galleries';
    }

    protected function defaultSort(): array
    {
        return ['sort_order', 'asc'];
    }

    protected function editRelations(): array
    {
        // `.media` is required: MediaAsset appends `url`/`thumb_url`, each of which
        // hits getFirstMedia() — without it a 40-photo gallery lazy-loads 40 times.
        return ['mediaAssets.media'];
    }

    protected function indexCounts(): array
    {
        return ['mediaAssets'];
    }

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('galleries')->ignore($model?->getKey())->whereNull('deleted_at')],
            'description' => ['nullable', 'string', 'max:5000'], 'status' => ['required', Rule::enum(PublishStatus::class)], 'published_at' => ['nullable', 'date'],
            'sort_order' => ['sometimes', 'integer', 'min:0'], 'media_assets' => ['sometimes', 'array'],
            'media_assets.*.id' => ['required', 'integer', 'distinct', new ExistingImageAsset], 'media_assets.*.alt_text' => ['required', 'string', 'max:255'],
            'media_assets.*.caption' => ['nullable', 'string', 'max:2000'], 'media_assets.*.sort_order' => ['required', 'integer', 'min:0'],
        ];
    }

    protected function prepare(Request $request, array $data, ?Model $model, BlockRenderer $blocks): array
    {
        $this->assets = $data['media_assets'] ?? null;
        unset($data['media_assets']);

        return parent::prepare($request, $data, $model, $blocks);
    }

    protected function afterSave(Model $model, array $data, MediaReferenceSynchronizer $references): void
    {
        if (! $model instanceof Gallery) {
            return;
        }

        if ($this->assets === null) {
            return;
        }
        $sync = [];
        foreach ($this->assets as $asset) {
            $sync[$asset['id']] = ['alt_text' => $asset['alt_text'], 'caption' => $asset['caption'] ?? null, 'sort_order' => $asset['sort_order']];
        }
        $model->mediaAssets()->sync($sync);

        // Mirror the pivot into media_references so the media library's usage
        // report is complete (deletion protection also uses the direct relation).
        $structured = ['media' => array_map(fn ($id) => ['media_asset_id' => (int) $id], array_keys($sync))];
        $references->sync($model, [], $structured);
    }
}
