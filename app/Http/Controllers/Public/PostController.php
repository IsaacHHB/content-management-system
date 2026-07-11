<?php

namespace App\Http\Controllers\Public;

use App\Models\Category;
use App\Models\Post;
use App\Services\BlockHydrator;
use Illuminate\Http\Request;
use Inertia\Response;

class PostController extends PublicController
{
    public function index(Request $request): Response
    {
        $posts = Post::published()
            ->with('categories:id,name,slug')
            ->when($request->string('category')->isNotEmpty(), fn ($q) => $q->whereHas('categories', fn ($c) => $c->where('slug', $request->string('category'))))
            ->latest('published_at')
            ->paginate(9)
            ->withQueryString();

        return $this->render('public/news/index', [
            'posts' => $posts,
            'categories' => Category::orderBy('name')->get(['id', 'name', 'slug']),
            'activeCategory' => $request->string('category')->toString(),
            'seo' => $this->seo('News'),
        ]);
    }

    public function show(string $slug, BlockHydrator $hydrator): Response
    {
        $post = Post::published()->where('slug', $slug)->with('categories:id,name,slug', 'author:id,name', 'ogMediaAsset.media')->firstOrFail();

        return $this->render('public/news/show', [
            'post' => [
                ...$post->only('id', 'title', 'slug', 'excerpt', 'published_at'),
                'author' => $post->author?->only('id', 'name'),
                'categories' => $post->categories->map->only('id', 'name', 'slug'),
                'blocks' => $hydrator->hydrate($post->blocks ?? []),
            ],
            'seo' => $this->seo($post->seo_title ?: $post->title, $post->seo_description ?: $post->excerpt, $post->ogMediaAsset?->url),
        ]);
    }
}
