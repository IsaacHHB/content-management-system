<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function __invoke(Request $request): Response
    {
        abort_unless($request->user()->can('activity.view'), 403);

        return Inertia::render('admin/activity/index', [
            'items' => Activity::query()
                ->with('causer:id,name')
                ->latest()
                ->paginate(min(100, max(1, $request->integer('per_page', 30))))
                ->withQueryString(),
        ]);
    }
}
