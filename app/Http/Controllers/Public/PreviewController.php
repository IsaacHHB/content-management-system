<?php

namespace App\Http\Controllers\Public;

use App\Models\Event;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use App\Services\BlockHydrator;
use Inertia\Response;

/**
 * Renders draft content in the real public layout for the admin editor's
 * preview iframe — the same components/routes the live site uses, reached via a
 * signed URL and without the published scope, so an editor sees exactly how the
 * page will look live (including any future theme) with zero drift.
 */
class PreviewController extends PublicController
{
    public function page(Page $page, BlockHydrator $hydrator): Response
    {
        return $this->render('public/page', [
            'page' => [
                'id' => $page->id,
                'title' => $page->title,
                'blocks' => $hydrator->hydrate($page->blocks ?? []),
            ],
            'seo' => $this->seo($page->seo_title ?: $page->title, $page->seo_description),
        ]);
    }

    public function program(Program $program, BlockHydrator $hydrator): Response
    {
        return $this->render('public/programs/show', [
            'program' => [
                ...$program->only('id', 'title', 'slug', 'excerpt', 'contact_name', 'contact_email', 'contact_phone', 'external_url'),
                'blocks' => $hydrator->hydrate($program->blocks ?? []),
            ],
            'seo' => $this->seo($program->seo_title ?: $program->title, $program->seo_description ?: $program->excerpt),
        ]);
    }

    public function post(Post $post, BlockHydrator $hydrator): Response
    {
        $post->load('categories:id,name,slug', 'author:id,name');

        return $this->render('public/news/show', [
            'post' => [
                ...$post->only('id', 'title', 'slug', 'excerpt', 'published_at'),
                'author' => $post->author?->only('id', 'name'),
                'categories' => $post->categories->map->only('id', 'name', 'slug'),
                'blocks' => $hydrator->hydrate($post->blocks ?? []),
            ],
            'seo' => $this->seo($post->seo_title ?: $post->title, $post->seo_description ?: $post->excerpt),
        ]);
    }

    public function event(Event $event, BlockHydrator $hydrator): Response
    {
        return $this->render('public/events/show', [
            'event' => [
                ...$event->only('id', 'title', 'slug', 'starts_at', 'ends_at', 'start_date', 'end_date', 'all_day', 'timezone', 'location_name', 'address', 'city', 'state', 'zip', 'is_virtual', 'virtual_url', 'registration_url'),
                'description' => $hydrator->hydrate($event->description ?? []),
            ],
            'seo' => $this->seo($event->seo_title ?: $event->title, $event->seo_description),
        ]);
    }
}
