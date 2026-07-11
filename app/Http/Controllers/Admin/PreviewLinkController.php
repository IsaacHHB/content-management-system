<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class PreviewLinkController extends Controller
{
    /** @var array<string, class-string<Model>> */
    private const MODELS = [
        'pages' => Page::class,
        'programs' => Program::class,
        'events' => Event::class,
        'posts' => Post::class,
    ];

    public function __invoke(Request $request, string $type, string $id): JsonResponse
    {
        abort_unless(isset(self::MODELS[$type]), 404);
        $model = self::MODELS[$type]::query()->findOrFail($id);
        $this->authorize('view', $model);

        return response()->json([
            'url' => URL::temporarySignedRoute(
                "preview.{$type}",
                now()->addHour(),
                [str($type)->singular()->toString() => $model->getKey()],
            ),
            'expires_at' => now()->addHour(),
        ]);
    }
}
