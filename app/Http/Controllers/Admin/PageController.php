<?php

namespace App\Http\Controllers\Admin;

use App\Enums\PublishStatus;
use App\Models\Page;
use App\Models\Redirect;
use App\Rules\ExistingImageAsset;
use App\Services\BlockRenderer;
use App\Services\MediaReferenceSynchronizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PageController extends ContentController
{
    /** @var array<int, string> */
    private array $oldPaths = [];

    protected function model(): string
    {
        return Page::class;
    }

    protected function key(): string
    {
        return 'pages';
    }

    protected function blockField(): ?string
    {
        return 'blocks';
    }

    protected function editRelations(): array
    {
        return ['ogMediaAsset'];
    }

    protected function formProps(?Model $model): array
    {
        return [
            'parentOptions' => Page::query()
                ->whereNull('parent_id')
                ->when($model instanceof Page, fn ($query) => $query->whereKeyNot($model->getKey()))
                ->orderBy('title')
                ->get(['id', 'title']),
        ];
    }

    protected function rules(Request $request, ?Model $model = null): array
    {
        $parentKey = (int) ($request->input('parent_id') ?: 0);
        $locale = (string) $request->input('locale', 'en');

        return [
            'parent_id' => ['nullable', 'integer', 'exists:pages,id'],
            'title' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'alpha_dash', 'max:255', Rule::unique('pages')->where(fn ($query) => $query->where('parent_key', $parentKey)->where('locale', $locale)->whereNull('deleted_at'))->ignore($model?->getKey())],
            'blocks' => ['present', 'array'],
            'status' => ['required', Rule::enum(PublishStatus::class)],
            'published_at' => ['nullable', 'date'],
            'seo_title' => ['nullable', 'string', 'max:255'],
            'seo_description' => ['nullable', 'string', 'max:255'],
            'og_media_asset_id' => ['nullable', 'integer', new ExistingImageAsset],
            'locale' => ['required', 'string', 'max:8'],
            'is_locked' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ];
    }

    protected function prepare(Request $request, array $data, ?Model $model, BlockRenderer $blocks): array
    {
        if ($model instanceof Page && $model->is_locked && ($data['slug'] !== $model->slug || ($data['parent_id'] ?? null) !== $model->parent_id)) {
            throw ValidationException::withMessages(['slug' => 'Locked pages cannot be moved or re-slugged.']);
        }
        if ($model !== null && (int) ($data['parent_id'] ?? 0) === $model->getKey()) {
            throw ValidationException::withMessages(['parent_id' => 'A page cannot be its own parent.']);
        }
        if (($data['parent_id'] ?? null) !== null) {
            $parent = Page::query()->findOrFail((int) $data['parent_id']);
            if ($parent->parent_id !== null) {
                throw ValidationException::withMessages(['parent_id' => 'Pages may be at most two levels deep.']);
            }
            if ($model instanceof Page && $model->children()->exists()) {
                throw ValidationException::withMessages(['parent_id' => 'A page with children cannot be moved below another page.']);
            }
        }

        return parent::prepare($request, $data, $model, $blocks);
    }

    protected function beforeSave(Model $model, array $data): void
    {
        if ($model instanceof Page && ($model->slug !== $data['slug'] || $model->parent_id !== ($data['parent_id'] ?? null))) {
            $this->capturePaths($model);
        }
    }

    protected function afterSave(Model $model, array $data, MediaReferenceSynchronizer $references): void
    {
        if (! $model instanceof Page) {
            return;
        }

        parent::afterSave($model, $data, $references);
        if ($this->oldPaths === []) {
            return;
        }
        $model->unsetRelation('parent')->load('children');
        $newPages = collect([$model, ...$model->children])->keyBy('id');
        foreach ($this->oldPaths as $id => $oldPath) {
            $page = $newPages->get($id) ?? Page::query()->find($id);
            if ($page !== null && $oldPath !== $page->path) {
                Redirect::updateOrCreate(['from_path' => $oldPath], ['to_path' => $page->path, 'status_code' => 301]);
            }
        }
    }

    private function capturePaths(Page $page): void
    {
        $this->oldPaths[$page->id] = $page->path;
        foreach ($page->children as $child) {
            $this->oldPaths[$child->id] = $child->path;
        }
    }
}
