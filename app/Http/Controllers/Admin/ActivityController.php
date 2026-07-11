<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_unless($request->user()->can('activity.view'), 403);

        return response()->json(Activity::query()
            ->with('causer')
            ->latest()
            ->paginate(min(100, max(1, $request->integer('per_page', 30)))));
    }
}
