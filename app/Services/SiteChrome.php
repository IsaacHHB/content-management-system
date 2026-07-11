<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Menu;
use App\Models\MenuItem;
use App\Models\Page;
use App\Models\Partner;
use App\Models\Post;
use App\Models\Program;
use App\Models\TeamMember;
use Illuminate\Support\Facades\Cache;

/**
 * Resolves the site "chrome" — the header/footer menu trees and the active
 * partner list — shared by the public site and by the admin visual/preview
 * editor so an editor previewing a page sees the real navigation and footer.
 * Both pieces are cached and busted when a menu or partner changes.
 */
class SiteChrome
{
    /**
     * @return array{header: array<int, array<string, mixed>>, footer: array<int, array<string, mixed>>}
     */
    public function menus(): array
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
     * @return array<int, array{id: int, name: string, website_url: string|null, logo_url: string|null}>
     */
    public function partners(): array
    {
        return Cache::rememberForever('public_partners', fn (): array => Partner::query()
            ->where('is_active', true)
            ->with('logo.media')
            ->orderBy('sort_order')
            ->get()
            ->map(fn (Partner $partner): array => [
                'id' => $partner->id,
                'name' => $partner->name,
                'website_url' => $partner->website_url,
                'logo_url' => $partner->logo?->url,
            ])
            ->all());
    }

    /**
     * Sample content for the admin visual editor so that newly-added list
     * blocks (events/news/team/partners) render real content immediately,
     * before the page is saved and server-hydrated. Shapes match what
     * BlockHydrator produces for the block renderers.
     *
     * @return array<string, mixed>
     */
    public function blockPreviews(): array
    {
        return [
            'events' => Event::published()->upcoming()->orderBy('starts_at')->orderBy('start_date')->limit(6)->get(['id', 'title', 'slug']),
            'posts' => Post::published()->latest('published_at')->limit(6)->get(['id', 'title', 'slug', 'excerpt']),
            'members' => TeamMember::query()->where('is_active', true)->with('photo.media')->orderBy('sort_order')->limit(12)->get(),
            'partners' => Partner::query()->where('is_active', true)->with('logo.media')->orderBy('sort_order')->get(),
        ];
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
}
