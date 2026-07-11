<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\Event;
use App\Models\Page;
use App\Models\Post;
use App\Models\Program;
use Illuminate\Http\JsonResponse;

class PreviewController extends Controller
{
    public function page(Page $page): JsonResponse
    {
        return response()->json($page->load(['parent', 'ogMediaAsset']));
    }

    public function program(Program $program): JsonResponse
    {
        return response()->json($program->load('ogMediaAsset'));
    }

    public function event(Event $event): JsonResponse
    {
        return response()->json($event->load('ogMediaAsset'));
    }

    public function post(Post $post): JsonResponse
    {
        return response()->json($post->load(['author:id,name', 'categories', 'ogMediaAsset']));
    }
}
