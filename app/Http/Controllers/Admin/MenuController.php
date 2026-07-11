<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Menu;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class MenuController extends Controller
{
    private const LINKABLES = [Page::class, Program::class, Post::class, Event::class];

    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('menus.manage'), 403);

        return response()->json(Menu::with('items.children.linkable', 'items.linkable')->get());
    }

    public function update(Request $request, Menu $menu): JsonResponse
    {
        abort_unless($request->user()->can('menus.manage'), 403);
        $data = $request->validate(['name' => ['required', 'string', 'max:255'], 'items' => ['present', 'array', 'max:50'], 'items.*.label' => ['required', 'string', 'max:255'], 'items.*.linkable_type' => ['nullable', Rule::in(self::LINKABLES)], 'items.*.linkable_id' => ['nullable', 'integer'], 'items.*.custom_url' => ['nullable', 'string', 'max:2048'], 'items.*.opens_new_tab' => ['required', 'boolean'], 'items.*.children' => ['sometimes', 'array', 'max:20'], 'items.*.children.*.label' => ['required', 'string', 'max:255'], 'items.*.children.*.linkable_type' => ['nullable', Rule::in(self::LINKABLES)], 'items.*.children.*.linkable_id' => ['nullable', 'integer'], 'items.*.children.*.custom_url' => ['nullable', 'string', 'max:2048'], 'items.*.children.*.opens_new_tab' => ['required', 'boolean']]);
        DB::transaction(function () use ($menu, $data): void {
            $menu->update(['name' => $data['name']]);
            $menu->items()->delete();
            foreach ($data['items'] as $order => $item) {
                $children = $item['children'] ?? [];
                unset($item['children']);
                $this->validateTarget($item);
                $parent = $menu->items()->create([...$item, 'sort_order' => $order]);
                foreach ($children as $childOrder => $child) {
                    $this->validateTarget($child);
                    $parent->children()->create([...$child, 'menu_id' => $menu->id, 'sort_order' => $childOrder]);
                }
            }
        });

        return response()->json($menu->load('items.children'));
    }

    /** @param array<string, mixed> $item */
    private function validateTarget(array $item): void
    {
        $hasLinkable = ! empty($item['linkable_type']) && ! empty($item['linkable_id']);
        $hasCustom = ! empty($item['custom_url']);
        abort_unless($hasLinkable xor $hasCustom, 422, 'Each menu item needs exactly one internal or custom target.');
        if ($hasLinkable) {
            abort_unless($item['linkable_type']::query()->whereKey($item['linkable_id'])->exists(), 422, 'A menu target does not exist.');
        }
    }
}
