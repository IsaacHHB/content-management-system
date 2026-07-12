<?php

namespace App\Http\Controllers\Admin;

use App\Models\Partner;
use App\Rules\ExistingImageAsset;
use App\Services\MediaReferenceSynchronizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PartnerController extends ContentController
{
    protected function model(): string
    {
        return Partner::class;
    }

    protected function key(): string
    {
        return 'partners';
    }

    protected function searchColumn(): string
    {
        return 'name';
    }

    protected function defaultSort(): array
    {
        return ['sort_order', 'asc'];
    }

    protected function editRelations(): array
    {
        return ['logo'];
    }

    protected function indexRelations(): array
    {
        return ['logo.media'];
    }

    protected function afterSave(Model $model, array $data, MediaReferenceSynchronizer $references): void
    {
        $references->sync($model, [], ['logo_media_asset_id' => $model->getAttribute('logo_media_asset_id')]);
    }

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('partners')->ignore($model?->getKey())->whereNull('deleted_at')],
            'website_url' => ['nullable', 'url', 'max:255'],
            'logo_media_asset_id' => ['nullable', 'integer', new ExistingImageAsset],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
