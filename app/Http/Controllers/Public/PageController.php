<?php

namespace App\Http\Controllers\Public;

use App\Models\Page;
use App\Models\Redirect;
use App\Services\BlockHydrator;
use Illuminate\Http\RedirectResponse;
use Inertia\Response;

class PageController extends PublicController
{
    public function show(string $slugPath, BlockHydrator $hydrator): RedirectResponse|Response
    {
        $path = '/'.trim($slugPath, '/');

        $redirect = Redirect::where('from_path', $path)->first();
        if ($redirect !== null) {
            return new RedirectResponse($redirect->to_path, $redirect->status_code ?? 301);
        }

        $segments = explode('/', trim($slugPath, '/'));
        $page = Page::published()->whereNull('parent_id')->where('slug', $segments[0])->firstOrFail();

        if (count($segments) > 1) {
            $page = Page::published()->where('parent_id', $page->id)->where('slug', $segments[1])->firstOrFail();
        }

        abort_if(count($segments) > 2, 404);

        return $this->render('public/page', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'blocks' => $hydrator->hydrate($page->blocks ?? []),
            ],
            'seo' => $this->seo($page->seo_title ?: $page->title, $page->seo_description),
        ]);
    }
}
