<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Models\Program;
use App\Rules\ExistingImageAsset;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ProgramController extends ContentController
{
    protected function model(): string
    {
        return Program::class;
    }

    protected function key(): string
    {
        return 'programs';
    }

    protected function defaultSort(): array
    {
        return ['sort_order', 'asc'];
    }

    protected function blockField(): ?string
    {
        return 'blocks';
    }

    protected function editRelations(): array
    {
        return ['ogMediaAsset'];
    }

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('programs')->ignore($model?->getKey())->whereNull('deleted_at')],
            'excerpt' => ['required', 'string', 'max:2000'], 'blocks' => ['present', 'array'], 'status' => ['required', Rule::enum(PublishStatus::class)],
            'published_at' => ['nullable', 'date'], 'seo_title' => ['nullable', 'string', 'max:255'], 'seo_description' => ['nullable', 'string', 'max:255'],
            'og_media_asset_id' => ['nullable', 'integer', new ExistingImageAsset], 'contact_name' => ['nullable', 'string', 'max:255'], 'contact_email' => ['nullable', 'email', 'max:255'],
            'contact_phone' => ['nullable', 'string', 'max:50'], 'external_url' => ['nullable', 'url', 'max:255'], 'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }
}
