<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\Event;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('dashboard', [
            'counts' => [
                'pages' => Page::count(),
                'programs' => Program::count(),
                'events' => Event::count(),
                'posts' => Post::count(),
                'unread_contacts' => ContactSubmission::where('is_read', false)->count(),
            ],
        ]);
    }
}
