<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Models\Category;
use App\Models\Post;
use App\Models\User;
use App\Services\BlockRenderer;
use App\Services\MediaReferenceSynchronizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PostController extends ContentController
{
    /** @var list<int>|null */
    private ?array $categoryIds = null;

    protected function model(): string
    {
        return Post::class;
    }

    protected function key(): string
    {
        return 'posts';
    }

    protected function blockField(): ?string
    {
        return 'blocks';
    }

    protected function editRelations(): array
    {
        return ['categories:id', 'ogMediaAsset'];
    }

    protected function formProps(?Model $model): array
    {
        return [
            'categories' => Category::query()->orderBy('name')->get(['id', 'name']),
            'authors' => User::query()->where('is_active', true)->orderBy('name')->get(['id', 'name']),
        ];
    }

    protected function rules(Request $request, ?Model $model = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'], 'slug' => ['nullable', 'alpha_dash', 'max:255', Rule::unique('posts')->ignore($model?->getKey())->whereNull('deleted_at')],
            'excerpt' => ['required', 'string', 'max:2000'], 'blocks' => ['present', 'array'], 'status' => ['required', Rule::enum(PublishStatus::class)],
            'published_at' => ['nullable', 'date'], 'seo_title' => ['nullable', 'string', 'max:255'], 'seo_description' => ['nullable', 'string', 'max:255'],
            'og_media_asset_id' => ['nullable', 'exists:media_assets,id'], 'author_id' => ['nullable', 'exists:users,id'], 'is_featured' => ['sometimes', 'boolean'],
            'category_ids' => ['sometimes', 'array'], 'category_ids.*' => ['integer', 'exists:categories,id'],
        ];
    }

    protected function prepare(Request $request, array $data, ?Model $model, BlockRenderer $blocks): array
    {
        $this->categoryIds = $data['category_ids'] ?? null;
        unset($data['category_ids']);

        return parent::prepare($request, $data, $model, $blocks);
    }

    protected function afterSave(Model $model, array $data, MediaReferenceSynchronizer $references): void
    {
        if (! $model instanceof Post) {
            return;
        }

        parent::afterSave($model, $data, $references);
        if ($this->categoryIds !== null) {
            $model->categories()->sync($this->categoryIds);
        }
    }
}
