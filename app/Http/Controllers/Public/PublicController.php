<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

abstract class PublicController extends Controller
{
    /**
     * @param  array<string, mixed>  $props
     */
    protected function render(string $component, array $props = []): Response
    {
        return Inertia::render($component, [
            'publicMenus' => $this->menus(),
            ...$props,
        ]);
    }

    /**
     * Header + footer menu trees with resolved URLs, cached until a menu changes.
     *
     * @return array{header: array<int, array<string, mixed>>, footer: array<int, array<string, mixed>>}
     */
    protected function menus(): array
    {
        return Cache::rememberForever('public_menus', function (): array {
            $menus = Menu::with('items.children.linkable', 'items.linkable')->get()->keyBy('slot');

            return [
                'header' => $this->tree($menus->get('header')),
                'footer' => $this->tree($menus->get('footer')),
            ];
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function tree(?Menu $menu): array
    {
        if ($menu === null) {
            return [];
        }

        return $menu->items->whereNull('parent_id')->sortBy('sort_order')->values()->map(fn (MenuItem $item) => [
            'id' => $item->id,
            'label' => $item->label,
            'url' => $this->itemUrl($item),
            'opens_new_tab' => (bool) $item->opens_new_tab,
            'children' => $item->children->sortBy('sort_order')->values()->map(fn (MenuItem $child) => [
                'id' => $child->id,
                'label' => $child->label,
                'url' => $this->itemUrl($child),
                'opens_new_tab' => (bool) $child->opens_new_tab,
            ])->all(),
        ])->all();
    }

    private function itemUrl(MenuItem $item): string
    {
        if ($item->custom_url) {
            return $item->custom_url;
        }

        $linkable = $item->linkable;

        return match (true) {
            $linkable instanceof Page => $linkable->path,
            $linkable instanceof Program => '/programs/'.$linkable->slug,
            $linkable instanceof Post => '/news/'.$linkable->slug,
            $linkable instanceof Event => '/events/'.$linkable->slug,
            default => '#',
        };
    }

    /**
     * @return array<string, string|null>
     */
    protected function seo(?string $title, ?string $description = null, ?string $image = null): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'image' => $image,
        ];
    }
}
