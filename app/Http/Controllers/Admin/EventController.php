<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Models\Event;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EventController extends ContentController
{
    protected function model(): string
    {
        return Event::class;
    }

    protected function key(): string
    {
        return 'events';
    }

    protected function blockField(): ?string
    {
        return 'description';
    }

    protected function editRelations(): array
    {
        return ['ogMediaAsset'];
    }

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('events')->ignore($model?->getKey())->whereNull('deleted_at')],
            'description' => ['present', 'array'], 'status' => ['required', Rule::enum(PublishStatus::class)], 'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'], 'seo_description' => ['nullable', 'string', 'max:255'], 'og_media_asset_id' => ['nullable', 'exists:media_assets,id'],
            'starts_at' => ['nullable', 'required_unless:all_day,true', 'date'], 'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'start_date' => ['nullable', 'required_if:all_day,true', 'date'], 'end_date' => ['nullable', 'date', 'after_or_equal:start_date'], 'all_day' => ['required', 'boolean'],
            'timezone' => ['required', 'timezone'], 'location_name' => ['nullable', 'string', 'max:255'], 'address' => ['nullable', 'string', 'max:255'],
            'city' => ['nullable', 'string', 'max:255'], 'state' => ['nullable', 'string', 'max:50'], 'zip' => ['nullable', 'string', 'max:20'],
            'is_virtual' => ['required', 'boolean'], 'virtual_url' => ['nullable', 'url', 'required_if:is_virtual,true'], 'registration_url' => ['nullable', 'url'],
        ];
    }
}
