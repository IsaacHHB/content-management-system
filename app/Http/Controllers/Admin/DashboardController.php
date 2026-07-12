<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContactSubmission;
use App\Models\Event;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('dashboard', [
            'counts' => [
                'pages' => Page::count(),
                'programs' => Program::count(),
                'events' => Event::count(),
                'posts' => Post::count(),
                'unread_contacts' => $request->user()->can('contacts.manage')
                    ? ContactSubmission::where('is_read', false)->count()
                    : 0,
            ],
        ]);
    }
}
