<?php

namespace App\Http\Controllers\Public;

use App\Models\Event;
use App\Models\Post;
use App\Models\Program;
use App\Models\TeamMember;
use Inertia\Response;

class HomeController extends PublicController
{
    public function __invoke(): Response
    {
        return $this->render('public/home', [
            'programs' => Program::published()->orderBy('sort_order')->limit(6)->get(['id', 'title', 'slug', 'excerpt']),
            'events' => Event::published()->upcoming()->orderBy('starts_at')->orderBy('start_date')->limit(3)
                ->get(['id', 'title', 'slug', 'starts_at', 'start_date', 'all_day', 'timezone', 'location_name', 'is_virtual']),
            'posts' => Post::published()->latest('published_at')->limit(3)->get(['id', 'title', 'slug', 'excerpt', 'published_at']),
            'team' => TeamMember::where('is_active', true)->orderBy('sort_order')->with('photo.media')->limit(8)->get(),
            'seo' => $this->seo(null),
        ]);
    }
}
