<?php

namespace App\Http\Controllers\Admin;

use App\Models\TeamMember;
use App\Services\MediaReferenceSynchronizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TeamMemberController extends ContentController
{
    protected function model(): string
    {
        return TeamMember::class;
    }

    protected function key(): string
    {
        return 'team';
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
        return ['photo'];
    }

    protected function afterSave(Model $model, array $data, MediaReferenceSynchronizer $references): void
    {
        $references->sync($model, [], ['photo_media_asset_id' => $model->getAttribute('photo_media_asset_id')]);
    }

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('team_members')->ignore($model?->getKey())->whereNull('deleted_at')],
            'title' => ['required', 'string', 'max:255'], 'group' => ['required', Rule::in(['staff', 'board'])], 'bio' => ['required', 'string', 'max:10000'], 'email' => ['nullable', 'email', 'max:255'],
            'show_email' => ['required', 'boolean'], 'phone' => ['nullable', 'string', 'max:50'], 'show_phone' => ['required', 'boolean'],
            'photo_media_asset_id' => ['nullable', 'exists:media_assets,id'], 'sort_order' => ['sometimes', 'integer', 'min:0'], 'is_active' => ['required', 'boolean'],
        ];
    }
}
