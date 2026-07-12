<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Gallery;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use App\Models\TeamMember;
use Illuminate\Http\Response;

class SitemapController extends Controller
{
    public function __invoke(): Response
    {
        return response()
            ->view('sitemap', ['urls' => $this->urls()])
            ->header('Content-Type', 'application/xml; charset=UTF-8');
    }

    /**
     * Served from a route rather than a static file so the `Sitemap:` directive can
     * carry an absolute URL — crawlers ignore a relative one.
     */
    public function robots(): Response
    {
        $lines = [
            'User-agent: *',
            'Disallow: /admin',
            'Disallow: /settings',
            'Disallow: /preview',
            'Sitemap: '.route('sitemap'),
        ];

        return response(implode("\n", $lines)."\n")
            ->header('Content-Type', 'text/plain; charset=UTF-8');
    }

    /** @return list<array{location: string, modified: string|null}> */
    private function urls(): array
    {
        $urls = [
            $this->entry('/'),
            $this->entry('/programs'),
            $this->entry('/events'),
            $this->entry('/events/calendar'),
            $this->entry('/news'),
            $this->entry('/gallery'),
            $this->entry('/about/team'),
            $this->entry('/contact'),
        ];

        foreach (Page::published()->orderBy('id')->lazy() as $page) {
            $urls[] = $this->entry($page->path, $page->updated_at?->toAtomString());
        }
        foreach (Program::published()->orderBy('id')->lazy() as $program) {
            $urls[] = $this->entry('/programs/'.$program->slug, $program->updated_at?->toAtomString());
        }
        foreach (Event::published()->orderBy('id')->lazy() as $event) {
            $urls[] = $this->entry('/events/'.$event->slug, $event->updated_at?->toAtomString());
        }
        foreach (Post::published()->orderBy('id')->lazy() as $post) {
            $urls[] = $this->entry('/news/'.$post->slug, $post->updated_at?->toAtomString());
        }
        foreach (Gallery::published()->orderBy('id')->lazy() as $gallery) {
            $urls[] = $this->entry('/gallery/'.$gallery->slug, $gallery->updated_at?->toAtomString());
        }
        foreach (TeamMember::query()->where('is_active', true)->orderBy('id')->lazy() as $member) {
            $urls[] = $this->entry('/about/team/'.$member->slug, $member->updated_at?->toAtomString());
        }

        return $urls;
    }

    /** @return array{location: string, modified: string|null} */
    private function entry(string $path, ?string $modified = null): array
    {
        return ['location' => url($path), 'modified' => $modified];
    }
}
