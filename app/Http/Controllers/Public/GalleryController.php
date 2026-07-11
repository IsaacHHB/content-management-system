<?php

namespace App\Http\Controllers\Public;

use App\Models\Gallery;
use Inertia\Response;

class GalleryController extends PublicController
{
    public function index(): Response
    {
        $galleries = Gallery::published()->orderBy('sort_order')->with('mediaAssets.media')->get()
            ->map(fn (Gallery $g) => [
                'id' => $g->id,
                'title' => $g->title,
                'slug' => $g->slug,
                'description' => $g->description,
                'cover' => $g->mediaAssets->first()?->thumb_url,
                'count' => $g->mediaAssets->count(),
            ]);

        return $this->render('public/gallery/index', [
            'galleries' => $galleries,
            'seo' => $this->seo('Gallery'),
        ]);
    }

    public function show(string $slug): Response
    {
        $gallery = Gallery::published()->where('slug', $slug)->with('mediaAssets.media')->firstOrFail();

        return $this->render('public/gallery/show', [
            'gallery' => [
                'id' => $gallery->id,
                'title' => $gallery->title,
                'description' => $gallery->description,
                'photos' => $gallery->mediaAssets->map(fn ($a) => [
                    'id' => $a->id,
                    'url' => $a->url,
                    'thumb_url' => $a->thumb_url,
                    'alt' => $a->pivot->alt_text ?? $a->alt_text,
                    'caption' => $a->pivot->caption ?? $a->caption,
                ]),
            ],
            'seo' => $this->seo($gallery->title),
        ]);
    }
}
