<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Services\SiteChrome;
use Inertia\Inertia;
use Inertia\Response;

abstract class PublicController extends Controller
{
    /**
     * @param  array<string, mixed>  $props
     */
    protected function render(string $component, array $props = []): Response
    {
        $chrome = app(SiteChrome::class);

        return Inertia::render($component, [
            'publicMenus' => $chrome->menus(),
            'publicPartners' => $chrome->partners(),
            ...$props,
        ]);
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
            'canonical' => request()->url(),
        ];
    }
}
