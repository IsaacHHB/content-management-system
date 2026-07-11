<?php

namespace App\Http\Controllers\Admin;

use App\Contracts\SoftDeletableContent;
use App\Enums\PublishStatus;
use App\Http\Controllers\Controller;
use App\Models\MediaReference;
use App\Services\BlockHydrator;
use App\Services\BlockRenderer;
use App\Services\MediaReferenceSynchronizer;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

abstract class ContentController extends Controller
{
    /** @return class-string<Model&SoftDeletableContent> */
    abstract protected function model(): string;

    /** Route/view key, e.g. "pages" → routes admin.pages.*, views admin/pages/*. */
    abstract protected function key(): string;

    /** @return array<string, mixed> */
    abstract protected function rules(Request $request, ?Model $model = null): array;

    protected function blockField(): ?string
    {
        return null;
    }

    protected function searchColumn(): string
    {
        return 'title';
    }

    /**
     * Extra relations to eager-load on the edit screen.
     *
     * @return array<int, string>
     */
    protected function editRelations(): array
    {
        return [];
    }

    /** @return array{0: string, 1: string} */
    protected function defaultSort(): array
    {
        return ['updated_at', 'desc'];
    }

    /**
     * Extra props for the index screen.
     *
     * @return array<string, mixed>
     */
    protected function indexProps(Request $request): array
    {
        return [];
    }

    /**
     * Extra props for the create/edit form (e.g. select options).
     *
     * @return array<string, mixed>
     */
    protected function formProps(?Model $model): array
    {
        return [];
    }

    public function index(Request $request): Response
    {
        $model = $this->model();
        $this->authorize('viewAny', $model);
        [$sortColumn, $sortDir] = $this->defaultSort();

        $items = $model::query()
            ->with('updatedByUser:id,name')
            ->when($request->string('search')->isNotEmpty(), fn ($query) => $query->where($this->searchColumn(), 'like', '%'.$request->string('search').'%'))
            ->when($request->string('status')->isNotEmpty(), fn ($query) => $query->where('status', $request->string('status')))
            ->orderBy($sortColumn, $sortDir === 'asc' ? 'asc' : 'desc')
            ->paginate(min(100, max(1, $request->integer('per_page', 20))))
            ->withQueryString();

        return Inertia::render("admin/{$this->key()}/index", [
            'items' => $items,
            'filters' => ['search' => $request->string('search')->toString(), 'status' => $request->string('status')->toString()],
            ...$this->indexProps($request),
        ]);
    }

    public function create(Request $request): Response
    {
        $this->authorize('create', $this->model());

        return Inertia::render("admin/{$this->key()}/create", [
            ...$this->formProps(null),
        ]);
    }

    public function store(Request $request, BlockRenderer $blocks, MediaReferenceSynchronizer $references): RedirectResponse
    {
        $modelClass = $this->model();
        $this->authorize('create', $modelClass);
        $data = $request->validate($this->rules($request));
        $data = $this->prepare($request, $data, null, $blocks);

        $item = DB::transaction(function () use ($request, $data, $modelClass, $references): Model {
            $item = $modelClass::create([
                ...$data,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);
            $this->afterSave($item, $data, $references);

            return $item;
        });

        return redirect()->route("admin.{$this->key()}.edit", $item)->with('success', 'Created.');
    }

    public function edit(Request $request, BlockHydrator $hydrator): Response
    {
        $item = $this->resolveModel($request);
        $this->authorize('view', $item);
        $item->load('updatedByUser:id,name', ...$this->editRelations());

        // Serialize with the block field hydrated so the editor previews media.
        $payload = $item->toArray();
        $blockField = $this->blockField();
        if ($blockField !== null) {
            $payload[$blockField] = $hydrator->hydrate($item->getAttribute($blockField) ?? []);
        }

        return Inertia::render("admin/{$this->key()}/edit", [
            'item' => $payload,
            ...$this->formProps($item),
        ]);
    }

    public function update(Request $request, BlockRenderer $blocks, MediaReferenceSynchronizer $references): RedirectResponse
    {
        $item = $this->resolveModel($request);
        $this->authorize('update', $item);
        $data = $request->validate($this->rules($request, $item));
        $data = $this->prepare($request, $data, $item, $blocks);
        $this->beforeSave($item, $data);

        DB::transaction(function () use ($request, $data, $item, $references): void {
            $item->update([...$data, 'updated_by' => $request->user()->id]);
            $this->afterSave($item, $data, $references);
        });

        return back()->with('success', 'Saved.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $item = $this->resolveModel($request);
        $this->authorize('delete', $item);
        abort_if((bool) ($item->is_locked ?? false), 422, 'This item is locked and cannot be deleted.');
        $item->delete();

        return redirect()->route("admin.{$this->key()}.index")->with('success', 'Moved to trash.');
    }

    public function restore(Request $request): RedirectResponse
    {
        $item = $this->resolveTrashedModel($request);
        $this->authorize('restore', $item);
        $this->guardSlugAvailableForRestore($item);
        $item->restore();

        return back()->with('success', 'Restored.');
    }

    public function forceDelete(Request $request): RedirectResponse
    {
        $item = $this->resolveTrashedModel($request);
        $this->authorize('forceDelete', $item);
        abort_if((bool) ($item->is_locked ?? false), 422, 'This item is locked and cannot be permanently deleted.');

        DB::transaction(function () use ($item): void {
            MediaReference::whereMorphedTo('referencer', $item)->delete();
            $item->forceDelete();
        });

        return back()->with('success', 'Permanently deleted.');
    }

    /**
     * A trashed row released its slug on delete, so a live row may have taken it
     * in the meantime. Restoring would then violate the slug-unique index; catch
     * it here with a clear message instead of a database error.
     */
    protected function guardSlugAvailableForRestore(Model $item): void
    {
        if ($item->getAttribute('slug') === null) {
            return;
        }

        $modelClass = $this->model();
        $conflict = $modelClass::query()->where('slug', $item->getAttribute('slug'));

        if ($item->getAttribute('parent_key') !== null) {
            $conflict->where('parent_key', $item->getAttribute('parent_key'))
                ->where('locale', $item->getAttribute('locale'));
        }

        abort_if($conflict->exists(), 422, 'A live item already uses this slug; rename or remove it before restoring.');
    }

    /** @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function prepare(Request $request, array $data, ?Model $model, BlockRenderer $blocks): array
    {
        $blockField = $this->blockField();
        if ($blockField !== null && array_key_exists($blockField, $data)) {
            $data[$blockField] = $blocks->sanitize($data[$blockField]);
        }
        if (($data['status'] ?? null) === PublishStatus::Published->value) {
            $modelClass = $this->model();
            $this->authorize('publish', $model ?? new $modelClass);
        }

        return $data;
    }

    /** @param array<string, mixed> $data */
    protected function afterSave(Model $model, array $data, MediaReferenceSynchronizer $references): void
    {
        $blockField = $this->blockField();
        if ($blockField !== null) {
            $references->sync($model, $model->getAttribute($blockField) ?? [], [
                'og_media_asset_id' => $model->getAttribute('og_media_asset_id'),
            ]);
        }
    }

    /** @param array<string, mixed> $data */
    protected function beforeSave(Model $model, array $data): void {}

    public function reorder(Request $request): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array'], 'ids.*' => ['integer', 'distinct']])['ids'];
        $model = $this->model();
        $items = $model::query()->whereIn('id', $ids)->get()->keyBy('id');
        abort_unless($items->count() === count($ids), 422, 'Every reorder ID must belong to this module.');

        DB::transaction(function () use ($ids, $items): void {
            foreach ($ids as $order => $id) {
                $this->authorize('update', $items[$id]);
                $items[$id]->update(['sort_order' => $order]);
            }
        });

        return back()->with('success', 'Reordered.');
    }

    private function resolveModel(Request $request): Model
    {
        $parameter = (string) array_key_first($request->route()->parameters());
        $value = $request->route($parameter);
        $model = $this->model();

        if ($value instanceof Model) {
            return $value;
        }

        abort_unless(is_string($value), 404);

        return (new $model)->newQuery()->findOrFail($value);
    }

    /** @return Model&SoftDeletableContent */
    private function resolveTrashedModel(Request $request): Model
    {
        $parameter = (string) array_key_first($request->route()->parameters());
        $value = $request->route($parameter);
        abort_unless(is_string($value), 404);
        $model = $this->model();

        $item = (new $model)->newQueryWithoutScopes()->whereNotNull('deleted_at')->findOrFail($value);
        abort_unless($item instanceof SoftDeletableContent, 404);

        return $item;
    }
}
